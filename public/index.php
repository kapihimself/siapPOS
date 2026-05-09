<?php
declare(strict_types=1);

use Siappos\App\Auth;
use Siappos\App\Response;
use Siappos\App\View;
use Siappos\Domain\Auth\Actions\AuthenticateAction;
use Siappos\Domain\Auth\LoginData;
use Siappos\Domain\Auth\UserRepository;
use Siappos\Domain\Product\ProductRepository;
use Siappos\Domain\Settings\Actions\CompleteOnboardingAction;
use Siappos\Domain\Settings\BusinessTemplate;
use Siappos\Domain\Settings\DTO\OnboardingData;
use Siappos\Domain\Settings\SettingsRepository;
use Siappos\Domain\Transaction\Actions\ProcessSaleAction;
use Siappos\Domain\Transaction\Actions\CreatePurchaseAction;
use Siappos\Domain\Transaction\DTO\CheckoutData;
use Siappos\Domain\Transaction\DTO\CartItemData;
use Siappos\Domain\Transaction\DTO\PurchaseData;
use Siappos\Domain\Transaction\DTO\PurchaseLineData;
use Siappos\Shared\Csrf;
use Siappos\Shared\Flash;
use Siappos\Shared\EventBus;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();
require_once dirname(__DIR__) . '/src/bootstrap.php';

$eventBus = new EventBus();
$userRepository = new UserRepository($pdo);
$productRepository = new ProductRepository($pdo);
$settingsRepository = new SettingsRepository($pdo);
$authAction = new AuthenticateAction($userRepository);
$completeOnboardingAction = new CompleteOnboardingAction($settingsRepository);

$page = (string) ($_GET['page'] ?? 'home');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

$requireCsrf = static function (string $fallbackPage = 'login'): void {
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        Flash::error('Sesi form tidak valid. Silakan kirim ulang form.');
        Response::redirect('/?page=' . $fallbackPage);
    }
};

$requireAuth = static function (): void {
    if (!Auth::check()) {
        Flash::error('Silakan login terlebih dahulu.');
        Response::redirect('/?page=login');
    }
};

$redirectDefault = static function () use ($settingsRepository): never {
    if (!Auth::check()) {
        Response::redirect('/?page=login');
    }

    if (!$settingsRepository->isOnboardingCompleted(Auth::businessId())) {
        Response::redirect('/?page=onboarding');
    }

    Response::redirect('/?page=dashboard');
};

if ($page === 'home') {
    $redirectDefault();
}

if ($page === 'login' && $method === 'GET') {
    if (Auth::check()) {
        $redirectDefault();
    }

    View::render('login', [
        'title' => 'Masuk',
        'defaultUsername' => (string) ($_SESSION['_login_username'] ?? ''),
    ]);
    exit;
}

if ($page === 'login' && $method === 'POST') {
    $requireCsrf('login');

    try {
        $_SESSION['_login_username'] = strtolower(trim((string) ($_POST['username'] ?? '')));

        $data = LoginData::fromRequest($_POST);
        $user = $authAction->execute($data);

        if (!is_array($user)) {
            Flash::error('Username atau PIN salah. Periksa kembali kredensial Anda.');
            Response::redirect('/?page=login');
        }

        unset($_SESSION['_login_username']);
        Auth::login($user);
        Flash::success('Login berhasil. Selamat bekerja.');
        $redirectDefault();
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
        Response::redirect('/?page=login');
    }
}

if ($page === 'try-demo' && $method === 'POST') {
    $requireCsrf('login');

    $user = $userRepository->findByUsername('cashier');

    if (!is_array($user)) {
        Flash::error('Akun demo tidak tersedia.');
        Response::redirect('/?page=login');
    }

    Auth::login([
        'id' => (int) $user['id'],
        'business_id' => (int) $user['business_id'],
        'username' => (string) $user['username'],
        'full_name' => (string) $user['full_name'],
        'role' => (string) $user['role'],
    ]);

    if (!$settingsRepository->isOnboardingCompleted(Auth::businessId())) {
        $completeOnboardingAction->execute(
            new OnboardingData(
                businessName: 'SiapPOS Demo Store',
                outletName: 'Outlet Demo',
                template: BusinessTemplate::Retail,
            ),
            Auth::businessId()
        );
    }

    Flash::success('Mode demo aktif. Anda bisa eksplorasi tanpa setup awal.');
    Response::redirect('/?page=dashboard');
}

