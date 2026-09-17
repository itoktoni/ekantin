<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->foreignId('item_id_gerai')->nullable()->after('item_id_transaksi')->constrained('gerai', 'gerai_id')->nullOnDelete();
            $table->index(['item_id_gerai']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->dropForeign(['item_id_gerai']);
            $table->dropIndex(['item_id_gerai']);
            $table->dropColumn('item_id_gerai');
        });
    }
};
