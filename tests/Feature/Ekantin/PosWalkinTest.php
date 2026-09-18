<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test("walk-in tunai sukses tanpa kartu, fee tetap, saldo kartu utuh", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $r = ProcessPurchaseAction::run(["metode" => "tunai", "items" => [["produk_id" => $produk->produk_id, "qty" => 2]], "idempotency" => "POS-W-1"]);
    expect($r["status"])->toBeTrue()
        ->and($r["data"]->transaksi_metode)->toBe("tunai")
        ->and($r["data"]->transaksi_id_kartu)->toBeNull()
        ->and($r["data"]->transaksi_fee_total)->toBe(1000)
        ->and($r["data"]->transaksi_fee_rincian)->toBe(["sistem" => 1000])
        ->and($gerai->fresh()->gerai_saldo)->toBe(20000 - 1000)
        ->and($kartu->fresh()->kartu_saldo)->toBe(50000);
});

test("walk-in qris sukses", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(harga: 10000);
    $r = ProcessPurchaseAction::run(["metode" => "qris", "items" => [["produk_id" => $produk->produk_id, "qty" => 1]], "idempotency" => "POS-W-2"]);
    expect($r["status"])->toBeTrue()->and($r["data"]->transaksi_metode)->toBe("qris");
});

test("walk-in ditolak jika gerai tutup", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $gerai->update(["gerai_status" => "tutup"]);
    $r = ProcessPurchaseAction::run(["metode" => "tunai", "items" => [["produk_id" => $produk->produk_id, "qty" => 1]]]);
    expect($r["status"])->toBeFalse();
});

test("alur kartu tidak berubah (default metode kartu)", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $r = ProcessPurchaseAction::run(["kartu_barcode" => $kartu->kartu_barcode, "items" => [["produk_id" => $produk->produk_id, "qty" => 1]]]);
    expect($r["status"])->toBeTrue()
        ->and($r["data"]->transaksi_metode)->toBe("kartu")
        ->and($kartu->fresh()->kartu_saldo)->toBe(50000 - 10000 - 500);
});

test("search kartu: kasir 200 dengan shape benar, vendor 403", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $kasir = User::create(["name" => "Kasir", "email" => "kasir@sekolah.id", "password" => "secret123", "role" => "kasir_sekolah"]);
    $kasir->markEmailAsVerified();
    $res = $this->actingAs($kasir)->getJson(route("kartu.getSearch", ["q" => "SW-TEST"]));
    $res->assertOk()->assertJsonFragment(["barcode" => "SW-TEST-001", "nama" => "Siswa Tes"]);
    $vendor = User::where("role", "vendor")->first();
    $vendor->markEmailAsVerified();
    $this->actingAs($vendor)->getJson(route("kartu.getSearch", ["q" => "SW-TEST"]))->assertForbidden();
});

test("postPos tanpa barcode + metode kartu ditolak validasi", function () {
    $this->seedEkantinDasar();
    $kasir = User::create(["name" => "Kasir2", "email" => "kasir2@sekolah.id", "password" => "secret123", "role" => "kasir_sekolah"]);
    $kasir->markEmailAsVerified();
    $this->actingAs($kasir)->post(route("kasir.postPos"), ["metode" => "kartu", "items" => [["produk_id" => 1, "qty" => 1]]])
        ->assertSessionHasErrors("kartu_barcode");
});
