<?php
declare(strict_types=1);

namespace Siappos\Domain\Settings;

enum BusinessTemplate: string
{
    case Fnb = 'fnb';
    case Service = 'service';
    case Retail = 'retail';
    case KelontongBangunan = 'kelontong_bangunan';

    /** @return array<int, self> */
    public static function all(): array
    {
        return [self::Fnb, self::Service, self::Retail, self::KelontongBangunan];
    }

    public function label(): string
    {
        return match ($this) {
            self::Fnb => 'F&B',
            self::Service => 'Service',
            self::Retail => 'Retail',
            self::KelontongBangunan => 'Kelontong/Bangunan',
        };
    }

    public function shortDescription(): string
    {
        return match ($this) {
            self::Fnb => 'Kasir cepat untuk menu, pajak PB1, dan kontrol bahan baku.',
            self::Service => 'Kelola layanan, staf, komisi, dan pembayaran bertahap.',
            self::Retail => 'Cocok untuk SKU, harga promo, dan ritme transaksi tinggi.',
            self::KelontongBangunan => 'Fokus kuantitas desimal, unit fleksibel, dan stok curah.',
        };
    }

    /** @return list<string> */
    public function enabledModules(): array
    {
        return match ($this) {
            self::Fnb => ['POS Kasir', 'Menu Produk', 'PB1 Tax', 'Stock Opname', 'Resep/BOM (next)'],
            self::Service => ['POS Kasir', 'Layanan', 'Invoice', 'Stock Opname', 'Booking + Komisi (next)'],
            self::Retail => ['POS Kasir', 'Produk SKU', 'Diskon + Pajak', 'Stock Opname', 'Laporan Penjualan'],
            self::KelontongBangunan => ['POS Kasir', 'Qty Desimal', 'Harga Produk', 'Stock Opname', 'Konversi Unit (next)'],
        };
    }

    /** @return list<string> */
    public function firstWeekChecklist(): array
    {
        return match ($this) {
            self::Fnb => [
                'Input minimal 10 menu aktif',
                'Set PB1 dan skema diskon harian',
                'Simulasi transaksi dine-in dan take-away',
            ],
            self::Service => [
                'Input layanan utama dan durasinya',
                'Siapkan aturan DP untuk booking',
                'Uji invoice lunas dan invoice parsial',
            ],
            self::Retail => [
                'Import SKU prioritas penjualan',
                'Set promo persentase dan nominal',
                'Uji retur dan stock opname awal',
            ],
            self::KelontongBangunan => [
                'Input produk curah dengan qty desimal',
                'Tetapkan unit jual dan unit stok',
                'Uji transaksi campuran ecer dan grosir',
            ],
        };
    }
}
