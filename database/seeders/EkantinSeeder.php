<?php

namespace Database\Seeders;

use App\Models\FeeConfig;
use Illuminate\Database\Seeder;

class EkantinSeeder extends Seeder
{
    public function run(): void
    {
        $fee = FeeConfig::firstOrCreate(
            ['fee_aktif' => true],
            ['fee_sistem' => 500, 'fee_kebersihan' => 200, 'fee_keamanan' => 200, 'fee_pengelolaan' => 300, 'fee_min_topup' => 10000]
        );

        // Jika fee aktif lama masih 0 di komponen lain, upgrade ke distribusi baru (total 1200)
        if ((int) $fee->fee_kebersihan === 0 && (int) $fee->fee_keamanan === 0 && (int) $fee->fee_pengelolaan === 0) {
            $fee->update([
                'fee_kebersihan' => 200,
                'fee_keamanan' => 200,
                'fee_pengelolaan' => 300,
                // fee_sistem tetap 500
            ]);
        }

        // Backfill: setiap transaksi beli yang fee_total nya 0 harus ada potongan fee dari config aktif
        $aktif = FeeConfig::aktif();
        $feeTotal = (int) $aktif->fee_kebersihan + (int) $aktif->fee_keamanan + (int) $aktif->fee_pengelolaan + (int) $aktif->fee_sistem;
        if ($feeTotal > 0) {
            \App\Models\Transaksi::where('transaksi_jenis', 'beli')
                ->whereRaw('(transaksi_fee_kebersihan + transaksi_fee_keamanan + transaksi_fee_pengelolaan + transaksi_fee_sistem) = 0')
                ->each(function ($trx) use ($aktif, $feeTotal) {
                    $trx->update([
                        'transaksi_fee_kebersihan' => $aktif->fee_kebersihan,
                        'transaksi_fee_keamanan' => $aktif->fee_keamanan,
                        'transaksi_fee_pengelolaan' => $aktif->fee_pengelolaan,
                        'transaksi_fee_sistem' => $aktif->fee_sistem,
                        'transaksi_bersih' => max((int) $trx->transaksi_total - $feeTotal, 0),
                    ]);
                });
        }
    }
}
