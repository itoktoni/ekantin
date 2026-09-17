<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembagian', function (Blueprint $table) {
            $table->id('pembagian_id');
            $table->date('pembagian_tanggal');
            $table->foreignId('pembagian_id_gerai')->constrained('gerai', 'gerai_id')->cascadeOnDelete();
            $table->unsignedBigInteger('pembagian_total')->default(0);
            $table->unsignedBigInteger('pembagian_fee')->default(0);
            $table->unsignedBigInteger('pembagian_bersih')->default(0);
            $table->string('pembagian_status', 20)->default('selesai'); // selesai/dibatalkan
            $table->foreignId('pembagian_id_kasir')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('pembagian_catatan', 255)->nullable();
            $table->timestamps();
            $table->unique(['pembagian_tanggal', 'pembagian_id_gerai']);
            $table->index(['pembagian_tanggal', 'pembagian_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembagian');
    }
};
