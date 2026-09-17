<?php

namespace Database\Seeders;

use App\Models\Gerai;
use App\Models\User;
use Illuminate\Database\Seeder;

class EkantinGeraiSeeder extends Seeder
{
    public function run(): void
    {
        $gerai = [
            // [nama gerai, email vendor] — 1 gerai per vendor (Andi/Rudi/Budi)
            ['Kantin Sehat', 'andi@sekolah.id'],
            ['Snack Ceria', 'rudi@sekolah.id'],
            ['Minuman Segar', 'budi.vendor@sekolah.id'],
        ];
        foreach ($gerai as [$nama, $email]) {
            $vendor = User::where('email', $email)->firstOrFail();
            Gerai::firstOrCreate(['gerai_nama' => $nama], ['gerai_id_vendor' => $vendor->id, 'gerai_status' => 'buka']);
        }
    }
}
