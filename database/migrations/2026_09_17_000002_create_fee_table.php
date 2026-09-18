<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee', function (Blueprint $table) {
            $table->id('fee_id');
            $table->string('code_fee', 30)->unique();
            $table->string('nama_fee', 100);
            $table->decimal('value_fee', 5, 2)->default(0);
            $table->timestamps();
        });
        Schema::table('fee_config', function (Blueprint $table) {
            $table->dropColumn(['fee_sistem', 'fee_kebersihan', 'fee_keamanan', 'fee_pengelolaan']);
        });
        Schema::table('transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('transaksi_fee_total')->default(0)->after('transaksi_fee_sistem');
            $table->json('transaksi_fee_rincian')->nullable()->after('transaksi_fee_total');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['transaksi_fee_total', 'transaksi_fee_rincian']);
        });
        Schema::table('fee_config', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_sistem')->default(0);
            $table->unsignedBigInteger('fee_kebersihan')->default(0);
            $table->unsignedBigInteger('fee_keamanan')->default(0);
            $table->unsignedBigInteger('fee_pengelolaan')->default(0);
        });
        Schema::dropIfExists('fee');
    }
};
