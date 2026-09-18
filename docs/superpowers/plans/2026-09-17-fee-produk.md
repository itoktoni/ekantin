# Fee Produk Key-Value (Persen) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fee penjualan jadi tabel `fee` bebas-banyak (code/nama/persen), dipotong per produk terjual; fee flat nominal dihapus.

**Architecture:** Tabel `fee` baru + CRUD generik admin-only; `ProcessPurchaseAction` hitung nominal dari persen × subtotal dan simpan total + rincian JSON di transaksi; semua pembaca fee beralih ke helper `Transaksi::feeTotal()`/`feeRincian()` dengan fallback kolom lama untuk riwayat.

**Tech Stack:** Laravel 13, lorisleiva/laravel-actions, izniburak/laravel-auto-routes, Pest, Blade generik (pola `pages/users/`).

## Global Constraints

- PHP 8.3+ syntax, 4-space indentation, single-quoted strings (ikuti file existing).
- Setiap model baru WAJIB punya Policy (tanpa itu 403) + `Route::auto()` + entry menu + permission admin-only.
- Deny-list permission: route tak terdaftar = ALLOW, jadi modul `fee.*` harus dikunci untuk role rendah.
- Jangan ubah alur kartu/saldo/limit; hanya sumber angka fee yang berubah.
- Test runner: `php artisan test`. Migration: `php artisan migrate`.
- Tidak ada git repo di workspace ini — lewati semua step commit.

---

### Task 1: Migrasi — tabel `fee`, susut `fee_config`, kolom baru `transaksi`

**Files:**
- Create: `database/migrations/2026_09_17_000002_create_fee_table.php` (satu file berisi 3 operasi di bawah agar urutan aman)
- Test: verifikasi via `php artisan migrate` + cek skema

**Interfaces:**
- Consumes: tabel `fee_config`, `transaksi` existing
- Produces: tabel `fee`; `transaksi.transaksi_fee_total` + `transaksi_fee_rincian` untuk Task 4–5

- [ ] **Step 1: Buat file migrasi**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee', function (Blueprint $table) {
            $table->id('fee_id');
            $table->string('code_fee', 30)->unique();
            $table->string('nama_fee', 100);
            $table->decimal('value_fee', 5, 2)->default(0);
            $table->timestamps();
        });
        Schema::table('fee_config', function (Blueprint $table) {
            $table->dropColumn(['fee_sistem', 'fee_kebersihan', 'fee_keamanan', 'fee_pengelolaan']);
        });
        Schema::table('transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('transaksi_fee_total')->default(0)->after('transaksi_fee_sistem');
            $table->json('transaksi_fee_rincian')->nullable()->after('transaksi_fee_total');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['transaksi_fee_total', 'transaksi_fee_rincian']);
        });
        Schema::table('fee_config', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_sistem')->default(0);
            $table->unsignedBigInteger('fee_kebersihan')->default(0);
            $table->unsignedBigInteger('fee_keamanan')->default(0);
            $table->unsignedBigInteger('fee_pengelolaan')->default(0);
        });
        Schema::dropIfExists('fee');
    }
};
```

- [ ] **Step 2: Jalankan migrasi**

Run: `php artisan migrate`
Expected: `DONE` tanpa error.

---

### Task 2: Modul `fee` — Model + Policy + Controller + route + menu + permission + views

**Files:**
- Create: `app/Models/Fee.php`, `app/Policies/FeePolicy.php`, `app/Http/Controllers/FeeController.php`, `resources/views/pages/fee/table.blade.php`, `resources/views/pages/fee/form.blade.php`
- Modify: `routes/web.php` (tambah `Route::auto`), `config/menu.php` (tambah item Fee), `config/permision.php` (kunci role rendah)
- Test: Task 8 (CRUD + 403)

**Interfaces:**
- Consumes: `ControllerTrait`, `BasePolicy`, `GeneralRequest`, pola view `pages/users/table.blade.php` + `form.blade.php`
- Produces: `Fee::rincian(int $subtotal): array` (`[code => nominal]`), `Fee::totalPersen(): float` untuk Task 5; route `fee.*`

- [ ] **Step 1: Buat model `app/Models/Fee.php`**

```php
<?php

