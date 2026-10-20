<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gerai', function (Blueprint $table) {
            $table->string('gerai_bank', 50)->nullable()->after('gerai_status');
            $table->string('gerai_no_rekening', 30)->nullable()->after('gerai_bank');
            $table->string('gerai_nama_rekening', 100)->nullable()->after('gerai_no_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('gerai', function (Blueprint $table) {
            $table->dropColumn(['gerai_bank', 'gerai_no_rekening', 'gerai_nama_rekening']);
        });
    }
};
