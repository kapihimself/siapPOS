<?php
declare(strict_types=1);

use Siappos\App\Auth;
use Siappos\App\Response;
use Siappos\App\View;
use Siappos\Domain\Auth\Actions\AuthenticateAction;
use Siappos\Domain\Auth\Actions\RegisterTenantAction;
use Siappos\Domain\Auth\LoginData;
use Siappos\Domain\Auth\UserRepository;
use Siappos\Domain\Product\ProductRepository;
use Siappos\Domain\Settings\Actions\CompleteOnboardingAction;
use Siappos\Domain\Settings\BusinessTemplate;
use Siappos\Domain\Settings\DTO\OnboardingData;
use Siappos\Domain\Settings\SettingsRepository;
use Siappos\Domain\Contact\ContactRepository;
use Siappos\Domain\Taxonomy\CategoryRepository;
use Siappos\Domain\Taxonomy\BrandRepository;
use Siappos\Domain\Taxonomy\UnitRepository;
use Siappos\Domain\Transaction\TransactionRepository;
use Siappos\Shared\Csrf;
use Siappos\Shared\Flash;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();
require_once dirname(__DIR__) . '/src/bootstrap.php';

$userRepository = new UserRepository($pdo);
$productRepository = new ProductRepository($pdo);
$settingsRepository = new SettingsRepository($pdo);
$contactRepository = new ContactRepository($pdo);
$categoryRepository = new CategoryRepository($pdo);
$brandRepository = new BrandRepository($pdo);
$unitRepository = new UnitRepository($pdo);
$transactionRepository = new TransactionRepository($pdo);
$authAction = new AuthenticateAction($userRepository);
$registerTenantAction = new RegisterTenantAction($pdo);
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

$template = Auth::check() ? $settingsRepository->activeTemplate(Auth::businessId()) : null;

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

if ($page === 'register' && $method === 'GET') {
    if (Auth::check()) {
        $redirectDefault();
    }

    View::render('register', [
        'title' => 'Daftar Bisnis Baru',
    ]);
    exit;
}

if ($page === 'register' && $method === 'POST') {
    $requireCsrf('register');

    try {
        $businessName = trim((string) ($_POST['business_name'] ?? ''));
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $pin = trim((string) ($_POST['pin'] ?? ''));

        if ($businessName === '' || $username === '' || $fullName === '' || $pin === '') {
            throw new \RuntimeException('Semua kolom wajib diisi.');
        }

        $registerTenantAction->execute($businessName, $username, $fullName, $pin);

        Flash::success('Pendaftaran berhasil. Silakan login menggunakan username dan PIN Anda.');
        Response::redirect('/?page=login');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
        Response::redirect('/?page=register');
    }
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
            Auth::businessId(),
            new OnboardingData(
                businessName: 'SiapPOS Demo Store',
                outletName: 'Outlet Demo',
                template: BusinessTemplate::Retail,
            )
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

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE business_id = :business_id AND type = 'sell' AND status = 'final'");
    $stmt->execute([':business_id' => Auth::businessId()]);
    $orderCount = (int) $stmt->fetchColumn();

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
        $completeOnboardingAction->execute(Auth::businessId(), $data);
        Flash::success('Setup bisnis tersimpan. Anda siap lanjut ke dashboard.');
        Response::redirect('/?page=dashboard');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
        Response::redirect('/?page=onboarding');
    }
}

if ($page === 'contacts' && $method === 'GET') {
    $requireAuth();
    View::render('contacts', [
        'title' => 'Manajemen Kontak',
        'contacts' => $contactRepository->all(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'taxonomy' && $method === 'GET') {
    $requireAuth();
    View::render('taxonomy', [
        'title' => 'Taksonomi Produk',
        'categories' => $categoryRepository->all(Auth::businessId()),
        'brands' => $brandRepository->all(Auth::businessId()),
        'units' => $unitRepository->all(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'products' && $method === 'GET') {
    $requireAuth();
    View::render('products', [
        'title' => 'Katalog Produk',
        'products' => $productRepository->all(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'purchases' && $method === 'GET') {
    $requireAuth();
    View::render('purchases', [
        'title' => 'Daftar Pembelian',
        'purchases' => $transactionRepository->allPurchases(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'stock-adjustments' && $method === 'GET') {
    $requireAuth();
    View::render('stock_adjustments', [
        'title' => 'Penyesuaian Stok',
        'adjustments' => $transactionRepository->allStockAdjustments(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'dashboard' && $method === 'GET') {
    $requireAuth();

    if (!$settingsRepository->isOnboardingCompleted(Auth::businessId())) {
        Response::redirect('/?page=onboarding');
    }

    $settings = $settingsRepository->get(Auth::businessId());
    $template = $settingsRepository->activeTemplate(Auth::businessId());

    $stmtOrders = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE business_id = :business_id AND type = 'sell' AND status = 'final'");
    $stmtOrders->execute([':business_id' => Auth::businessId()]);
    $orderCount = (int) $stmtOrders->fetchColumn();

    $stmtRevenue = $pdo->prepare("SELECT COALESCE(SUM(final_total_cents),0) FROM transactions WHERE business_id = :business_id AND type = 'sell' AND status = 'final'");
    $stmtRevenue->execute([':business_id' => Auth::businessId()]);
    $totalRevenueCents = (int) $stmtRevenue->fetchColumn();

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
