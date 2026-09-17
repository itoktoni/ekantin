<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class ProdukKategoriEnum extends Enum
{
    use EnumTrait;

    const MAKANAN = 'makanan';
    const MINUMAN = 'minuman';
    const SNACK = 'snack';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::MAKANAN => 'Makanan',
            self::MINUMAN => 'Minuman',
            self::SNACK => 'Snack',
            default => parent::getDescription($value),
        };
    }
}
