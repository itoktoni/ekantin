<?php

$lines = file(__DIR__.'/.env');
$line = $lines[86] ?? '';   // baris 87 (0-index 86)
echo 'baris 87 panjang: '.strlen($line)."\n";
echo '10 byte pertama : '.bin2hex(substr($line, 0, 12))."\n";
echo 'kode karakter awal: ';
for ($i = 0; $i < 12; $i++) {
    $c = $line[$i] ?? '';
    echo $c.'('.ord($c).') ';
}
echo "\n";

// cek karakter non-ASCII di seluruh baris
$nonAscii = [];
for ($i = 0; $i < strlen($line); $i++) {
    $o = ord($line[$i]);
    if ($o > 126 || ($o < 32 && $o !== 10 && $o !== 13)) {
        $nonAscii[] = "pos $i: ord $o (".bin2hex($line[$i]).')';
    }
}
echo 'karakter non-ASCII: '.(empty($nonAscii) ? 'tidak ada' : implode(', ', array_slice($nonAscii, 0, 10)))."\n";

// parse pakai Dotenv langsung
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$hasil = $dotenv->safeLoad();
echo "hasil parse Dotenv untuk QRIS: ";
if (! array_key_exists('QRIS', $hasil)) {
    echo "KEY TIDAK ADA\n";
} else {
    echo 'panjang '.strlen((string) $hasil['QRIS']).' -> "'.substr((string) $hasil['QRIS'], 0, 50)."\"\n";
}
echo 'jumlah key terparse: '.count($hasil)."\n";
echo 'key terakhir: '.implode(', ', array_slice(array_keys($hasil), -5))."\n";
