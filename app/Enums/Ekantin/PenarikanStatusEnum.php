<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class PenarikanStatusEnum extends Enum
{
    use EnumTrait;

    const DIAJUKAN = 'diajukan';

    const SELESAI = 'diselesaikan';

    const DITOLAK = 'ditolak';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::DIAJUKAN => 'Diajukan',
            self::SELESAI => 'Diselesaikan',
            self::DITOLAK => 'Ditolak',
            default => parent::getDescription($value),
        };
    }
}
