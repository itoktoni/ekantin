# POS Walk-in + Pilih Kartu Siswa Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Kasir bisa layani walk-in (tunai/QRIS, fee tetap) dan search + pilih kartu siswa di `/kasir/pos`.

**Architecture:** Tambah kolom `transaksi_metode` (`kartu`/`tunai`/`qris`); `ProcessPurchaseAction` bercabang walk-in (tanpa sentuh kartu, split fee tetap); POS view dapat toggle mode + search AJAX; endpoint `kartu.getSearch` khusus kasir/admin.

**Tech Stack:** Laravel 13, lorisleiva/laravel-actions, izniburak/laravel-auto-routes (method `getSearch` → route `kartu.getSearch`), Pest, Tailwind/Blade + vanilla JS.

## Global Constraints

- PHP 8.3+ syntax, 4-space indentation, double-quoted strings preferred.
- Deny-list permission: setiap route baru wajib tambah entry `config/permision.php` (route tak terdaftar = ALLOW).
- `ControllerTrait::template()` sudah normalisasi alias `trait*` — override baru yang pakai alias aman.
- Jangan ubah alur kartu existing; walk-in = tambah cabang, bukan refactor.
- Test runner: `php artisan test`. Migration: `php artisan migrate`.

---

### Task 1: Migrasi `transaksi_metode`

**Files:**
- Create: `database/migrations/2026_09_17_000001_add_metode_to_transaksi_table.php`
- Test: n/a (verifikasi via `php artisan migrate` + cek kolom)

**Interfaces:**
- Consumes: tabel `transaksi` dari `2026_09_16_000004_create_transaksi_table.php`
- Produces: kolom `transaksi_metode` string(10) default `'kartu'` untuk Task 2–3

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
        Schema::table("transaksi", function (Blueprint $table) {
            $table->string("transaksi_metode", 10)->default("kartu")->after("transaksi_status");
        });
    }

    public function down(): void
    {
        Schema::table("transaksi", function (Blueprint $table) {
            $table->dropColumn("transaksi_metode");
        });
    }
};
```

- [ ] **Step 2: Jalankan migrasi**

Run: `php artisan migrate`
Expected: `DONE` tanpa error; kolom `transaksi_metode` ada dengan default `kartu`.

---

### Task 2: Model `Transaksi` — fillable + rules

**Files:**
- Modify: `app/Models/Transaksi.php:18-35` (tambah fillable), `app/Models/Transaksi.php:71-80` (tambah rule)
- Test: tercakup Task 7 (create dengan `transaksi_metode` harus lolos mass-assignment)

**Interfaces:**
- Consumes: kolom `transaksi_metode` dari Task 1
- Produces: `Transaksi::create([... "transaksi_metode" => ...])` bisa dipakai Task 3

- [ ] **Step 1: Tambah fillable**

```php
"transaksi_jenis",
"transaksi_status",
"transaksi_metode",
```

(tambah tepat setelah `"transaksi_status",` di `$fillable`)

- [ ] **Step 2: Tambah rule**

```php
"transaksi_metode" => "required|in:kartu,tunai,qris",
```

(tambah tepat setelah rule `transaksi_jenis` di `rules()`)

---

### Task 3: `ProcessPurchaseAction` — cabang walk-in

**Files:**
- Modify: `app/Actions/Ekantin/ProcessPurchaseAction.php:22-108`
- Test: `tests/Feature/Ekantin/PosWalkinTest.php` (ditulis di Task 7, dijalankan di sini setelah implementasi)

**Interfaces:**
- Consumes: `FeeConfig::aktif()`, `Kartu`, `Produk`, `Gerai`, `SplitDana::bagi()`, kolom `transaksi_metode` (Task 1–2)
- Produces: `handle(array $input): array` dengan input baru opsional `metode` (`kartu` default; `tunai`/`qris` = walk-in, `kartu_barcode` boleh null)

- [ ] **Step 1: Ganti blok kartu (baris 29–35) jadi bercabang**

```php
$metode = $input["metode"] ?? "kartu";
$isWalkin = in_array($metode, ["tunai", "qris"], true);