namespace App\Models;

class Fee extends BaseModel
{
    protected $table = 'fee';

    protected $primaryKey = 'fee_id';

    protected $fillable = [
        'code_fee',
        'nama_fee',
        'value_fee',
    ];

    public static $filterColumns = [
        'code_fee' => 'Kode',
        'nama_fee' => 'Nama',
    ];

    public static $sortColumns = [
        'code_fee',
        'nama_fee',
        'value_fee',
    ];

    protected function casts(): array
    {
        return [
            'value_fee' => 'float',
        ];
    }

    public static function field_name(): string
    {
        return 'nama_fee';
    }

    public function rules(): array
    {
        return [
            'code_fee' => 'required|string|max:30',
            'nama_fee' => 'required|string|max:100',
            'value_fee' => 'required|numeric|min:0|max:100',
        ];
    }

    public static function totalPersen(): float
    {
        return (float) static::query()->sum('value_fee');
    }

    public static function rincian(int $subtotal): array
    {
        $out = [];
        foreach (static::query()->orderBy('fee_id')->get() as $fee) {
            $nominal = (int) round($subtotal * (float) $fee->value_fee / 100);
            if ($nominal > 0) {
                $out[$fee->code_fee] = $nominal;
            }
        }

        return $out;
    }
}
```

(Catatan uniqueness `code_fee`: cek manual di controller saat create/update karena `UpdateAction`/`CreateAction` generik memakai `rules()` model — tambah rule `unique:fee,code_fee` hanya untuk create tidak dimungkinkan di satu `rules()`. Solusi: di `FeeController`, override `postCreate`/`postUpdate` validasi `code_fee` unik sebelum delegasi trait — lihat Step 3.)

- [ ] **Step 2: Buat policy `app/Policies/FeePolicy.php`**

```php
<?php

namespace App\Policies;

