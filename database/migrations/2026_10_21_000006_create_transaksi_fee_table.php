<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_fee', function (Blueprint $table) {
            $table->id('tfee_id');
            $table->unsignedBigInteger('tfee_id_transaksi');
            $table->unsignedBigInteger('tfee_id_fee')->nullable();
            $table->string('tfee_code', 30);
            $table->decimal('tfee_persen', 5, 2)->default(0);
            $table->unsignedBigInteger('tfee_nominal')->default(0);
            $table->unique(['tfee_id_transaksi', 'tfee_code']);
            $table->index(['tfee_id_fee']);
            $table->foreign('tfee_id_transaksi')->references('transaksi_id')->on('transaksi')->cascadeOnDelete();
            $table->foreign('tfee_id_fee')->references('fee_id')->on('fee')->nullOnDelete();
        });

        // Pindahkan rincian JSON lama ke tabel relasi (kode warisan → fee_id null).
        DB::table('transaksi')->whereNotNull('transaksi_fee_rincian')->orderBy('transaksi_id')->chunk(200, function ($rows) {
            $feeIds = DB::table('fee')->pluck('fee_id', 'code_fee');
            $persen = DB::table('fee')->pluck('value_fee', 'code_fee');
            foreach ($rows as $row) {
                $rincian = json_decode((string) $row->transaksi_fee_rincian, true);
                if (! is_array($rincian) || $rincian === []) {
                    continue;
                }
                foreach ($rincian as $code => $nominal) {
                    DB::table('transaksi_fee')->insertOrIgnore([
                        'tfee_id_transaksi' => $row->transaksi_id,
                        'tfee_id_fee' => $feeIds[$code] ?? null,
                        'tfee_code' => (string) $code,
                        'tfee_persen' => $persen[$code] ?? 0,
                        'tfee_nominal' => (int) $nominal,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_fee');
    }
};
