<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class RoleEnum extends Enum
{
    use EnumTrait;

    const USER = 'user';

    const EDITOR = 'editor';

    const ADMIN = 'admin';

    const DEVELOPER = 'developer';

    const SUPER_ADMIN = 'super_admin';

    const KASIR_SEKOLAH = 'kasir_sekolah';

    const VENDOR = 'vendor';

    const ORANG_TUA = 'orang_tua';

    const SISWA = 'siswa';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::ADMIN => 'Administrator Utama',
            self::EDITOR => 'Editor',
            self::DEVELOPER => 'Developer',
            self::USER => 'Pengguna Biasa',
            self::SUPER_ADMIN => 'Super Admin',
            self::KASIR_SEKOLAH => 'Kasir Sekolah',
            self::VENDOR => 'Vendor',
            self::ORANG_TUA => 'Orang Tua',
            self::SISWA => 'Siswa',
            default => parent::getDescription($value),
        };
    }
}
