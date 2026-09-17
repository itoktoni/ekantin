<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class KartuStatusEnum extends Enum
{
    use EnumTrait;

    const AKTIF = 'aktif';

    const NONAKTIF = 'nonaktif';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::AKTIF => 'Aktif',
            self::NONAKTIF => 'Nonaktif',
            default => parent::getDescription($value),
        };
    }
}
