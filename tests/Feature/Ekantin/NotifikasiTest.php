<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Jobs\KirimNotifikasiJob;
use App\Models\NotifikasiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('job menandai notifikasi terkirim max 3 percobaan', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $trx = ProcessPurchaseAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'gerai_id' => $gerai->gerai_id, 'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]]])['data'];
    KirimNotifikasiJob::dispatchSync($trx->transaksi_id);
    $log = NotifikasiLog::where('notif_id_transaksi', $trx->transaksi_id)->first();
    expect($log->notif_status)->toBe('terkirim')->and((int) $log->notif_percobaan)->toBeLessThanOrEqual(3);
});