class FeePolicy extends BasePolicy {}
```

- [ ] **Step 3: Buat controller `app/Http/Controllers/FeeController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\AuditLog;
use App\Models\Fee;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    use ControllerTrait {
        postCreate as traitPostCreate;
        postUpdate as traitPostUpdate;
        postDelete as traitPostDelete;
    }

    public function __construct(Fee $model)
    {
        $this->model = $model::getModel();
    }

    private function validateCode(string $code, ?int $kecuali = null): void
    {
        if (! preg_match('/^[a-z0-9_\-]+$/', $code)) {
            throw ValidationException::withMessages(['code_fee' => 'Kode hanya huruf kecil, angka, underscore, dash.']);
        }
        $ada = Fee::where('code_fee', $code)->when($kecuali, fn ($q) => $q->where('fee_id', '!=', $kecuali))->exists();
        if ($ada) {
            throw ValidationException::withMessages(['code_fee' => 'Kode fee sudah dipakai.']);
        }
    }

    private function audit(string $aksi, $lama, $baru, $id): void
    {
        AuditLog::create([
            'audit_aksi' => $aksi,
            'audit_model' => 'fee',
            'audit_id_record' => $id,
            'audit_lama' => $lama,
            'audit_baru' => $baru,
            'audit_id_user' => auth()->id(),
        ]);
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->validateCode((string) $request->input('code_fee'));
        $response = $this->traitPostCreate($request);
        if ($response['status']) {
            $this->audit('create_fee', null, $request->only(['code_fee', 'nama_fee', 'value_fee']), $response['data']->fee_id ?? null);
        }

        return $this->response($response);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $lama = $this->model->findOrFail($id)->only(['code_fee', 'nama_fee', 'value_fee']);
        $this->validateCode((string) $request->input('code_fee', $lama['code_fee']), (int) $id);
        $response = $this->traitPostUpdate($request, $id);
        if ($response['status']) {
            $this->audit('update_fee', $lama, $this->model->findOrFail($id)->only(['code_fee', 'nama_fee', 'value_fee']), $id);
        }

        return $this->response($response);
    }

    public function postDelete(GeneralRequest $request)
    {
        $response = $this->traitPostDelete($request);
        if ($response['status']) {
            $this->audit('delete_fee', $request->input('ids'), null, null);
        }

        return $this->response($response);
    }
}
```

(Cek properti `AuditLog` fillable sebelum pakai: `audit_aksi`, `audit_model`, `audit_id_record`, `audit_lama`, `audit_baru`, `audit_id_user` — sama seperti dipakai `FeeConfigController` existing, jadi aman.)

- [ ] **Step 4: Daftarkan route di `routes/web.php`** (di bawah baris `Route::auto('/fee-config', ...)`)

```php
Route::auto('/fee', 'FeeController', ['name' => 'fee']);
```

- [ ] **Step 5: Tambah menu di `config/menu.php`** (grup 'Laporan & Fee', setelah Fee Config)

```php
['route' => 'fee.getTable', 'icon' => 'percent', 'label' => 'Fee Produk', 'match' => ['fee.*']],
```

- [ ] **Step 6: Kunci permission di `config/permision.php`** (sebelum `return $restrict;`)

```php
// Modul fee: admin saja — vendor/kasir/orang_tua/siswa ditolak total.
foreach (['vendor', 'kasir_sekolah', 'orang_tua', 'siswa'] as $peran) {
    foreach (['fee.getTable', 'fee.getCreate', 'fee.postCreate', 'fee.getUpdate', 'fee.postUpdate', 'fee.getDelete', 'fee.postDelete', 'fee.getShow'] as $mod) {
        foreach (['table', 'save', 'create', 'update', 'delete', 'show'] as $act) {
            $restrict[$peran][$mod][] = $act;
        }
    }
}
```

- [ ] **Step 7: Buat views generik** — copy `resources/views/pages/users/table.blade.php` → `resources/views/pages/fee/table.blade.php` dan `form.blade.php` → `resources/views/pages/fee/form.blade.php`, lalu:
  - table: kolom loop `$model::$sortColumns` tetap (generik, jalan otomatis); mobile card: ganti sel kolom jadi Kode/Nama/Value (`{{ $table->code_fee }}`, `{{ $table->nama_fee }}`, `{{ $table->value_fee }}%`).
  - form: input `code_fee` (text), `nama_fee` (text), `value_fee` (number step 0.01, helper "Persen 0–100") via `<x-input>` dalam `@bind`, tanpa `enctype` (tidak ada file).

- [ ] **Step 8: Verifikasi route + sintaks**

Run: `php -l app/Models/Fee.php; php -l app/Policies/FeePolicy.php; php -l app/Http/Controllers/FeeController.php; php -l config/permision.php; php artisan route:list --name=fee.getTable`
Expected: no syntax errors; route `fee/table` terdaftar.

---

### Task 3: Susutkan `FeeConfig` (model + controller + views)

**Files:**
- Modify: `app/Models/FeeConfig.php` (fillable/casts/rules/sortColumns), `app/Http/Controllers/FeeConfigController.php:16` (`$feeFields`), `resources/views/pages/feeconfig/form.blade.php` (hapus 4 input flat), `resources/views/pages/feeconfig/table.blade.php:54-56` (mobile card fee_sistem → min topup/aktif)
- Test: Task 8

**Interfaces:**
- Consumes: skema `fee_config` baru dari Task 1
- Produces: `FeeConfig::aktif()` tetap untuk `fee_min_topup` (dipakai `TopupWebAction`, `TopupController`)

- [ ] **Step 1: Update model** — fillable jadi `['fee_min_topup', 'fee_aktif']`; casts sisakan dua itu; rules sisakan dua itu; `$sortColumns` jadi `['fee_min_topup', 'created_at']`.

- [ ] **Step 2: Update controller** — `$feeFields` jadi `['fee_min_topup']`. (Loop `FeeHistory`/`AuditLog` tetap jalan untuk min_topup; casts integer aman.)

- [ ] **Step 3: Update form view** — hapus 4 `<x-input>` fee flat, sisakan `fee_min_topup` + `fee_aktif` toggle.

- [ ] **Step 4: Update mobile card table view** — ganti blok "Fee Sistem" jadi "Status" (`{{ $table->fee_aktif ? 'Aktif' : 'Nonaktif' }}`).

---

### Task 4: Helper fee di `Transaksi` + fillable/casts

**Files:**
- Modify: `app/Models/Transaksi.php` (fillable, casts, tambah 2 helper)
- Test: Task 8 (fallback + kolom baru)

**Interfaces:**
- Consumes: kolom `transaksi_fee_total`, `transaksi_fee_rincian` dari Task 1
- Produces: `feeTotal(): int`, `feeRincian(): array` untuk Task 5–6

- [ ] **Step 1: Tambah fillable + casts**

fillable tambah `'transaksi_fee_total'`, `'transaksi_fee_rincian'`; casts tambah `'transaksi_fee_total' => 'integer'`, `'transaksi_fee_rincian' => 'array'`.

- [ ] **Step 2: Tambah helper** (setelah `rules()`)

```php
public function feeTotal(): int
{
    if (is_array($this->transaksi_fee_rincian) && $this->transaksi_fee_rincian !== []) {
        return (int) array_sum($this->transaksi_fee_rincian);
    }
    if ((int) $this->transaksi_fee_total > 0) {
        return (int) $this->transaksi_fee_total;
    }

    return (int) $this->transaksi_fee_kebersihan + (int) $this->transaksi_fee_keamanan
        + (int) $this->transaksi_fee_pengelolaan + (int) $this->transaksi_fee_sistem;
}

