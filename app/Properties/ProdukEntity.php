<?php

namespace App\Properties;

trait ProdukEntity
{
    public static function field_id_gerai(): string
    {
        return 'produk_id_gerai';
    }

    public function getFieldIdGeraiAttribute(): mixed
    {
        return $this->{static::field_id_gerai()};
    }

    public static function field_nama(): string
    {
        return 'produk_nama';
    }

    public function getFieldNamaAttribute(): mixed
    {
        return $this->{static::field_nama()};
    }

    public static function field_harga(): string
    {
        return 'produk_harga';
    }

    public function getFieldHargaAttribute(): mixed
    {
        return $this->{static::field_harga()};
    }

    public static function field_status(): string
    {
        return 'produk_status';
    }

    public function getFieldStatusAttribute(): mixed
    {
        return $this->{static::field_status()};
    }

    public static function field_kategori(): string
    {
        return 'produk_kategori';
    }

    public function getFieldKategoriAttribute(): mixed
    {
        return $this->{static::field_kategori()};
    }

    public static function field_foto(): string
    {
        return 'produk_foto';
    }

    public function getFieldFotoAttribute(): mixed
    {
        return $this->{static::field_foto()};
    }
}
