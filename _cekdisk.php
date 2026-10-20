<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo 'default disk: '.config('filesystems.default').PHP_EOL;
echo 'local root: '.config('filesystems.disks.local.root').PHP_EOL;
echo 'public root: '.config('filesystems.disks.public.root').PHP_EOL;
