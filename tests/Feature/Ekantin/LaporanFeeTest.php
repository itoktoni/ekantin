<?php

use App\Models\Fee;
use App\Models\Gerai;
use App\Models\Penarikan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ekantin\SeedEkantin;

uses(RefreshDatabase::class, SeedEkantin::class);

// Transaksi beli + baris fee relasional (pengganti kolom JSON yang sudah di-drop).
function buatTransaksiBerfee(int $kartuId, int $total, array $fees, int $bersih, int $saldo): Transaksi
{
    $trx = Transaksi::create([
        'transaksi_jenis' => 'beli', 'transaksi_status' => 'berhasil', 'transaksi_metode' => 'kartu',
        'transaksi_id_kartu' => $kartuId, 'transaksi_total' => $total,
        'transaksi_bersih' => $bersih, 'transaksi_saldo_akhir' => $saldo,
    ]);
    $ids = Fee::query()->pluck('fee_id', 'code_fee');
    $persen = Fee::persenMap();
    $tipe = Fee::tipeMap();
    $trx->hasFees()->createMany(
        collect($fees)->map(fn ($nominal, $code) => [
            'tfee_id_fee' => $ids[$code] ?? null,
            'tfee_code' => (string) $code,
            'tfee_tipe' => $tipe[$code] ?? 'persen',
            'tfee_persen' => $persen[$code] ?? 0,
            'tfee_nominal' => (int) $nominal,
        ])->values()->all()
    );

    return $trx->fresh();
}

test("hub laporan tampil dan tiap laporan terpisah bisa dibuka admin", function () {
    [$kartu, $gerai] = $this->seedEkantinDasar(); // seed: fee sistem 5%
    Fee::create(['code_fee' => 'kebersihan', 'nama_fee' => 'Kebersihan', 'value_fee' => 1]);
    buatTransaksiBerfee($kartu->kartu_id, 20000, ['sistem' => 1000, 'kebersihan' => 200], 18800, 30000);
    Penarikan::create([
        'penarikan_id_gerai' => $gerai->gerai_id, 'penarikan_nominal' => 15000,
        'penarikan_tanggal' => today(), 'penarikan_status' => 'diajukan',
    ]);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    $this->get(route('laporan.index'))
        ->assertOk()
        ->assertSee('Laporan Transaksi')
        ->assertSee('Laporan Fee')
        ->assertSee('Laporan Vendor')
        ->assertSee('Laporan Penarikan');
    $this->get(route('laporan.transaksi'))->assertOk()->assertSee('Rp20.000');
    $this->get(route('laporan.fee'))->assertOk()
        ->assertSee('Rincian per Fee')->assertSee('Kebersihan')->assertSee('Rp1.200')
        ->assertSee('1%'); // persen dari master fee
    $gerai->update(['gerai_bank' => 'BCA', 'gerai_no_rekening' => '1234567890', 'gerai_nama_rekening' => 'Budi']);
    $this->get(route('laporan.vendor'))->assertOk()->assertSee($gerai->gerai_nama)->assertSee('Omzet')
        ->assertSee('BCA')->assertSee('1234567890')->assertSee('Budi');
    $this->get(route('laporan.penarikan'))->assertOk()->assertSee('diajukan')->assertSee('Rp15.000');
    foreach (['transaksi', 'fee', 'vendor', 'penarikan'] as $rep) {
        $this->get(route('laporan.ekspor', ['report' => $rep, 'format' => 'csv']))->assertOk();
    }
});

test("laporan fee bisa difilter per fee dan ekspor ikut filter", function () {
    [$kartu] = $this->seedEkantinDasar(); // seed: fee sistem 5%
    Fee::create(['code_fee' => 'kebersihan', 'nama_fee' => 'Kebersihan', 'value_fee' => 1]);
    buatTransaksiBerfee($kartu->kartu_id, 20000, ['sistem' => 1000, 'kebersihan' => 200], 18800, 30000);
    buatTransaksiBerfee($kartu->kartu_id, 10000, ['sistem' => 500], 9500, 20000);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    // Tanpa filter: 2 baris 1-fee; filter kebersihan: 1 baris + banner hitungan + dropdown terselect.
    $this->get(route('laporan.fee'))->assertOk()->assertSee('Rp20.000')->assertSee('Rp10.000');
    $this->get(route('laporan.fee', ['transaksi_fee' => 'kebersihan']))
        ->assertOk()->assertSee('Rp20.000')->assertDontSee('Rp10.000')
        ->assertSee('1 transaksi')->assertSee('TRX-');
    // Dropdown fee tetap terselect sesuai filter (cek via DOM, bukan string).
    $html = $this->get(route('laporan.fee', ['transaksi_fee' => 'kebersihan']))->getContent();
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xp = new DOMXPath($dom);
    $opt = $xp->query('//select[@name="transaksi_fee"]/option[@selected]')->item(0);
    expect($opt?->getAttribute('value'))->toBe('kebersihan');
    $csv = $this->get(route('laporan.ekspor', ['report' => 'fee', 'format' => 'csv', 'transaksi_fee' => 'kebersihan']))
        ->assertOk()->streamedContent();
    expect($csv)->toContain('kebersihan')->toContain('200')->not->toContain('sistem');
});