$kartu = null;
if (! $isWalkin) {
    $kartu = Kartu::where("kartu_barcode", $input["kartu_barcode"])->lockForUpdate()->first();
    if (! $kartu) {
        return $this->payload(TOAST_FAILED, "Kartu tidak ditemukan.");
    }
    if ($kartu->kartu_status !== "aktif") {
        return $this->payload(TOAST_FAILED, "Kartu nonaktif, transaksi ditolak.");
    }
}
```

- [ ] **Step 2: Bungkus cek saldo + limit (baris 64–76) untuk non-walk-in saja**

```php
if (! $isWalkin) {
    if ((int) $kartu->kartu_saldo < $total + $feeTotal) {
        return $this->payload(TOAST_FAILED, "Saldo tidak mencukupi. Kurang Rp".number_format($total + $feeTotal - (int) $kartu->kartu_saldo, 0, ",", "."));
    }
    if (! empty($kartu->kartu_limit_harian)) {
        $pakai = (int) Transaksi::where("transaksi_id_kartu", $kartu->kartu_id)
            ->where("transaksi_jenis", "beli")->where("transaksi_status", "berhasil")
            ->whereDate("created_at", today())->sum("transaksi_total");
        if ($pakai + $total > (int) $kartu->kartu_limit_harian) {
            $sisa = (int) $kartu->kartu_limit_harian - $pakai;

            return $this->payload(TOAST_FAILED, "Limit harian tercapai. Sisa limit Rp".number_format(max($sisa, 0), 0, ",", "."));
        }
    }
}
```

(Pindahkan blok existing apa adanya ke dalam `if`, tanpa ubah pesan.)

- [ ] **Step 3: Sesuaikan create transaksi + update saldo (baris 79–98)**

```php
$saldoAkhir = $isWalkin ? null : (int) $kartu->kartu_saldo - $total - $feeTotal;
$trx = Transaksi::create([
    "transaksi_jenis" => "beli",
    "transaksi_status" => "berhasil",
    "transaksi_metode" => $metode,
    "transaksi_id_kartu" => $isWalkin ? null : $kartu->kartu_id,
    ...
    "transaksi_saldo_akhir" => $saldoAkhir,
    "transaksi_limit_snapshot" => $isWalkin ? null : $kartu->kartu_limit_harian,
    "transaksi_idempotency" => $input["idempotency"] ?? ($isWalkin ? "POS-W-".time() : "POS-".$kartu->kartu_id."-".time()),
    "transaksi_id_kasir" => $input["id_kasir"] ?? null,
]);
```

(`...` = field existing fee/total/bersih, tidak berubah. Idempotency check di baris 36–41 tidak berubah.)

```php
if (! $isWalkin) {
    $kartu->update(["kartu_saldo" => $saldoAkhir]);
}
```

(increment `gerai_saldo`, create items, notif, dispatch job tidak berubah)

- [ ] **Step 4: Sintaks check**

Run: `php -l app/Actions/Ekantin/ProcessPurchaseAction.php`
Expected: `No syntax errors detected`

---

### Task 4: `KasirController` — validasi `postPos` + `getSearch`

**Files:**
- Modify: `app/Http/Controllers/KasirController.php:48-76` (validasi + teruskan metode), tambah method `getSearch` setelah `getStruk`
- Test: tercakup Task 7 (endpoint + validasi)

**Interfaces:**
- Consumes: `ProcessPurchaseAction::handle()` menerima `metode` (Task 3), model `Kartu` + relasi `hasUser`
- Produces: route `kartu.getSearch` (auto dari nama method via `Route::auto("/kartu", ...)`), response JSON `[{kartu_id, barcode, nis, nama, saldo}]`

- [ ] **Step 1: Ubah validasi `postPos`**

```php
$data = $request->validate([
    "metode" => "required|in:kartu,tunai,qris",
    "kartu_barcode" => "required_if:metode,kartu|nullable|string|max:50",
    "idempotency" => "required|string|max:64",
    "items" => "required|array|min:1",
    "items.*.produk_id" => "required|integer",
    "items.*.qty" => "required|integer|min:0",
]);
```

- [ ] **Step 2: Teruskan metode ke action**

```php
$response = ProcessPurchaseAction::run([
    "metode" => $data["metode"],
    "kartu_barcode" => $data["kartu_barcode"] ?? null,
    "items" => $items,
    "idempotency" => $data["idempotency"],
    "id_kasir" => auth()->id(),
]);
```

- [ ] **Step 3: Tambah method `getSearch` di `KartuController` (bukan KasirController)**

File: `app/Http/Controllers/KartuController.php`, tambah setelah method `getAnak`:

```php
// Search kartu untuk picker kasir (via Route::auto '/kartu' → kartu.getSearch).
// Kasir/admin saja — role lain ditolak via config/permision.php.
public function getSearch(GeneralRequest $request)
{
    $q = trim((string) $request->input("q", ""));
    if (mb_strlen($q) < 2) {
        return response()->json([]);
    }
    $rows = Kartu::query()->with("hasUser:id,name")
        ->where("kartu_status", "aktif")
        ->where(function ($w) use ($q) {
            $w->where("kartu_barcode", "like", "%{$q}%")
                ->orWhere("kartu_nis", "like", "%{$q}%")
                ->orWhereHas("hasUser", fn ($u) => $u->where("name", "like", "%{$q}%"));
        })
        ->limit(10)->get()
        ->map(fn ($k) => [
            "kartu_id" => $k->kartu_id,
            "barcode" => $k->kartu_barcode,
            "nis" => $k->kartu_nis,
            "nama" => $k->hasUser?->name ?? "-",
            "saldo" => (int) $k->kartu_saldo,
        ])->values();

    return response()->json($rows);
}
```

(`GeneralRequest` dan `Kartu` sudah di-import di file itu. Ability `search` di-resolve via `BasePolicy::__call`, module `kartu.getSearch`.)

- [ ] **Step 4: Sintaks check + pastikan route terdaftar**

Run: `php -l app/Http/Controllers/KasirController.php; php -l app/Http/Controllers/KartuController.php; php artisan route:list --name=kartu.getSearch`
Expected: no syntax errors; satu baris route `kartu/search .. kartu.getSearch`.

---

### Task 5: Policy `kartu.getSearch`

**Files:**
- Modify: `config/permision.php` (tambah blok sebelum `return $restrict;`)

**Interfaces:**
- Consumes: route `kartu.getSearch` dari Task 4
- Produces: vendor/orang_tua/siswa/kasir_sekolah? — kasir HARUS boleh. Tolak: vendor, orang_tua, siswa. (kasir_sekolah boleh.)

- [ ] **Step 1: Tambah deny-list**

```php
// Picker kartu kasir: kasir/admin saja — vendor/orang_tua/siswa ditolak.
foreach (["vendor", "orang_tua", "siswa"] as $peran) {
    foreach (["search", "table", "save", "create", "update", "delete", "show"] as $act) {
        $restrict[$peran]["kartu.getSearch"][] = $act;
    }
}
```

- [ ] **Step 2: Verifikasi tanpa server (simulasi deny)**

Run: `php artisan route:list --name=kartu.getSearch`
Expected: route ada. (Cek perilaku 403/200 di Task 7 via HTTP test.)

---

### Task 6: View POS — toggle mode + search picker + struk label

**Files:**
- Modify: `resources/views/pages/kasir/pos.blade.php` (blok ① kartu + JS submit), `resources/views/pages/kasir/struk.blade.php:5-7` (label walk-in)

**Interfaces:**
- Consumes: endpoint `kartu.getSearch` (Task 4), hidden input `metode`, validasi `postPos` (Task 4)
- Produces: form submit `metode` + (`kartu_barcode` | metode bayar)

- [ ] **Step 1: Tambah toggle mode + hidden metode di atas blok ①**

```blade
<input type="hidden" name="metode" id="metodeInput" value="kartu">
<div class="mb-2.5 flex gap-1.5" role="group" aria-label="Mode pembeli">
    <button type="button" id="modeKartu" class="chip" aria-pressed="true">
        <span class="material-symbols-outlined">badge</span> Kartu siswa
    </button>
    <button type="button" id="modeWalkin" class="chip" aria-pressed="false">
        <span class="material-symbols-outlined">person</span> Walk-in
    </button>
