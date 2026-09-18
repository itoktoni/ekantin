<?php

use App\Models\FeeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fee config aktif menolak nilai negatif', function () {
    FeeConfig::create(['fee_min_topup' => 10000, 'fee_aktif' => true]);
    expect(FeeConfig::aktif()->fee_min_topup)->toBe(10000);
    $v = validator(['fee_min_topup' => -100], (new FeeConfig)->rules());
    expect($v->fails())->toBeTrue();
});
