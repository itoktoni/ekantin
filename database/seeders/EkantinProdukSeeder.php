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
            // [nama gerai, nama produk, harga, kategori, label kesehatan]
            // Aturan: segar tanpa pengawet = sehat; manis kemasan/berpengawet/gorengan = tidak_sehat.
            ['Kantin Sehat', 'Nasi Goreng', 10000, 'makanan', 'sehat'],
            ['Kantin Sehat', 'Ayam Geprek', 12000, 'makanan', 'sehat'],
            ['Kantin Sehat', 'Es Teh Manis', 5000, 'minuman', 'tidak_sehat'],
            ['Kantin Sehat', 'Air Mineral', 3000, 'minuman', 'sehat'],
            ['Snack Ceria', 'Roti Bakar Coklat', 8000, 'snack', 'tidak_sehat'],
            ['Snack Ceria', 'Pisang Goreng', 6000, 'snack', 'tidak_sehat'],
            ['Snack Ceria', 'Susu Jahe', 7000, 'minuman', 'sehat'],
            ['Minuman Segar', 'Jus Alpukat', 12000, 'minuman', 'sehat'],
            ['Minuman Segar', 'Es Jeruk', 6000, 'minuman', 'tidak_sehat'],
            ['Minuman Segar', 'Kopi Susu', 10000, 'minuman', 'tidak_sehat'],
        ];
        foreach ($produk as [$geraiNama, $nama, $harga, $kategori, $label]) {
            $gerai = Gerai::where('gerai_nama', $geraiNama)->firstOrFail();
            Produk::updateOrCreate(
                ['produk_id_gerai' => $gerai->gerai_id, 'produk_nama' => $nama],
                ['produk_harga' => $harga, 'produk_status' => 'tersedia', 'produk_kategori' => $kategori, 'produk_label' => $label]
            );
        }
    }
}