public function feeRincian(): array
{
    if (is_array($this->transaksi_fee_rincian) && $this->transaksi_fee_rincian !== []) {
        return $this->transaksi_fee_rincian;
    }
    $lama = [
        'kebersihan' => (int) $this->transaksi_fee_kebersihan,
        'keamanan' => (int) $this->transaksi_fee_keamanan,
        'pengelolaan' => (int) $this->transaksi_fee_pengelolaan,
        'sistem' => (int) $this->transaksi_fee_sistem,
    ];

    return array_filter($lama, fn ($v) => $v > 0);
}
```

---

### Task 5: `ProcessPurchaseAction` pakai tabel `fee`

**Files:**
- Modify: `app/Actions/Ekantin/ProcessPurchaseAction.php:7,24-25,93-97` (catatan: baris sudah bergeser akibat Task walk-in; cari dari konteks, bukan nomor baris)
- Test: Task 8

**Interfaces:**
- Consumes: `Fee::rincian(int): array` dari Task 2
- Produces: transaksi baru berisi `transaksi_fee_total` + `transaksi_fee_rincian`, kolom lama = 0

- [ ] **Step 1: Ganti import + hitung fee**

```php
use App\Models\Fee;
```

Ganti blok:

```php
$fee = FeeConfig::aktif();
$feeTotal = (int) $fee->fee_kebersihan + (int) $fee->fee_keamanan + (int) $fee->fee_pengelolaan + (int) $fee->fee_sistem;
```

menjadi:

```php
$rincianFee = []; // dihitung setelah $total diketahui — lihat Step 2
$feeTotal = 0;
```

Hapus juga `use App\Models\FeeConfig;` jika tidak dipakai lagi di file ini.

- [ ] **Step 2: Hitung rincian setelah total diketahui** (setelah loop items, sebelum cek saldo — cari blok `if (empty($rows))`):

```php
$rincianFee = Fee::rincian($total);
$feeTotal = (int) array_sum($rincianFee);
```

(Blok cek saldo/limit di bawahnya memakai `$feeTotal` — tidak berubah.)

- [ ] **Step 3: Tulis kolom baru, nol-kan kolom lama** di `Transaksi::create`:

```php
'transaksi_fee_kebersihan' => 0,
'transaksi_fee_keamanan' => 0,
'transaksi_fee_pengelolaan' => 0,
'transaksi_fee_sistem' => 0,
'transaksi_fee_total' => $feeTotal,
'transaksi_fee_rincian' => $rincianFee,
```

- [ ] **Step 4: Sintaks check**

Run: `php -l app/Actions/Ekantin/ProcessPurchaseAction.php`
Expected: `No syntax errors detected`

---

### Task 6: Alihkan semua pembaca fee ke helper

**Files:**
- Modify: `app/Http/Controllers/TransaksiController.php` (±baris 93, feeTotal + subtotalPerGerai loop tidak berubah), `resources/views/pages/transaksi/detail.blade.php` (rincian `@foreach` + `$feeTotal`), `app/Http/Controllers/PembagianController.php` (2× sum fee harian), `app/Http/Controllers/DashboardController.php` (4× sum), `app/Charts/DashboardChart.php:87`, `app/Http/Controllers/LaporanController.php:79-80,139-140`, `app/Actions/Ekantin/RefundAction.php:32,54`, `resources/views/pages/kasir/struk.blade.php:3` (catatan: baris bergeser akibat Task walk-in; cari dari konteks)

**Interfaces:**
- Consumes: `feeTotal()`, `feeRincian()` dari Task 4
- Produces: tampilan + hitung bagi + laporan + refund konsisten untuk data baru dan lama

Pola pengganti (berlaku untuk semua sum satu-baris):

```php
// SEBELUM:
->get()->sum(fn ($t) => (int) $t->transaksi_fee_kebersihan + (int) $t->transaksi_fee_keamanan + (int) $t->transaksi_fee_pengelolaan + (int) $t->transaksi_fee_sistem);
// SESUDAH:
->get()->sum(fn ($t) => $t->feeTotal());
```

- [ ] **Step 1: `TransaksiController::getUpdate`** — ganti hitung `$feeTotal` jadi `$feeTotal = $trx->feeTotal();` dan kirim rincian ke view: tambah `'feeRincian' => $trx->feeRincian(),` di `$this->views('pages.transaksi.detail', [...])`.

- [ ] **Step 2: `detail.blade.php`** — ganti `@php` atas: `$feeTotal` tetap dipakai dari controller (tidak dihitung di view); blok fee kebersihan/keamanan/pengelolaan/sistem (4 baris) diganti loop:

```blade
@foreach ($feeRincian as $nama => $nominal)
<div class="flex items-center justify-between text-sm">
    <span class="text-on-surface-variant">Fee {{ $nama }}</span>
    <span class="text-on-surface">{{ $uang($nominal) }}</span>
