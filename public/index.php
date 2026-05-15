<?php
declare(strict_types=1);

use Siappos\App\Auth;
use Siappos\App\Response;
use Siappos\App\View;
use Siappos\Domain\Auth\Actions\AuthenticateAction;
use Siappos\Domain\Auth\LoginData;
use Siappos\Domain\Auth\UserRepository;
use Siappos\Domain\Report\ReportRepository;
use Siappos\Domain\Accounting\AccountRepository;
use Siappos\Domain\CashRegister\CashRegisterRepository;
use Siappos\Domain\CashRegister\Actions\OpenRegisterAction;
use Siappos\Domain\CashRegister\Actions\CloseRegisterAction;
use Siappos\Domain\Product\CategoryRepository;
use Siappos\Domain\Product\BrandRepository;
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
use Siappos\Shared\QueueManager;
use Siappos\Domain\Restaurant\TableRepository;
use Siappos\Domain\Restaurant\ResModifierRepository;
use Siappos\Domain\Contact\ContactRepository;
use Siappos\Domain\Accounting\Actions\RecordExpenseAction;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();
require_once dirname(__DIR__) . '/src/bootstrap.php';

$eventBus = new EventBus();
$queueManager = new QueueManager($pdo);
$userRepository = new UserRepository($pdo);
$cashRegisterRepository = new CashRegisterRepository($pdo);
$accountRepository = new AccountRepository($pdo);
$reportRepository = new ReportRepository($pdo);
$tableRepository = new TableRepository($pdo);
$resModifierRepository = new ResModifierRepository($pdo);
$contactRepository = new ContactRepository($pdo);
$categoryRepository = new CategoryRepository($pdo);
$brandRepository = new BrandRepository($pdo);
$productRepository = new ProductRepository($pdo);
$openRegisterAction = new OpenRegisterAction($cashRegisterRepository);
$closeRegisterAction = new CloseRegisterAction($cashRegisterRepository);
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
    $orderCount = (int) $pdo->query("SELECT COUNT(*) FROM transactions WHERE business_id = {$businessId} AND type = 'sell' AND status IN ('checked_out', 'final')")->fetchColumn();

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

    $activeRegister = $cashRegisterRepository->getActiveRegister(Auth::businessId(), Auth::id());

    if (!$activeRegister) {
        View::render('cash-register-open', [
            'title' => 'Buka Shift Kasir',
        ]);
        exit;
    }

    $settings = $settingsRepository->get(Auth::businessId());

            View::render('pos', [
        'title' => 'Terminal POS',
        'settings' => $settings,
        'activeRegister' => $activeRegister,
        'agents' => $userRepository->getCommissionAgents(Auth::businessId()),
        'tables' => ($settings['active_template'] ?? '') === 'fnb' ? $tableRepository->all(Auth::businessId()) : []
    ]);
    exit;
}

if ($page === 'pos/open-register' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('pos');

    try {
        $openingAmount = (int) ($_POST['opening_amount'] ?? 0);
        $openRegisterAction->execute(Auth::businessId(), Auth::id(), $openingAmount * 100);
        Flash::success('Shift kasir berhasil dibuka. Selamat bertugas!');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
    }
    Response::redirect('/?page=pos');
}

