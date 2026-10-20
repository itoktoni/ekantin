<?php

use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test("admin bisa batalkan transaksi beli dan saldo kembali", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $beli = \App\Actions\Ekantin\ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode, 'gerai_id' => $gerai->gerai_id,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]],
    ]);
    expect($beli['status'])->toBeTrue();
    $trx = Transaksi::where('transaksi_jenis', 'beli')->latest('transaksi_id')->firstOrFail();
    $saldoHabis = $kartu->fresh()->kartu_saldo;

    $this->actingAs($admin)
        ->post(route('transaksi.postRefund', ['id' => $trx->transaksi_id]), ['transaksi_alasan' => 'salah input'])
        ->assertRedirect();
    $refund = Transaksi::where('transaksi_id_reversal_of', $trx->transaksi_id)->firstOrFail();
    expect($refund->transaksi_jenis)->toBe('refund')
        ->and($kartu->fresh()->kartu_saldo)->toBe($saldoHabis + (int) $trx->transaksi_total + $trx->feeTotal())
        ->and($refund->transaksi_alasan)->toBe('salah input');

    // Refund kedua ditolak (flash error, tidak redirect sukses bikin baris baru).
    $this->actingAs($admin)
        ->post(route('transaksi.postRefund', ['id' => $trx->transaksi_id]), ['transaksi_alasan' => 'coba lagi']);
    expect(Transaksi::where('transaksi_id_reversal_of', $trx->transaksi_id)->count())->toBe(1);
});

test("transaksi otomatis dapat kode struk unik tanpa cek DB", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 100000, harga: 10000);
    $jalan = fn ($i) => \App\Actions\Ekantin\ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]],
        'idempotency' => 'POS-KODE-'.$i,
    ]);
    $a = $jalan(1);
    $b = $jalan(2);
    expect($a['status'])->toBeTrue()->and($b['status'])->toBeTrue();
    $ka = $a['data']->transaksi_kode;
    $kb = $b['data']->transaksi_kode;
    expect($ka)->toMatch('/^TRX-\d{8}-[A-Z]{6}$/')
        ->and($kb)->not->toBe($ka);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    $this->get(route('transaksi.getTable'))->assertOk()->assertSee($ka);
    $this->get(route('kasir.struk', ['id' => $a['data']->transaksi_id]))->assertOk()->assertSee($ka);
});

test("refund wajib alasan dan vendor ditolak", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $beli = \App\Actions\Ekantin\ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode, 'gerai_id' => $gerai->gerai_id,
        'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]],
    ]);
    $trx = Transaksi::where('transaksi_jenis', 'beli')->latest('transaksi_id')->firstOrFail();
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin)
        ->post(route('transaksi.postRefund', ['id' => $trx->transaksi_id]), ['transaksi_alasan' => ''])
        ->assertSessionHasErrors('transaksi_alasan');

    $vendor = User::where('role', 'vendor')->first();
    $vendor->markEmailAsVerified();
    $this->actingAs($vendor)->post(route('transaksi.postRefund', ['id' => $trx->transaksi_id]), ['transaksi_alasan' => 'x'])->assertForbidden();
    $this->actingAs($vendor)->get(route('transaksi.getTable'))->assertOk()->assertDontSee('transaksi.postRefund');
});
