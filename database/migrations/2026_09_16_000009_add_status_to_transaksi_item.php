<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->string('item_status', 20)->default('baru')->after('item_subtotal');
            $table->index(['item_id_gerai', 'item_status']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->dropIndex(['item_id_gerai', 'item_status']);
            $table->dropColumn('item_status');
        });
    }
};
