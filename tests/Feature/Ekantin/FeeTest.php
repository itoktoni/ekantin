<?php

use App\Models\Fee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test("rincian fee 5% + 1% + 2% dari subtotal benar", function () {
    $this->seedEkantinDasar(); // seed: sistem 5%
    Fee::create(['code_fee' => 'kebersihan', 'nama_fee' => 'Kebersihan', 'value_fee' => 1]);
    Fee::create(['code_fee' => 'layanan', 'nama_fee' => 'Layanan', 'value_fee' => 2]);
    expect(Fee::totalPersen())->toBe(8.0)
        ->and(Fee::rincian(20000))->toBe(['sistem' => 1000, 'kebersihan' => 200, 'layanan' => 400]);
});

test("validasi fee menolak persen di atas 100 dan code duplikat", function () {
    $admin = User::create(['name' => 'A', 'email' => 'a@sekolah.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin)->post(route('fee.postCreate'), ['code_fee' => 'x', 'nama_fee' => 'X', 'value_fee' => 101])
        ->assertSessionHasErrors('value_fee');
    Fee::create(['code_fee' => 'kebersihan', 'nama_fee' => 'K', 'value_fee' => 1]);
    $this->actingAs($admin)->post(route('fee.postCreate'), ['code_fee' => 'kebersihan', 'nama_fee' => 'K2', 'value_fee' => 1])
        ->assertSessionHasErrors('code_fee');
});

test("vendor tidak bisa buka modul fee", function () {
    $this->seedEkantinDasar();
    $vendor = User::where('role', 'vendor')->first();
    $vendor->markEmailAsVerified();
    $this->actingAs($vendor)->get(route('fee.getTable'))->assertForbidden();
});

test("fallback transaksi lama tanpa rincian", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $trx = \App\Models\Transaksi::create([
        'transaksi_jenis' => 'beli', 'transaksi_status' => 'berhasil', 'transaksi_metode' => 'kartu',
        'transaksi_id_kartu' => $kartu->kartu_id, 'transaksi_total' => 10000,
        'transaksi_fee_kebersihan' => 300, 'transaksi_fee_sistem' => 200,
    ]);
    expect($trx->fresh()->feeTotal())->toBe(500)
        ->and($trx->fresh()->feeRincian())->toBe(['kebersihan' => 300, 'sistem' => 200]);
});