test("kode fee warisan di luar master masuk bucket lainnya dan total tetap cocok", function () {
    [$kartu] = $this->seedEkantinDasar(); // seed: fee sistem 5% (master)
    buatTransaksiBerfee($kartu->kartu_id, 20000, ['sistem' => 1000, 'pengelolaan' => 200], 18800, 30000);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    // Master tampil sendiri; kode warisan gabung ke Lainnya (riwayat): 1 transaksi Rp200.
    $this->get(route('laporan.fee'))->assertOk()
        ->assertSee('Sistem')->assertSee('Lainnya (riwayat)')->assertDontSee('pengelolaan')
        ->assertSee('Rp1.200');
    // Filter lainnya hanya menampilkan transaksi warisan tersebut.
    $this->get(route('laporan.fee', ['transaksi_fee' => 'lainnya']))
        ->assertOk()->assertSee('Rp20.000')->assertSee('1 transaksi');
});

test("laporan fee tampilkan semua master walau nol dan sembunyikan transaksi tanpa fee", function () {
    [$kartu] = $this->seedEkantinDasar(); // seed: fee sistem 5% (master)
    Fee::create(['code_fee' => 'sekolah', 'nama_fee' => 'Sekolah', 'value_fee' => 1]);
    buatTransaksiBerfee($kartu->kartu_id, 20000, ['sistem' => 1000], 19000, 30000);
    Transaksi::create([ // tanpa fee: tidak ikut tabel fee
        'transaksi_jenis' => 'beli', 'transaksi_status' => 'berhasil', 'transaksi_metode' => 'kartu',
        'transaksi_id_kartu' => $kartu->kartu_id, 'transaksi_total' => 5000,
        'transaksi_bersih' => 5000, 'transaksi_saldo_akhir' => 25000,
    ]);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    $this->get(route('laporan.fee'))->assertOk()
        ->assertSee('Sekolah')->assertSee('Sistem') // semua master tampil
        ->assertSee('Rp20.000')->assertDontSee('Rp5.000'); // hanya yang berfee
});

test("fee dari tabel relasi terbaca di laporan", function () {
    [$kartu] = $this->seedEkantinDasar(); // seed: fee sistem 5% (master)
    $r = \App\Actions\Ekantin\ProcessPurchaseAction::run([
        'kartu_barcode' => $kartu->kartu_barcode,
        'items' => [['produk_id' => \App\Models\Produk::firstOrFail()->produk_id, 'qty' => 1]],
    ]);
    expect($r['status'])->toBeTrue();
    $trx = $r['data']->fresh();
    expect($trx->hasFees()->count())->toBe(1)
        ->and($trx->hasFees()->first()->hasFee->code_fee)->toBe('sistem')
        ->and($trx->feeRincian())->toBe(['sistem' => 500]);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    $this->get(route('laporan.fee'))->assertOk()->assertSee('Sistem')->assertSee('Rp500');
});

test("cetak laporan terbuka di tab baru untuk semua jenis yang boleh diakses", function () {
    [$kartu, $gerai] = $this->seedEkantinDasar();
    buatTransaksiBerfee($kartu->kartu_id, 20000, ['sistem' => 1000], 19000, 30000);
    Penarikan::create([
        'penarikan_id_gerai' => $gerai->gerai_id, 'penarikan_nominal' => 15000,
        'penarikan_tanggal' => today(), 'penarikan_status' => 'diajukan',
    ]);
    $admin = User::create(['name' => 'A', 'email' => 'a@x.id', 'password' => 'secret123', 'role' => 'super_admin']);
    $admin->markEmailAsVerified();
    $this->actingAs($admin);
    foreach (['transaksi', 'fee', 'vendor', 'penarikan'] as $jenis) {
        $this->get(route('laporan.cetak', ['jenis' => $jenis]))->assertOk();
    }
    $this->get(route('laporan.cetak', ['jenis' => 'fee', 'transaksi_fee' => 'sistem']))
        ->assertOk()->assertSee('Sistem')->assertSee('Rp1.000');
    $this->get(route('laporan.cetak', ['jenis' => 'fee', 'export' => 'xls']))
        ->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
        ->assertDontSee('Kembali', false); // toolbar tombol disembunyikan di file xls
    $this->get(route('laporan.cetak', ['jenis' => 'ngawur']))->assertNotFound();
    $kasir = User::create(['name' => 'K', 'email' => 'k@x.id', 'password' => 'secret123', 'role' => 'kasir_sekolah']);
    $kasir->markEmailAsVerified();
    $this->actingAs($kasir);
    $this->get(route('laporan.cetak', ['jenis' => 'transaksi']))->assertForbidden();
    $this->get(route('laporan.cetak', ['jenis' => 'penarikan']))->assertOk();
});

test("kasir ditolak di laporan transaksi fee vendor tapi boleh penarikan", function () {
    $kasir = User::create(['name' => 'K', 'email' => 'k@x.id', 'password' => 'secret123', 'role' => 'kasir_sekolah']);
    $kasir->markEmailAsVerified();
    $this->actingAs($kasir);
    $this->get(route('laporan.transaksi'))->assertForbidden();
    $this->get(route('laporan.fee'))->assertForbidden();
    $this->get(route('laporan.vendor'))->assertForbidden();
    $this->get(route('laporan.penarikan'))->assertOk();
    $this->get(route('laporan.ekspor', ['report' => 'transaksi', 'format' => 'csv']))->assertForbidden();
});

test("orang tua ditolak di laporan vendor dan penarikan", function () {
    $ortu = User::create(['name' => 'O', 'email' => 'o@x.id', 'password' => 'secret123', 'role' => 'orang_tua']);
    $ortu->markEmailAsVerified();
    $this->actingAs($ortu);
    $this->get(route('laporan.vendor'))->assertForbidden();
    $this->get(route('laporan.penarikan'))->assertForbidden();
    $this->get(route('laporan.transaksi'))->assertOk();
    $this->get(route('laporan.fee'))->assertOk();
});
