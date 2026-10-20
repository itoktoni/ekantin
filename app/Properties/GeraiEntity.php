<?php

namespace App\Properties;

trait GeraiEntity
{
    public static function field_nama(): string
    {
        return 'gerai_nama';
    }

    public function getFieldNamaAttribute(): mixed
    {
        return $this->{static::field_nama()};
    }

    public static function field_id_vendor(): string
    {
        return 'gerai_id_vendor';
    }

    public function getFieldIdVendorAttribute(): mixed
    {
        return $this->{static::field_id_vendor()};
    }

    public static function field_saldo(): string
    {
        return 'gerai_saldo';
    }

    public function getFieldSaldoAttribute(): mixed
    {
        return $this->{static::field_saldo()};
    }

    public static function field_status(): string
    {
        return 'gerai_status';
    }

    public function getFieldStatusAttribute(): mixed
    {
        return $this->{static::field_status()};
    }

    public static function field_bank(): string
    {
        return 'gerai_bank';
    }

    public function getFieldBankAttribute(): mixed
    {
        return $this->{static::field_bank()};
    }

    public static function field_no_rekening(): string
    {
        return 'gerai_no_rekening';
    }

    public function getFieldNoRekeningAttribute(): mixed
    {
        return $this->{static::field_no_rekening()};
    }

    public static function field_nama_rekening(): string
    {
        return 'gerai_nama_rekening';
    }

    public function getFieldNamaRekeningAttribute(): mixed
    {
        return $this->{static::field_nama_rekening()};
    }
}
