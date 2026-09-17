<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class ProdukStatusEnum extends Enum
{
    use EnumTrait;

    const TERSEDIA = 'tersedia';

    const TIDAK = 'tidak';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::TERSEDIA => 'Tersedia',
            self::TIDAK => 'Tidak Tersedia',
            default => parent::getDescription($value),
        };
    }
}
