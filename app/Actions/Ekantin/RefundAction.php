<?php

namespace App\Actions\Ekantin;

use App\Concerns\PayloadTrait;
use App\Models\AuditLog;
use App\Models\Gerai;
use App\Models\Transaksi;
use App\Services\Ekantin\SplitDana;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RefundAction
{
    use AsAction, PayloadTrait;

    public function handle(array $input): array
    {
        try {
            return DB::transaction(function () use ($input) {
                if (empty($input['alasan'])) {
                    return $this->payload(TOAST_FAILED, 'Alasan refund wajib diisi.');
                }
                $asal = Transaksi::whereKey($input['transaksi_id'])->lockForUpdate()->firstOrFail();
                if ($asal->transaksi_jenis !== 'beli' || $asal->transaksi_status !== 'berhasil') {
                    return $this->payload(TOAST_FAILED, 'Hanya transaksi pembelian berhasil yang bisa di-refund.');
                }
                if (Transaksi::where('transaksi_id_reversal_of', $asal->transaksi_id)->exists()) {
                    return $this->payload(TOAST_FAILED, 'Transaksi ini sudah pernah di-refund.');
                }
                $kartu = $asal->hasKartu()->lockForUpdate()->firstOrFail();
                $kembali = (int) $asal->transaksi_total + (int) $asal->transaksi_fee_kebersihan + (int) $asal->transaksi_fee_keamanan + (int) $asal->transaksi_fee_pengelolaan + (int) $asal->transaksi_fee_sistem;
                $saldoAkhir = (int) $kartu->kartu_saldo + $kembali;
                $refund = Transaksi::create([
                    'transaksi_jenis' => 'refund',
                    'transaksi_status' => 'berhasil',
                    'transaksi_id_kartu' => $kartu->kartu_id,
                    'transaksi_id_gerai' => $asal->transaksi_id_gerai,
                    'transaksi_total' => $kembali,
                    'transaksi_bersih' => $kembali,
                    'transaksi_saldo_akhir' => $saldoAkhir,
                    'transaksi_id_reversal_of' => $asal->transaksi_id,
                    'transaksi_alasan' => $input['alasan'],
                ]);
                $kartu->update(['kartu_saldo' => $saldoAkhir]);
                // Kembalikan saldo tiap gerai proporsional seperti saat bagi (POS sentral).
                $subPerGerai = [];
                foreach ($asal->hasItems()->get() as $item) {
                    if ($item->item_id_gerai) {
                        $subPerGerai[$item->item_id_gerai] = ($subPerGerai[$item->item_id_gerai] ?? 0) + (int) $item->item_subtotal;
                    }
                }
                if (! empty($subPerGerai)) {
                    $feeAsal = (int) $asal->transaksi_fee_kebersihan + (int) $asal->transaksi_fee_keamanan + (int) $asal->transaksi_fee_pengelolaan + (int) $asal->transaksi_fee_sistem;
                    $kembaliPerGerai = SplitDana::bagi($subPerGerai, min($feeAsal, (int) $asal->transaksi_total));
                    foreach ($kembaliPerGerai as $gid => $bersih) {
                        $gerai = Gerai::whereKey($gid)->lockForUpdate()->first();
                        if ($gerai) {
                            $gerai->update(['gerai_saldo' => max((int) $gerai->gerai_saldo - max($bersih, 0), 0)]);
                        }
                    }
                } elseif ($asal->transaksi_id_gerai) {
                    $gerai = $asal->hasGerai()->lockForUpdate()->first();
                    if ($gerai) {
                        $gerai->update(['gerai_saldo' => max((int) $gerai->gerai_saldo - (int) $asal->transaksi_bersih, 0)]);
                    }
                }
                AuditLog::create([
                    'audit_aksi' => 'refund',
                    'audit_model' => 'transaksi',
                    'audit_id_record' => $asal->transaksi_id,
                    'audit_lama' => ['transaksi_status' => 'berhasil'],
                    'audit_baru' => ['refund_id' => $refund->transaksi_id, 'alasan' => $input['alasan']],
                    'audit_id_user' => $input['id_admin'] ?? null,
                ]);

                return $this->payload(TOAST_SUCCESS, $refund->fresh());
            });
        } catch (\Throwable $th) {
            report($th);

            return $this->payload(TOAST_FAILED, $th->getMessage());
        }
    }
}