if ($page === 'pos/close-register' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('pos');

    try {
        $closingAmount = (int) ($_POST['closing_amount'] ?? 0);
        $registerId = $closeRegisterAction->execute(Auth::businessId(), Auth::id(), $closingAmount * 100);
        Flash::success('Shift kasir berhasil ditutup.');
        Response::redirect('/?page=pos/z-report&id=' . $registerId);
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
        Response::redirect('/?page=pos');
    }
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
        $activeRegister = $cashRegisterRepository->getActiveRegister(Auth::businessId(), Auth::id());
        if (!$activeRegister) {
            throw new Exception('Anda belum membuka shift kasir. Buka kasir terlebih dahulu sebelum transaksi.');
        }

        $items = [];
        foreach ($payload['items'] ?? [] as $item) {
            $items[] = new CartItemData(
                productId: (int) $item['product_id'],
                qty: (float) $item['qty'],
                variationId: isset($item['variation_id']) ? (int) $item['variation_id'] : null
            );
        }

        $settings = $settingsRepository->get(Auth::businessId());
        $taxRate = (float) ($settings['pb1_rate'] ?? 10);

        $data = CheckoutData::fromRequest(
            $payload,
            $items,
            Auth::id(),
            $taxRate,
            Auth::businessId(),
            (int) $activeRegister['id']
        );

        $processSale = new ProcessSaleAction($pdo, $productRepository, $eventBus, clone $queueManager);
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
                lineTotalCents: ((int) ($lineData['line_total'] ?? 0)) * 100,
                variationId: !empty($lineData['variation_id']) ? (int) $lineData['variation_id'] : null
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

if ($page === 'accounts' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Hanya Admin/Manager yang bisa mengakses Akuntansi.');
        Response::redirect('/?page=dashboard');
    }

    View::render('accounts', [
        'title' => 'Bagan Akun',
        'accounts' => $accountRepository->all(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'accounts/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('accounts');

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? ''));

        if ($accountNumber === '' || $name === '' || !in_array($type, ['asset', 'liability', 'equity', 'revenue', 'expense'], true)) {
            throw new Exception('Data form tidak valid atau tidak lengkap.');
        }

        $accountRepository->create(Auth::businessId(), $name, $accountNumber, $type);
        Flash::success("Akun '$name' berhasil dibuat.");
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
    }

    Response::redirect('/?page=accounts');
}

if ($page === 'accounts/ledger' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    $accountId = (int) ($_GET['id'] ?? 0);
    $accounts = $accountRepository->all(Auth::businessId());
    $account = null;

    foreach ($accounts as $a) {
        if ((int) $a['id'] === $accountId) {
            $account = $a;
            break;
        }
    }

    if ($account === null) {
        Flash::error('Akun tidak ditemukan.');
        Response::redirect('/?page=accounts');
    }

    View::render('ledger', [
        'title' => 'Buku Besar - ' . $account['name'],
        'account' => $account,
        'ledger' => $accountRepository->getLedger($accountId, Auth::businessId()),
    ]);
    exit;
}

if ($page === 'reports' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Hanya Admin/Manager yang bisa mengakses Laporan.');
        Response::redirect('/?page=dashboard');
    }

    View::render('reports', [
        'title' => 'Laporan (Reports)',
        'profitLoss' => $reportRepository->getProfitLoss(Auth::businessId()),
        'trendingProducts' => $reportRepository->getTrendingProducts(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'contacts' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }

    View::render('contacts', [
        'title' => 'CRM Kontak',
        'contacts' => $contactRepository->all(Auth::businessId())
    ]);
    exit;
}

if ($page === 'contacts/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('contacts');
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }


    try {
        $contactRepository->create(
            Auth::businessId(),
            $_POST['type'] ?? 'customer',
            $_POST['name'] ?? '',
            empty($_POST['email']) ? null : $_POST['email'],
            empty($_POST['phone']) ? null : $_POST['phone'],
            empty($_POST['address']) ? null : $_POST['address']
        );
        Flash::success('Kontak berhasil ditambahkan.');
    } catch (\Exception $e) {
        Flash::error('Gagal menambahkan kontak: ' . $e->getMessage());
    }

    Response::redirect('/?page=contacts');
}

if ($page === 'contacts/ledger' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager', 'cashier')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    $id = (int) ($_GET['id'] ?? 0);
    $contact = $contactRepository->find($id, Auth::businessId());

    if (!$contact) {
        Flash::error('Kontak tidak ditemukan.');
        Response::redirect('/?page=contacts');
    }

    View::render('contact-ledger', [
        'title' => 'Buku Besar Kontak',
        'contact' => $contact,
        'ledger' => $contactRepository->getLedger($id, Auth::businessId())
    ]);
    exit;
}

