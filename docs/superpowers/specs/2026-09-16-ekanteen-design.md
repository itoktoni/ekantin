# Desain Sistem e-Kanteen Sekolah — 2026-09-16

> Keputusan user: (1) topup web + notifikasi = simulasi + log dulu (tanpa gateway asli);
> (2) identitas = semua-auth-di-User (tanpa model Siswa terpisah); (3) 100% ikut `AGENTS.md`.

## 1. Arsitektur

Pendekatan A — Full AGENTS CRUD + Actions uang. Tiap entitas: Model (`BaseModel`,
kolom `module_field`, relasi prefix `has`) + Property Entity + Policy + Controller
(`ControllerTrait`) + `Route::auto` + views tiru `pages/users/table+form`.
Logika uang hanya di `app/Actions/Ekantin/`. POS & laporan = method custom
`getPos/postPos`, `getLaporan/getEkspor` + route manual. POS = Blade + Alpine
(keranjang lokal, 1 POST final), keyboard-only, struk + WA async via queue.

## 2. Auth & peran

Tetap tabel `users` (Fortify). `RoleEnum` ditambah:
`SUPER_ADMIN`, `KASIR_SEKOLAH`, `VENDOR`, `ORANG_TUA`, `SISWA`
(bensampo + `EnumTrait`, `getDescription()` via `match`).
Profil anak + dompet menyatu di `kartu` (1 user siswa ↔ 1 kartu aktif);
ortu = User role `ORANG_TUA` di-link `kartu_id_orangtua`.

## 3. Data model (kolom selalu `module_field`)

- `kartu` (pk `kartu_id`): `kartu_barcode` UK, `kartu_id_user` FK users (siswa),
  `kartu_id_orangtua` FK users nullable, `kartu_nis`, `kartu_kelas`,
  `kartu_saldo` int ≥0 default 0, `kartu_status` aktif/nonaktif,
  `kartu_limit_harian` nullable (null = unlimited).
- `gerai` (pk `gerai_id`): `gerai_nama`, `gerai_id_vendor` FK users,
  `gerai_saldo` int default 0, `gerai_status`.
- `produk` (pk `produk_id`): `produk_id_gerai` FK, `produk_nama`,
  `produk_harga` int >0, `produk_status` tersedia/tidak, `produk_foto` nullable path.
- `transaksi` (pk `transaksi_id`): `transaksi_jenis`
  (topup_web/topup_tunai/beli/refund/koreksi/withdraw), `transaksi_status`,
  `transaksi_id_kartu`, `transaksi_id_gerai` nullable, `transaksi_total`,
  `transaksi_fee_kebersihan`, `transaksi_fee_keamanan`, `transaksi_fee_pengelolaan`,
  `transaksi_fee_sistem`, `transaksi_bersih`, `transaksi_saldo_akhir`,
  `transaksi_limit_snapshot` nullable, `transaksi_idempotency` UK nullable,
  `transaksi_id_reversal_of` nullable, `transaksi_alasan` nullable,
  `transaksi_id_kasir` nullable (identitas kasir pemroses).
- `transaksi_item` (pk `item_id`): `item_id_transaksi` FK,
  snapshot `item_nama`, `item_harga`, `item_qty`, `item_subtotal`.
- `fee_config` (1 baris aktif): `fee_sistem`, `fee_kebersihan`, `fee_keamanan`,
  `fee_pengelolaan` (int ≥0), `fee_min_topup` default 10000.
- `fee_history`: nilai lama→baru + `fee_id_admin` + waktu.
- `penarikan` (pk `penarikan_id`): `penarikan_id_gerai`, `penarikan_nominal`,
  `penarikan_status` diajukan/diselesaikan/ditolak, `penarikan_bukti` path nullable,
  `penarikan_id_admin` nullable.
- `notifikasi_log`: `notif_id_transaksi`, `notif_saluran` (log),
  `notif_status`, `notif_percobaan`, `notif_payload` json, waktu.
- `audit_log`: `audit_aksi`, `audit_model`, `audit_id_record`,
  `audit_lama`/`audit_baru` json, `audit_id_user`, waktu.

Relasi (`has*`): Kartu `hasUser` (siswa), `hasOrangtua`, `hasTransaksis`;
Gerai `hasVendor`, `hasProduks`, `hasTransaksis`; Produk `hasGerai`;
Transaksi `hasKartu`, `hasGerai`, `hasItems`, `hasKasir`.

## 4. Enum (`App\Enums\Ekantin\`)

`KartuStatusEnum` (aktif/nonaktif), `ProdukStatusEnum` (tersedia/tidak),
`TransaksiJenisEnum`, `TransaksiStatusEnum` (berhasil/gagal/dibatalkan),
`PenarikanStatusEnum`, `NotifStatusEnum` (terkirim/gagal/menunggu),
`GeraiStatusEnum`. Semua bensampo + `EnumTrait`.

## 5. Actions (`app/Actions/Ekantin/`)

- `ProcessPurchaseAction`: `DB::transaction` + `lockForUpdate` urut
  kartu→gerai; cek kartu aktif; cek saldo ≥ total+fee; cek limit harian
  (SUM beli hari ini + total baru ≤ limit, reset alami per hari kalender);
  fee dari `fee_config` aktif (snapshot per transaksi, prospektif);
  insert transaksi + item + snapshot; update `kartu_saldo` & `gerai_saldo`;
  tulis `notifikasi_log`; dispatch job kirim. Idempotency key
  hash(barcode+gerai+total+menit) UNIQUE — retry kembalikan struk sama.