if ($page === 'onboarding' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat mengatur setup bisnis.');
        Response::redirect('/?page=dashboard');
    }

    $settings = $settingsRepository->get(Auth::businessId());
    $productCount = $productRepository->count(Auth::businessId());
    $businessId = Auth::businessId();
    $orderCount = (int) $pdo->query("SELECT COUNT(*) FROM transactions WHERE business_id = {$businessId} AND status = 'checked_out'")->fetchColumn();

    $checklist = [
        ['label' => 'Isi identitas bisnis dan outlet', 'done' => ((string) ($settings['business_name'] ?? '')) !== '' && ((string) ($settings['outlet_name'] ?? '')) !== ''],
        ['label' => 'Pilih template bisnis yang tepat', 'done' => BusinessTemplate::tryFrom((string) ($settings['active_template'] ?? '')) instanceof BusinessTemplate],
        ['label' => 'Pastikan katalog produk siap jual', 'done' => $productCount > 0],
        ['label' => 'Lakukan transaksi pertama', 'done' => $orderCount > 0],
    ];

    $done = 0;
    foreach ($checklist as $item) {
        if ($item['done']) {
            $done++;
        }
    }

    $progressPercent = (int) round(($done / count($checklist)) * 100);

    View::render('onboarding', [
        'title' => 'Onboarding Bisnis',
        'settings' => $settings,
        'templates' => BusinessTemplate::all(),
        'progressPercent' => $progressPercent,
        'checklist' => $checklist,
    ]);
    exit;
}

if ($page === 'onboarding' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('onboarding');

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat menyimpan setup bisnis.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $data = OnboardingData::fromRequest($_POST);
        $completeOnboardingAction->execute($data, Auth::businessId());
        Flash::success('Setup bisnis tersimpan. Anda siap lanjut ke dashboard.');
        Response::redirect('/?page=dashboard');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
        Response::redirect('/?page=onboarding');
    }
}

if ($page === 'pos' && $method === 'GET') {
    $requireAuth();

    if (!$settingsRepository->isOnboardingCompleted(Auth::businessId())) {
        Response::redirect('/?page=onboarding');
    }

    $settings = $settingsRepository->get(Auth::businessId());

    View::render('pos', [
        'title' => 'Terminal POS',
        'settings' => $settings,
    ]);
    exit;
}

if ($page === 'api/products' && $method === 'GET') {
    $requireAuth();
    header('Content-Type: application/json');
    $search = $_GET['q'] ?? null;
    $products = $productRepository->activeForPos(Auth::businessId(), $search);
    echo json_encode($products);
    exit;
}

if ($page === 'api/checkout' && $method === 'POST') {
    $requireAuth();
    header('Content-Type: application/json');

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!Csrf::verify($payload['_csrf'] ?? null)) {
        http_response_code(400);
        echo json_encode(['error' => 'Sesi form tidak valid.']);
        exit;
    }

    try {
        $items = [];
        foreach ($payload['items'] ?? [] as $item) {
            $items[] = new CartItemData(
                productId: (int) $item['product_id'],
                qty: (float) $item['qty']
            );
        }

        $settings = $settingsRepository->get(Auth::businessId());
        $taxRate = (float) ($settings['pb1_rate'] ?? 10);

        $data = CheckoutData::fromRequest($payload, $items, Auth::id(), $taxRate, Auth::businessId());

        $processSale = new ProcessSaleAction($pdo, $productRepository, $eventBus);
        $transaction = $processSale->execute($data);

        echo json_encode(['success' => true, 'transaction_number' => $transaction['transaction_number']]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($page === 'purchases' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat mengakses daftar pembelian.');
        Response::redirect('/?page=dashboard');
    }

    $businessId = Auth::businessId();
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE business_id = :business_id AND type = 'purchase' ORDER BY created_at DESC");
    $stmt->execute([':business_id' => $businessId]);
    $purchases = $stmt->fetchAll();

    View::render('purchases', [
        'title' => 'Daftar Pembelian (Procurement)',
        'purchases' => $purchases,
    ]);
    exit;
}

if ($page === 'purchases/create' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat membuat transaksi pembelian.');
        Response::redirect('/?page=dashboard');
    }

    View::render('purchase-form', [
        'title' => 'Buat Pembelian Baru',
    ]);
    exit;
}

