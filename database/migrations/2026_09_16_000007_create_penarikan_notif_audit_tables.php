<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penarikan', function (Blueprint $table) {
            $table->id('penarikan_id');
            $table->foreignId('penarikan_id_gerai')->constrained('gerai', 'gerai_id')->cascadeOnDelete();
            $table->unsignedBigInteger('penarikan_nominal');
            $table->string('penarikan_status', 20)->default('diajukan');
            $table->string('penarikan_bukti', 255)->nullable();
            $table->foreignId('penarikan_id_admin')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('notifikasi_log', function (Blueprint $table) {
            $table->id('notif_id');
            $table->foreignId('notif_id_transaksi')->constrained('transaksi', 'transaksi_id')->cascadeOnDelete();
            $table->string('notif_saluran', 20)->default('log');
            $table->string('notif_status', 20)->default('menunggu');
            $table->unsignedTinyInteger('notif_percobaan')->default(0);
            $table->json('notif_payload')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id('audit_id');
            $table->string('audit_aksi', 40);
            $table->string('audit_model', 60);
            $table->unsignedBigInteger('audit_id_record')->nullable();
            $table->json('audit_lama')->nullable();
            $table->json('audit_baru')->nullable();
            $table->foreignId('audit_id_user')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamps();
            $table->index(['audit_model', 'audit_id_record']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('notifikasi_log');
        Schema::dropIfExists('penarikan');
    }
};
