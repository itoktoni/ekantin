<?php

namespace App\Services\Ekantin;

use Illuminate\Support\Facades\Log;

class LogChannel
{
    public function send(string $penerima, string $judul, string $isi): bool
    {
        Log::channel('single')->info("[notif] {$penerima} | {$judul} | {$isi}");

        return true;
    }
}