</div>
```

- [ ] **Step 2: Tambah tombol search + panel hasil di kartu box, dan radio Tunai/QRIS (hidden awal)**

Setelah `<input id="kartu_barcode" ...>` tambah:

```blade
<div class="mt-2 flex gap-1.5">
    <button type="button" id="cariKartuBtn" class="inline-flex h-11 items-center gap-1 rounded-lg border border-outline-variant px-3 text-[13px] font-semibold text-on-surface hover:border-primary hover:text-primary">
        <span class="material-symbols-outlined text-[18px]">person_search</span> Cari siswa
    </button>
    <span id="kartuTerpilih" class="hidden min-w-0 flex-1 truncate self-center text-[13px] font-semibold text-primary"></span>
</div>
<div id="hasilKartu" class="mt-2 hidden max-h-56 space-y-1 overflow-y-auto"></div>
<div id="walkinBox" class="mt-2 hidden gap-1.5" role="group" aria-label="Metode bayar walk-in">
    <label class="chip cursor-pointer"><input type="radio" name="metode_bayar" value="tunai" class="sr-only"> Tunai</label>
    <label class="chip cursor-pointer"><input type="radio" name="metode_bayar" value="qris" class="sr-only"> QRIS</label>
</div>
```

(`.chip` dan `[aria-pressed]` styling sudah dipakai rail filter existing — pakai pola yang sama.)

- [ ] **Step 3: JS — toggle mode, search AJAX, validasi submit**

Tambah di `<script>` existing (sebelum `refreshCart();` akhir tidak perlu; tambah block baru):

```js
const metodeInput = document.getElementById("metodeInput");
const kartuBox = document.getElementById("kartu_barcode");
const walkinBox = document.getElementById("walkinBox");
const hasilKartu = document.getElementById("hasilKartu");
const kartuTerpilih = document.getElementById("kartuTerpilih");
let mode = "kartu";

