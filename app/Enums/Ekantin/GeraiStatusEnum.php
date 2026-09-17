<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class GeraiStatusEnum extends Enum
{
    use EnumTrait;

    const BUKA = 'buka';

    const TUTUP = 'tutup';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::BUKA => 'Buka',
            self::TUTUP => 'Tutup',
            default => parent::getDescription($value),
        };
    }
}
