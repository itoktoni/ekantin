<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class NotifStatusEnum extends Enum
{
    use EnumTrait;

    const MENUNGGU = 'menunggu';

    const TERKIRIM = 'terkirim';

    const GAGAL = 'gagal';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::MENUNGGU => 'Menunggu',
            self::TERKIRIM => 'Terkirim',
            self::GAGAL => 'Gagal',
            default => parent::getDescription($value),
        };
    }
}
