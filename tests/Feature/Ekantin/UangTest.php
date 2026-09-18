<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Models\Gerai;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('beli sukses potong saldo + fee snapshot, idempotency ganda satu struk', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $input = ['kartu_barcode' => $kartu->kartu_barcode, 'items' => [['produk_id' => $produk->produk_id, 'qty' => 2]], 'idempotency' => 'POS-1'];
    $r1 = ProcessPurchaseAction::run($input);
    $r2 = ProcessPurchaseAction::run($input);
    expect($r1['status'])->toBeTrue()
        ->and($kartu->fresh()->kartu_saldo)->toBe(50000 - 20000 - 1000)
        ->and($gerai->fresh()->gerai_saldo)->toBe(20000 - 1000)
        ->and($r1['data']->transaksi_fee_total)->toBe(1000)
        ->and($r1['data']->transaksi_fee_rincian)->toBe(['sistem' => 1000])
        ->and($r2['data']->transaksi_id)->toBe($r1['data']->transaksi_id);
});

test('saldo kurang, kartu nonaktif, dan limit harian ditolak dengan pesan', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 5000, harga: 10000);
    expect(ProcessPurchaseAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]]])['status'])->toBeFalse();
    $kartu->update(['kartu_saldo' => 999999, 'kartu_status' => 'nonaktif']);
    expect(ProcessPurchaseAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]]])['data'])->toContain('nonaktif');
    $kartu->update(['kartu_status' => 'aktif', 'kartu_limit_harian' => 15000]);
    $r = ProcessPurchaseAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'items' => [['produk_id' => $produk->produk_id, 'qty' => 2]]]);
    expect($r['status'])->toBeFalse()->and($r['data'])->toContain('Limit harian');
});

test('keranjang campur dua gerai bayar sekali dan saldo terbagi', function () {
    [$kartu, $geraiA, $produkA] = $this->seedEkantinDasar(saldo: 100000, harga: 10000);
    $vendor = User::create(['name' => 'V2', 'email' => 'v2@x.id', 'password' => 'secret123', 'role' => 'vendor']);
    $geraiB = Gerai::create(['gerai_nama' => 'Gerai B', 'gerai_id_vendor' => $vendor->id, 'gerai_status' => 'buka']);
    $produkB = Produk::create(['produk_id_gerai' => $geraiB->gerai_id, 'produk_nama' => 'Es Teh', 'produk_harga' => 5000, 'produk_status' => 'tersedia']);
    $r = ProcessPurchaseAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'items' => [
        ['produk_id' => $produkA->produk_id, 'qty' => 1],
        ['produk_id' => $produkB->produk_id, 'qty' => 2],
    ], 'idempotency' => 'POS-MIX-1']);
    // total 20000, fee 5% = 1000 proporsional -> tiap gerai 10000-500=9500; siswa 79000
    expect($r['status'])->toBeTrue()
        ->and($kartu->fresh()->kartu_saldo)->toBe(100000 - 20000 - 1000)
        ->and($geraiA->fresh()->gerai_saldo)->toBe(9500)
        ->and($geraiB->fresh()->gerai_saldo)->toBe(9500)
        ->and($r['data']->hasItems()->whereNotNull('item_id_gerai')->count())->toBe(2);
});
