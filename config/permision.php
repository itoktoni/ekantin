<?php

$restrict = [];

// $restrict['user']['product'][] = 'show';

// E-Kanteen: terdaftar = ditolak (BasePolicy).
// Vendor BOLEH POS (jual produk gerai sendiri) — yang tetap ditolak: kartu CRUD, top up, user, CMS, settings.
$restrict['vendor']['kartu.getTable'][] = 'table';
$restrict['vendor']['fee-config.getTable'][] = 'table';
$restrict['vendor']['audit-log.getTable'][] = 'table';
// Vendor tidak boleh top up (tunai/web): halaman + POST + status + konfirmasi.
foreach (['tunai','web','webbayar','postTunai','postWeb','webstatus'] as $act) {
    $restrict['vendor']['topup.tunai'][] = $act;
    $restrict['vendor']['topup.postTunai'][] = $act;
    $restrict['vendor']['topup.web'][] = $act;
    $restrict['vendor']['topup.postWeb'][] = $act;
    $restrict['vendor']['topup.web.status'][] = $act;
    $restrict['vendor']['topup.web.bayar'][] = $act;
}
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
// kasir_sekolah HANYA top up kartu (tunai + web/QRIS status & konfirmasi): hide SEMUA modul lain
// (produk, kasir POS, pesanan, transaksi, kartu CRUD, gerai CRUD, user, CMS, settings, laporan, penarikan/pembagian/fee)
// kartu.getSearch (picker kasir di halaman tunai) & kartu.getAnak/table-read? — search tetap boleh agar picker jalan;
// topup.* & topup.web.* sengaja TIDAK di-deny agar halaman tunai/web + POST + polling status + tombol konfirmasi jalan.
$restrict['kasir_sekolah']['fee-config.getTable'][] = 'table';
// penarikan.getTable BOLEH utk kasir — daftar pengajuan gerai yang harus ditukar (penukaran uang).
foreach (['table','save','create','update','delete','show','pesanan','pesananCetak','pos','struk','bagi','tunai','web','webbayar','search'] as $act) {
    // POS sentral — kasir tidak boleh (vendor yang jualan via POS gerainya sendiri)
    $restrict['kasir_sekolah']['kasir.pos'][] = $act;
    $restrict['kasir_sekolah']['kasir.postPos'][] = $act;
    $restrict['kasir_sekolah']['kasir.struk'][] = $act;
    // kelola produk — kasir tidak boleh
    $restrict['kasir_sekolah']['produk.getTable'][] = $act;
    $restrict['kasir_sekolah']['produk.getCreate'][] = $act;
    $restrict['kasir_sekolah']['produk.postCreate'][] = $act;
    $restrict['kasir_sekolah']['produk.getUpdate'][] = $act;
    $restrict['kasir_sekolah']['produk.postUpdate'][] = $act;
    $restrict['kasir_sekolah']['produk.getDelete'][] = $act;
    $restrict['kasir_sekolah']['produk.postDelete'][] = $act;
    $restrict['kasir_sekolah']['produk.getShow'][] = $act;
    // pesanan gerai — kasir tidak boleh
    $restrict['kasir_sekolah']['gerai.getPesanan'][] = $act;
    $restrict['kasir_sekolah']['gerai.postPesanan'][] = $act;
    $restrict['kasir_sekolah']['gerai.getPesananCetak'][] = $act;
    // transaksi — kasir tidak boleh (reprint pun tidak)
    $restrict['kasir_sekolah']['transaksi.getTable'][] = $act;
    $restrict['kasir_sekolah']['transaksi.getCreate'][] = $act;
    $restrict['kasir_sekolah']['transaksi.postCreate'][] = $act;
    $restrict['kasir_sekolah']['transaksi.getUpdate'][] = $act;
    $restrict['kasir_sekolah']['transaksi.postUpdate'][] = $act;
    // pembagian: kasir BOLEH (pelaku Bagi Harian) — lihat blok pembagian di bawah
    // kartu pengguna — hide
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
$restrict['pengguna']['fee-config.getTable'][] = 'table';
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

// pengguna hanya boleh: dashboard, kartu sendiri, transaksi sendiri, laporan sendiri, topup.web
foreach (['table','save','create','update','delete','show','pesanan','pos','struk'] as $act) {
    $restrict['pengguna']['gerai.getTable'][] = $act;
    $restrict['pengguna']['gerai.getCreate'][] = $act;
    $restrict['pengguna']['produk.getTable'][] = $act;
    $restrict['pengguna']['penarikan.getTable'][] = $act;
    $restrict['pengguna']['user.getTable'][] = $act;
    foreach (['cms-type','field','section','content','category','tag','menu'] as $cms) $restrict['pengguna'][$cms.'.getTable'][] = $act;
    $restrict['pengguna']['settings.website'][] = $act;
    $restrict['pengguna']['audit-log.getTable'][] = $act;
    $restrict['pengguna']['topup.tunai'][] = $act;
    $restrict['pengguna']['kasir.pos'][] = $act;
}
// POS: vendor BOLEH (jual produk gerai sendiri via Kasir POS) — yang ditolak hanya ortu/pengguna.
// Kasir justru TIDAK boleh POS (hanya top up) — deny kasir.pos/postPos/struk di blok kasir di atas.
foreach (['orang_tua', 'pengguna'] as $peran) {
    $restrict[$peran]['kasir.pos'][] = 'pos';
    $restrict[$peran]['kasir.struk'][] = 'struk';
}

// Pool pesanan gerai (via Route::auto GeraiController::getPesanan/postPesanan + cetak ulang per gerai): vendor + admin boleh.
// kasir TIDAK boleh (hanya top up) — deny kasir ada di blok kasir di atas.
foreach (['orang_tua', 'pengguna'] as $peran) {
    $restrict[$peran]['gerai.getPesanan'][] = 'pesanan';
    $restrict[$peran]['gerai.postPesanan'][] = 'pesanan';
    $restrict[$peran]['gerai.getPesananCetak'][] = 'pesananCetak';
}
// Topup QRIS konfirmasi hanya kasir/admin — orang tua/pengguna/vendor tidak boleh flag berhasil
foreach (['orang_tua','pengguna','vendor'] as $peran) {
    $restrict[$peran]['topup.web.bayar'][] = 'webbayar';
}
// Pembagian harian: kasir BOLEH (pelaku bagi — getTable/getBagi/postBagi);
// vendor hanya lihat history (table); orang tua/pengguna tidak boleh sama sekali.
foreach (['orang_tua','pengguna'] as $peran) {
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
foreach (['kasir_sekolah','vendor','orang_tua','pengguna'] as $peran) {
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
// Transaksi read-only untuk SEMUA role: hapus baris tanpa reversal merusak
// kartu_saldo/gerai_saldo (lihat RefundAction untuk pembatalan yang benar).
// Deny 'delete' di getTable menyembunyikan SEMUA tombol delete (checkbox,
// ikon baris, bulk bar); controller tetap abort 404 sebagai lapis kedua.
foreach (['user','editor','admin','developer','super_admin','kasir_sekolah','vendor','orang_tua','pengguna'] as $peran) {
    $restrict[$peran]['transaksi.getTable'][] = 'delete';
    $restrict[$peran]['transaksi.getDelete'][] = 'delete';
    $restrict[$peran]['transaksi.postDelete'][] = 'delete';
}
// Pembagian tidak punya form edit (snapshot hitungan) — sembunyikan ikon edit
// di SEMUA role agar tidak ada tombol rusak ke view yang tidak ada.
foreach (['user','editor','admin','developer','super_admin','kasir_sekolah','vendor','orang_tua','pengguna'] as $peran) {
    $restrict[$peran]['pembagian.getTable'][] = 'update';
    $restrict[$peran]['pembagian.getUpdate'][] = 'update';
    $restrict[$peran]['pembagian.postUpdate'][] = 'update';
    $restrict[$peran]['pembagian.getCreate'][] = 'create';
    $restrict[$peran]['pembagian.postCreate'][] = 'create';
}
// User management: vendor/kasir/orang_tua/pengguna tidak boleh sama sekali (lihat, buat, ubah, hapus).
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
foreach (['vendor', 'kasir_sekolah', 'orang_tua', 'pengguna'] as $peran) {
    foreach ($userModules as $mod) {
        foreach (['table', 'save', 'create', 'update', 'delete', 'show', 'boot'] as $act) {
            $restrict[$peran][$mod][] = $act;
        }
    }
}
// Gerai: hapus hanya admin — vendor/kasir/orang_tua/pengguna tidak boleh delete (langsung via URL maupun bulk).
// NOTE: deny-list per route-name — gerai.getDelete/postDelete yang tidak terdaftar = ALLOW,
// sehingga GET /gerai/delete/7 lolos walau tombol di table disembunyikan. Kunci backend + UI (getTable).
foreach (['vendor', 'kasir_sekolah', 'orang_tua', 'pengguna'] as $peran) {
    foreach (['delete'] as $act) {
        $restrict[$peran]['gerai.getDelete'][] = $act;
        $restrict[$peran]['gerai.postDelete'][] = $act;
        $restrict[$peran]['gerai.getTable'][] = $act;
    }
}
// IDOR satu keluarga: orang_tua/pengguna tidak boleh sentuh gerai sama sekali — kunci juga
// update/show via ganti ID langsung (getTable/getCreate/postCreate sudah dikunci di atas).
foreach (['orang_tua', 'pengguna'] as $peran) {
    foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
        $restrict[$peran]['gerai.getUpdate'][] = $act;
        $restrict[$peran]['gerai.postUpdate'][] = $act;
        $restrict[$peran]['gerai.getDelete'][] = $act;
        $restrict[$peran]['gerai.postDelete'][] = $act;
        $restrict[$peran]['gerai.getShow'][] = $act;
    }
}
// POST /kasir/pos (module kasir.postPos): vendor BOLEH (jualan gerai sendiri, scope di controller),
// ortu/pengguna ditolak. Kasir ditolak via blok kasir di atas (hanya top up).
foreach (['orang_tua', 'pengguna'] as $peran) {
    $restrict[$peran]['kasir.postPos'][] = 'pos';
}
// Produk: orang_tua/pengguna tidak boleh sama sekali — kunci rute yang belum terdaftar
// (getTable/getCreate sudah dikunci di atas; update/delete/show lolos via ganti ID).
foreach (['orang_tua', 'pengguna'] as $peran) {
    foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
        $restrict[$peran]['produk.getUpdate'][] = $act;
        $restrict[$peran]['produk.postUpdate'][] = $act;
        $restrict[$peran]['produk.getDelete'][] = $act;
        $restrict[$peran]['produk.postDelete'][] = $act;
        $restrict[$peran]['produk.getShow'][] = $act;
        $restrict[$peran]['produk.postCreate'][] = $act;
    }
}
// Penarikan: orang_tua/pengguna/kasir tidak boleh CRUD manual — kasir hanya getTable + postTukar/postBatal
// (penukaran uang); vendor hanya ajukan/batal milik sendiri (scope via controller).
foreach (['orang_tua', 'pengguna', 'kasir_sekolah'] as $peran) {
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
// Penukaran uang (penagihan harian gerai → kasir):
// - postTukar (ability 'tukar') HANYA kasir/admin — vendor tidak boleh menukar sendiri.
// - getAjukan/postAjukan (ability 'ajukan') vendor + admin; kasir ditolak (kasir menerima, tidak mengajukan).
// - postBatal (ability 'batal') vendor (milik sendiri)/kasir/admin; orang tua/pengguna ditolak semua.
$restrict['vendor']['penarikan.postTukar'][] = 'tukar';
$restrict['kasir_sekolah']['penarikan.getAjukan'][] = 'ajukan';
$restrict['kasir_sekolah']['penarikan.postAjukan'][] = 'ajukan';
foreach (['orang_tua', 'pengguna'] as $peran) {
    $restrict[$peran]['penarikan.getAjukan'][] = 'ajukan';
    $restrict[$peran]['penarikan.postAjukan'][] = 'ajukan';
    $restrict[$peran]['penarikan.postTukar'][] = 'tukar';
    $restrict[$peran]['penarikan.postBatal'][] = 'batal';
}
// Modul fee: admin saja — vendor/kasir/orang_tua/pengguna ditolak total.
foreach (['vendor', 'kasir_sekolah', 'orang_tua', 'pengguna'] as $peran) {
    foreach (['fee.getTable', 'fee.getCreate', 'fee.postCreate', 'fee.getUpdate', 'fee.postUpdate', 'fee.getDelete', 'fee.postDelete', 'fee.getShow'] as $mod) {
        foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
            $restrict[$peran][$mod][] = $act;
        }
    }
}
// Picker kartu (search di halaman POS vendor + halaman topup kasir): vendor/kasir/admin saja — orang_tua/pengguna ditolak.
foreach (['orang_tua', 'pengguna'] as $peran) {
    foreach (['search', 'table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
        $restrict[$peran]['kartu.getSearch'][] = $act;
    }
}
// Pembagian getShow (JSON satu record, tanpa scope) — vendor hanya via table;
// orang_tua/pengguna tidak boleh sama sekali.
$restrict['vendor']['pembagian.getShow'][] = 'show';
foreach (['orang_tua', 'pengguna'] as $peran) {
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
