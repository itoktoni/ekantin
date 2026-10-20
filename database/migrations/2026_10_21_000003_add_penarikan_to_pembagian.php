<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembagian', function (Blueprint $table) {
            $table->foreignId('pembagian_id_penarikan')->nullable()->after('pembagian_id_kasir')
                ->constrained('penarikan', 'penarikan_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembagian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pembagian_id_penarikan');
        });
    }
};
