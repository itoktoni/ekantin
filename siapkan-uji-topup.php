<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Actions\Ekantin\TopupWebAction;
use App\Models\Kartu;
use Illuminate\Support\Facades\Auth;

$statis = (string) config('website.qris');
echo "=== QRIS DARI .env ===\n";
echo 'panjang           : '.strlen($statis)."\n";
echo 'ada tanda kutip   : '.(str_contains($statis, '"') ? 'MASIH ADA (salah)' : 'tidak (benar, sudah dibersihkan Dotenv)')."\n";
echo 'awal payload      : '.substr($statis, 0, 40)."\n";

// CRC payload statis harus valid apa adanya
$body = substr($statis, 0, -4);
$crc = substr($statis, -4);
$hitung = strtoupper(str_pad(dechex(crc16($body)), 4, '0', STR_PAD_LEFT));
echo "CRC statis        : tertulis $crc / hitung $hitung => ".($crc === $hitung ? 'VALID' : 'TIDAK VALID')."\n\n";

$nominal = 25000;
$dinamis = nominalQRIS($statis, $nominal);
$body2 = substr($dinamis, 0, -4);
$crc2 = substr($dinamis, -4);
$hitung2 = strtoupper(str_pad(dechex(crc16($body2)), 4, '0', STR_PAD_LEFT));

echo "=== PAYLOAD DINAMIS (nominal $nominal) ===\n";
echo 'panjang           : '.strlen($dinamis)."\n";
echo "CRC               : tertulis $crc2 / hitung $hitung2 => ".($crc2 === $hitung2 ? 'VALID' : 'TIDAK VALID')."\n";
preg_match('/(54\d{2})([\d.]+)/', $dinamis, $m);
echo 'tag 54 (nominal)  : '.($m ? "tag={$m[1]} nilai={$m[2]}" : 'TIDAK ADA')."\n";
echo 'akhiran           : '.substr($dinamis, -12)."\n";
echo 'tag 63 di akhir   : '.(preg_match('/6304[0-9A-F]{4}$/', $dinamis) ? 'ya' : 'tidak')."\n\n";

// buat transaksi pending untuk diuji di browser
$admin = App\Models\User::where('email', 'admin@sekolah.id')->firstOrFail();
Auth::loginUsingId($admin->id);
$kartu = Kartu::where('kartu_status', 'aktif')->orderBy('kartu_barcode')->first();

$r = TopupWebAction::run([
    'kartu_barcode' => $kartu->kartu_barcode,
    'nominal' => $nominal,
    'idempotency' => 'BROWSER-TOPUP-UJI',
]);
$trx = $r['data'];
echo "=== TRANSAKSI UJI ===\n";
echo "kartu             : {$kartu->kartu_barcode} (saldo ".(int) $kartu->kartu_saldo.")\n";
echo "transaksi_id      : {$trx->transaksi_id}\n";
echo "status            : {$trx->transaksi_status}\n";
file_put_contents(__DIR__.'/storage/app/public/uji-topup.json', json_encode([
    'transaksi_id' => $trx->transaksi_id,
    'kartu_barcode' => $kartu->kartu_barcode,
    'saldo_awal' => (int) $kartu->kartu_saldo,
    'nominal' => $nominal,
]));
echo "info uji disimpan : storage/app/public/uji-topup.json\n";
