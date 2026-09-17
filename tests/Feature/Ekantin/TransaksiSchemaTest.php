<?php

use App\Models\Transaksi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('transaksi menyimpan snapshot fee dan idempotency unik', function () {
    [$kartu, $gerai] = $this->seedEkantinDasar();
    Transaksi::create(['transaksi_jenis' => 'beli', 'transaksi_status' => 'berhasil', 'transaksi_id_kartu' => $kartu->kartu_id, 'transaksi_id_gerai' => $gerai->gerai_id, 'transaksi_total' => 10000, 'transaksi_fee_sistem' => 500, 'transaksi_bersih' => 9500, 'transaksi_saldo_akhir' => 40000, 'transaksi_idempotency' => 'K1']);
    expect(fn () => Transaksi::create(['transaksi_jenis' => 'beli', 'transaksi_status' => 'berhasil', 'transaksi_id_kartu' => $kartu->kartu_id, 'transaksi_total' => 10000, 'transaksi_fee_sistem' => 500, 'transaksi_bersih' => 9500, 'transaksi_saldo_akhir' => 30000, 'transaksi_idempotency' => 'K1']))
        ->toThrow(QueryException::class);
});
