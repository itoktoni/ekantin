<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class TransaksiStatusEnum extends Enum
{
    use EnumTrait;

    const MENUNGGU = 'menunggu';

    const BERHASIL = 'berhasil';

    const GAGAL = 'gagal';

    const BATAL = 'dibatalkan';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::MENUNGGU => 'Menunggu Pembayaran',
            self::BERHASIL => 'Berhasil',
            self::GAGAL => 'Gagal',
            self::BATAL => 'Dibatalkan',
            default => parent::getDescription($value),
        };
    }
}
