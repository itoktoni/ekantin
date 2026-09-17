<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('transaksi_id');
            $table->string('transaksi_jenis', 20);
            $table->string('transaksi_status', 20)->default('berhasil');
            $table->foreignId('transaksi_id_kartu')->nullable()->constrained('kartu', 'kartu_id')->nullOnDelete();
            $table->foreignId('transaksi_id_gerai')->nullable()->constrained('gerai', 'gerai_id')->nullOnDelete();
            $table->unsignedBigInteger('transaksi_total');
            $table->unsignedBigInteger('transaksi_fee_kebersihan')->default(0);
            $table->unsignedBigInteger('transaksi_fee_keamanan')->default(0);
            $table->unsignedBigInteger('transaksi_fee_pengelolaan')->default(0);
            $table->unsignedBigInteger('transaksi_fee_sistem')->default(0);
            $table->unsignedBigInteger('transaksi_bersih')->default(0);
            $table->unsignedBigInteger('transaksi_saldo_akhir')->nullable();
            $table->unsignedBigInteger('transaksi_limit_snapshot')->nullable();
            $table->string('transaksi_idempotency', 64)->nullable()->unique();
            $table->foreignId('transaksi_id_reversal_of')->nullable()->constrained('transaksi', 'transaksi_id')->nullOnDelete();
            $table->string('transaksi_alasan', 255)->nullable();
            $table->foreignId('transaksi_id_kasir')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();
            $table->index(['transaksi_jenis', 'transaksi_status', 'created_at']);
            $table->index(['transaksi_id_kartu', 'created_at']);
            $table->index(['transaksi_id_gerai', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
