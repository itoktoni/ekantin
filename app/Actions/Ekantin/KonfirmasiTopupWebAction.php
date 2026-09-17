<?php

namespace App\Actions\Ekantin;

use App\Concerns\PayloadTrait;
use App\Jobs\KirimNotifikasiJob;
use App\Models\Kartu;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class KonfirmasiTopupWebAction
{
    use AsAction, PayloadTrait;

    /**
     * Menyelesaikan top up web: status menjadi "berhasil" dan saldo kartu masuk.
     *
     * Ini titik di mana pembayaran QRIS dinyatakan lunas. Dipanggil dari halaman
     * top up maupun dari callback payment gateway. Aman dipanggil berulang
     * (idempoten) — transaksi yang sudah berhasil tidak menambah saldo dua kali.
     */
    public function handle(int $transaksiId, ?int $idKasir = null): array
    {
        try {
            return DB::transaction(function () use ($transaksiId, $idKasir) {
                $trx = Transaksi::whereKey($transaksiId)->lockForUpdate()->first();
                if (! $trx) {
                    return $this->payload(TOAST_FAILED, 'Transaksi tidak ditemukan.');
                }
                if ($trx->transaksi_status === 'berhasil') {
                    return $this->payload(TOAST_SUCCESS, $trx);
                }
                if ($trx->transaksi_status !== 'menunggu') {
                    return $this->payload(TOAST_FAILED, 'Transaksi berstatus '.$trx->transaksi_status.', tidak bisa dikonfirmasi.');
                }

                $kartu = Kartu::whereKey($trx->transaksi_id_kartu)->lockForUpdate()->first();
                if (! $kartu) {
                    return $this->payload(TOAST_FAILED, 'Kartu tidak ditemukan.');
                }

                $saldoSebelum = (int) $kartu->kartu_saldo;
                $saldoAkhir = $saldoSebelum + (int) $trx->transaksi_total;

                $trx->update([
                    'transaksi_status' => 'berhasil',
                    'transaksi_saldo_akhir' => $saldoAkhir,
                    'transaksi_id_kasir' => $idKasir,
                ]);
                $kartu->update(['kartu_saldo' => $saldoAkhir]);

                $trx->hasNotif()->create([
                    'notif_saluran' => 'log',
                    'notif_status' => 'menunggu',
                    'notif_payload' => ['total' => (int) $trx->transaksi_total, 'saldo_sebelum' => $saldoSebelum, 'saldo_akhir' => $saldoAkhir],
                ]);
                if (class_exists(KirimNotifikasiJob::class)) {
                    KirimNotifikasiJob::dispatch($trx->transaksi_id);
                }

                return $this->payload(TOAST_SUCCESS, $trx->fresh());
            });
        } catch (\Throwable $th) {
            report($th);

            return $this->payload(TOAST_FAILED, $th->getMessage());
        }
    }
}
