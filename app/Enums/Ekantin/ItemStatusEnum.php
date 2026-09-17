<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class ItemStatusEnum extends Enum
{
    use EnumTrait;

    const BARU = 'baru';

    const DISIAPKAN = 'disiapkan';

    const SELESAI = 'selesai';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::BARU => 'Baru',
            self::DISIAPKAN => 'Disiapkan',
            self::SELESAI => 'Selesai',
            default => parent::getDescription($value),
        };
    }
}
