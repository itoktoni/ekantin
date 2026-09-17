<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(EkantinSeeder::class);
        $this->call(EkantinFeeSeeder::class);
        $this->call(EkantinUserSeeder::class);
        $this->call(EkantinGeraiSeeder::class);
        $this->call(EkantinProdukSeeder::class);
        $this->call(EkantinKartuSeeder::class);
        $this->call(EkantinTransaksiSeeder::class);
    }
}
