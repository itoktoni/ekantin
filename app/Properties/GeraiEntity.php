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
}