- `TopupTunaiAction`: peran kasir only; scan barcode → tampilkan siswa+saldo;
  tambah saldo + catat `transaksi_id_kasir`.
- `TopupWebAction`: validasi ≥ `fee_min_topup`; status simulasi confirmed
  (target ≤30 dtk); konfirmasi tampil nominal + saldo sebelum/sesudah.
- `RefundAction`: reversal-only (baris baru link `transaksi_id_reversal_of`,
  alasan wajib); tak pernah delete; koreksi saldo kartu/vendor/fee via jurnal balik.
- `WithdrawAction`: hanya dari `gerai_saldo` settled; status diajukan→diselesaikan;
  bukti transfer; lock batch anti double-claim.
- `EksporLaporanAction`: CSV + XLSX sesuai filter aktif (target ≤30 dtk).

## 6. Controller & route

CRUD `Route::auto`: `KartuController`, `GeraiController`, `ProdukController`
(`getData()` scoped gerai milik vendor + Policy), `TransaksiController`
(read-only table + show), `FeeConfigController` (super admin only, tulis
`fee_history`), `PenarikanController`, `NotifikasiLogController` (read-only),
`AuditLogController` (read-only). Custom + route manual:
`KasirController@getPos/postPos`, `TopupController@getTunai/postTunai`,
`TopupController@getWeb/postWeb`, `LaporanController@getIndex/getEkspor`,
`KartuController@getCetak` (barcode `milon/barcode`, massal per kelas).
`share()` untuk opsi enum (`KartuStatusEnum::getOptions()` dsb.) dan
opsi user/siswa/gerai (`::getOptions()` via `OptionTrait`).

## 7. Views (`resources/views/pages/{module}/`)

Tiru `pages/users/table.blade.php` + `form.blade.php` persis:
filter dari `$filterColumns`, kolom dari `$sortColumns`, `field_primary`,
`<x-slot:mobile>`, `<x-pagination>`, action bar create/delete vs save,
hidden `.module` + `initTable`. Form kartu: barcode, siswa & ortu (select),
NIS, kelas, status, limit (saldo read-only, diubah hanya via topup/transaksi).
Form produk: nama, harga, status, foto (`x-file` + trait aliasing upload).
POS view custom ringan (satu input autofocus + Alpine cart + F9 bayar).
Dashboard ortu: ringkas saldo, pengeluaran hari ini vs limit, 10 terakhir,
tombol topup (MS 6.5 PRD). Laporan: filter tanggal/siswa/gerai/status;
vendor hanya gerainya, tanpa fee sistem gerai lain.

## 8. Notifikasi & topup simulasi

`NotificationChannelFactory::create('log')` + baris `notifikasi_log`;
job queue retry 3x jeda ≥30 dtk; isi beli (siswa, gerai, item, total, saldo akhir),
topup (nominal + saldo). Log status/waktu/saluran per Req 8.
Payment gateway diganti status simulasi `confirmed`; interface disiapkan agar
Midtrans/Xendit bisa dipasang belakangan tanpa ubah skema.

## 9. Keamanan & audit

Fortify login tetap; RBAC via Policy + `config/permision.php`
(tanpa policy = 403); topup tunai & fee config dibatasi peran;
kunci akun 5x/15 mnt + timeout 30 mnt ikut config auth/session
(ditambah bila belum ada); password hashed; `audit_log` untuk
akun/fee/limit/refund/penarikan.

## 10. Testing

Pest per Action: saldo cukup/kurang, kartu nonaktif ditolak, limit terlampaui
+ sisa limit, idempotency ganda 1 struk, fee prospektif (riwayat lama utuh),
refund reversal, vendor scoping, ekspor CSV+XLSX. Plus `composer lint:check`.

## 11. Fase bangun

1. kartu (+barcode cetak), 2. gerai+produk, 3. topup tunai+web,
4. POS+fee+limit, 5. notifikasi log+retry, 6. laporan+ekspor+penarikan+refund,
7. fee config+history+audit, 8. peran/menu/permision+seed, 9. Pest.

## 12. Self-review

- Placeholder: tidak ada TBD; threshold konkret (30/5/60/10 dtk, 3x retry,
  5x/15 mnt, 30 mnt, min topup 10000) ikut PRD/requirements.
- Konsistensi: tanpa model Siswa → semua referensi siswa = `kartu`/`users`;
  saldo hanya berubah via Actions; harga/fee selalu snapshot.
- Scope: satu spec utuh e-kanteen; dipecah ke plan per fase oleh writing-plans.
- Ambiguitas: "kasir gerai" = akun vendor (ikut PRD §asumsi); limit reset alami
  per hari kalender (tanpa cron); XLSX bila paket excel tersedia, else CSV dulu.

## 13. Revisi: POS sentral (atas permintaan user)

- 1 keranjang campur semua gerai buka, bayar sekali di kasir (`KasirController`),
  struk dikelompokkan per gerai untuk diambil siswa (`kasir.struk`).
- `transaksi_item.item_id_gerai` mencatat asal gerai tiap item;
  `transaksi.transaksi_id_gerai` null untuk beli (dipakai hanya untuk withdraw).
- Fee flat/transaksi dibagi proporsional per gerai via `SplitDana::bagi()`
  (total pas, sisa pembulatan ke gerai terakhir); dipakai beli + refund.
- Scope vendor (tabel transaksi, laporan) via `hasItems.item_id_gerai`;
  ekspor/laporan tulis multi-gerai; POS ditolak untuk vendor/ortu/siswa.
