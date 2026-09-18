<?php

namespace App\Actions\Ekantin;

use App\Concerns\PayloadTrait;
use App\Jobs\KirimNotifikasiJob;
use App\Models\Fee;
use App\Models\Gerai;
use App\Models\Kartu;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\Ekantin\SplitDana;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ProcessPurchaseAction
{
    use AsAction, PayloadTrait;

    // POS sentral: 1 keranjang campur banyak gerai, bayar sekali di kasir.
    // Input: ['kartu_barcode', 'items' => [['produk_id', 'qty']], 'idempotency'].
    public function handle(array $input): array
    {
        $rincianFee = [];
        $feeTotal = 0;

        try {
            return DB::transaction(function () use ($input) {
                $metode = $input['metode'] ?? 'kartu';
                $isWalkin = in_array($metode, ['tunai', 'qris'], true);

                $kartu = null;
                if (! $isWalkin) {
                    $kartu = Kartu::where('kartu_barcode', $input['kartu_barcode'])->lockForUpdate()->first();
                    if (! $kartu) {
                        return $this->payload(TOAST_FAILED, 'Kartu tidak ditemukan.');
                    }
                    if ($kartu->kartu_status !== 'aktif') {
                        return $this->payload(TOAST_FAILED, 'Kartu nonaktif, transaksi ditolak.');
                    }
                }
                if (! empty($input['idempotency'])) {
                    $ada = Transaksi::where('transaksi_idempotency', $input['idempotency'])->first();
                    if ($ada) {
                        return $this->payload(TOAST_SUCCESS, $ada);
                    }
                }
                $total = 0;
                $rows = [];
                $subPerGerai = [];
                $geraiIds = [];
                foreach ($input['items'] as $it) {
                    $p = Produk::whereKey($it['produk_id'])->where('produk_status', 'tersedia')->first();
                    if (! $p) {
                        return $this->payload(TOAST_FAILED, 'Ada produk tidak tersedia.');
                    }
                    $gerai = Gerai::whereKey($p->produk_id_gerai)->where('gerai_status', 'buka')->lockForUpdate()->first();
                    if (! $gerai) {
                        return $this->payload(TOAST_FAILED, "Gerai {$p->produk_nama} sedang tutup.");
                    }
                    $sub = (int) $p->produk_harga * (int) $it['qty'];
                    $total += $sub;
                    $rows[] = ['gerai_id' => $gerai->gerai_id, 'gerai_nama' => $gerai->gerai_nama, 'nama' => $p->produk_nama, 'harga' => (int) $p->produk_harga, 'qty' => (int) $it['qty'], 'subtotal' => $sub];
                    $subPerGerai[$gerai->gerai_id] = ($subPerGerai[$gerai->gerai_id] ?? 0) + $sub;
                    $geraiIds[$gerai->gerai_id] = $gerai;
                }
                if (empty($rows)) {
                    return $this->payload(TOAST_FAILED, 'Keranjang kosong.');
                }
                $rincianFee = Fee::rincian($total);
                $feeTotal = (int) array_sum($rincianFee);
                if (! $isWalkin) {
                    if ((int) $kartu->kartu_saldo < $total + $feeTotal) {
                        return $this->payload(TOAST_FAILED, 'Saldo tidak mencukupi. Kurang Rp'.number_format($total + $feeTotal - (int) $kartu->kartu_saldo, 0, ',', '.'));
                    }
                    if (! empty($kartu->kartu_limit_harian)) {
                        $pakai = (int) Transaksi::where('transaksi_id_kartu', $kartu->kartu_id)
                            ->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
                            ->whereDate('created_at', today())->sum('transaksi_total');
                        if ($pakai + $total > (int) $kartu->kartu_limit_harian) {
                            $sisa = (int) $kartu->kartu_limit_harian - $pakai;

                            return $this->payload(TOAST_FAILED, 'Limit harian tercapai. Sisa limit Rp'.number_format(max($sisa, 0), 0, ',', '.'));
                        }
                    }
                }
                $bersihTotal = max($total - $feeTotal, 0);
                $bersihPerGerai = SplitDana::bagi($subPerGerai, min($feeTotal, $total));
                $saldoAkhir = $isWalkin ? null : (int) $kartu->kartu_saldo - $total - $feeTotal;
                $trx = Transaksi::create([
                    'transaksi_jenis' => 'beli',
                    'transaksi_status' => 'berhasil',
                    'transaksi_metode' => $metode,
                    'transaksi_id_kartu' => $isWalkin ? null : $kartu->kartu_id,
                    'transaksi_total' => $total,
                    'transaksi_fee_kebersihan' => 0,
                    'transaksi_fee_keamanan' => 0,
                    'transaksi_fee_pengelolaan' => 0,
                    'transaksi_fee_sistem' => 0,
                    'transaksi_fee_total' => $feeTotal,
                    'transaksi_fee_rincian' => $rincianFee,
                    'transaksi_bersih' => $bersihTotal,
                    'transaksi_saldo_akhir' => $saldoAkhir,
                    'transaksi_limit_snapshot' => $isWalkin ? null : $kartu->kartu_limit_harian,
                    'transaksi_idempotency' => $input['idempotency'] ?? ($isWalkin ? 'POS-W-'.time() : 'POS-'.$kartu->kartu_id.'-'.time()),
                    'transaksi_id_kasir' => $input['id_kasir'] ?? null,
                ]);
                foreach ($rows as $r) {
                    $trx->hasItems()->create(['item_id_gerai' => $r['gerai_id'], 'item_nama' => $r['nama'], 'item_harga' => $r['harga'], 'item_qty' => $r['qty'], 'item_subtotal' => $r['subtotal'], 'item_status' => 'baru']);
                }
                if (! $isWalkin) {
                    $kartu->update(['kartu_saldo' => $saldoAkhir]);
                }
                foreach ($bersihPerGerai as $gid => $bersih) {
                    $geraiIds[$gid]->increment('gerai_saldo', max($bersih, 0));
                }
                $trx->hasNotif()->create(['notif_saluran' => 'log', 'notif_status' => 'menunggu', 'notif_payload' => ['total' => $total, 'fee' => $feeTotal, 'saldo_akhir' => $saldoAkhir, 'gerai' => array_values(array_unique(array_column($rows, 'gerai_nama')))]]);
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
