<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test("uploadFile menyimpan ke disk public dan path-nya valid", function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('foto.jpg', 100, 100);
    $path = uploadFile($file, 'produk', ['max_size' => 2048]);
    expect($path)->toStartWith('produk/')
        ->and(Storage::disk('public')->exists($path))->toBeTrue()
        ->and(fileUrl($path))->toBe('/storage/'.$path);
});

test("uploadFile menolak file terlalu besar dan tipe dilarang", function () {
    Storage::fake('public');
    expect(fn () => uploadFile(UploadedFile::fake()->image('besar.jpg', 10, 10)->size(3000), 'produk', ['max_size' => 2048]))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => uploadFile(UploadedFile::fake()->create('jahat.php', 100, 'application/x-php'), 'produk'))
        ->toThrow(InvalidArgumentException::class);
});
