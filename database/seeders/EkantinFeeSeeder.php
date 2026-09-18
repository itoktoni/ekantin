<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeConfig;
use Illuminate\Database\Seeder;

class EkantinFeeSeeder extends Seeder
{
    /**
     * Seeder fee: pastikan config topup aktif + fee penjualan default terisi.
     * - Fee penjualan = persen per jenis (tabel fee), dipotong dari harga produk
     *   via Fee::rincian() di ProcessPurchaseAction.
     * - Nominal pakai formatAngka/formatQty di view, bukan di seeder.
     */
    public function run(): void
    {
        // 1. Pastikan config topup aktif ada.
        FeeConfig::firstOrCreate(
            ['fee_aktif' => true],
            ['fee_min_topup' => 10000]
        );

        // 2. Pastikan fee penjualan default ada (total 5%).
        $default = [
            ['code_fee' => 'kebersihan', 'nama_fee' => 'Kebersihan', 'value_fee' => 1],
            ['code_fee' => 'keamanan', 'nama_fee' => 'Keamanan', 'value_fee' => 1],
            ['code_fee' => 'pengelolaan', 'nama_fee' => 'Pengelolaan', 'value_fee' => 1],
            ['code_fee' => 'sistem', 'nama_fee' => 'Sistem', 'value_fee' => 2],
        ];
        foreach ($default as $row) {
            Fee::firstOrCreate(['code_fee' => $row['code_fee']], $row);
        }

        $total = Fee::totalPersen();
        $this->command?->info("Fee penjualan aktif: total {$total}% — fee kosong berarti tanpa potongan.");
    }
}
