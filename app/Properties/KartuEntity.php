<?php

namespace App\Properties;

trait KartuEntity
{
    public static function field_barcode(): string
    {
        return 'kartu_barcode';
    }

    public function getFieldBarcodeAttribute(): mixed
    {
        return $this->{static::field_barcode()};
    }

    public static function field_id_user(): string
    {
        return 'kartu_id_user';
    }

    public function getFieldIdUserAttribute(): mixed
    {
        return $this->{static::field_id_user()};
    }

    public static function field_id_orangtua(): string
    {
        return 'kartu_id_orangtua';
    }

    public function getFieldIdOrangtuaAttribute(): mixed
    {
        return $this->{static::field_id_orangtua()};
    }

    public static function field_nis(): string
    {
        return 'kartu_nis';
    }

    public function getFieldNisAttribute(): mixed
    {
        return $this->{static::field_nis()};
    }

    public static function field_kelas(): string
    {
        return 'kartu_kelas';
    }

    public function getFieldKelasAttribute(): mixed
    {
        return $this->{static::field_kelas()};
    }

    public static function field_saldo(): string
    {
        return 'kartu_saldo';
    }

    public function getFieldSaldoAttribute(): mixed
    {
        return $this->{static::field_saldo()};
    }

    public static function field_status(): string
    {
        return 'kartu_status';
    }

    public function getFieldStatusAttribute(): mixed
    {
        return $this->{static::field_status()};
    }

    public static function field_limit_harian(): string
    {
        return 'kartu_limit_harian';
    }

    public function getFieldLimitHarianAttribute(): mixed
    {
        return $this->{static::field_limit_harian()};
    }

    public static function field_label_dilarang(): string
    {
        return 'kartu_label_dilarang';
    }

    public function getFieldLabelDilarangAttribute(): mixed
    {
        return $this->{static::field_label_dilarang()};
    }

    public static function field_produk_dilarang(): string
    {
        return 'kartu_produk_dilarang';
    }

    public function getFieldProdukDilarangAttribute(): mixed
    {
        return $this->{static::field_produk_dilarang()};
    }

    public static function field_catatan_kesehatan(): string
    {
        return 'kartu_catatan_kesehatan';
    }

    public function getFieldCatatanKesehatanAttribute(): mixed
    {
        return $this->{static::field_catatan_kesehatan()};
    }
}