if ($page === 'purchases/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('purchases/create');

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $lines = [];
        foreach ($_POST['lines'] ?? [] as $lineData) {
            $lines[] = new PurchaseLineData(
                productId: (int) ($lineData['product_id'] ?? 0),
                productName: (string) ($lineData['product_name'] ?? ''),
                qty: (float) ($lineData['qty'] ?? 0),
                unitPriceCents: ((int) ($lineData['unit_price'] ?? 0)) * 100,
                lineTotalCents: ((int) ($lineData['line_total'] ?? 0)) * 100
            );
        }

        $data = PurchaseData::fromRequest($_POST, $lines, Auth::id(), Auth::businessId());

        $createPurchase = new CreatePurchaseAction($pdo);
        $transaction = $createPurchase->execute($data);

        Flash::success('Pembelian ' . $transaction['transaction_number'] . ' berhasil disimpan dan stok telah diperbarui.');
        Response::redirect('/?page=purchases');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
        Response::redirect('/?page=purchases/create');
    }
}

if ($page === 'dashboard' && $method === 'GET') {
    $requireAuth();

    if (!$settingsRepository->isOnboardingCompleted(Auth::businessId())) {
        Response::redirect('/?page=onboarding');
    }

    $settings = $settingsRepository->get(Auth::businessId());
    $template = $settingsRepository->activeTemplate(Auth::businessId());

    $businessId = Auth::businessId();
    $orderCount = (int) $pdo->query("SELECT COUNT(*) FROM transactions WHERE business_id = {$businessId} AND status = 'checked_out'")->fetchColumn();
    $totalRevenueCents = (int) $pdo->query("SELECT COALESCE(SUM(total_cents),0) FROM transactions WHERE business_id = {$businessId} AND status = 'checked_out'")->fetchColumn();

    $setupChecklist = [
        ['label' => 'Onboarding bisnis selesai', 'done' => true],
        ['label' => 'Katalog produk minimal 1 item', 'done' => $productRepository->count(Auth::businessId()) > 0],
        ['label' => 'Transaksi pertama tercatat', 'done' => $orderCount > 0],
    ];

    $setupDone = 0;
    foreach ($setupChecklist as $item) {
        if ($item['done']) {
            $setupDone++;
        }
    }

    $setupProgressPercent = (int) round(($setupDone / count($setupChecklist)) * 100);

    $roleCopy = match (Auth::role()) {
        'admin' => 'Anda memegang kontrol penuh atas setup dan arah operasional.',
        'manager' => 'Anda mengawasi operasional harian dan kepatuhan proses.',
        'cashier' => 'Anda fokus pada transaksi cepat, akurat, dan rapi.',
        default => 'Selamat datang di SiapPOS.',
    };

    View::render('dashboard', [
        'title' => 'Dashboard',
        'settings' => $settings,
        'template' => $template,
        'modules' => $template->enabledModules(),
        'firstWeekChecklist' => $template->firstWeekChecklist(),
        'setupChecklist' => $setupChecklist,
        'setupProgressPercent' => $setupProgressPercent,
        'roleCopy' => $roleCopy,
        'kpi' => [
            'users' => $userRepository->count(Auth::businessId()),
            'products' => $productRepository->count(Auth::businessId()),
            'orders' => $orderCount,
            'revenue_cents' => $totalRevenueCents,
        ],
    ]);
    exit;
}

if ($page === 'logout') {
    Auth::logout();
    Flash::success('Anda sudah logout dengan aman.');
    Response::redirect('/?page=login');
}

http_response_code(404);
View::render('not-found', ['title' => 'Halaman Tidak Ditemukan']);
