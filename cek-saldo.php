<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Kartu;
use App\Models\Transaksi;

$kartu = Kartu::orderBy('kartu_barcode')->get();
echo "=== SALDO KARTU SEKARANG ===\n";
foreach ($kartu as $k) {
    echo "{$k->kartu_barcode} : saldo {$k->kartu_saldo}\n";
}

echo "\n=== TRANSAKSI KARTU 1 (SW-1001) ===\n";
foreach (Transaksi::where('transaksi_id_kartu', 1)->orderBy('transaksi_id')->get() as $t) {
    echo "#{$t->transaksi_id} {$t->transaksi_jenis} {$t->transaksi_status} total={$t->transaksi_total} idem=".($t->transaksi_idempotency ?? '-')."\n";
}

echo "\n=== TRANSAKSI UJI (sisa) ===\n";
$uji = Transaksi::whereIn('transaksi_idempotency', ['UJI-TOPUPWEB-1', 'BROWSER-TOPUP-UJI'])->get();
foreach ($uji as $t) {
    echo "#{$t->transaksi_id} {$t->transaksi_jenis} {$t->transaksi_status} total={$t->transaksi_total} idem={$t->transaksi_idempotency}\n";
}
echo 'jumlah sisa transaksi uji: '.$uji->count()."\n";

echo "\n=== SEMUA TRANSAKSI (jenis/status) ===\n";
foreach (Transaksi::orderBy('transaksi_id')->get() as $t) {
    echo "#{$t->transaksi_id} kartu={$t->transaksi_id_kartu} {$t->transaksi_jenis} {$t->transaksi_status} total={$t->transaksi_total} idem=".substr((string) $t->transaksi_idempotency, 0, 30)."\n";
}
