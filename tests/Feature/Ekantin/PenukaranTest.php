<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Models\Penarikan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('vendor ajukan penagihan harian atas transaksi hari itu, kasir tukar uang', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 2]],
        'idempotency' => 'TUKAR-1',
    ]);
    // gerai menerima 20000 - fee 1000 = 19000
    expect($gerai->fresh()->gerai_saldo)->toBe(19000);

    $vendor = User::where('role', 'vendor')->firstOrFail();
    $vendor->markEmailAsVerified();
    $tanggal = today()->toDateString();

    $this->actingAs($vendor)->get(route('penarikan.getAjukan'))->assertOk();

    $this->actingAs($vendor)->post(route('penarikan.postAjukan'), [
        'tanggal' => $tanggal,
        'gerai_id' => $gerai->gerai_id,
    ])->assertRedirect();

    $p = Penarikan::firstOrFail();
    expect($p->penarikan_status)->toBe('diajukan')
        ->and($p->penarikan_nominal)->toBe(19000)
        ->and($p->penarikan_tanggal?->toDateString())->toBe($tanggal)
        ->and($gerai->fresh()->gerai_saldo)->toBe(0); // saldo ditahan, menunggu penukaran

    // duplikat per gerai+tanggal ditolak — tetap 1 record
    $this->actingAs($vendor)->post(route('penarikan.postAjukan'), [
        'tanggal' => $tanggal,
        'gerai_id' => $gerai->gerai_id,
    ]);
    expect(Penarikan::count())->toBe(1);

    // kasir melihat daftar lalu menukar uang fisik
    $kasir = User::create(['name' => 'Kasir Tukar', 'email' => 'kasir.tukar@sekolah.id', 'password' => 'secret123', 'role' => 'kasir_sekolah', 'verified_at' => now()]);
    $kasir->markEmailAsVerified();
    $this->actingAs($kasir)->get(route('penarikan.getTable'))->assertOk();
    $this->actingAs($kasir)->post(route('penarikan.postTukar', ['id' => $p->penarikan_id]))->assertRedirect();

    $p = $p->fresh();
    expect($p->penarikan_status)->toBe('diselesaikan')
        ->and($p->penarikan_id_kasir)->toBe($kasir->id);
});

test('hak akses penukaran: vendor tidak boleh tukar, kasir tidak boleh ajukan', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $vendor = User::where('role', 'vendor')->firstOrFail();
    $vendor->markEmailAsVerified();

    $p = Penarikan::create([
        'penarikan_id_gerai' => $gerai->gerai_id,
        'penarikan_nominal' => 1000,
        'penarikan_tanggal' => today()->toDateString(),
        'penarikan_status' => 'diajukan',
    ]);
    $this->actingAs($vendor)->post(route('penarikan.postTukar', ['id' => $p->penarikan_id]))->assertForbidden();

    $kasir = User::create(['name' => 'Kasir Akses', 'email' => 'kasir.aks@sekolah.id', 'password' => 'secret123', 'role' => 'kasir_sekolah', 'verified_at' => now()]);
    $kasir->markEmailAsVerified();
    $this->actingAs($kasir)->post(route('penarikan.postAjukan'), [
        'tanggal' => today()->toDateString(),
        'gerai_id' => $gerai->gerai_id,
    ])->assertForbidden();
    $this->actingAs($kasir)->get(route('penarikan.getAjukan'))->assertForbidden();
});

test('vendor membatalkan pengajuan mengembalikan saldo gerai', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 2]],
        'idempotency' => 'TUKAR-BATAL-1',
    ]);
    $vendor = User::where('role', 'vendor')->firstOrFail();
    $vendor->markEmailAsVerified();
    $this->actingAs($vendor)->post(route('penarikan.postAjukan'), [
        'tanggal' => today()->toDateString(),
        'gerai_id' => $gerai->gerai_id,
    ]);
    expect($gerai->fresh()->gerai_saldo)->toBe(0);

    $p = Penarikan::firstOrFail();
    $trxSebelum = Transaksi::count();
    $this->actingAs($vendor)->post(route('penarikan.postBatal', ['id' => $p->penarikan_id]))->assertRedirect();

    expect($p->fresh()->penarikan_status)->toBe('dibatalkan')
        ->and($gerai->fresh()->gerai_saldo)->toBe(19000) // saldo kembali
        ->and(Transaksi::count())->toBe($trxSebelum + 1); // koreksi pengembalian tercatat

    // setelah dibatalkan, gerai boleh ajukan ulang untuk tanggal yang sama
    $this->actingAs($vendor)->post(route('penarikan.postAjukan'), [
        'tanggal' => today()->toDateString(),
        'gerai_id' => $gerai->gerai_id,
    ]);
    expect(Penarikan::where('penarikan_status', 'diajukan')->count())->toBe(1);
});
