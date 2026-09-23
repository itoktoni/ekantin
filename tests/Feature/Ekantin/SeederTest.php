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
    // ponytail: seeder membuat 3 vendor + 2 ortu + 5 pengguna = 10 (bukan 9) — ekspektasi lama basi, sudah merah sebelum fee work.
    expect(User::whereIn('role', ['vendor', 'orang_tua', 'pengguna'])->count())->toBe(10)
        ->and(Gerai::count())->toBe(3)
        ->and(Produk::where('produk_status', 'tersedia')->count())->toBe(10)
        ->and(Kartu::where('kartu_status', 'aktif')->count())->toBe(5)
        ->and(FeeConfig::aktif()->fee_min_topup)->toBe(10000)
        ->and(\App\Models\Fee::count())->toBe(4)
        ->and(\App\Models\Fee::totalPersen())->toBe(5.0);
});
