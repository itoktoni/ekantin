<?php

$restrict = [];

// $restrict['user']['product'][] = 'show';

// E-Kanteen: terdaftar = ditolak (BasePolicy).
$restrict['vendor']['kartu.getTable'][] = 'table';
$restrict['vendor']['fee-config.getTable'][] = 'table';
$restrict['vendor']['audit-log.getTable'][] = 'table';
foreach (['table','save','create','update','delete','show'] as $act) {
    $restrict['vendor']['kartu.getTable'][] = $act;
    $restrict['vendor']['kartu.getCreate'][] = $act;
    $restrict['vendor']['user.getTable'][] = $act;
    $restrict['vendor']['user.getCreate'][] = $act;
    foreach (['cms-type','field','section','content','category','tag','menu'] as $cms) $restrict['vendor'][$cms.'.getTable'][] = $act;
    $restrict['vendor']['settings.website'][] = $act;
    $restrict['vendor']['settings.env'][] = $act;
    $restrict['vendor']['topup.tunai'][] = $act;
    $restrict['vendor']['topup.web'][] = $act;
}
// kasir_sekolah hanya boleh: product (produk.*), kasir (kasir.pos/struk), pesanan gerai (gerai.getPesanan/postPesanan), transaksi (reprint) & dashboard
// hide fee-config, penarikan, dll sudah ada — tambah hide kartu, gerai CRUD, user, CMS, settings, laporan
$restrict['kasir_sekolah']['fee-config.getTable'][] = 'table';
$restrict['kasir_sekolah']['penarikan.getTable'][] = 'table';
foreach (['table','save','create','update','delete','show','pesanan','pos','struk'] as $act) {
    // kartu siswa — hide
    $restrict['kasir_sekolah']['kartu.getTable'][] = $act;
    $restrict['kasir_sekolah']['kartu.getCreate'][] = $act;
    $restrict['kasir_sekolah']['kartu.postCreate'][] = $act;
    $restrict['kasir_sekolah']['kartu.getUpdate'][] = $act;
    $restrict['kasir_sekolah']['kartu.postUpdate'][] = $act;
    // gerai CRUD — hide, tapi pesanan tetap boleh
    $restrict['kasir_sekolah']['gerai.getTable'][] = $act;
    $restrict['kasir_sekolah']['gerai.getCreate'][] = $act;
    $restrict['kasir_sekolah']['gerai.postCreate'][] = $act;
    $restrict['kasir_sekolah']['gerai.getUpdate'][] = $act;
    $restrict['kasir_sekolah']['gerai.postUpdate'][] = $act;
    // laporan
    $restrict['kasir_sekolah']['laporan.index'][] = $act;
    $restrict['kasir_sekolah']['laporan.ekspor'][] = $act;
    // users master
    $restrict['kasir_sekolah']['user.getTable'][] = $act;
    $restrict['kasir_sekolah']['user.getCreate'][] = $act;
    $restrict['kasir_sekolah']['user.postCreate'][] = $act;
    // CMS
    foreach (['cms-type','field','section','content','category','tag','menu'] as $cms) {
        $restrict['kasir_sekolah'][$cms.'.getTable'][] = $act;
    }
    // settings
    $restrict['kasir_sekolah']['settings.website'][] = $act;
    $restrict['kasir_sekolah']['settings.env'][] = $act;
    $restrict['kasir_sekolah']['native-bridge-test'][] = $act;
    $restrict['kasir_sekolah']['audit-log.getTable'][] = $act;
}
$restrict['orang_tua']['fee-config.getTable'][] = 'table';
$restrict['siswa']['fee-config.getTable'][] = 'table';
// orang_tua hanya boleh: dashboard, kartu anak (getAnak), transaksi anak, laporan anak, topup.web — hide kartu table generic, gerai/produk/penarikan/user/CMS/kasir/topup.tunai
foreach (['table','save','create','update','delete','show','pesanan','pos','struk'] as $act) {
    $restrict['orang_tua']['kartu.getTable'][] = $act;
    $restrict['orang_tua']['kartu.getCreate'][] = $act;
    $restrict['orang_tua']['kartu.postCreate'][] = $act;
    $restrict['orang_tua']['kartu.getUpdate'][] = $act;
    $restrict['orang_tua']['gerai.getTable'][] = $act;
    $restrict['orang_tua']['gerai.getCreate'][] = $act;
    $restrict['orang_tua']['gerai.postCreate'][] = $act;
    $restrict['orang_tua']['produk.getTable'][] = $act;
    $restrict['orang_tua']['produk.getCreate'][] = $act;
    $restrict['orang_tua']['penarikan.getTable'][] = $act;
    $restrict['orang_tua']['user.getTable'][] = $act;
    foreach (['cms-type','field','section','content','category','tag','menu'] as $cms) $restrict['orang_tua'][$cms.'.getTable'][] = $act;
    $restrict['orang_tua']['settings.website'][] = $act;
    $restrict['orang_tua']['settings.env'][] = $act;
    $restrict['orang_tua']['audit-log.getTable'][] = $act;
    $restrict['orang_tua']['topup.tunai'][] = $act;
    $restrict['orang_tua']['kasir.pos'][] = $act;
}