</div>
@endforeach
```

(Jika `$feeRincian` kosong, loop tidak render apa-apa — benar untuk fee 0%.)

- [ ] **Step 3: `PembagianController`** — 2 lokasi sum fee harian → pola pengganti di atas.

- [ ] **Step 4: `DashboardController`** — 4 lokasi sum → pola pengganti.

- [ ] **Step 5: `DashboardChart`, `LaporanController`, `RefundAction`** — sum → pola pengganti. Di `RefundAction` baris 32 (`$kembali`) dan 54 (`$feeAsal`) keduanya jadi `$asal->feeTotal()`.

- [ ] **Step 6: `struk.blade.php`** — ganti `$feeTotal = (int) $trx->transaksi_fee_kebersihan + ...` jadi `$feeTotal = $trx->feeTotal();`.

---

### Task 7: Tests — seed + ekspektasi + modul fee

**Files:**
- Modify: `tests/Feature/Ekantin/SeedEkantin.php` (setup fee persen), `tests/Feature/Ekantin/UangTest.php` (ekspektasi nominal dari persen), `tests/Feature/Ekantin/PosWalkinTest.php` (jika ekspektasi fee 500 pecah — cek dan sesuaikan)
- Create: `tests/Feature/Ekantin/FeeTest.php`
- Test: file-file di atas

**Interfaces:**
- Consumes: model `Fee`, `ProcessPurchaseAction` baru, helper `feeTotal()`
- Produces: bukti persen→nominal, fallback, validasi, 403

- [ ] **Step 1: Update `SeedEkantin::seedEkantinDasar`** — ganti blok `FeeConfig::firstOrCreate([...fee_sistem => 500...])` jadi:

```php
if (class_exists(FeeConfig::class)) {
    FeeConfig::firstOrCreate(['fee_aktif' => true], ['fee_min_topup' => 10000]);
}
\App\Models\Fee::firstOrCreate(['code_fee' => 'sistem'], ['nama_fee' => 'Sistem', 'value_fee' => 5]);
```

(Total fee seed = 5%. Harga 10000 × 2 = 20000 → fee 1000, bukan 500 lagi.)

- [ ] **Step 2: Update `UangTest`** — hitung ulang dengan fee 5%:
  - Test 1: saldo akhir `50000 - 20000 - 1000`; gerai `20000 - 1000`.
  - Test 3 (mix): total 20000 → fee 1000 proporsional 500/gerai → tiap gerai 9500; siswa `100000 - 20000 - 1000`.
  - Test limit (15000, qty 2 = 20000) tetap ditolak — tidak berubah.

- [ ] **Step 3: Cek `PosWalkinTest`** — ekspektasi `transaksi_fee_sistem == 500` dan `gerai_saldo == 20000 - 500` harus jadi: fee_total 1000, rincian `['sistem' => 1000]`, gerai `20000 - 1000`, saldo `50000 - 20000 - 1000`. Update semua angka 500 → 1000 dan tambah assert `transaksi_fee_rincian`.

- [ ] **Step 4: Tulis `FeeTest.php`**

```php
<?php

