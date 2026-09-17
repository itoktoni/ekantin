<?php

namespace App\Properties;

trait PembagianEntity
{
    public static function field_pembagian_id() { return 'pembagian_id'; }
    public function getFieldPembagianIdAttribute() { return $this->{static::field_pembagian_id()}; }
    public static function field_pembagian_tanggal() { return 'pembagian_tanggal'; }
    public function getFieldPembagianTanggalAttribute() { return $this->{static::field_pembagian_tanggal()}; }
    public static function field_pembagian_id_gerai() { return 'pembagian_id_gerai'; }
    public function getFieldPembagianIdGeraiAttribute() { return $this->{static::field_pembagian_id_gerai()}; }
}
