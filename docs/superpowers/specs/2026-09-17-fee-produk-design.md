# Fee Produk Key-Value (Persen) — Design

Tanggal: 2026-09-17. Status: disetujui user, siap ke implementation plan.

## Tujuan

Setiap produk terjual dipotong fee. Fee bebas banyak, key-value (`nama → persen`),
diatur di `/fee-config/table`. Total persen = jumlah semua nilai, dipotong dari harga produk.

## Keputusan yang sudah dikunci user

- Bentuk nilai: persen (bukan nominal, bukan campuran).
- Fee flat nominal lama: dihapus.
- Berlaku seragam untuk semua produk dari config (vendor tidak set fee sendiri).
- Contoh: config berisi 4 fee total 3% → tiap produk terjual dipotong 3%.

## Arsitektur

Tabel `fee` proper (satu row per jenis fee): `code_fee` (key unik, misal `kebersihan`),
`nama_fee` (label), `value_fee` (persen). Saat transaksi, jumlahkan semua persen,
hitung nominal per komponen (`round(subtotal × persen/100)`), simpan total + rincian
JSON di transaksi. Kolom fee lama di `transaksi` dipertahankan hanya untuk riwayat
(fallback baca). `fee_config` tersisa untuk `fee_min_topup` (alur topup tidak berubah).

Alternatif yang ditolak: kolom fee per produk (maunya seragam), satu JSON di config aktif
(user minta tabel database proper), konversi nominal lama ke persen (tidak ada pemetaan
yang benar).

## Komponen

### 1. Migrasi

- Baru: tabel `fee` (`fee_id`, `code_fee` string unik, `nama_fee` string,
  `value_fee` decimal(5,2) default 0, timestamps).
- `fee_config`: drop `fee_sistem`, `fee_kebersihan`, `fee_keamanan`, `fee_pengelolaan`.
  `fee_min_topup`, `fee_aktif` tetap (dipakai alur topup).
- `transaksi`: tambah `transaksi_fee_total` unsigned big int default 0,
  `transaksi_fee_rincian` JSON nullable.
- Tidak ada backfill persen (tabel fee awal kosong = 0%, admin isi sendiri).

### 2. `Fee` model (baru) + `FeeConfig` model (susut)

- `Fee`: fillable `code_fee`, `nama_fee`, `value_fee`; casts `value_fee => float`;
  rules `code_fee => required|string|max:30|unique`, `nama_fee => required|string|max:100`,
  `value_fee => required|numeric|min:0|max:100`; `field_name()` = `nama_fee`;
  `$filterColumns`/`$sortColumns` = ketiga kolom; helper static `totalPersen(): float`
  dan `rincian(int $subtotal): array` (`[code => nominal]`, skip nilai 0).
- Wajib: `FeePolicy` + `FeeController` (ControllerTrait) + `Route::auto('/fee', ...)` +
  entry menu + permission admin-only (tanpa policy semua request 403).
- `FeeConfig`: fillable/rules susut ke `fee_min_topup`, `fee_aktif`; views generik
  otomatis ikut (tidak ada custom UI — CRUD standar cukup karena sudah tabel proper).

### 3. `Transaksi` model

- Fillable + casts (`transaksi_fee_total => integer`, `transaksi_fee_rincian => array`).
- Helper `feeTotal(): int` = `transaksi_fee_total` jika > 0 atau rincian ada,
  else fallback jumlah 4 kolom lama (untuk riwayat).
- Helper `feeRincian(): array` = rincian JSON, else fallback
  `['kebersihan' => ..., 'keamanan' => ..., 'pengelolaan' => ..., 'sistem' => ...]`
  dari kolom lama yang > 0.

### 4. `ProcessPurchaseAction`

- Ganti `FeeConfig::aktif()` flat → ambil `Fee::rincian($total)` (array code → nominal),
  `$feeTotal = array_sum(...)`. Fee kosong = tanpa potongan (tidak throw).
- Cek saldo/limit, bersih, `SplitDana`, tambah `gerai_saldo`: tidak berubah (konsumsi nominal).
- `Transaksi::create` tambah `transaksi_fee_total`, `transaksi_fee_rincian`;
  4 kolom lama diisi 0 (eksplisit, bukan default ambigu).
- Fee 0% (config kosong): tanpa potongan, rincian `[]`.

### 5. Pembaca fee (pakai helper `feeTotal()`/`feeRincian()`)

- `TransaksiController::getUpdate` + `pages/transaksi/detail.blade.php`: rincian dari
  `feeRincian()`, total dari `feeTotal()`.
- `PembagianController::getBagi/postBagi` (penjumlahan fee harian): pakai `feeTotal()`.
- `LaporanController` bila membaca kolom fee lama: alihkan ke `feeTotal()`.
- `KasirController::getStruk` bila tampil fee: sama.

### 6. UI fee (CRUD standar) + `fee-config` susut

- Halaman fee baru (`/fee/table`, `/fee/form` dari template generik users): tabel
  code/nama/value, form tambah-ubah biasa — tambah jenis fee = tambah row.
- `fee-config` tinggal atur min topup; policy/menu fee-config tidak berubah.
- Policy/menu/permission untuk modul `fee`: admin-only (ikuti pola `fee-config` existing).

## Data flow

Admin isi fee di fee-config → kasir jual → action hitung rincian dari persen × subtotal →
simpan total + rincian → saldo kartu terpotong total + fee, gerai terima bersih → detail
transaksi/pembagian/laporan baca via helper (fallback otomatis untuk data lama).

## Error handling

- Tabel fee kosong → tanpa potongan (tidak error).
- `value_fee` > 100 → tolak validasi max:100; `code_fee` duplikat → tolak unique.
- Produk tutup/keranjang kosong → pesan existing.

## Testing (Pest)

1. Fee 3% dari 2 row (1% + 2%): nominal per komponen + total benar, `gerai_saldo` = subtotal − fee.
2. Fee kosong: tanpa potongan, rincian `[]`, kolom lama 0.
3. Fallback: transaksi lama (rincian null, kolom lama terisi) → helper baca jumlah lama.
4. Alur kartu (saldo/limit/idempotency) tidak berubah.
5. CRUD fee: tolak persen > 100 dan code duplikat; vendor 403 (policy baru).

## Di luar scope (YAGNI)

- Fee beda per produk/gerai, fee nominal, tabel fee terpisah, migrasi konversi nominal→persen.
