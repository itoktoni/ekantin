<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penarikan', function (Blueprint $table) {
            // Tanggal transaksi yang diajukan gerai (penagihan harian akhir sesi);
            // null = penarikan lama (dihitung dari created_at).
            $table->date('penarikan_tanggal')->nullable()->after('penarikan_nominal');
            // Pelaku penukaran uang (kasir) saat status diselesaikan.
            $table->foreignId('penarikan_id_kasir')->nullable()->after('penarikan_id_admin')->constrained('users', 'id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penarikan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penarikan_id_kasir');
            $table->dropColumn('penarikan_tanggal');
        });
    }
};
