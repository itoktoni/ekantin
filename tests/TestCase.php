<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Paksa environment testing SEBELUM aplikasi boot.
     * `php artisan test` (proses induk) mem-load .env produksi lalu men-spawn child
     * test yang mewarisi $_SERVER/$_ENV/putenv (APP_ENV=local, DB ekantin).
     * Env phpunit.xml tidak selalu menembus warisan itu, sehingga RefreshDatabase
     * sempat menjalankan migrate:fresh di database produksi. Set ketiganya agar
     * adapter Env (Server/$_ENV/Putenv) semuanya membaca "testing".
     */
    protected function setUp(): void
    {
        $forced = [
            'APP_ENV' => 'testing',
            'DB_URL' => '',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'BROADCAST_CONNECTION' => 'null',
        ];

        foreach ($forced as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        parent::setUp();
    }
}
