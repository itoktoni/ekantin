<?php

use App\Models\Kartu;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('barcode kartu unik dan relasi pengguna terbaca', function () {
    $pengguna = User::create(['name' => 'Budi', 'email' => 'budi@sekolah.id', 'password' => 'secret123', 'role' => 'pengguna']);
    Kartu::create(['kartu_barcode' => 'SW001', 'kartu_id_user' => $pengguna->id, 'kartu_nis' => '123', 'kartu_saldo' => 50000, 'kartu_status' => 'aktif']);
    expect(fn () => Kartu::create(['kartu_barcode' => 'SW001', 'kartu_id_user' => $pengguna->id, 'kartu_nis' => '124']))
        ->toThrow(QueryException::class);
    expect(Kartu::first()->hasUser->name)->toBe('Budi');
});