function setMode(m) {
    mode = m;
    metodeInput.value = m === "walkin" ? (document.querySelector('input[name="metode_bayar"]:checked')?.value || "tunai") : "kartu";
    document.getElementById("modeKartu").setAttribute("aria-pressed", String(m === "kartu"));
    document.getElementById("modeWalkin").setAttribute("aria-pressed", String(m === "walkin"));
    kartuBox.required = m === "kartu";
    kartuBox.closest("div.rounded-xl").classList.toggle("hidden", m === "walkin");
    walkinBox.classList.toggle("hidden", m === "kartu");
    walkinBox.classList.toggle("flex", m === "walkin");
}
document.getElementById("modeKartu").addEventListener("click", () => setMode("kartu"));
document.getElementById("modeWalkin").addEventListener("click", () => setMode("walkin"));
walkinBox.addEventListener("change", () => {
    metodeInput.value = document.querySelector('input[name="metode_bayar"]:checked')?.value || "tunai";
});

document.getElementById("cariKartuBtn").addEventListener("click", async () => {
    const q = kartuBox.value.trim();
    if (q.length < 2) { kartuBox.focus(); return; }
    const res = await fetch("{{ route('kartu.getSearch') }}?q=" + encodeURIComponent(q), { headers: { "X-Requested-With": "XMLHttpRequest" } });
    const rows = await res.json();
    hasilKartu.innerHTML = "";
    hasilKartu.classList.remove("hidden");
    if (!rows.length) {
        hasilKartu.innerHTML = '<p class="p-2 text-[13px] text-on-surface-variant">Tidak ketemu.</p>';
        return;
    }
    rows.forEach((r) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "flex w-full items-center justify-between gap-2 rounded-lg border border-outline-variant px-3 py-2 text-left hover:border-primary";
        b.innerHTML = "<span class='min-w-0'><span class='block truncate text-[13px] font-bold'>" + r.nama + "</span><span class='block font-data-mono text-[11px] text-on-surface-variant'>" + r.barcode + (r.nis ? " • " + r.nis : "") + "</span></span><span class='font-data-mono text-[12px] font-bold text-primary'>Rp" + r.saldo.toLocaleString("id-ID") + "</span>";
        b.addEventListener("click", () => {
            kartuBox.value = r.barcode;
            kartuTerpilih.textContent = r.nama + " • Rp" + r.saldo.toLocaleString("id-ID");
            kartuTerpilih.classList.remove("hidden");
            hasilKartu.classList.add("hidden");
        });
        hasilKartu.appendChild(b);
    });
});
```

Ubah handler submit existing: ganti kondisi barcode-only jadi:

```js
form.addEventListener("submit", (e) => {
    if (mode === "kartu") {
        const barcode = document.getElementById("kartu_barcode");
        if (barcode && !barcode.value.trim()) {
            e.preventDefault();
            barcode.focus();
            barcode.classList.add("border-error");
            return;
        }
    } else {
        const bayar = document.querySelector('input[name="metode_bayar"]:checked');
        if (!bayar) {
            e.preventDefault();
            return;
        }
        metodeInput.value = bayar.value;
    }
    bayarBtn.disabled = true;
});
```

(Hapus handler submit lama yang hanya cek barcode — ganti dengan blok di atas.)

- [ ] **Step 4: Label walk-in di struk (`struk.blade.php` baris 5–7)**

```blade
$metode = $trx->transaksi_metode ?? "kartu";
$namaSiswa = $trx->hasKartu?->hasUser?->name ?? "Walk-in (".ucfirst($metode).")";
$barcodeSiswa = $trx->hasKartu?->kartu_barcode ?? "-";
```

- [ ] **Step 5: Compile check**

Run: `php artisan view:cache` lalu `php artisan view:clear`
Expected: `Blade templates cached successfully` tanpa exception.

---

### Task 7: Pest tests

**Files:**
- Create: `tests/Feature/Ekantin/PosWalkinTest.php`

**Interfaces:**
- Consumes: `ProcessPurchaseAction::run()` (Task 3), route `kartu.getSearch` (Task 4), `SeedEkantin::seedEkantinDasar()` (fee total 500, saldo/gerai/produk jadi)
- Produces: 7 skenario hijau dari spec

- [ ] **Step 1: Tulis test file**

```php
<?php

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Models\Gerai;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

