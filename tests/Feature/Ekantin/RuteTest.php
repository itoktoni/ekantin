<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin membuka tabel kartu, vendor ditolak membuka fee-config', function () {
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $vendor = User::create(['name' => 'V', 'email' => 'v@x.id', 'password' => 'secret123', 'role' => 'vendor']);
    expect($this->actingAs($admin)->getJson(route('kartu.getTable'))->status())->toBe(200);
    expect($this->actingAs($vendor)->getJson(route('fee-config.getTable'))->status())->toBe(403);
});
