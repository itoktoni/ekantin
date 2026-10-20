<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('transaksi_kode', 20)->nullable()->after('transaksi_id');
            $table->unique(['transaksi_kode']);
        });

        // Isi baris lama: TRX-YYYYMMDD-XXXXXX (acak per baris, tanpa cek DB).
        DB::table('transaksi')->orderBy('transaksi_id')->chunk(200, function ($rows) {
            $pakai = [];
            foreach ($rows as $row) {
                $tgl = str_replace('-', '', substr((string) $row->created_at, 0, 10));
                do {
                    $kode = 'TRX-'.$tgl.'-'.strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 6));
                } while (isset($pakai[$kode]));
                $pakai[$kode] = true;
                DB::table('transaksi')->where('transaksi_id', $row->transaksi_id)->update(['transaksi_kode' => $kode]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropUnique(['transaksi_kode']);
            $table->dropColumn('transaksi_kode');
        });
    }
};