if ($page === 'contacts/pay' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('contacts/ledger');

    try {
        $transactionId = (int) $_POST['transaction_id'];
        $amountCents = (int) ($_POST['amount'] * 100);

        $pdo->beginTransaction();

        $stmtTx = $pdo->prepare('SELECT payment_status FROM transactions WHERE id = :id AND business_id = :business_id');
        $stmtTx->execute([':id' => $transactionId, ':business_id' => Auth::businessId()]);
        $tx = $stmtTx->fetch();

        if (!$tx) throw new Exception('Transaksi tidak valid.');

        $stmtPayment = $pdo->prepare('INSERT INTO transaction_payments (transaction_id, amount_cents, payment_method, created_by) VALUES (:tx_id, :amount, :method, :created_by)');
        $stmtPayment->execute([
            ':tx_id' => $transactionId,
            ':amount' => $amountCents,
            ':method' => 'cash',
            ':created_by' => Auth::id()
        ]);

        $stmtCheck = $pdo->prepare('SELECT total_cents, COALESCE(SUM(tp.amount_cents),0) as paid FROM transactions t LEFT JOIN transaction_payments tp ON t.id = tp.transaction_id WHERE t.id = :id GROUP BY t.id');
        $stmtCheck->execute([':id' => $transactionId]);
        $check = $stmtCheck->fetch();

        $status = 'due';
        if ($check['paid'] >= $check['total_cents']) $status = 'paid';
        elseif ($check['paid'] > 0) $status = 'partial';

        $stmtUpdate = $pdo->prepare('UPDATE transactions SET payment_status = :status WHERE id = :id');
        $stmtUpdate->execute([':status' => $status, ':id' => $transactionId]);

        $pdo->commit();
        Flash::success('Pembayaran dicatat.');
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Flash::error('Gagal mencatat pembayaran: ' . $e->getMessage());
    }

    Response::redirect('/?page=contacts/ledger&id=' . (int)$_POST['contact_id']);
}

if ($page === 'expenses' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }

    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE business_id = :business_id AND type = 'expense' ORDER BY created_at DESC");
    $stmt->execute([':business_id' => Auth::businessId()]);
    $expenses = $stmt->fetchAll();

    View::render('expenses', [
        'title' => 'Pengeluaran',
        'accounts' => $accountRepository->all(Auth::businessId()),
        'expenses' => $expenses
    ]);
    exit;
}

if ($page === 'expenses/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('expenses');
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }


    try {
        $action = new \Siappos\Domain\Accounting\Actions\RecordExpenseAction($pdo);
        $amountCents = (int) ($_POST['amount'] ?? 0) * 100;

        $action->execute(
            Auth::businessId(),
            (int) $_POST['account_id'],
            $amountCents,
            $_POST['payment_method'] ?? 'cash',
            $_POST['description'] ?? '',
            Auth::id()
        );

        Flash::success('Pengeluaran berhasil dicatat.');
    } catch (\Exception $e) {
        Flash::error('Gagal mencatat pengeluaran: ' . $e->getMessage());
    }

    Response::redirect('/?page=expenses');
}

if ($page === 'stock-adjustments' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }

    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE business_id = :business_id AND type = 'stock_adjustment' ORDER BY created_at DESC");
    $stmt->execute([':business_id' => Auth::businessId()]);

    View::render('stock-adjustments', [
        'title' => 'Penyesuaian Stok',
        'adjustments' => $stmt->fetchAll()
    ]);
    exit;
}

if ($page === 'stock-adjustments/create' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }

    View::render('stock-adjustment-form', [
        'title' => 'Buat Penyesuaian Stok',
        'products' => $productRepository->activeForPos(Auth::businessId())
    ]);
    exit;
}

