<?php

use App\Models\Gerai;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('vendor hanya melihat produk gerainya (scoping)', function () {
    $v1 = User::create(['name' => 'V1', 'email' => 'v1@x.id', 'password' => 'secret123', 'role' => 'vendor']);
    $v2 = User::create(['name' => 'V2', 'email' => 'v2@x.id', 'password' => 'secret123', 'role' => 'vendor']);
    $g1 = Gerai::create(['gerai_nama' => 'Kantin A', 'gerai_id_vendor' => $v1->id, 'gerai_status' => 'buka']);
    $g2 = Gerai::create(['gerai_nama' => 'Kantin B', 'gerai_id_vendor' => $v2->id, 'gerai_status' => 'buka']);
    Produk::create(['produk_id_gerai' => $g1->gerai_id, 'produk_nama' => 'Nasi', 'produk_harga' => 10000, 'produk_status' => 'tersedia']);
    $own = Produk::whereIn('produk_id_gerai', Gerai::where('gerai_id_vendor', $v1->id)->pluck('gerai_id'))->get();
    expect($own)->toHaveCount(1)->and($g1->hasVendor->name)->toBe('V1');
});