test("walk-in tunai sukses tanpa kartu, fee tetap, saldo kartu utuh", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $r = ProcessPurchaseAction::run(["metode" => "tunai", "items" => [["produk_id" => $produk->produk_id, "qty" => 2]], "idempotency" => "POS-W-1"]);
    expect($r["status"])->toBeTrue()
        ->and($r["data"]->transaksi_metode)->toBe("tunai")
        ->and($r["data"]->transaksi_id_kartu)->toBeNull()
        ->and($r["data"]->transaksi_fee_sistem)->toBe(500)
        ->and($gerai->fresh()->gerai_saldo)->toBe(20000 - 500)
        ->and($kartu->fresh()->kartu_saldo)->toBe(50000);
});

test("walk-in qris sukses", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(harga: 10000);
    $r = ProcessPurchaseAction::run(["metode" => "qris", "items" => [["produk_id" => $produk->produk_id, "qty" => 1]], "idempotency" => "POS-W-2"]);
    expect($r["status"])->toBeTrue()->and($r["data"]->transaksi_metode)->toBe("qris");
});

test("walk-in ditolak jika gerai tutup", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $gerai->update(["gerai_status" => "tutup"]);
    $r = ProcessPurchaseAction::run(["metode" => "tunai", "items" => [["produk_id" => $produk->produk_id, "qty" => 1]]]);
    expect($r["status"])->toBeFalse();
});

