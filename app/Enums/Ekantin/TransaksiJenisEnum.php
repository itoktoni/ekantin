<?php

namespace App\Enums\Ekantin;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class TransaksiJenisEnum extends Enum
{
    use EnumTrait;

    const TOPUP_WEB = 'topup_web';

    const TOPUP_TUNAI = 'topup_tunai';

    const BELI = 'beli';

    const REFUND = 'refund';

    const KOREKSI = 'koreksi';

    const WITHDRAW = 'withdraw';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::TOPUP_WEB => 'Top Up Web',
            self::TOPUP_TUNAI => 'Top Up Tunai',
            self::BELI => 'Pembelian',
            self::REFUND => 'Refund',
            self::KOREKSI => 'Koreksi',
            self::WITHDRAW => 'Penarikan',
            default => parent::getDescription($value),
        };
    }
}
