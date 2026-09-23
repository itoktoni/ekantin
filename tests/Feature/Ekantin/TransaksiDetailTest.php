<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Actions\Ekantin\TopupWebAction;
use App\Models\Gerai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('halaman detail transaksi menampilkan rincian item, fee, dan pembagian gerai', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@sekolah.id', 'password' => 'secret123', 'role' => 'super_admin']);

    $hasil = ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 2]],
        'idempotency' => 'DETAIL-1',
        'id_kasir' => $admin->id,
    ]);

    expect($hasil['status'])->toBeTrue();
    $trx = $hasil['data'];

    $this->actingAs($admin)
        ->get(route('transaksi.getUpdate', ['id' => $trx->transaksi_id]))
        ->assertOk()
        ->assertSee('Nasi Goreng')
        ->assertSee('Gerai Tes')
        ->assertSee('Fee Sistem')
        ->assertSee('(5%)')
        ->assertSee('Saldo kartu pengguna')
        ->assertSee('Pembagian Dana ke Gerai')
        ->assertSee('Rp1.000')
        ->assertSee('Rp20.000')
        ->assertSee('Rp21.000')
        ->assertSee('Rp19.000')
        ->assertSee('Rp29.000');
});

test('detail top up web yang masih menunggu tidak menampilkan saldo akhir', function () {
    [$kartu] = $this->seedEkantinDasar();
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@sekolah.id', 'password' => 'secret123', 'role' => 'super_admin']);

    $trx = TopupWebAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'nominal' => 20000])['data'];

    $this->actingAs($admin)
        ->get(route('transaksi.getUpdate', ['id' => $trx->transaksi_id]))
        ->assertOk()
        ->assertSee('Menunggu Pembayaran')
        ->assertSee('Tidak ada fee')
        ->assertSee('Belum bertambah — menunggu pembayaran')
        ->assertDontSee('Pembagian Dana ke Gerai');
});

test('tabel transaksi menautkan tiap baris ke halaman detail', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@sekolah.id', 'password' => 'secret123', 'role' => 'super_admin']);

    $trx = ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]],
        'idempotency' => 'DETAIL-3',
    ])['data'];

    $this->actingAs($admin)
        ->get(route('transaksi.getTable'))
        ->assertOk()
        ->assertSee(route('transaksi.getUpdate', ['id' => $trx->transaksi_id]), escape: false);
});

test('detail transaksi hanya bisa dibuka vendor pemilik gerai', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();

    $trx = ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]],
        'idempotency' => 'DETAIL-2',
    ])['data'];

    $vendorPemilik = User::where('email', 'vendor@sekolah.id')->firstOrFail();
    $vendorLain = User::create(['name' => 'Vendor Lain', 'email' => 'lain@sekolah.id', 'password' => 'secret123', 'role' => 'vendor']);
    Gerai::create(['gerai_nama' => 'Gerai Lain', 'gerai_id_vendor' => $vendorLain->id, 'gerai_status' => 'buka']);

    expect($this->actingAs($vendorPemilik)->get(route('transaksi.getUpdate', ['id' => $trx->transaksi_id]))->status())->toBe(200);
    expect($this->actingAs($vendorLain)->get(route('transaksi.getUpdate', ['id' => $trx->transaksi_id]))->status())->toBe(404);
});
