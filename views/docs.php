<div class="panel">
    <h2>📖 Buku Pintar (Knowledge Bank) SiapPOS</h2>
    <p class="muted">Panduan resmi penggunaan sistem kasir dan manajemen operasional bisnis Anda.</p>
    <hr>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <h3>Daftar Isi</h3>
            <ul>
                <li><a href="#pengenalan">1. Pengenalan Role & Akses</a></li>
                <li><a href="#dashboard">2. Memahami Dashboard & Laporan</a></li>
                <li><a href="#setup">3. Setup Bisnis Awal</a></li>
                <li><a href="#katalog">4. Katalog: Kategori, Brand, & Produk</a></li>
                <li><a href="#stok">5. Manajemen Stok (Purchases & Adjustments)</a></li>
                <li><a href="#pos">6. Terminal Kasir (POS)</a></li>
                <li><a href="#restoran">7. Mode Restoran (KDS, Meja & Modifiers)</a></li>
                <li><a href="#keuangan">8. Keuangan & Akuntansi Ledger</a></li>
                <li><a href="#kontak">9. Manajemen Kontak (Pelanggan & Pemasok)</a></li>
                <li><a href="#zreport">10. Penutupan Kasir (Z-Report)</a></li>
            </ul>
        </div>
    </div>

    <hr>

    <div id="pengenalan" class="panel bg-light" style="margin-top: 20px;">
        <h3>1. Pengenalan Role & Akses</h3>
        <p>Sistem ini dirancang dengan keamanan dan batasan tugas yang ketat menggunakan sistem Role-Based Access Control (RBAC). Terdapat 3 role utama:</p>
        <ul>
            <li><strong>Admin (Owner):</strong> Memiliki kontrol absolut. Dapat merubah profil bisnis, mereset sistem, melihat Laporan Laba/Rugi, dan mengatur akun perbankan (Accounts).</li>
            <li><strong>Manager:</strong> Bertugas memantau operasional harian. Dapat menambah produk, melakukan pembelian stok barang (Purchase), dan melakukan penyesuaian stok jika barang rusak (Stock Adjustments).</li>
            <li><strong>Cashier (Kasir):</strong> Bertugas melayani pelanggan. Hanya dapat membuka shift kas (Cash Register), mengakses Terminal POS, dan melakukan cetak struk tagihan.</li>
        </ul>
    </div>

    <div id="dashboard" class="panel bg-light" style="margin-top: 20px;">
        <h3>2. Memahami Dashboard & Laporan</h3>
        <p>Halaman utama aplikasi yang merangkum kesehatan bisnis Anda.</p>
        <ul>
            <li><strong>Metrik Utama:</strong> Menampilkan Total Penjualan hari ini, Total Pembelian/Restock, dan Total Pengeluaran Operasional.</li>
            <li><strong>Laporan Kadaluarsa:</strong> Jika Anda mengaktifkan pelacakan batch FIFO, produk yang mendekati masa kadaluarsa (Expiry) akan muncul di peringatan stok.</li>
            <li><strong>Z-Report (Laporan Shift Kasir):</strong> Kasir dapat mencetak laporan harian tentang jumlah uang cash yang diterima di laci kasir saat mereka menutup shift.</li>
        </ul>
    </div>

    <div id="setup" class="panel bg-light" style="margin-top: 20px;">
        <h3>3. Setup Bisnis Awal</h3>
        <p>Gunakan menu "Setup Bisnis" dari navbar atas (Hanya Admin & Manager). Menu ini sangat kritikal:</p>
        <ul>
            <li>Tentukan <strong>Nama Bisnis</strong> dan <strong>Mata Uang</strong> utama.</li>
            <li>Aktifkan <strong>Pajak (Tax/PB1)</strong> jika bisnis Anda memungut pajak restoran atau PPN. (Contoh: 10%). Pajak ini akan otomatis ditambahkan ke total keranjang pelanggan saat di POS.</li>
            <li>Pilih <strong>Tipe Bisnis:</strong> Mode Restoran akan mengaktifkan fitur Modifiers, Meja, dan Kitchen Display System. Mode Retail akan fokus pada barcode dan manajemen SKU.</li>
        </ul>
    </div>

    <div id="katalog" class="panel bg-light" style="margin-top: 20px;">
        <h3>4. Katalog: Kategori, Brand, & Produk</h3>
        <p>Hierarki barang dagangan diatur dari hal yang paling umum hingga spesifik:</p>
        <ul>
            <li><strong>Kategori & Brand:</strong> Buat Kategori (cth: "Minuman Dingin") dan Brand (cth: "Coca Cola") sebelum membuat Produk untuk mempermudah pelacakan laporan.</li>
            <li><strong>Tipe Produk Tunggal (Single):</strong> Produk standar yang tidak memiliki ukuran atau variasi. (cth: Aqua Botol 600ml).</li>
            <li><strong>Tipe Produk Variasi (Variable):</strong> Produk yang memiliki turunan. (cth: Kaos dengan variasi S, M, L).</li>
            <li><strong>SKU:</strong> Barcode unik yang bisa discan dengan pemindai barcode di layar POS.</li>
        </ul>
    </div>

    <div id="stok" class="panel bg-light" style="margin-top: 20px;">
        <h3>5. Manajemen Stok (Purchases & Adjustments)</h3>
        <p>SiapPOS menggunakan perhitungan akuntansi <strong>First-In, First-Out (FIFO)</strong>. Produk tidak bisa asal ditambah stoknya.</p>
        <ul>
            <li><strong>Purchases (Pembelian/Restock):</strong> Ini adalah cara resmi menambah stok. Anda mencatat pembelian barang dari Pemasok (Supplier). Sistem akan menyimpan data "Berapa modal beli per-item pada tanggal tersebut?". Saat barang ini terjual nanti, sistem akan menghitung Laba Bersih berdasarkan modal beli batch ini.</li>
            <li><strong>Stock Adjustments (Penyesuaian Stok/Opname):</strong> Digunakan jika ada barang yang hilang, rusak, basi, atau kadaluarsa. Ini akan mengurangi stok fisik dan mencatat nilai kerugian barang tersebut secara otomatis ke laporan rugi/laba.</li>
        </ul>
    </div>

    <div id="pos" class="panel bg-light" style="margin-top: 20px;">
        <h3>6. Terminal Kasir (POS)</h3>
        <p>Halaman utama untuk kasir bertransaksi (/pos). Sebelum mengakses ini, kasir wajib membuka "Shift Laci Kasir" (Cash Register) dan memasukkan modal awal koin kembalian.</p>
        <ul>
            <li>Pencarian produk bisa dilakukan dengan <strong>mengetik nama</strong> atau menembak barcode dengan <strong>scanner</strong>.</li>
            <li>Ubah jumlah pesanan (Qty) atau klik tombol produk beberapa kali untuk menambah qty.</li>
            <li><strong>Diskon:</strong> Diskon keranjang dapat diberikan dalam bentuk Nominal Fix (Rp) atau Persentase (%).</li>
            <li><strong>Pembayaran (Checkout):</strong> Anda bisa menerima Cash (tunai) atau QRIS/Transfer. Jika tunai yang diterima kurang dari total, status transaksi akan berubah menjadi "Hutang/Due".</li>
            <li><strong>Draft/Suspend:</strong> Jika pelanggan ingin menambah pesanan nanti, klik "Suspend". Transaksi akan ditangguhkan dan meja bisa digunakan untuk transaksi orang lain.</li>
        </ul>
    </div>

    <div id="restoran" class="panel bg-light" style="margin-top: 20px;">
        <h3>7. Mode Restoran (KDS, Meja & Modifiers)</h3>
        <p>Jika tipe bisnis diset ke "F&B/Restoran", fitur tambahan berikut akan aktif:</p>
        <ul>
            <li><strong>Manajemen Meja:</strong> Anda bisa membuat nomor/nama meja. Kasir dapat melampirkan pesanan pelanggan ke "Meja 4".</li>
            <li><strong>Modifiers (Opsi Tambahan):</strong> Atur grup tambahan seperti "Topping Pizza" atau "Level Pedas". Saat kasir mengklik produk, pop-up modifier akan muncul, dan tambahan harga (misal: Extra Keju +Rp5.000) akan dihitung otomatis.</li>
            <li><strong>Kitchen Display System (KDS):</strong> Layar khusus untuk kru dapur. Begitu kasir klik "Checkout", pesanan akan otomatis muncul di layar KDS dapur beserta suara bel notifikasi. Koki bisa mengklik "Selesai" jika makanan sudah siap dihidangkan.</li>
        </ul>
    </div>

    <div id="keuangan" class="panel bg-light" style="margin-top: 20px;">
        <h3>8. Keuangan & Akuntansi Ledger</h3>
        <p>Sistem ini memiliki Double-Entry Accounting sederhana namun kuat.</p>
        <ul>
            <li><strong>Accounts (Rekening):</strong> Buat wadah uang digital (BCA Cabang Sudirman, Laci Kasir Toko, Dana Petty Cash).</li>
            <li>Saat kasir menerima pembayaran "QRIS", Anda bisa mengatur agar dana tersebut otomatis mem-posting kredit ke Akun "BCA".</li>
            <li><strong>Expenses (Pengeluaran Beban):</strong> Catat bayar listrik, gaji karyawan, atau beli air galon. Pilih uangnya keluar dari rekening mana. Transaksi ini akan otomatis muncul sebagai faktor pengurang di Laporan Keuntungan Laba/Rugi.</li>
        </ul>
    </div>

    <div id="kontak" class="panel bg-light" style="margin-top: 20px;">
        <h3>9. Manajemen Kontak (Pelanggan & Pemasok)</h3>
        <p>Manajemen utang piutang (Ledger).</p>
        <ul>
            <li>Jika pelanggan langganan (Customer) membeli barang namun kasirnya mencatat penerimaan uang "Rp 0" (Pembayaran tempo), maka sistem akan menaruh tagihan ke dalam <strong>Buku Besar (Ledger) Kontak</strong> tersebut.</li>
            <li>Buka halaman "Kontak", klik nama pelanggan, lalu masuk ke tab "Ledger/Hutang". Anda bisa melihat riwayat cicilan atau melakukan pembayaran pelunasan atas hutang-hutang nota lama mereka.</li>
            <li>Berlaku juga untuk utang kita ke Pemasok (Supplier).</li>
        </ul>
    </div>

    <div id="zreport" class="panel bg-light" style="margin-top: 20px;">
        <h3>10. Penutupan Kasir (Z-Report)</h3>
        <p>Prosedur akhir hari untuk kasir.</p>
        <ul>
            <li>Kasir mengklik "Tutup Kasir" dari Navbar.</li>
            <li>Sistem akan menyajikan <strong>Z-Report</strong> yang merupakan rekap detail: Berapa saldo awal koin, total uang masuk dari tunai, dan berapa total dari QRIS.</li>
            <li>Manajer mencetak Z-Report, lalu kasir menyetorkan uang fisik yang ada di laci ke manajer. Hal ini mencegah kebocoran atau kecurangan dana (Fraud).</li>
        </ul>
    </div>
</div>
