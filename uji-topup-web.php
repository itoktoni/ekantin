<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Actions\Ekantin\KonfirmasiTopupWebAction;
use App\Actions\Ekantin\TopupWebAction;
use App\Models\Kartu;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

/** Bangun TLV EMVCo sederhana untuk menguji nominalQRIS(). */
function tlv(string $tag, string $value): string
{
    return $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
}

function qrisStatis(string $nama, string $kota): string
{
    $merchant = tlv('00', 'ID.CO.QRIS.WWW').tlv('01', '936000000000000000').tlv('02', 'ID1020000000000');
    $body = tlv('00', '01')
        .tlv('01', '11')                 // 11 = statis
        .tlv('26', $merchant)
        .tlv('52', '5812')               // MCC
        .tlv('53', '360')                // IDR
        .tlv('58', 'ID')
        .tlv('59', $nama)
        .tlv('60', $kota)
        .'6304';

    return $body.strtoupper(str_pad(dechex(crc16($body)), 4, '0', STR_PAD_LEFT));
}

$admin = App\Models\User::where('email', 'admin@sekolah.id')->firstOrFail();
Auth::loginUsingId($admin->id);

$kartu = Kartu::where('kartu_status', 'aktif')->orderBy('kartu_barcode')->first();
$saldoAwal = (int) $kartu->kartu_saldo;
echo "kartu: {$kartu->kartu_barcode} saldo awal: {$saldoAwal}\n\n";

// ---------- 1. Validitas payload QRIS ----------
echo "=== 1. PAYLOAD QRIS ===\n";
$statis = qrisStatis('KANTIN SEKOLAH', 'JAKARTA');
$nominal = 25000;
$dinamis = nominalQRIS($statis, $nominal);

$body = substr($dinamis, 0, -4);
$crcTertulis = substr($dinamis, -4);
$crcHitung = strtoupper(str_pad(dechex(crc16($body)), 4, '0', STR_PAD_LEFT));
echo 'panjang payload   : '.strlen($dinamis)."\n";
echo "crc tertulis      : $crcTertulis\n";
echo "crc dihitung ulang: $crcHitung\n";
echo 'CRC VALID         : '.($crcTertulis === $crcHitung ? 'YA' : 'TIDAK')."\n";

// tag 54 (nominal) harus ada dan bernilai 25000.00, tag 63 (CRC) di akhir
$ada54 = str_contains($dinamis, '54'.'07'.'25000.00') || str_contains($dinamis, '540725000.00');
echo 'tag 54 nominal    : '.($ada54 ? 'ADA (25000.00)' : 'TIDAK ADA — payload: '.$dinamis)."\n";
echo 'tag 63 di akhir   : '.(str_ends_with($dinamis, $crcTertulis) ? 'YA' : 'TIDAK')."\n";
echo 'masih tag 01 statis: '.(str_contains($dinamis, '0111') ? 'ya' : 'tidak')."\n\n";

// ---------- 2. Buat top up pending ----------
echo "=== 2. BUAT TOP UP (pending) ===\n";
$r1 = TopupWebAction::run([
    'kartu_barcode' => $kartu->kartu_barcode,
    'nominal' => $nominal,
    'idempotency' => 'UJI-TOPUPWEB-1',
]);
echo 'status action     : '.($r1['status'] ? 'sukses' : 'gagal')."\n";
$trx = $r1['data'];
echo "transaksi_id      : {$trx->transaksi_id}\n";
echo "transaksi_status  : {$trx->transaksi_status}\n";
echo 'saldo setelah buat: '.(int) $kartu->fresh()->kartu_saldo." (harus sama dengan $saldoAwal)\n";

// idempotency: kirim ulang form yang sama tidak boleh bikin transaksi baru
$r1b = TopupWebAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'nominal' => $nominal, 'idempotency' => 'UJI-TOPUPWEB-1']);
echo 'kirim ulang sama  : transaksi_id '.$r1b['data']->transaksi_id.' (harus '.$trx->transaksi_id.")\n\n";

