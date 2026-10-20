<?php

namespace Database\Seeders;

use App\Models\Gerai;
use App\Models\Label;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class EkantinProdukSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan master label ada (fresh seed maupun DB lama).
        foreach (['manis', 'seafood', 'alergen', 'pedas', 'gorengan', 'berkafein'] as $nama) {
            Label::firstOrCreate(['label_nama' => $nama]);
        }
        $produk = [
            // [nama gerai, nama produk, harga, kategori, label kesehatan (master label)]
            ['Kantin Sehat', 'Nasi Goreng', 10000, 'makanan', 'gorengan'],
            ['Kantin Sehat', 'Ayam Geprek', 12000, 'makanan', 'pedas'],
            ['Kantin Sehat', 'Es Teh Manis', 5000, 'minuman', 'manis'],
            ['Kantin Sehat', 'Air Mineral', 3000, 'minuman', 'berkafein'],
            ['Snack Ceria', 'Roti Bakar Coklat', 8000, 'snack', 'manis'],
            ['Snack Ceria', 'Pisang Goreng', 6000, 'snack', 'gorengan'],
            ['Snack Ceria', 'Susu Jahe', 7000, 'minuman', 'berkafein'],
            ['Minuman Segar', 'Jus Alpukat', 12000, 'minuman', 'manis'],
            ['Minuman Segar', 'Es Jeruk', 6000, 'minuman', 'manis'],
            ['Minuman Segar', 'Kopi Susu', 10000, 'minuman', 'berkafein'],
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
