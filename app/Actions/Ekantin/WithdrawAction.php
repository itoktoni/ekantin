<?php

namespace App\Actions\Ekantin;

use App\Concerns\PayloadTrait;
use App\Models\Gerai;
use App\Models\Penarikan;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class WithdrawAction
{
    use AsAction, PayloadTrait;

    public function handle(array $input): array
    {
        try {
            return DB::transaction(function () use ($input) {
                $nominal = (int) ($input['nominal'] ?? 0);
                if ($nominal < 1) {
                    return $this->payload(TOAST_FAILED, 'Nominal penarikan tidak valid.');
                }
                $gerai = Gerai::whereKey($input['gerai_id'])->lockForUpdate()->firstOrFail();
                if ($nominal > (int) $gerai->gerai_saldo) {
                    return $this->payload(TOAST_FAILED, 'Nominal melebihi saldo gerai Rp'.number_format((int) $gerai->gerai_saldo, 0, ',', '.'));
                }
                $gerai->decrement('gerai_saldo', $nominal);
                $tarik = Penarikan::create([
                    'penarikan_id_gerai' => $gerai->gerai_id,
                    'penarikan_nominal' => $nominal,
                    'penarikan_status' => 'diajukan',
                    'penarikan_id_admin' => $input['id_admin'] ?? null,
                ]);
                Transaksi::create([
                    'transaksi_jenis' => 'withdraw',
                    'transaksi_status' => 'berhasil',
                    'transaksi_id_gerai' => $gerai->gerai_id,
                    'transaksi_total' => $nominal,
                    'transaksi_bersih' => $nominal,
                    'transaksi_alasan' => "Penarikan dana #{$tarik->penarikan_id}",
                ]);

                return $this->payload(TOAST_SUCCESS, $tarik->fresh());
            });
        } catch (\Throwable $th) {
            report($th);

            return $this->payload(TOAST_FAILED, $th->getMessage());
        }
    }
}
