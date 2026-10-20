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
            // [nama gerai, email vendor, bank, no rekening, atas nama] — contoh untuk laporan transfer.
            ['Kantin Sehat', 'andi@sekolah.id', 'BCA', '8210456789', 'Andi Pratama'],
            ['Snack Ceria', 'rudi@sekolah.id', 'BRI', '00210123456789', 'Rudi Hartono'],
            ['Minuman Segar', 'budi.vendor@sekolah.id', 'Mandiri', '8900123456789', 'Budi Santoso'],
        ];
        foreach ($gerai as [$nama, $email, $bank, $norek, $an]) {
            $vendor = User::where('email', $email)->firstOrFail();
            $g = Gerai::firstOrCreate(['gerai_nama' => $nama], ['gerai_id_vendor' => $vendor->id, 'gerai_status' => 'buka']);
            // Contoh saja: jangan timpa data bank yang sudah diisi manual.
            if (empty($g->gerai_bank) && empty($g->gerai_no_rekening)) {
                $g->update(['gerai_bank' => $bank, 'gerai_no_rekening' => $norek, 'gerai_nama_rekening' => $an]);
            }
        }
    }
}
