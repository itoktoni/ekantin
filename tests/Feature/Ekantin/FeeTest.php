<?php

use App\Models\Fee;
use App\Models\Gerai;
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

test("tabel fee tampilkan tipe persen atau nominal", function () {
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    Fee::create(['code_fee' => 'sistem', 'nama_fee' => 'Sistem', 'fee_tipe' => 'persen', 'value_fee' => 2]);
    Fee::create(['code_fee' => 'parkir', 'nama_fee' => 'Parkir', 'fee_tipe' => 'nominal', 'value_fee' => 500]);
    $this->actingAs($admin)->get(route('fee.getTable'))->assertOk()
        ->assertSee('Persen')->assertSee('Nominal')->assertSee('Rp500');
});

test("fee nominal flat per transaksi dan snapshot persen nol", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    Fee::create(['code_fee' => 'parkir', 'nama_fee' => 'Parkir', 'fee_tipe' => 'nominal', 'value_fee' => 500]);
    expect(Fee::rincian(10000))->toBe(['sistem' => 500, 'parkir' => 500]);
    $r = \App\Actions\Ekantin\ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]],
    ]);
    expect($r['status'])->toBeTrue();
    $rows = $r['data']->hasFees()->orderBy('tfee_code')->get();
    expect($rows->pluck('tfee_nominal', 'tfee_code')->all())->toBe(['parkir' => 500, 'sistem' => 500])
        ->and($rows->where('tfee_code', 'parkir')->first()->tfee_persen)->toBe(0.0)
        ->and($rows->where('tfee_code', 'parkir')->first()->tfee_tipe)->toBe('nominal')
        ->and($rows->where('tfee_code', 'sistem')->first()->tfee_tipe)->toBe('persen')
        ->and($kartu->fresh()->kartu_saldo)->toBe(50000 - 10000 - 1000);
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

test("gerai bisa simpan data rekening bank", function () {
    $vendor = User::create(['name' => 'V', 'email' => 'v@x.id', 'password' => 'secret123', 'role' => 'vendor']);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $gerai = Gerai::create(['gerai_nama' => 'Gerai Tes', 'gerai_id_vendor' => $vendor->id, 'gerai_status' => 'buka']);
    $this->actingAs($admin)->post(route('gerai.postUpdate', ['id' => $gerai->gerai_id]), [
        'gerai_nama' => 'Gerai Tes',
        'gerai_id_vendor' => $vendor->id,
        'gerai_status' => 'buka',
        'gerai_bank' => 'BCA',
        'gerai_no_rekening' => '1234567890',
        'gerai_nama_rekening' => 'Budi Santoso',
    ])->assertRedirect();
    $fresh = $gerai->fresh();
    expect($fresh->gerai_bank)->toBe('BCA')
        ->and($fresh->gerai_no_rekening)->toBe('1234567890')
        ->and($fresh->gerai_nama_rekening)->toBe('Budi Santoso');
    $this->actingAs($admin)->get(route('gerai.getUpdate', ['id' => $gerai->gerai_id]))->assertOk()->assertSee('1234567890');
});

test("admin bisa simpan edit fee dan tercatat di audit", function () {
    $admin = User::create(['name' => 'A', 'email' => 'a@sekolah.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $fee = Fee::create(['code_fee' => 'sistem', 'nama_fee' => 'Sistem', 'value_fee' => 5]);
    $this->actingAs($admin)
        ->post(route('fee.postUpdate', ['id' => $fee->fee_id]), ['code_fee' => 'sistem', 'nama_fee' => 'Sistem Baru', 'fee_tipe' => 'persen', 'value_fee' => 6])
        ->assertRedirect();
    expect(Fee::find($fee->fee_id)->nama_fee)->toBe('Sistem Baru')
        ->and(\App\Models\AuditLog::where('audit_aksi', 'update_fee')->exists())->toBeTrue();
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
