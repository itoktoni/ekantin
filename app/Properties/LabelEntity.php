<?php

namespace App\Properties;

trait LabelEntity
{
    public static function field_nama(): string
    {
        return 'label_nama';
    }

    public function getFieldNamaAttribute(): mixed
    {
        return $this->{static::field_nama()};
    }

    public static function field_keterangan(): string
    {
        return 'label_keterangan';
    }

    public function getFieldKeteranganAttribute(): mixed
    {
        return $this->{static::field_keterangan()};
    }
}
