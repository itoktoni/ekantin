<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->string('produk_kategori', 20)->default('makanan')->after('produk_nama');
            $table->index(['produk_kategori']);
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropIndex(['produk_kategori']);
            $table->dropColumn('produk_kategori');
        });
    }
};
