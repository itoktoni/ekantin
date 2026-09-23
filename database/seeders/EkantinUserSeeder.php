<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EkantinUserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            // [nama, email, peran]
            ['Admin Kantin', 'admin@sekolah.id', 'super_admin'],
            ['Kasir Sekolah', 'kasir@sekolah.id', 'kasir_sekolah'],
            ['Andi Vendor', 'andi@sekolah.id', 'vendor'],
            ['Rudi Vendor', 'rudi@sekolah.id', 'vendor'],
            ['Budi Vendor', 'budi.vendor@sekolah.id', 'vendor'],
            ['Siti Ortu', 'siti@sekolah.id', 'orang_tua'],
            ['Dewi Ortu', 'dewi@sekolah.id', 'orang_tua'],
            ['Ayu Pengguna', 'ayu@sekolah.id', 'pengguna'],
            ['Bima Pengguna', 'bima@sekolah.id', 'pengguna'],
            ['Citra Pengguna', 'citra@sekolah.id', 'pengguna'],
            ['Dimas Pengguna', 'dimas@sekolah.id', 'pengguna'],
            ['Eka Pengguna', 'eka@sekolah.id', 'pengguna'],
        ];
        foreach ($akun as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('sekolah123'),
                    'role' => $role,
                    'verified_at' => now(),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
