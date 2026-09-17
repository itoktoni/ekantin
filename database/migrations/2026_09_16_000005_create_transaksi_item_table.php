<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_item', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('item_id_transaksi')->constrained('transaksi', 'transaksi_id')->cascadeOnDelete();
            $table->string('item_nama', 100);
            $table->unsignedBigInteger('item_harga');
            $table->unsignedInteger('item_qty');
            $table->unsignedBigInteger('item_subtotal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_item');
    }
};
