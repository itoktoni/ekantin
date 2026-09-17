<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu', function (Blueprint $table) {
            $table->id('kartu_id');
            $table->string('kartu_barcode', 50)->unique();
            $table->foreignId('kartu_id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('kartu_id_orangtua')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('kartu_nis', 30)->nullable();
            $table->string('kartu_kelas', 20)->nullable();
            $table->unsignedBigInteger('kartu_saldo')->default(0);
            $table->string('kartu_status', 20)->default('aktif');
            $table->unsignedBigInteger('kartu_limit_harian')->nullable();
            $table->timestamps();
            $table->index(['kartu_id_user', 'kartu_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu');
    }
};
