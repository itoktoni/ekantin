<?php

namespace App\Jobs;

use App\Models\NotifikasiLog;
use App\Services\Ekantin\NotificationChannelFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class KirimNotifikasiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $transaksiId) {}

    public function handle(): void
    {
        $log = NotifikasiLog::where('notif_id_transaksi', $this->transaksiId)->first();
        if (! $log || $log->notif_status === 'terkirim') {
            return;
        }
        $trx = $log->hasTransaksi()->with(['hasKartu.hasUser', 'hasGerai', 'hasItems'])->first();
        if (! $trx) {
            return;
        }
        $nama = $trx->hasKartu?->hasUser?->name ?? 'Pengguna';
        $gerai = $trx->hasGerai?->gerai_nama ?? '-';
        $item = $trx->hasItems->map(fn ($i) => "{$i->item_nama} x{$i->item_qty}")->join(', ');
        $isi = "{$nama} jajan di {$gerai}: {$item}. Total Rp".number_format((int) $trx->transaksi_total, 0, ',', '.').'. Saldo Rp'.number_format((int) $trx->transaksi_saldo_akhir, 0, ',', '.');
        $log->increment('notif_percobaan');
        $ok = NotificationChannelFactory::create('log')->send($nama, 'Notifikasi e-Kanteen', $isi);
        $log->update(['notif_status' => $ok ? 'terkirim' : ($log->notif_percobaan >= 3 ? 'gagal' : 'menunggu')]);
        if (! $ok && $log->notif_percobaan < 3) {
            throw new \RuntimeException('Kirim notifikasi gagal, coba lagi.');
        }
    }
}
