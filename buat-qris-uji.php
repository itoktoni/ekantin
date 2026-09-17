<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function tlv(string $tag, string $value): string
{
    return $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
}

$merchant = tlv('00', 'ID.CO.QRIS.WWW').tlv('01', '936000000000000000').tlv('02', 'ID1020000000000');
$body = tlv('00', '01')
    .tlv('01', '11')
    .tlv('26', $merchant)
    .tlv('52', '5812')
    .tlv('53', '360')
    .tlv('58', 'ID')
    .tlv('59', 'KANTINSEKOLAH')
    .tlv('60', 'JAKARTA')
    .'6304';

$statis = $body.strtoupper(str_pad(dechex(crc16($body)), 4, '0', STR_PAD_LEFT));

echo $statis."\n";
