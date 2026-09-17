<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('ekspor csv dan pdf memuat data filter aktif', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    ProcessPurchaseAction::run(['kartu_barcode' => $kartu->kartu_barcode, 'gerai_id' => $gerai->gerai_id, 'items' => [['produk_id' => $produk->produk_id, 'qty' => 1]]]);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $this->actingAs($admin);
    $this->get(route('laporan.ekspor', ['format' => 'csv']))->assertOk();
    $this->get(route('laporan.ekspor', ['format' => 'pdf']))->assertOk();
});
