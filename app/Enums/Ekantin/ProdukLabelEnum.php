<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class ProdukLabelEnum extends Enum
{
    use EnumTrait;

    const SEHAT = 'sehat';
    const TIDAK_SEHAT = 'tidak_sehat';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::SEHAT => 'Sehat',
            self::TIDAK_SEHAT => 'Tidak Sehat',
            default => parent::getDescription($value),
        };
    }
}
