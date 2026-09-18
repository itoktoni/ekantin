# POS Walk-in + Pilih Kartu Siswa — Design

Tanggal: 2026-09-17. Status: disetujui user, siap ke implementation plan.

## Tujuan

Kasir sekolah di `/kasir/pos` bisa melayani dua tipe pembeli:

1. **Kartu siswa** (existing, tetap jalan) — scan barcode atau **search + pilih** (nama/NIS/barcode).
2. **Walk-in** (baru) — pembeli tanpa kartu, bayar **tunai** atau **QRIS**, fee kantin tetap dipotong.

## Keputusan yang sudah dikunci user

- Walk-in: cash dan QRIS (bukan bon).
- Fee (kebersihan/keamanan/pengelolaan/sistem): tetap dipotong via `SplitDana`, sama seperti kartu.
- Picker kartu: search + pilih (bukan scan saja).

## Arsitektur (pendekatan A: kolom metode di transaksi)

Satu tabel `transaksi`, tanpa tabel baru, tanpa kartu dummy. Walk-in = `transaksi_id_kartu NULL`
(kolom sudah nullable di migrasi `2026_09_16_000004`) + kolom baru `transaksi_metode`.

Alternatif yang ditolak: kartu "tamu" dummy (merusak saldo/limit), tabel walk-in terpisah (duplikasi).

## Komponen

### 1. Migrasi: `transaksi_metode`

- `string('transaksi_metode', 10)->default('kartu')`, nilai: `kartu` | `tunai` | `qris`.
- Backfill: semua row existing = `kartu`.

### 2. `ProcessPurchaseAction::handle()` — cabang walk-in

Input tambah: `metode` (`kartu` default), `kartu_barcode` opsional (wajib hanya jika `metode=kartu`).

- `metode=kartu`: alur identik seperti sekarang (cek kartu aktif, idempotency, saldo, limit harian, potong saldo).
- `metode=tunai|qris`: lewati seluruh blok kartu (tidak ada cek saldo/limit, tidak ada update `kartu_saldo`,
  `transaksi_id_kartu = null`, `transaksi_saldo_akhir = null`, `transaksi_limit_snapshot = null`).
- Sama untuk keduanya: validasi produk tersedia + gerai buka (dengan lock), hitung total, `SplitDana` fee,
  `Transaksi::create` (tambah `transaksi_metode`), create items `baru`, increment `gerai_saldo`,
  create notif log, dispatch `KirimNotifikasiJob`, idempotency check tetap jalan.
- `Transaksi::rules()`: tambah `transaksi_metode => required|in:kartu,tunai,qris`.

### 3. `KasirController`

- `getPos`: tidak berubah (tetap kirim `kelompok`, `kategori`, `idempotency`).
- `postPos`: validasi menyesuaikan —
  `metode` required `in:kartu,tunai,qris`; `kartu_barcode` required hanya jika `metode=kartu`
  (`required_if:metode,kartu`); teruskan `metode` ke `ProcessPurchaseAction`.
- Baru `getSearchKartu`: `GET /kartu/search?q=` (kasir/admin saja via policy), cari
  `kartu_barcode`/`kartu_nis`/nama user (LIKE, limit 10, hanya `aktif`), return JSON
  `[{id, barcode, nis, nama, saldo}]`. Saldo ditampilkan agar kasir tahu cukup tidaknya.

### 4. View `pages/kasir/pos.blade.php`

- Toggle mode "Kartu siswa / Walk-in" di atas form (radio/chip, default kartu).
- Mode kartu: input scan seperti sekarang + tombol/ikon search → panel hasil AJAX dari `/kartu/search`
  → klik hasil mengisi `kartu_barcode` (tampilkan nama + saldo terpilih).
- Mode walk-in: input scan disembunyikan (tidak `required`), muncul pilihan Tunai/QRIS (radio cards).
- Validasi submit JS: mode kartu wajib barcode terisi; mode walk-in wajib pilih metode.
- Hidden input `metode` ikut ter-submit; item/keranjang/filter tidak berubah.
- `pages/kasir/struk.blade.php`: tweak kecil — jika `hasKartu` null, tampilkan label
  "Walk-in (Tunai/QRIS)" dari `transaksi_metode` sebagai pengganti nama/nis siswa.

### 5. Policy (`config/permision.php`)

- `kartu.search` (route `kartu.getSearch`): tolak `vendor`, `orang_tua`, `siswa`
  (deny-list per route-name, konsisten dengan pola existing).
- `kasir.postPos` untuk `metode=tunai|qris`: kasir/admin sudah boleh `pos`; tidak ada perubahan role.

## Data flow

- Kartu: POS form → `postPos` (metode=kartu + barcode) → `ProcessPurchaseAction` (potong saldo,
  split fee) → redirect struk. Tidak berubah.
- Walk-in: POS form → `postPos` (metode=tunai/qris, tanpa barcode) → `ProcessPurchaseAction`
  (tanpa sentuh kartu, split fee tetap) → redirect struk. Struk tampil "Walk-in (Tunai/QRIS)".
- Search: input search → `kartu.getSearch` → isi barcode + tampilkan nama/saldo.

## Error handling

- Walk-in tanpa pilih metode → validasi 422 + pesan inline (mode walk-in).
- Kartu tidak ditemukan / nonaktif / saldo kurang / limit → pesan existing via flash (tidak berubah).
- Produk tutup di tengah jalan → pesan existing "Gerai ... sedang tutup".
- Double-submit → idempotency existing (return transaksi yang sudah ada).

## Testing (Pest)

1. Beli kartu sukses seperti biasa (saldo terpotong, fee ter-split, `metode=kartu`).
2. Walk-in tunai tanpa barcode: sukses, `transaksi_id_kartu` null, `metode=tunai`,
   saldo kartu tak tersentuh, `gerai_saldo` nambah sebesar bersih (total − fee).
3. Walk-in QRIS: sama dengan `metode=qris`.
4. Walk-in tetap kena fee: `transaksi_fee_*` terisi, bersih = total − fee.
5. Walk-in tolak produk tutup / keranjang kosong.
6. Search kartu: kasir dapat hasil; vendor/ortu/siswa 403.
7. `postPos` tanpa barcode + metode kartu → 422; tanpa metode → default kartu (422 barcode).

## Di luar scope (YAGNI)

- Integrasi QRIS otomatis (QR dinamis/API): QRIS di sini hanya pencatatan metode.
- Struk terpisah untuk walk-in: pakai struk existing + label metode.
- Limit/hutang walk-in: tidak ada.
