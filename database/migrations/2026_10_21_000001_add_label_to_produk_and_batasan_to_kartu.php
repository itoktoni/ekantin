<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->string('produk_label', 20)->default('sehat')->after('produk_kategori');
            $table->index(['produk_label']);
        });

        Schema::table('kartu', function (Blueprint $table) {
            // Batasan jajan per anak: label dilarang (mis. manis saat batuk),
            // produk spesifik dilarang (alergi), + catatan kesehatan bebas.
            $table->json('kartu_label_dilarang')->nullable()->after('kartu_limit_harian');
            $table->json('kartu_produk_dilarang')->nullable()->after('kartu_label_dilarang');
            $table->string('kartu_catatan_kesehatan', 255)->nullable()->after('kartu_produk_dilarang');
        });
    }

    public function down(): void
    {
        Schema::table('kartu', function (Blueprint $table) {
            $table->dropColumn(['kartu_label_dilarang', 'kartu_produk_dilarang', 'kartu_catatan_kesehatan']);
        });

        Schema::table('produk', function (Blueprint $table) {
            $table->dropIndex(['produk_label']);
            $table->dropColumn('produk_label');
        });
    }
};
