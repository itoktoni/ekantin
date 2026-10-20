<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label', function (Blueprint $table) {
            $table->id('label_id');
            $table->string('label_nama', 50)->unique();
            $table->string('label_keterangan', 255)->nullable();
            $table->timestamps();
        });

        // Label awal pengganti sehat/tidak_sehat — user bisa tambah/hapus sendiri via menu Label.
        $now = now()->toDateTimeString();
        foreach (['manis', 'seafood', 'alergen', 'pedas', 'gorengan', 'berkafein'] as $nama) {
            DB::table('label')->insertOrIgnore([
                'label_nama' => $nama,
                'label_keterangan' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('label');
    }
};