if ($page === 'stock-adjustments/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('stock-adjustments/create');
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }


    try {
        $action = new \Siappos\Domain\Transaction\Actions\AdjustStockAction($pdo, $productRepository);

        $parsedLines = [];
        foreach (($_POST['lines'] ?? []) as $line) {
            if (empty($line['product_data']) || empty($line['qty'])) continue;

            $parts = explode('|', $line['product_data']);
            $parsedLines[] = [
                'product_id' => (int) $parts[0],
                'variation_id' => empty($parts[1]) ? null : (int) $parts[1],
                'qty' => (float) $line['qty'],
                'type' => $line['action_type'] === 'add' ? 'add' : 'subtract',
            ];
        }

        if (empty($parsedLines)) {
            throw new \Exception('Minimal harus ada 1 produk yang disesuaikan.');
        }

        $action->execute(
            Auth::businessId(),
            $_POST['type'] ?? 'normal',
            $parsedLines,
            Auth::id()
        );

        Flash::success('Penyesuaian stok berhasil disimpan.');
        Response::redirect('/?page=stock-adjustments');
    } catch (\Exception $e) {
        Flash::error('Gagal menyesuaikan stok: ' . $e->getMessage());
        Response::redirect('/?page=stock-adjustments/create');
    }
}

if ($page === 'accounts/transfer' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('accounts');

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak. Anda tidak memiliki izin.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $action = new \Siappos\Domain\Accounting\Actions\TransferFundAction($pdo);
        $amountCents = (int) ($_POST['amount'] ?? 0) * 100;

        $action->execute(
            Auth::businessId(),
            (int) $_POST['from_account_id'],
            (int) $_POST['to_account_id'],
            $amountCents,
            $_POST['description'] ?? 'Mutasi antar akun'
        );

        Flash::success('Mutasi dana berhasil.');
    } catch (\Exception $e) {
        Flash::error('Gagal mutasi dana: ' . $e->getMessage());
    }

    Response::redirect('/?page=accounts');
}

if ($page === 'tables' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat mengakses Meja.');
        Response::redirect('/?page=dashboard');
    }
    View::render('tables', [
        'title' => 'Manajemen Meja',
        'tables' => $tableRepository->all(Auth::businessId())
    ]);
    exit;
}

if ($page === 'tables/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('tables');
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $tableRepository->create(Auth::businessId(), $_POST['name'] ?? '', empty($_POST['description']) ? null : $_POST['description']);
        Flash::success('Meja berhasil ditambahkan.');
    } catch (\Exception $e) {
        Flash::error('Gagal menambahkan meja.');
    }
    Response::redirect('/?page=tables');
}

if ($page === 'modifiers' && $method === 'GET') {
    $requireAuth();
    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }
    View::render('modifiers', [
        'title' => 'Manajemen Modifier',
        'sets' => $resModifierRepository->getSets(Auth::businessId()),
        'modifierRepo' => $resModifierRepository
    ]);
    exit;
}

if ($page === 'modifiers/set-store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('modifiers');
    try {
        $resModifierRepository->createSet(Auth::businessId(), $_POST['name']);
        Flash::success('Grup modifier ditambahkan.');
    } catch (\Exception $e) {
        Flash::error('Gagal menambah grup.');
    }
    Response::redirect('/?page=modifiers');
}

if ($page === 'modifiers/item-store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('modifiers');
    try {
        $resModifierRepository->createModifier((int)$_POST['set_id'], $_POST['name'], (int)($_POST['price'] * 100));
        Flash::success('Opsi modifier ditambahkan.');
    } catch (\Exception $e) {
        Flash::error('Gagal menambah opsi.');
    }
    Response::redirect('/?page=modifiers');
}

if ($page === 'invoice' && $method === 'GET') {
    $token = $_GET['token'] ?? '';
    if (!$token) {
        http_response_code(404);
        echo "Invoice tidak ditemukan.";
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE payment_token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        http_response_code(404);
        echo "Invoice tidak valid.";
        exit;
    }

    // Pass an empty token to Csrf::verify/token if session isn't strictly needed for public,
    // but session_start is active globally in index.php so Csrf::token() works.
    View::render('public-invoice', [
        'title' => 'Tagihan #' . $transaction['transaction_number'],
        'transaction' => $transaction
    ]);
    exit;
}

