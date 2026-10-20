<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_fee', function (Blueprint $table) {
            $table->string('tfee_tipe', 10)->default('persen')->after('tfee_code');
        });

        // Isi tipe dari master untuk baris yang ter-link; baris warisan era persen → persen.
        DB::table('transaksi_fee')->orderBy('tfee_id')->chunk(200, function ($rows) {
            $tipes = DB::table('fee')->pluck('fee_tipe', 'fee_id');
            foreach ($rows as $row) {
                $tipe = ($row->tfee_id_fee && isset($tipes[$row->tfee_id_fee])) ? $tipes[$row->tfee_id_fee] : 'persen';
                DB::table('transaksi_fee')->where('tfee_id', $row->tfee_id)->update(['tfee_tipe' => $tipe]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_fee', function (Blueprint $table) {
            $table->dropColumn('tfee_tipe');
        });
    }
};
