<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Define menu items for desktop sidebar, mobile drawer, and bottom nav.
    | Each item: route (string), icon (string), label (string)
    | Sections: label (string), items (array)
    | Bottom nav: only 5 items max, uses short label
    |
    */

    'sidebar' => [
        [
            'label' => null,
            'items' => [
                ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
            ],
        ],
        [
            'label' => 'Kasir',
            'items' => [
                ['route' => 'kasir.pos', 'icon' => 'point_of_sale', 'label' => 'Kasir POS', 'match' => ['kasir.*']],
                ['route' => 'gerai.getPesanan', 'icon' => 'notifications', 'label' => 'Pesanan Gerai', 'match' => ['gerai.getPesanan', 'gerai.postPesanan']],
            ],
        ],
        [
            'label' => 'Kartu & Top Up',
            'items' => [
                ['route' => 'kartu.getTable', 'icon' => 'badge', 'label' => 'Kartu Siswa', 'match' => ['kartu.getTable','kartu.getCreate','kartu.postCreate','kartu.getUpdate','kartu.postUpdate','kartu.getDelete','kartu.postDelete','kartu.getShow','kartu.getCetak']],
                ['route' => 'kartu.getAnak', 'icon' => 'family_restroom', 'label' => 'Anak Saya', 'match' => ['kartu.getAnak']],
                ['route' => 'topup.tunai', 'icon' => 'payments', 'label' => 'Top Up Tunai', 'match' => ['topup.tunai']],
                ['route' => 'topup.web', 'icon' => 'account_balance_wallet', 'label' => 'Top Up Web', 'match' => ['topup.web']],
            ],
        ],
        [
            'label' => 'Gerai & Produk',
            'items' => [
                ['route' => 'gerai.getTable', 'icon' => 'store', 'label' => 'Gerai', 'match' => ['gerai.getTable','gerai.getCreate','gerai.postCreate','gerai.getUpdate','gerai.postUpdate','gerai.getDelete','gerai.postDelete','gerai.getShow']],
                ['route' => 'produk.getTable', 'icon' => 'fastfood', 'label' => 'Produk', 'match' => ['produk.*']],
                ['route' => 'transaksi.getTable', 'icon' => 'receipt_long', 'label' => 'Transaksi', 'match' => ['transaksi.*']],
                ['route' => 'penarikan.getTable', 'icon' => 'savings', 'label' => 'Penarikan', 'match' => ['penarikan.*']],
                ['route' => 'pembagian.getTable', 'icon' => 'payments', 'label' => 'Pembagian Harian', 'match' => ['pembagian.*']],
            ],
        ],
        [
            'label' => 'Laporan & Fee',
            'items' => [
                ['route' => 'laporan.index', 'icon' => 'assessment', 'label' => 'Laporan', 'match' => ['laporan.*']],
                ['route' => 'fee.getTable', 'icon' => 'percent', 'label' => 'Fee Produk', 'match' => ['fee.*']],
            ],
        ],
        [
            'label' => 'Master Data',
            'items' => [
                ['route' => 'user.getTable', 'icon' => 'manage_accounts', 'label' => 'Users', 'match' => ['user.*']],
            ],
        ],
        [
            'label' => 'CMS',
            'items' => [
                ['route' => 'cms-type.getTable', 'icon' => 'category', 'label' => 'Types', 'match' => ['cms-type.*']],
                ['route' => 'field.getTable', 'icon' => 'input', 'label' => 'Fields', 'match' => ['field.*']],
                ['route' => 'section.getTable', 'icon' => 'view_agenda', 'label' => 'Sections', 'match' => ['section.*']],
                ['route' => 'content.getTable', 'icon' => 'article', 'label' => 'Content', 'match' => ['content.*']],
                ['route' => 'category.getTable', 'icon' => 'sell', 'label' => 'Categories', 'match' => ['category.*']],
                ['route' => 'tag.getTable', 'icon' => 'label', 'label' => 'Tags', 'match' => ['tag.*']],
                ['route' => 'menu.getTable', 'icon' => 'menu', 'label' => 'Menus', 'match' => ['menu.*']],
            ],
        ],
        [
            'label' => 'Settings',
            'items' => [
                ['route' => 'settings.website', 'icon' => 'language', 'label' => 'Website'],
                ['route' => 'settings.env', 'icon' => 'settings', 'label' => 'Environment'],
                ['route' => 'native-bridge-test', 'icon' => 'phone_android', 'label' => 'NativeBridge Test'],
            ],
        ],
    ],

    'bottom_nav' => [

        ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
        ['route' => 'transaksi.getTable', 'icon' => 'receipt_long', 'label' => 'Transaksi'],
        ['route' => 'kasir.pos', 'icon' => 'point_of_sale', 'label' => 'Kasir'],
        ['route' => 'laporan.index', 'icon' => 'assessment', 'label' => 'Laporan'],
        ['route' => 'user.getTable', 'icon' => 'manage_accounts', 'label' => 'Users'],

    ],

];
