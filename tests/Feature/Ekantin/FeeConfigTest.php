<?php

use App\Models\FeeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fee config aktif menolak nilai negatif', function () {
    FeeConfig::create(['fee_sistem' => 500, 'fee_kebersihan' => 0, 'fee_keamanan' => 0, 'fee_pengelolaan' => 0, 'fee_min_topup' => 10000, 'fee_aktif' => true]);
    expect(FeeConfig::aktif()->fee_sistem)->toBe(500);
    $v = validator(['fee_sistem' => -100], (new FeeConfig)->rules());
    expect($v->fails())->toBeTrue();
});
