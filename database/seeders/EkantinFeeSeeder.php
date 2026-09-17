<?php

namespace Database\Seeders;

use App\Models\FeeConfig;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;

class EkantinFeeSeeder extends Seeder
{
    /**
     * Seeder fee: pastikan config aktif terdistribusi & setiap transaksi beli ada potongan fee.
     * - Fee flat per transaksi dibagi proporsional ke gerai via SplitDana (lihat ProcessPurchaseAction).
     * - Nominal pakai formatAngka/formatQty di view, bukan di seeder.
     */
    public function run(): void
    {
        // 1. Pastikan config aktif ada dengan distribusi realistis (total 1200)
        $fee = FeeConfig::where('fee_aktif', true)->latest('fee_id')->first();
        if (! $fee) {
            $fee = FeeConfig::create([
                'fee_sistem' => 500,
                'fee_kebersihan' => 200,
                'fee_keamanan' => 200,
                'fee_pengelolaan' => 300,
                'fee_min_topup' => 10000,
                'fee_aktif' => true,
            ]);
        } elseif ((int) $fee->fee_kebersihan === 0 && (int) $fee->fee_keamanan === 0 && (int) $fee->fee_pengelolaan === 0) {
            $fee->update([
                'fee_kebersihan' => 200,
                'fee_keamanan' => 200,
                'fee_pengelolaan' => 300,
            ]);
            $fee->refresh();
        }

        $aktif = FeeConfig::aktif();
        $feeTotal = (int) $aktif->fee_kebersihan + (int) $aktif->fee_keamanan + (int) $aktif->fee_pengelolaan + (int) $aktif->fee_sistem;

        // 2. Backfill transaksi beli yang belum ada fee (legacy seed / manual insert)
        Transaksi::where('transaksi_jenis', 'beli')
            ->whereRaw('(COALESCE(transaksi_fee_kebersihan,0) + COALESCE(transaksi_fee_keamanan,0) + COALESCE(transaksi_fee_pengelolaan,0) + COALESCE(transaksi_fee_sistem,0)) = 0')
            ->each(function (Transaksi $trx) use ($aktif, $feeTotal) {
                $trx->update([
                    'transaksi_fee_kebersihan' => $aktif->fee_kebersihan,
                    'transaksi_fee_keamanan' => $aktif->fee_keamanan,
                    'transaksi_fee_pengelolaan' => $aktif->fee_pengelolaan,
                    'transaksi_fee_sistem' => $aktif->fee_sistem,
                    'transaksi_bersih' => max((int) $trx->transaksi_total - $feeTotal, 0),
                ]);
            });

        $this->command?->info("Fee aktif: kebersihan={$aktif->fee_kebersihan}, keamanan={$aktif->fee_keamanan}, pengelolaan={$aktif->fee_pengelolaan}, sistem={$aktif->fee_sistem} (total {$feeTotal}) — transaksi beli tanpa fee telah di-backfill.");
    }
}
