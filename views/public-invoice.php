<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="/assets/app.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .invoice-box {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="invoice-box">
        <?php \Siappos\Shared\Flash::display(); ?>

        <div class="text-center mb-4">
            <h2>Tagihan: <?= htmlspecialchars((string)$transaction['transaction_number']) ?></h2>
            <p class="text-muted">Tanggal: <?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></p>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <strong>Status Pembayaran:</strong><br>
                <?php if ($transaction['payment_status'] === 'paid'): ?>
                    <span class="badge bg-success fs-6">LUNAS</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6">BELUM LUNAS</span>
                <?php endif; ?>
            </div>
            <div class="col-sm-6 text-end">
                <strong>Total Tagihan:</strong><br>
                <span class="fs-4 fw-bold"><?= \Siappos\Shared\Money::format((int)$transaction['total_cents']) ?></span>
            </div>
        </div>

        <?php if ($transaction['payment_status'] !== 'paid'): ?>
            <div class="card bg-light mt-4">
                <div class="card-body text-center">
                    <h5>Bayar Tagihan Secara Online</h5>
                    <p class="text-muted">Gunakan gateway pembayaran kami yang aman.</p>
                    <form action="/?page=api/pay-invoice" method="POST">
                        <?php \Siappos\Shared\Csrf::token(); ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars((string)$transaction['payment_token']) ?>">
                        <button type="submit" class="btn btn-primary btn-lg">Bayar Sekarang MOCK</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/app.js"></script>
</body>
</html>
