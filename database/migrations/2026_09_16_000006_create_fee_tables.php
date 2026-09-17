<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_config', function (Blueprint $table) {
            $table->id('fee_id');
            $table->unsignedBigInteger('fee_sistem')->default(500);
            $table->unsignedBigInteger('fee_kebersihan')->default(0);
            $table->unsignedBigInteger('fee_keamanan')->default(0);
            $table->unsignedBigInteger('fee_pengelolaan')->default(0);
            $table->unsignedBigInteger('fee_min_topup')->default(10000);
            $table->boolean('fee_aktif')->default(true);
            $table->timestamps();
        });
        Schema::create('fee_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->string('history_field', 40);
            $table->unsignedBigInteger('history_lama')->default(0);
            $table->unsignedBigInteger('history_baru')->default(0);
            $table->foreignId('history_id_admin')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_history');
        Schema::dropIfExists('fee_config');
    }
};
