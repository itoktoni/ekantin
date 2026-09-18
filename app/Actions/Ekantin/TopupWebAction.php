<?php

namespace App\Actions\Ekantin;

use App\Concerns\PayloadTrait;
use App\Models\FeeConfig;
use App\Models\Kartu;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class TopupWebAction
{
    use AsAction, PayloadTrait;

    /**
     * Membuat transaksi top up web berstatus "menunggu".
     *
     * Saldo TIDAK ditambah di sini — saldo baru masuk setelah pembayaran QRIS
     * terkonfirmasi lewat KonfirmasiTopupWebAction.
     */
    public function handle(array $input): array
    {
        try {
            return DB::transaction(function () use ($input) {
                $nominal = (int) ($input['nominal'] ?? 0);
                $min = FeeConfig::minTopup();
                if ($nominal < $min) {
                    return $this->payload(TOAST_FAILED, 'Minimal top up Rp'.number_format($min, 0, ',', '.'));
                }

                $kartu = Kartu::where('kartu_barcode', $input['kartu_barcode'])->lockForUpdate()->first();
                if (! $kartu) {
                    return $this->payload(TOAST_FAILED, 'Kartu tidak ditemukan.');
                }
                if ($kartu->kartu_status !== 'aktif') {
                    return $this->payload(TOAST_FAILED, 'Kartu nonaktif, top up ditolak.');
                }

                // idempotency mencegah satu form terkirim dua kali menjadi dua transaksi.
                $idempotency = $input['idempotency'] ?? 'TOPUPWEB-'.$kartu->kartu_id.'-'.now()->format('YmdHis');
                $trx = Transaksi::firstOrCreate(
                    ['transaksi_idempotency' => $idempotency],
                    [
                        'transaksi_jenis' => 'topup_web',
                        'transaksi_status' => 'menunggu',
                        'transaksi_id_kartu' => $kartu->kartu_id,
                        'transaksi_total' => $nominal,
                        'transaksi_bersih' => $nominal,
                    ]
                );

                return $this->payload(TOAST_SUCCESS, $trx->fresh());
            });
        } catch (\Throwable $th) {
            report($th);

            return $this->payload(TOAST_FAILED, $th->getMessage());
        }
    }
}
