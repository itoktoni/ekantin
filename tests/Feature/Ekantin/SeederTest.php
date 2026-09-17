<?php

use App\Models\FeeConfig;
use App\Models\Gerai;
use App\Models\Kartu;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\EkantinGeraiSeeder;
use Database\Seeders\EkantinKartuSeeder;
use Database\Seeders\EkantinProdukSeeder;
use Database\Seeders\EkantinSeeder;
use Database\Seeders\EkantinUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeder master data terisi lengkap dan idempoten', function () {
    $this->seed([EkantinSeeder::class, EkantinUserSeeder::class, EkantinGeraiSeeder::class, EkantinProdukSeeder::class, EkantinKartuSeeder::class]);
    $this->seed([EkantinSeeder::class, EkantinUserSeeder::class, EkantinGeraiSeeder::class, EkantinProdukSeeder::class, EkantinKartuSeeder::class]);
    expect(User::whereIn('role', ['vendor', 'orang_tua', 'siswa'])->count())->toBe(9)
        ->and(Gerai::count())->toBe(3)
        ->and(Produk::where('produk_status', 'tersedia')->count())->toBe(10)
        ->and(Kartu::where('kartu_status', 'aktif')->count())->toBe(5)
        ->and(FeeConfig::aktif()->fee_sistem)->toBe(500);
});