test("alur kartu tidak berubah (default metode kartu)", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar(saldo: 50000, harga: 10000);
    $r = ProcessPurchaseAction::run(["kartu_barcode" => $kartu->kartu_barcode, "items" => [["produk_id" => $produk->produk_id, "qty" => 1]]]);
    expect($r["status"])->toBeTrue()
        ->and($r["data"]->transaksi_metode)->toBe("kartu")
        ->and($kartu->fresh()->kartu_saldo)->toBe(50000 - 10000 - 500);
});

test("search kartu: kasir 200 dengan shape benar, vendor 403", function () {
    [$kartu, $gerai, $produk] = $this->seedEkantinDasar();
    $kasir = User::create(["name" => "Kasir", "email" => "kasir@sekolah.id", "password" => "secret123", "role" => "kasir_sekolah"]);
    $kasir->markEmailAsVerified();
    $res = $this->actingAs($kasir)->getJson(route("kartu.getSearch", ["q" => "SW-TEST"]));
    $res->assertOk()->assertJsonFragment(["barcode" => "SW-TEST-001", "nama" => "Siswa Tes"]);
    $vendor = User::where("role", "vendor")->first();
    $vendor->markEmailAsVerified();
    $this->actingAs($vendor)->getJson(route("kartu.getSearch", ["q" => "SW-TEST"]))->assertForbidden();
});

test("postPos tanpa barcode + metode kartu ditolak validasi", function () {
    $this->seedEkantinDasar();
    $kasir = User::create(["name" => "Kasir2", "email" => "kasir2@sekolah.id", "password" => "secret123", "role" => "kasir_sekolah"]);
    $kasir->markEmailAsVerified();
    $this->actingAs($kasir)->post(route("kasir.postPos"), ["metode" => "kartu", "items" => [["produk_id" => 1, "qty" => 1]]])
        ->assertSessionHasErrors("kartu_barcode");
});
```

(Catatan: `Transaksi`/`Gerai`/`Produk` import yang tak terpakai boleh dihapus worker saat final; tidak memengaruhi hasil.)

- [ ] **Step 2: Jalankan test baru**

Run: `php artisan test --filter=PosWalkinTest`
Expected: 6 passed. Jika merah karena middleware/auth di test search/postPos, debug dengan `php artisan test --filter=PosWalkinTest -v` dan sesuaikan (mis. `markEmailAsVerified` sudah termasuk).

- [ ] **Step 3: Regresi modul uang**

Run: `php artisan test --filter=UangTest`
Expected: semua passed (alur kartu tidak berubah).

---

### Task 8: Verifikasi akhir manual + commit

**Files:** — (tidak ada perubahan kode; hanya verifikasi)

- [ ] **Step 1: Buka POS sebagai kasir** — toggle Kartu/Walk-in tampil; search "SW-" menampilkan siswa; klik hasil mengisi barcode + nama/saldo; mode walk-in menampilkan Tunai/QRIS; bayar walk-in redirect ke struk dengan label "Walk-in (Tunai)".
- [ ] **Step 2: Full test suite modul ekantin** — Run: `php artisan test --filter=Ekantin` — Expected: passed.
- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "feat(pos): walk-in tunai/qris + search kartu siswa"
```

(Jika repo ini belum git, inisialisasi/lewati sesuai kebijakan tim — kode tidak tergantung git.)
