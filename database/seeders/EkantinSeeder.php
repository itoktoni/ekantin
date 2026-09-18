<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeConfig;
use Illuminate\Database\Seeder;

class EkantinSeeder extends Seeder
{
    public function run(): void
    {
        FeeConfig::firstOrCreate(
            ['fee_aktif' => true],
            ['fee_min_topup' => 10000]
        );

        // Fee penjualan default (persen, dipotong dari harga produk terjual).
        $default = [
            ['code_fee' => 'kebersihan', 'nama_fee' => 'Kebersihan', 'value_fee' => 1],
            ['code_fee' => 'keamanan', 'nama_fee' => 'Keamanan', 'value_fee' => 1],
            ['code_fee' => 'pengelolaan', 'nama_fee' => 'Pengelolaan', 'value_fee' => 1],
            ['code_fee' => 'sistem', 'nama_fee' => 'Sistem', 'value_fee' => 2],
        ];
        foreach ($default as $row) {
            Fee::firstOrCreate(['code_fee' => $row['code_fee']], $row);
        }
    }
}
