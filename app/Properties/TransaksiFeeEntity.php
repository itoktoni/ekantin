<?php

namespace App\Properties;

trait TransaksiFeeEntity
{
    public static function field_id_transaksi(): string
    {
        return 'tfee_id_transaksi';
    }

    public function getFieldIdTransaksiAttribute(): mixed
    {
        return $this->{static::field_id_transaksi()};
    }

    public static function field_id_fee(): string
    {
        return 'tfee_id_fee';
    }

    public function getFieldIdFeeAttribute(): mixed
    {
        return $this->{static::field_id_fee()};
    }

    public static function field_code(): string
    {
        return 'tfee_code';
    }

    public function getFieldCodeAttribute(): mixed
    {
        return $this->{static::field_code()};
    }

    public static function field_tipe(): string
    {
        return 'tfee_tipe';
    }

    public function getFieldTipeAttribute(): mixed
    {
        return $this->{static::field_tipe()};
    }

    public static function field_persen(): string
    {
        return 'tfee_persen';
    }

    public function getFieldPersenAttribute(): mixed
    {
        return $this->{static::field_persen()};
    }

    public static function field_nominal(): string
    {
        return 'tfee_nominal';
    }

    public function getFieldNominalAttribute(): mixed
    {
        return $this->{static::field_nominal()};
    }
}
