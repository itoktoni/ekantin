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
            $table->dropColumn('transaksi_fee_rincian');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->json('transaksi_fee_rincian')->nullable()->after('transaksi_fee_total');
        });

        // Kembalikan JSON dari tabel relasi agar rollback tidak menghilangkan data.
        DB::table('transaksi_fee')->orderBy('tfee_id')->chunk(200, function ($rows) {
            $perTrx = [];
            foreach ($rows as $row) {
                $perTrx[$row->tfee_id_transaksi][$row->tfee_code] = (int) $row->tfee_nominal;
            }
            foreach ($perTrx as $trxId => $rincian) {
                DB::table('transaksi')->where('transaksi_id', $trxId)->update([
                    'transaksi_fee_rincian' => json_encode($rincian),
                ]);
            }
        });
    }
};
