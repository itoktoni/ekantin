<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id('produk_id');
            $table->foreignId('produk_id_gerai')->constrained('gerai', 'gerai_id')->cascadeOnDelete();
            $table->string('produk_nama', 100);
            $table->unsignedBigInteger('produk_harga');
            $table->string('produk_status', 20)->default('tersedia');
            $table->string('produk_foto', 255)->nullable();
            $table->timestamps();
            $table->index(['produk_id_gerai', 'produk_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
