<?php

namespace App\Services\Ekantin;

class NotificationChannelFactory
{
    public static function create(string $type = 'log'): LogChannel
    {
        return new LogChannel;
    }
}
