<?php

namespace Database\Seeders;

use App\Models\Gerai;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class EkantinProdukSeeder extends Seeder
{
    public function run(): void
    {
        $produk = [
            // [nama gerai, nama produk, harga, kategori]
            ['Kantin Sehat', 'Nasi Goreng', 10000, 'makanan'],
            ['Kantin Sehat', 'Ayam Geprek', 12000, 'makanan'],
            ['Kantin Sehat', 'Es Teh Manis', 5000, 'minuman'],
            ['Kantin Sehat', 'Air Mineral', 3000, 'minuman'],
            ['Snack Ceria', 'Roti Bakar Coklat', 8000, 'snack'],
            ['Snack Ceria', 'Pisang Goreng', 6000, 'snack'],
            ['Snack Ceria', 'Susu Jahe', 7000, 'minuman'],
            ['Minuman Segar', 'Jus Alpukat', 12000, 'minuman'],
            ['Minuman Segar', 'Es Jeruk', 6000, 'minuman'],
            ['Minuman Segar', 'Kopi Susu', 10000, 'minuman'],
        ];
        foreach ($produk as [$geraiNama, $nama, $harga, $kategori]) {
            $gerai = Gerai::where('gerai_nama', $geraiNama)->firstOrFail();
            Produk::updateOrCreate(
                ['produk_id_gerai' => $gerai->gerai_id, 'produk_nama' => $nama],
                ['produk_harga' => $harga, 'produk_status' => 'tersedia', 'produk_kategori' => $kategori]
            );
        }
    }
}
