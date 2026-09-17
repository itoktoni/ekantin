<?php

namespace App\Actions\Ekantin;

use App\Concerns\PayloadTrait;
use App\Jobs\KirimNotifikasiJob;
use App\Models\Kartu;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class TopupTunaiAction
{
    use AsAction, PayloadTrait;

    public function handle(array $input): array
    {
        try {
            return DB::transaction(function () use ($input) {
                $nominal = (int) ($input['nominal'] ?? 0);
                if ($nominal < 1) {
                    return $this->payload(TOAST_FAILED, 'Nominal top up tidak valid.');
                }
                $kartu = Kartu::where('kartu_barcode', $input['kartu_barcode'])->lockForUpdate()->first();
                if (! $kartu) {
                    return $this->payload(TOAST_FAILED, 'Kartu tidak ditemukan.');
                }
                if ($kartu->kartu_status !== 'aktif') {
                    return $this->payload(TOAST_FAILED, 'Kartu nonaktif, top up ditolak.');
                }
                $saldoAkhir = (int) $kartu->kartu_saldo + $nominal;
                $trx = Transaksi::create([
                    'transaksi_jenis' => 'topup_tunai',
                    'transaksi_status' => 'berhasil',
                    'transaksi_id_kartu' => $kartu->kartu_id,
                    'transaksi_total' => $nominal,
                    'transaksi_bersih' => $nominal,
                    'transaksi_saldo_akhir' => $saldoAkhir,
                    'transaksi_id_kasir' => $input['id_kasir'] ?? null,
                ]);
                $kartu->update(['kartu_saldo' => $saldoAkhir]);
                $trx->hasNotif()->create(['notif_saluran' => 'log', 'notif_status' => 'menunggu', 'notif_payload' => ['total' => $nominal, 'saldo_akhir' => $saldoAkhir]]);
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