// ---------- 3. Endpoint polling (HTTP GET) ----------
echo "=== 3. POLLING STATUS ===\n";
$urlStatus = "/topup/web/status/{$trx->transaksi_id}";
$resp = $app->handle(Illuminate\Http\Request::create($urlStatus, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']));
echo 'http status       : '.$resp->getStatusCode()."\n";
$json = json_decode($resp->getContent(), true);
echo 'response          : '.json_encode($json)."\n";
echo 'paid              : '.($json['paid'] ? 'true' : 'false')." (harus false)\n\n";

// ---------- 4. Halaman top up menampilkan QR ----------
echo "=== 4. HALAMAN TOP UP ===\n";
config(['website.qris' => $statis]);
$page = $app->handle(Illuminate\Http\Request::create(
    '/topup/web?kartu_barcode='.$kartu->kartu_barcode.'&trx='.$trx->transaksi_id,
    'GET'
));
$html = $page->getContent();
echo 'http status       : '.$page->getStatusCode()."\n";
echo 'ada panel menunggu: '.(str_contains($html, 'id="panelMenunggu"') ? 'ya' : 'tidak')."\n";
echo 'ada panel lunas   : '.(str_contains($html, 'id="panelLunas"') ? 'ya' : 'tidak')."\n";
echo 'QR base64         : '.(str_contains($html, 'data:image/png;base64,') ? 'ya' : 'tidak')."\n";
echo 'nominal tampil    : '.(str_contains($html, '25.000') ? 'ya' : 'tidak')."\n";
echo 'interval 5000ms   : '.(str_contains($html, 'POLL_MS = 5000') ? 'ya' : 'tidak')."\n";
echo 'endpoint polling  : '.(str_contains($html, $urlStatus) ? 'ya' : 'tidak')."\n";
echo 'teks PAID         : '.(str_contains($html, 'PAID') ? 'ya' : 'tidak')."\n";
echo 'panel lunas hidden: '.(preg_match('/id="panelLunas" class="hidden/', $html) ? 'ya (benar, belum bayar)' : 'tidak')."\n\n";

// ---------- 5. Konfirmasi pembayaran ----------
echo "=== 5. KONFIRMASI PEMBAYARAN ===\n";
$r2 = KonfirmasiTopupWebAction::run($trx->transaksi_id, $admin->id);
echo 'status action     : '.($r2['status'] ? 'sukses' : 'gagal')."\n";
echo 'transaksi_status  : '.$r2['data']->transaksi_status."\n";
echo 'saldo setelah bayar: '.(int) $kartu->fresh()->kartu_saldo." (harus ".($saldoAwal + $nominal).")\n";

// idempoten: konfirmasi dua kali tidak menambah saldo dua kali
$r3 = KonfirmasiTopupWebAction::run($trx->transaksi_id, $admin->id);
echo 'konfirmasi ulang  : status '.$r3['data']->transaksi_status.', saldo '.(int) $kartu->fresh()->kartu_saldo." (tidak boleh dobel)\n\n";

// ---------- 6. Polling setelah dibayar ----------
echo "=== 6. POLLING SETELAH DIBAYAR ===\n";
$resp2 = $app->handle(Illuminate\Http\Request::create($urlStatus, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']));
$json2 = json_decode($resp2->getContent(), true);
echo 'response          : '.json_encode($json2)."\n";
echo 'paid              : '.($json2['paid'] ? 'true' : 'false')." (harus true)\n";
echo 'saldo             : '.$json2['saldo']." (harus ".($saldoAwal + $nominal).")\n";

// ---------- 7. Saldo di kartu harus = saldo_akhir transaksi ----------
$trxFresh = Transaksi::find($trx->transaksi_id);
echo "\nkonsistensi saldo : transaksi_saldo_akhir='.$trxFresh->transaksi_saldo_akhir.', kartu='.$kartu->fresh()->kartu_saldo.\n";

// bersihkan transaksi uji
$trxFresh->hasNotif()->delete();
$trxFresh->delete();
$kartu->update(['kartu_saldo' => $saldoAwal]);
echo "\ndata uji dibersihkan, saldo dikembalikan ke $saldoAwal\n";