if ($page === 'api/pay-invoice' && $method === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!Csrf::verify($_POST['_csrf'] ?? null) || !$token) {
        http_response_code(400);
        echo "Permintaan tidak valid.";
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, total_cents, payment_status FROM transactions WHERE payment_token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $transaction = $stmt->fetch();

    if (!$transaction || $transaction['payment_status'] === 'paid') {
        Flash::error('Tagihan sudah lunas atau tidak valid.');
        Response::redirect('/?page=invoice&token=' . $token);
    }

    // Mock successful payment
    $pdo->beginTransaction();
    try {
        $stmtUpdate = $pdo->prepare('UPDATE transactions SET payment_status = \'paid\', cash_received_cents = total_cents WHERE id = :id');
        $stmtUpdate->execute([':id' => $transaction['id']]);

        // Create payment record
        $stmtPay = $pdo->prepare('INSERT INTO transaction_payments (transaction_id, amount_cents, payment_method, created_by) VALUES (:tx_id, :amount, \'custom\', 0)');
        $stmtPay->execute([
            ':tx_id' => $transaction['id'],
            ':amount' => $transaction['total_cents']
        ]);

        $pdo->commit();
        Flash::success('Pembayaran online (MOCK) berhasil diproses. Terima kasih!');
    } catch (\Exception $e) {
        $pdo->rollBack();
        Flash::error('Terjadi kesalahan pada gateway pembayaran.');
    }

    Response::redirect('/?page=invoice&token=' . $token);
}

if ($page === 'pos/z-report' && $method === 'GET') {
    $requireAuth();
    $registerId = (int) ($_GET['id'] ?? 0);
    $reportData = $cashRegisterRepository->getZReportData(Auth::businessId(), $registerId);

    View::render('z-report', [
        'title' => 'Laporan Shift (Z-Report)',
        'reportData' => $reportData
    ]);
    exit;
}

