<?php

use App\Enums\Ekantin\KartuStatusEnum;
use App\Enums\RoleEnum;

test('peran ekantin dan status kartu tersedia', function () {
    expect(RoleEnum::getOptions())->toHaveKeys(['super_admin', 'kasir_sekolah', 'vendor', 'orang_tua', 'siswa']);
    expect(KartuStatusEnum::getOptions())->toBe(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']);
});
