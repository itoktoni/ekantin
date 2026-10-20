<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Label kesehatan kini opsional (peringatan seperti manis/seafood/alergen) —
        // produk tanpa peringatan (mis. air mineral) boleh kosong.
        Schema::table('produk', function (Blueprint $table) {
            $table->string('produk_label', 50)->nullable()->default(null)->change();
        });

        // Petakan label lama sehat/tidak_sehat ke master label baru.
        $peta = [
            'Nasi Goreng' => 'gorengan',
            'Ayam Geprek' => 'pedas',
            'Es Teh Manis' => 'manis',
            'Air Mineral' => null,
            'Roti Bakar Coklat' => 'manis',
            'Pisang Goreng' => 'gorengan',
            'Susu Jahe' => null,
            'Jus Alpukat' => null,
            'Es Jeruk' => 'manis',
            'Kopi Susu' => 'berkafein',
        ];
        foreach ($peta as $nama => $label) {
            DB::table('produk')->where('produk_nama', $nama)->update(['produk_label' => $label]);
        }
        // Sisa data lain berlabel lama (mis. input manual) → kosongkan daripada warning palsu.
        DB::table('produk')->whereIn('produk_label', ['sehat', 'tidak_sehat'])->update(['produk_label' => null]);
    }

    public function down(): void
    {
        DB::table('produk')->whereNull('produk_label')->update(['produk_label' => 'manis']);
        Schema::table('produk', function (Blueprint $table) {
            $table->string('produk_label', 20)->default('sehat')->change();
        });
    }
};