// siswa hanya boleh: dashboard, kartu sendiri, transaksi sendiri, laporan sendiri, topup.web
foreach (['table','save','create','update','delete','show','pesanan','pos','struk'] as $act) {
    $restrict['siswa']['gerai.getTable'][] = $act;
    $restrict['siswa']['gerai.getCreate'][] = $act;
    $restrict['siswa']['produk.getTable'][] = $act;
    $restrict['siswa']['penarikan.getTable'][] = $act;
    $restrict['siswa']['user.getTable'][] = $act;
    foreach (['cms-type','field','section','content','category','tag','menu'] as $cms) $restrict['siswa'][$cms.'.getTable'][] = $act;
    $restrict['siswa']['settings.website'][] = $act;
    $restrict['siswa']['audit-log.getTable'][] = $act;
    $restrict['siswa']['topup.tunai'][] = $act;
    $restrict['siswa']['kasir.pos'][] = $act;
}
// POS sentral hanya kasir/admin: vendor, ortu, siswa ditolak.
foreach (['vendor', 'orang_tua', 'siswa'] as $peran) {
    $restrict[$peran]['kasir.pos'][] = 'pos';
    $restrict[$peran]['kasir.struk'][] = 'struk';
}

// Pool pesanan gerai (via Route::auto GeraiController::getPesanan/postPesanan + cetak ulang per gerai): vendor + kasir/admin boleh.
foreach (['orang_tua', 'siswa'] as $peran) {
    $restrict[$peran]['gerai.getPesanan'][] = 'pesanan';
    $restrict[$peran]['gerai.postPesanan'][] = 'pesanan';
    $restrict[$peran]['gerai.getPesananCetak'][] = 'pesananCetak';
}
// Topup QRIS konfirmasi hanya kasir/admin — orang tua/siswa/vendor tidak boleh flag berhasil
foreach (['orang_tua','siswa','vendor'] as $peran) {
    $restrict[$peran]['topup.web.bayar'][] = 'webbayar';
}
// Pembagian harian: vendor hanya lihat history (table) — tidak boleh bagi/delete; orang tua/siswa tidak boleh sama sekali; kasir boleh bagi & lihat, delete hanya super_admin
foreach (['orang_tua','siswa'] as $peran) {
    foreach (['table','bagi','save','create','update','delete','show'] as $act) {
        $restrict[$peran]['pembagian.getTable'][] = $act;
        $restrict[$peran]['pembagian.getBagi'][] = $act;
        $restrict[$peran]['pembagian.postBagi'][] = $act;
    }
}
foreach (['vendor'] as $peran) {
    foreach (['bagi','save','create','update','delete','show'] as $act) {
        $restrict[$peran]['pembagian.getBagi'][] = $act;
        $restrict[$peran]['pembagian.postBagi'][] = $act;
        $restrict[$peran]['pembagian.getDelete'][] = $act;
        $restrict[$peran]['pembagian.postDelete'][] = $act;
        $restrict[$peran]['pembagian.getCreate'][] = $act;
        $restrict[$peran]['pembagian.postCreate'][] = $act;
        $restrict[$peran]['pembagian.getUpdate'][] = $act;
        $restrict[$peran]['pembagian.postUpdate'][] = $act;
    }
}
foreach (['kasir_sekolah','vendor','orang_tua','siswa'] as $peran) {
    $restrict[$peran]['pembagian.getDelete'][] = 'delete';
    $restrict[$peran]['pembagian.postDelete'][] = 'delete';
}
// Vendor pembagian: hanya lihat history (table) — sembunyikan tombol Bagi/Edit/Delete di halaman table.
// NOTE: @can() di Blade berjalan dengan module = route saat ini (pembagian.getTable),
// jadi deny harus dipasang di getTable, bukan hanya di route target (getBagi/getDelete).
foreach (['bagi','save','create','update','delete'] as $act) {
    $restrict['vendor']['pembagian.getTable'][] = $act;
}
// Vendor transaksi: read-only (lihat table + detail via getUpdate) — sembunyikan tombol delete/create di table.
// Backend create/update/delete juga ditolak via policy (controller sudah abort 404 sebagai lapis kedua).
foreach (['save','create','delete'] as $act) {
    $restrict['vendor']['transaksi.getTable'][] = $act;
}
foreach (['save','create','delete','show','update','table'] as $act) {
    $restrict['vendor']['transaksi.getCreate'][] = $act;
    $restrict['vendor']['transaksi.postCreate'][] = $act;
    $restrict['vendor']['transaksi.getDelete'][] = $act;
    $restrict['vendor']['transaksi.postDelete'][] = $act;
}
// postUpdate ditolak (update), tapi getUpdate tetap boleh (halaman detail read-only)
foreach (['save','create','delete','show','update'] as $act) {
    $restrict['vendor']['transaksi.postUpdate'][] = $act;
}
// User management: vendor/kasir/orang_tua/siswa tidak boleh sama sekali (lihat, buat, ubah, hapus).
// NOTE: deny-list per route-name — route yang tidak terdaftar = ALLOW. Sebelumnya hanya
// user.getTable/getCreate yang di-deny sehingga GET user/delete/{id} (module user.getDelete)
// dan API users.getDelete lolos dan vendor bisa hapus user lain. Kunci semua modul user web + api.
$userModules = [
    'user.getTable', 'user.getCreate', 'user.postCreate',
    'user.getUpdate', 'user.postUpdate',
    'user.getDelete', 'user.postDelete', 'user.getShow',
    'user.index', 'user.boot',
    'users.getTable', 'users.getCreate', 'users.postCreate',
    'users.getUpdate', 'users.postUpdate',
    'users.getDelete', 'users.postDelete', 'users.getShow',
    'users.index', 'users.boot',
];
foreach (['vendor', 'kasir_sekolah', 'orang_tua', 'siswa'] as $peran) {
    foreach ($userModules as $mod) {
        foreach (['table', 'save', 'create', 'update', 'delete', 'show', 'boot'] as $act) {
            $restrict[$peran][$mod][] = $act;
        }
    }
}
// Gerai: hapus hanya admin — vendor/kasir/orang_tua/siswa tidak boleh delete (langsung via URL maupun bulk).
// NOTE: deny-list per route-name — gerai.getDelete/postDelete yang tidak terdaftar = ALLOW,
// sehingga GET /gerai/delete/7 lolos walau tombol di table disembunyikan. Kunci backend + UI (getTable).
foreach (['vendor', 'kasir_sekolah', 'orang_tua', 'siswa'] as $peran) {
    foreach (['delete'] as $act) {
        $restrict[$peran]['gerai.getDelete'][] = $act;
        $restrict[$peran]['gerai.postDelete'][] = $act;
        $restrict[$peran]['gerai.getTable'][] = $act;
    }
}
// IDOR satu keluarga: orang_tua/siswa tidak boleh sentuh gerai sama sekali — kunci juga
// update/show via ganti ID langsung (getTable/getCreate/postCreate sudah dikunci di atas).
foreach (['orang_tua', 'siswa'] as $peran) {
    foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
        $restrict[$peran]['gerai.getUpdate'][] = $act;
        $restrict[$peran]['gerai.postUpdate'][] = $act;
        $restrict[$peran]['gerai.getDelete'][] = $act;
        $restrict[$peran]['gerai.postDelete'][] = $act;
        $restrict[$peran]['gerai.getShow'][] = $act;
    }
}
// POS hanya kasir/admin: halaman (kasir.pos) sudah dikunci, tapi POST /kasir/pos
// (module kasir.postPos) belum — vendor/ortu/siswa bisa tembak pembelian langsung.
foreach (['vendor', 'orang_tua', 'siswa'] as $peran) {
    $restrict[$peran]['kasir.postPos'][] = 'pos';
}
// Produk: orang_tua/siswa tidak boleh sama sekali — kunci rute yang belum terdaftar
// (getTable/getCreate sudah dikunci di atas; update/delete/show lolos via ganti ID).
foreach (['orang_tua', 'siswa'] as $peran) {
    foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
        $restrict[$peran]['produk.getUpdate'][] = $act;
        $restrict[$peran]['produk.postUpdate'][] = $act;
        $restrict[$peran]['produk.getDelete'][] = $act;
        $restrict[$peran]['produk.postDelete'][] = $act;
        $restrict[$peran]['produk.getShow'][] = $act;
        $restrict[$peran]['produk.postCreate'][] = $act;
    }
}
// Penarikan: orang_tua/siswa/kasir tidak boleh sentuh — yang boleh hanya admin + vendor
// (vendor dibatasi milik sendiri via controller). Kunci update/delete/show langsung.
foreach (['orang_tua', 'siswa', 'kasir_sekolah'] as $peran) {
    foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
        $restrict[$peran]['penarikan.getUpdate'][] = $act;
        $restrict[$peran]['penarikan.postUpdate'][] = $act;
        $restrict[$peran]['penarikan.getDelete'][] = $act;
        $restrict[$peran]['penarikan.postDelete'][] = $act;
        $restrict[$peran]['penarikan.getShow'][] = $act;
        $restrict[$peran]['penarikan.getCreate'][] = $act;
        $restrict[$peran]['penarikan.postCreate'][] = $act;
    }
}
// Pembagian getShow (JSON satu record, tanpa scope) — vendor hanya via table;
// orang_tua/siswa tidak boleh sama sekali.
$restrict['vendor']['pembagian.getShow'][] = 'show';
foreach (['orang_tua', 'siswa'] as $peran) {
    foreach (['table', 'save', 'create', 'update', 'delete', 'show', 'bagi'] as $act) {
        $restrict[$peran]['pembagian.getUpdate'][] = $act;
        $restrict[$peran]['pembagian.postUpdate'][] = $act;
        $restrict[$peran]['pembagian.getDelete'][] = $act;
        $restrict[$peran]['pembagian.postDelete'][] = $act;
        $restrict[$peran]['pembagian.getShow'][] = $act;
        $restrict[$peran]['pembagian.getCreate'][] = $act;
        $restrict[$peran]['pembagian.postCreate'][] = $act;
    }
}

return $restrict;
