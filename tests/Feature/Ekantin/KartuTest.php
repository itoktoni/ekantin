<?php

use App\Models\Kartu;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('barcode kartu unik dan relasi siswa terbaca', function () {
    $siswa = User::create(['name' => 'Budi', 'email' => 'budi@sekolah.id', 'password' => 'secret123', 'role' => 'siswa']);
    Kartu::create(['kartu_barcode' => 'SW001', 'kartu_id_user' => $siswa->id, 'kartu_nis' => '123', 'kartu_saldo' => 50000, 'kartu_status' => 'aktif']);
    expect(fn () => Kartu::create(['kartu_barcode' => 'SW001', 'kartu_id_user' => $siswa->id, 'kartu_nis' => '124']))
        ->toThrow(QueryException::class);
    expect(Kartu::first()->hasUser->name)->toBe('Budi');
});
