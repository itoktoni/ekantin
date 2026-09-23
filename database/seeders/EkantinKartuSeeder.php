<?php

namespace Database\Seeders;

use App\Models\Kartu;
use App\Models\User;
use Illuminate\Database\Seeder;

class EkantinKartuSeeder extends Seeder
{
    public function run(): void
    {
        $kartu = [
            // [barcode, email pengguna, email ortu, nis, kelas, saldo, limit]
            ['SW-1001', 'ayu@sekolah.id', 'siti@sekolah.id', 'NIS1001', '7A', 100000, 30000],
            ['SW-1002', 'bima@sekolah.id', 'siti@sekolah.id', 'NIS1002', '7A', 75000, 30000],
            ['SW-1003', 'citra@sekolah.id', 'dewi@sekolah.id', 'NIS1003', '8B', 50000, 25000],
            ['SW-1004', 'dimas@sekolah.id', 'dewi@sekolah.id', 'NIS1004', '8B', 50000, null],
            ['SW-1005', 'eka@sekolah.id', null, 'NIS1005', '9C', 100000, 50000],
        ];
        foreach ($kartu as [$barcode, $emailPengguna, $emailOrtu, $nis, $kelas, $saldo, $limit]) {
            $pengguna = User::where('email', $emailPengguna)->firstOrFail();
            $ortu = $emailOrtu ? User::where('email', $emailOrtu)->firstOrFail() : null;
            Kartu::firstOrCreate(['kartu_barcode' => $barcode], [
                'kartu_id_user' => $pengguna->id,
                'kartu_id_orangtua' => $ortu?->id,
                'kartu_nis' => $nis,
                'kartu_kelas' => $kelas,
                'kartu_saldo' => $saldo,
                'kartu_status' => 'aktif',
                'kartu_limit_harian' => $limit,
            ]);
        }
    }
}