if ($page === 'sells/suspended' && $method === 'GET') {
    $requireAuth();
    $stmt = $pdo->prepare('
        SELECT t.*, c.name as contact_name
        FROM transactions t
        LEFT JOIN contacts c ON t.contact_id = c.id
        WHERE t.business_id = :business_id AND t.type = \'sell\' AND t.status IN (\'draft\', \'suspended\')
        ORDER BY t.created_at DESC
    ');
    $stmt->execute([':business_id' => Auth::businessId()]);

    View::render('suspended-sells', [
        'title' => 'Transaksi Tertunda',
        'suspended' => $stmt->fetchAll()
    ]);
    exit;
}

if ($page === 'sells/delete' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('sells/suspended');

    try {
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id AND business_id = :business_id AND status IN (\'draft\', \'suspended\')');
        $stmt->execute([
            ':id' => (int) $_POST['transaction_id'],
            ':business_id' => Auth::businessId()
        ]);
        Flash::success('Transaksi berhasil dihapus.');
    } catch (\Exception $e) {
        Flash::error('Gagal menghapus transaksi.');
    }
    Response::redirect('/?page=sells/suspended');
}

if ($page === 'sells/resume' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('sells/suspended');

    try {
        $transactionId = (int) $_POST['transaction_id'];

        $stmtVerify = $pdo->prepare('SELECT id FROM transactions WHERE id = :id AND business_id = :business_id AND status IN (\'draft\', \'suspended\') LIMIT 1');
        $stmtVerify->execute([':id' => $transactionId, ':business_id' => Auth::businessId()]);
        if (!$stmtVerify->fetch()) {
            throw new \Exception('Transaksi tidak valid atau sudah diselesaikan.');
        }

        $stmtLines = $pdo->prepare('SELECT * FROM transaction_sell_lines WHERE transaction_id = :id');
        $stmtLines->execute([':id' => $transactionId]);
        $lines = $stmtLines->fetchAll();

        $stmtPurchaseLines = $pdo->prepare(
            'SELECT pl.id, pl.qty, pl.qty_sold FROM purchase_lines pl
             JOIN transactions t ON pl.transaction_id = t.id
             WHERE t.business_id = :business_id AND pl.product_id = :product_id AND (pl.variation_id = :variation_id OR (pl.variation_id IS NULL AND :variation_id IS NULL))
               AND pl.qty > pl.qty_sold
               AND t.status IN (\'received\', \'final\')
             ORDER BY pl.created_at ASC'
        );
        $stmtUpdatePurchaseLine = $pdo->prepare(
            'UPDATE purchase_lines SET qty_sold = qty_sold + :qty_sold WHERE id = :id'
        );
        $stmtMapping = $pdo->prepare(
            'INSERT INTO transaction_sell_lines_purchase_lines (sell_line_id, purchase_line_id, qty) VALUES (:sell_line_id, :purchase_line_id, :qty)'
        );

        $pdo->beginTransaction();

        foreach ($lines as $line) {
            $productRepository->decrementStock((int)$line['product_id'], (float)$line['qty'], Auth::businessId(), empty($line['variation_id']) ? null : (int)$line['variation_id']);

            $stmtPurchaseLines->execute([
                ':business_id' => Auth::businessId(),
                ':product_id' => $line['product_id'],
                ':variation_id' => $line['variation_id']
            ]);
            $availableLots = $stmtPurchaseLines->fetchAll();

            $qtyToDeduct = (float) $line['qty'];

            foreach ($availableLots as $lot) {
                if ($qtyToDeduct <= 0) break;

                $availableQtyInLot = (float) $lot['qty'] - (float) $lot['qty_sold'];
                $deductedFromLot = min($qtyToDeduct, $availableQtyInLot);

                $stmtUpdatePurchaseLine->execute([
                    ':qty_sold' => $deductedFromLot,
                    ':id' => $lot['id']
                ]);

                $stmtMapping->execute([
                    ':sell_line_id' => $line['id'],
                    ':purchase_line_id' => $lot['id'],
                    ':qty' => $deductedFromLot
                ]);

                $qtyToDeduct -= $deductedFromLot;
            }
        }

        $stmt = $pdo->prepare("UPDATE transactions SET status = 'checked_out', payment_status = 'due' WHERE id = :id AND business_id = :business_id");
        $stmt->execute([
            ':id' => $transactionId,
            ':business_id' => Auth::businessId()
        ]);

        $pdo->commit();

        $stmtContact = $pdo->prepare('SELECT contact_id FROM transactions WHERE id = :id');
        $stmtContact->execute([':id' => $transactionId]);
        $contactId = $stmtContact->fetchColumn();

        if ($contactId) {
            Flash::success('Transaksi diselesaikan (Belum Lunas). Silakan catat pembayaran.');
            Response::redirect('/?page=contacts/ledger&id=' . $contactId);
        }

        Flash::success('Transaksi berhasil diselesaikan.');
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        Flash::error('Gagal memproses transaksi: ' . $e->getMessage());
    }
    Response::redirect('/?page=sells/suspended');
}

if ($page === 'categories' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat mengakses Kategori.');
        Response::redirect('/?page=dashboard');
    }

    View::render('categories', [
        'title' => 'Kategori Produk',
        'categories' => $categoryRepository->all(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'categories/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('categories');

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            throw new Exception('Nama kategori tidak boleh kosong.');
        }

        $categoryRepository->create(Auth::businessId(), $name);
        Flash::success('Kategori berhasil ditambahkan.');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
    }
    Response::redirect('/?page=categories');
}

if ($page === 'brands' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Hanya Admin/Manager yang dapat mengakses Merek.');
        Response::redirect('/?page=dashboard');
    }

    View::render('brands', [
        'title' => 'Merek (Brands)',
        'brands' => $brandRepository->all(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'brands/store' && $method === 'POST') {
    $requireAuth();
    $requireCsrf('brands');

    if (!Auth::hasAnyRole('admin', 'manager')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            throw new Exception('Nama merek tidak boleh kosong.');
        }

        $brandRepository->create(Auth::businessId(), $name);
        Flash::success('Merek berhasil ditambahkan.');
    } catch (Throwable $throwable) {
        Flash::error($throwable->getMessage());
    }
    Response::redirect('/?page=brands');
}

if ($page === 'kds' && $method === 'GET') {
    $requireAuth();

    if (!Auth::hasAnyRole('admin', 'manager', 'cashier')) {
        Flash::error('Akses ditolak.');
        Response::redirect('/?page=dashboard');
    }

    View::render('kds', [
        'title' => 'Kitchen Display System (KDS)',
        'settings' => $settingsRepository->get(Auth::businessId()),
    ]);
    exit;
}

if ($page === 'api/kds' && $method === 'GET') {
    $requireAuth();
    header('Content-Type: application/json');

    $businessId = Auth::businessId();

    // Fetch active orders (status 'checked_out' or 'ordered' conceptually meant for kitchen)
    $stmt = $pdo->prepare("
        SELECT id, transaction_number, created_at, status
        FROM transactions
        WHERE business_id = :business_id AND type = 'sell' AND status = 'checked_out'
        ORDER BY created_at ASC
    ");
    $stmt->execute([':business_id' => $businessId]);
    $ordersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    $stmtLines = $pdo->prepare("SELECT product_name, qty FROM transaction_sell_lines WHERE transaction_id = :id");

    foreach ($ordersRaw as $order) {
        $stmtLines->execute([':id' => $order['id']]);
        $lines = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

        $created = new DateTime($order['created_at']);
        $now = new DateTime();
        $diff = $now->diff($created);

        $elapsed = '';
        if ($diff->h > 0) $elapsed .= $diff->h . 'j ';
        if ($diff->i > 0) $elapsed .= $diff->i . 'm ';
        $elapsed .= $diff->s . 's';

        $orders[] = [
            'id' => (int)$order['id'],
            'transaction_number' => $order['transaction_number'],
            'status' => $order['status'],
            'time_elapsed' => $elapsed === '0s' ? 'Baru saja' : $elapsed,
            'lines' => $lines
        ];
    }

    echo json_encode($orders);
    exit;
}

if ($page === 'api/kds/complete' && $method === 'POST') {
    $requireAuth();
    header('Content-Type: application/json');

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!Csrf::verify($payload['_csrf'] ?? null)) {
        http_response_code(400);
        echo json_encode(['error' => 'CSRF tidak valid']);
        exit;
    }

    $transactionId = (int)($payload['transaction_id'] ?? 0);
    if ($transactionId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID transaksi tidak valid']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'final' WHERE id = :id AND business_id = :business_id");
        $stmt->execute([
            ':id' => $transactionId,
            ':business_id' => Auth::businessId()
        ]);

        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($page === 'dashboard' && $method === 'GET') {
    $requireAuth();

    if (!$settingsRepository->isOnboardingCompleted(Auth::businessId())) {
        Response::redirect('/?page=onboarding');
    }

    $settings = $settingsRepository->get(Auth::businessId());
    $template = $settingsRepository->activeTemplate(Auth::businessId());

    $businessId = Auth::businessId();
    $orderCount = (int) $pdo->query("SELECT COUNT(*) FROM transactions WHERE business_id = {$businessId} AND type = 'sell' AND status IN ('checked_out', 'final')")->fetchColumn();
    $totalRevenueCents = (int) $pdo->query("SELECT COALESCE(SUM(total_cents),0) FROM transactions WHERE business_id = {$businessId} AND type = 'sell' AND status IN ('checked_out', 'final')")->fetchColumn();

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
