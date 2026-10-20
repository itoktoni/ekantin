<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee', function (Blueprint $table) {
            $table->string('fee_tipe', 10)->default('persen')->after('nama_fee');
        });
    }

    public function down(): void
    {
        Schema::table('fee', function (Blueprint $table) {
            $table->dropColumn('fee_tipe');
        });
    }
};
