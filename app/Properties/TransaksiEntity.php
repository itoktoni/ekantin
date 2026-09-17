<?php

namespace App\Properties;

trait TransaksiEntity
{
    public static function field_jenis(): string
    {
        return 'transaksi_jenis';
    }

    public function getFieldJenisAttribute(): mixed
    {
        return $this->{static::field_jenis()};
    }

    public static function field_status(): string
    {
        return 'transaksi_status';
    }

    public function getFieldStatusAttribute(): mixed
    {
        return $this->{static::field_status()};
    }

    public static function field_id_kartu(): string
    {
        return 'transaksi_id_kartu';
    }

    public function getFieldIdKartuAttribute(): mixed
    {
        return $this->{static::field_id_kartu()};
    }

    public static function field_id_gerai(): string
    {
        return 'transaksi_id_gerai';
    }

    public function getFieldIdGeraiAttribute(): mixed
    {
        return $this->{static::field_id_gerai()};
    }

    public static function field_total(): string
    {
        return 'transaksi_total';
    }

    public function getFieldTotalAttribute(): mixed
    {
        return $this->{static::field_total()};
    }

    public static function field_fee_sistem(): string
    {
        return 'transaksi_fee_sistem';
    }

    public function getFieldFeeSistemAttribute(): mixed
    {
        return $this->{static::field_fee_sistem()};
    }

    public static function field_bersih(): string
    {
        return 'transaksi_bersih';
    }

    public function getFieldBersihAttribute(): mixed
    {
        return $this->{static::field_bersih()};
    }

    public static function field_saldo_akhir(): string
    {
        return 'transaksi_saldo_akhir';
    }

    public function getFieldSaldoAkhirAttribute(): mixed
    {
        return $this->{static::field_saldo_akhir()};
    }

    public static function field_idempotency(): string
    {
        return 'transaksi_idempotency';
    }

    public function getFieldIdempotencyAttribute(): mixed
    {
        return $this->{static::field_idempotency()};
    }
}
