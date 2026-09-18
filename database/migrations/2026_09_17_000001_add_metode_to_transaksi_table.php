<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("transaksi", function (Blueprint $table) {
            $table->string("transaksi_metode", 10)->default("kartu")->after("transaksi_status");
        });
    }

    public function down(): void
    {
        Schema::table("transaksi", function (Blueprint $table) {
            $table->dropColumn("transaksi_metode");
        });
    }
};
