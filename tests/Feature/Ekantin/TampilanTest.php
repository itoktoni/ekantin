<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman tabel kartu, pos kasir, dan laporan ter-render', function () {
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $this->actingAs($admin);
    expect($this->get(route('kartu.getTable'))->status())->toBe(200);
    expect($this->get(route('kasir.pos'))->status())->toBe(200);
    expect($this->get(route('laporan.index'))->status())->toBe(200);
    expect($this->get(route('kartu.cetak'))->status())->toBe(200);
});
