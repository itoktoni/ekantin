<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gerai', function (Blueprint $table) {
            $table->id('gerai_id');
            $table->string('gerai_nama', 100);
            $table->foreignId('gerai_id_vendor')->constrained('users', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('gerai_saldo')->default(0);
            $table->string('gerai_status', 20)->default('buka');
            $table->timestamps();
            $table->index(['gerai_id_vendor', 'gerai_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gerai');
    }
};
