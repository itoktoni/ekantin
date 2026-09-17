<?php

namespace Tests\Feature\Ekantin;

use App\Models\FeeConfig;
use App\Models\Gerai;
use App\Models\Kartu;
use App\Models\Produk;
use App\Models\User;

trait SeedEkantin
{
    public function seedEkantinDasar(int $saldo = 50000, int $harga = 10000): array
    {
        $siswa = User::create(['name' => 'Siswa Tes', 'email' => 'siswa@sekolah.id', 'password' => 'secret123', 'role' => 'siswa']);
        $vendor = User::create(['name' => 'Vendor Tes', 'email' => 'vendor@sekolah.id', 'password' => 'secret123', 'role' => 'vendor']);
        $kartu = Kartu::create(['kartu_barcode' => 'SW-TEST-001', 'kartu_id_user' => $siswa->id, 'kartu_nis' => 'NIS001', 'kartu_kelas' => '7A', 'kartu_saldo' => $saldo, 'kartu_status' => 'aktif']);
        $gerai = Gerai::create(['gerai_nama' => 'Gerai Tes', 'gerai_id_vendor' => $vendor->id, 'gerai_status' => 'buka']);
        $produk = Produk::create(['produk_id_gerai' => $gerai->gerai_id, 'produk_nama' => 'Nasi Goreng', 'produk_harga' => $harga, 'produk_status' => 'tersedia']);
        if (class_exists(FeeConfig::class)) {
            FeeConfig::firstOrCreate(['fee_aktif' => true], ['fee_sistem' => 500, 'fee_kebersihan' => 0, 'fee_keamanan' => 0, 'fee_pengelolaan' => 0, 'fee_min_topup' => 10000]);
        }

        return [$kartu, $gerai, $produk];
    }
}