use App\Models\Fee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test('rincian fee 5% + 1% + 2% dari subtotal benar', function () {
    $this->seedEkantinDasar(); // seed: sistem 5%
    Fee::create(['code_fee' => 'kebersihan', 'nama_fee' => 'Kebersihan', 'value_fee' => 1]);
    Fee::create(['code_fee' => 'layanan', 'nama_fee' => 'Layanan', 'value_fee' => 2]);
    expect(Fee::totalPersen())->toBe(8.0)
        ->and(Fee::rincian(20000))->toBe(['sistem' => 1000, 'kebersihan' => 200, 'layanan' => 400]);
});

```php
test('validasi fee menolak persen di atas 100 dan code duplikat', function () {
    $admin = User::create(['name' => 'A', 'email' => 'a@sekolah.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin)->post(route('fee.postCreate'), ['code_fee' => 'x', 'nama_fee' => 'X', 'value_fee' => 101])
        ->assertSessionHasErrors('value_fee');
    Fee::create(['code_fee' => 'kebersihan', 'nama_fee' => 'K', 'value_fee' => 1]);
    $this->actingAs($admin)->post(route('fee.postCreate'), ['code_fee' => 'kebersihan', 'nama_fee' => 'K2', 'value_fee' => 1])
        ->assertSessionHasErrors('code_fee');
});

test('vendor tidak bisa buka modul fee', function () {
    $this->seedEkantinDasar();
    $vendor = User::where('role', 'vendor')->first();
    $vendor->markEmailAsVerified();
    $this->actingAs($vendor)->get(route('fee.getTable'))->assertForbidden();
});

test('fallback transaksi lama tanpa rincian', function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $trx = \App\Models\Transaksi::create([
        'transaksi_jenis' => 'beli', 'transaksi_status' => 'berhasil', 'transaksi_metode' => 'kartu',
        'transaksi_id_kartu' => $kartu->kartu_id, 'transaksi_total' => 10000,
        'transaksi_fee_kebersihan' => 300, 'transaksi_fee_sistem' => 200,
    ]);
    expect($trx->fresh()->feeTotal())->toBe(500)
        ->and($trx->fresh()->feeRincian())->toBe(['kebersihan' => 300, 'sistem' => 200]);
});
```

- [ ] **Step 5: Jalankan**

Run: `php artisan test --filter=FeeTest`
Expected: PASS semua. Lalu `php artisan test --filter=UangTest`, `php artisan test --filter=PosWalkinTest` — PASS.

---

### Task 8: Verifikasi akhir

**Files:** — (tidak ada perubahan kode; hanya verifikasi)

- [ ] **Step 1: Full suite modul ekantin**

Run: `php artisan test --filter=Ekantin`
Expected: PASS. (Catatan: `SeederTest` gagal sebelum perubahan ini dengan selisih count seed — jika masih merah dengan pesan count yang sama, itu pre-existing, bukan regresi; bandingkan pesannya.)

- [ ] **Step 2: Manual sebagai admin** — buka `/fee/table`, tambah fee kebersihan 1% + sistem 2%, lakukan pembelian kasir, cek struk/detail transaksi menampilkan rincian + total benar, cek `/pembagian/table` dan laporan tetap konsisten.
