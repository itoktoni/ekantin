<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\PenarikanStatusEnum;
use App\Enums\Ekantin\TransaksiJenisEnum;
use App\Enums\Ekantin\TransaksiStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Fee;
use App\Models\Gerai;
use App\Models\Kartu;
use App\Models\Penarikan;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    use ControllerTrait;

    public function __construct(Transaksi $model)
    {
        $this->model = $model::getModel();
    }

    // Batas role per laporan (lapis controller; cermin di config/permision.php):
    // - kasir hanya boleh penarikan (penukaran uang)
    // - orang_tua/pengguna tidak boleh vendor & penarikan
    private function assertLaporanAkses(string $laporan): void
    {
        $deny = [
            'kasir_sekolah' => ['transaksi', 'fee', 'vendor'],
            'orang_tua' => ['vendor', 'penarikan'],
            'pengguna' => ['vendor', 'penarikan'],
        ];
        if (in_array($laporan, $deny[auth()->user()?->role] ?? [], true)) {
            abort(403, 'Tidak boleh membuka laporan ini.');
        }
    }

    protected function baseQuery(GeneralRequest $request)
    {
        $q = Transaksi::query()->with(['hasKartu.hasUser', 'hasGerai', 'hasItems.hasGerai', 'hasFees']);
        if ($request->filled('dari')) {
            $q->whereDate('transaksi.created_at', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $q->whereDate('transaksi.created_at', '<=', $request->input('sampai'));
        }
        if ($request->filled('transaksi_id_kartu')) {
            $q->where('transaksi.transaksi_id_kartu', $request->input('transaksi_id_kartu'));
        }
        if ($request->filled('transaksi_id_gerai')) {
            $gid = $request->input('transaksi_id_gerai');
            $q->where(function ($w) use ($gid) {
                $w->where('transaksi.transaksi_id_gerai', $gid)
                    ->orWhereHas('hasItems', fn ($i) => $i->where('item_id_gerai', $gid));
            });
        }
        if ($request->filled('transaksi_jenis')) {
            $q->where('transaksi.transaksi_jenis', $request->input('transaksi_jenis'));
        }
        if ($request->filled('transaksi_status')) {
            $q->where('transaksi.transaksi_status', $request->input('transaksi_status'));
        }
        $role = auth()->user()?->role;
        if ($role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $q->where(function ($w) use ($ids) {
                $w->whereIn('transaksi.transaksi_id_gerai', $ids)
                    ->orWhereHas('hasItems', fn ($i) => $i->whereIn('item_id_gerai', $ids));
            });
        } elseif (in_array($role, ['orang_tua', 'pengguna'])) {
            $ids = Kartu::where('kartu_id_user', auth()->id())->orWhere('kartu_id_orangtua', auth()->id())->pluck('kartu_id');
            $q->whereIn('transaksi.transaksi_id_kartu', $ids);
        }

        return $q->orderByDesc('transaksi.transaksi_id');
    }

    protected function namaGerai($r): string
    {
        $dariItem = $r->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->values()->all();
        if (! empty($dariItem)) {
            return implode(', ', $dariItem);
        }

        return $r->hasGerai?->gerai_nama ?? '-';
    }

    protected function geraiOptions(): array
    {
        $q = Gerai::query();
        if (auth()->user()?->role === 'vendor') {
            $q->where('gerai_id_vendor', auth()->id());
        }

        return $q->orderBy('gerai_nama')->pluck('gerai_nama', 'gerai_id')->all();
    }

    protected function kartuOptions(GeneralRequest $request): array
    {
        $kartuQ = Kartu::query()->with('hasUser');
        $role = auth()->user()?->role;
        if ($role === 'orang_tua') {
            $kartuQ->where('kartu_id_orangtua', auth()->id());
        } elseif ($role === 'pengguna') {
            $kartuQ->where('kartu_id_user', auth()->id());
        } elseif ($role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $kartuIds = Transaksi::where(function ($w) use ($ids) {
                $w->whereIn('transaksi_id_gerai', $ids)->orWhereHas('hasItems', fn ($qq) => $qq->whereIn('item_id_gerai', $ids));
            })->pluck('transaksi_id_kartu')->unique()->filter();
            if ($kartuIds->isNotEmpty()) {
                $kartuQ->whereIn('kartu_id', $kartuIds);
            }
        }

        return $kartuQ->get()->mapWithKeys(fn ($k) => [$k->kartu_id => ($k->hasUser?->name ?? $k->kartu_barcode).' — '.$k->kartu_barcode])->toArray();
    }

    protected function ringkas($query): array
    {
        $rows = (clone $query)->where('transaksi.transaksi_status', 'berhasil')->get();

        // Rincian per fee: [code => total Rp] + [code => jml transaksi].
        // Daftar selalu ikut master fee (yang nol tetap tampil); kode warisan
        // di transaksi lama digabung ke satu bucket "lainnya" agar total tetap rekonsiliasi.
        $nama = Fee::namaMap();
        $urut = array_flip(array_keys($nama));
        $per = array_fill_keys(array_keys($nama), 0);
        $hit = array_fill_keys(array_keys($nama), 0);
        $lainTotal = 0;
        $lainTrx = 0;
        foreach ($rows as $r) {
            $adaLain = false;
            foreach ($r->feeRincian() as $code => $nominal) {
                $code = (string) $code;
                if (array_key_exists($code, $nama)) {
                    $per[$code] = ($per[$code] ?? 0) + (int) $nominal;
                    $hit[$code] = ($hit[$code] ?? 0) + 1;
                } else {
                    $lainTotal += (int) $nominal;
                    $adaLain = true;
                }
            }
            if ($adaLain) {
                $lainTrx++;
            }
        }
        uksort($per, fn ($a, $b) => ($urut[$a] ?? 999) <=> ($urut[$b] ?? 999));
        if ($lainTrx > 0) {
            $per['lainnya'] = $lainTotal;
            $hit['lainnya'] = $lainTrx;
            $nama['lainnya'] = 'Lainnya (riwayat)';
        }

        return [
            'total' => (int) $rows->sum('transaksi_total'),
            'fee_kelola' => (int) $rows->sum(fn ($r) => $r->feeTotal()),
            'fee_sistem' => 0,
            'bersih' => (int) $rows->sum('transaksi_bersih'),
            'jumlah' => $rows->count(),
            'fee_per' => $per,
            'fee_hit' => $hit,
            'fee_nama' => $nama,
            'fee_persen' => Fee::persenMap(),
        ];
    }

    // === Hub: GET /laporan → laporan.index ===
    public function getIndex(GeneralRequest $request)
    {
        $ringkas = $this->ringkas($this->baseQuery($request));
        $role = auth()->user()?->role;
        $boleh = fn ($lap) => ! in_array($lap, [
            'kasir_sekolah' => ['transaksi', 'fee', 'vendor'],
            'orang_tua' => ['vendor', 'penarikan'],
            'pengguna' => ['vendor', 'penarikan'],
        ][$role] ?? [], true);

        $geraiQ = Gerai::query();
        if ($role === 'vendor') {
            $geraiQ->where('gerai_id_vendor', auth()->id());
        }
        $penarikanQ = Penarikan::query()->where('penarikan_status', 'diajukan');
        if ($role === 'vendor') {
            $penarikanQ->whereIn('penarikan_id_gerai', Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id'));
        }

        return $this->views('pages.laporan.index', [
            'model' => $this->model,
            'ringkas' => $ringkas,
            'boleh' => $boleh,
            'jmlGerai' => (clone $geraiQ)->count(),
            'tarikAjuan' => $penarikanQ->count(),
            'tarikNominal' => (int) $penarikanQ->sum('penarikan_nominal'),
        ]);
    }

    // === Laporan Transaksi: GET /laporan/transaksi ===
    public function getTransaksi(GeneralRequest $request)
    {
        $this->assertLaporanAkses('transaksi');
        $query = $this->baseQuery($request);
        $filterKartu = null;
        if ($request->filled('transaksi_id_kartu')) {
            $filterKartu = Kartu::with('hasUser')->find($request->input('transaksi_id_kartu'));
        }

        return $this->views('pages.laporan.transaksi', [
            'model' => $this->model,
            'data' => $query->cursorPaginate($request->input('per_page', 25))->withQueryString(),
            'ringkas' => $this->ringkas($query),
            'jenis' => TransaksiJenisEnum::getOptions(),
            'status' => TransaksiStatusEnum::getOptions(),
            'kartuOptions' => $this->kartuOptions($request),
            'geraiOptions' => $this->geraiOptions(),
            'filterKartu' => $filterKartu,
        ]);
    }

    // === Laporan Fee: GET /laporan/fee ===
    public function getFee(GeneralRequest $request)
    {
        $this->assertLaporanAkses('fee');
        $ringkas = $this->ringkas($this->baseQuery($request));
        $feeDipilih = $request->input('transaksi_fee');

        return $this->views('pages.laporan.fee', [
            'model' => $this->model,
            'data' => $this->feeRows($request)->cursorPaginate($request->input('per_page', 25))->withQueryString(),
            'ringkas' => $ringkas,
            'feeOptions' => $this->feeOptions($ringkas),
            'feeDipilih' => $feeDipilih,
            'jenis' => TransaksiJenisEnum::getOptions(),
            'geraiOptions' => $this->geraiOptions(),
        ]);
    }

    // Opsi dropdown fee: master + bucket lainnya bila ada.
    private function feeOptions(array $ringkas): array
    {
        return $ringkas['fee_nama'];
    }

    // Baris transaksi berstatus berhasil yang memuat fee; bila filter fee diisi,
    // hanya yang memuat fee tersebut ('lainnya' = kode warisan di luar master).
    private function feeRows(GeneralRequest $request)
    {
        $q = $this->baseQuery($request)->where('transaksi.transaksi_status', 'berhasil');
        $code = $request->filled('transaksi_fee') ? (string) $request->input('transaksi_fee') : null;
        $master = array_keys(Fee::namaMap());
        $ids = (clone $q)->get()->filter(fn ($r) => $code === null
            ? $r->feeTotal() > 0
            : ($code === 'lainnya'
                ? collect($r->feeRincian())->keys()->map('strval')->diff($master)->isNotEmpty()
                : array_key_exists($code, $r->feeRincian())))
            ->pluck('transaksi_id')->all() ?: [0];
        $q->whereIn('transaksi.transaksi_id', $ids);

        return $q;
    }

    // === Laporan Vendor: GET /laporan/vendor ===
    public function getVendor(GeneralRequest $request)
    {
        $this->assertLaporanAkses('vendor');

        return $this->views('pages.laporan.vendor', array_merge([
            'model' => $this->model,
            'geraiOptions' => $this->geraiOptions(),
        ], $this->dataVendor($request)));
    }

    private function dataVendor(GeneralRequest $request): array
    {
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');

        $geraiQ = Gerai::query()->with('hasVendor')->orderBy('gerai_nama');
        if (auth()->user()?->role === 'vendor') {
            $geraiQ->where('gerai_id_vendor', auth()->id());
        }
        if ($request->filled('transaksi_id_gerai')) {
            $geraiQ->where('gerai.gerai_id', $request->input('transaksi_id_gerai'));
        }
        $gerais = $geraiQ->get();

        $rekap = [];
        $tot = ['omzet' => 0, 'fee' => 0, 'bersih' => 0, 'trx' => 0, 'diajukan' => 0, 'selesai' => 0];
        foreach ($gerais as $g) {
            $r = $this->rekapGerai((int) $g->gerai_id, $dari, $sampai);
            $rekap[] = array_merge(['gerai' => $g], $r);
            foreach (array_keys($tot) as $k) {
                $tot[$k] += $r[$k];
            }
        }

        return compact('rekap', 'tot');
    }

    // Omzet dari item per gerai; fee dibagi proporsional seperti PenarikanController::rekapHarian.
    private function rekapGerai(int $geraiId, ?string $dari, ?string $sampai): array
    {
        $periode = fn ($q) => $q->when($dari, fn ($w) => $w->whereDate('transaksi.created_at', '>=', $dari))
            ->when($sampai, fn ($w) => $w->whereDate('transaksi.created_at', '<=', $sampai));

        $omzet = (int) TransaksiItem::join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->where('transaksi_item.item_id_gerai', $geraiId)
            ->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
            ->tap($periode)->sum('transaksi_item.item_subtotal');

        $trx = Transaksi::with('hasFees')->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
            ->tap($periode)
            ->where(fn ($q) => $q->where('transaksi_id_gerai', $geraiId)
                ->orWhereHas('hasItems', fn ($qq) => $qq->where('item_id_gerai', $geraiId)))
            ->get();
        $feeSemua = $trx->sum(fn ($t) => $t->feeTotal());
        $subSemua = (int) TransaksiItem::join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->whereIn('transaksi_item.item_id_transaksi', $trx->pluck('transaksi_id')->all() ?: [0])
            ->tap($periode)->sum('transaksi_item.item_subtotal');
        $fee = ($omzet > 0 && $subSemua > 0) ? (int) round($feeSemua * ($omzet / $subSemua)) : 0;

        $tarik = Penarikan::where('penarikan_id_gerai', $geraiId)
            ->when($dari, fn ($w) => $w->whereDate('penarikan_tanggal', '>=', $dari))
            ->when($sampai, fn ($w) => $w->whereDate('penarikan_tanggal', '<=', $sampai));

        return [
            'omzet' => $omzet,
            'fee' => $fee,
            'bersih' => max($omzet - $fee, 0),
            'trx' => $trx->count(),
            'diajukan' => (int) (clone $tarik)->where('penarikan_status', 'diajukan')->sum('penarikan_nominal'),
            'selesai' => (int) (clone $tarik)->where('penarikan_status', 'diselesaikan')->sum('penarikan_nominal'),
        ];
    }

    // === Laporan Penarikan: GET /laporan/penarikan ===
    public function getPenarikan(GeneralRequest $request)
    {
        $this->assertLaporanAkses('penarikan');

        return $this->views('pages.laporan.penarikan', array_merge([
            'model' => $this->model,
            'geraiOptions' => $this->geraiOptions(),
        ], $this->dataPenarikan($request)));
    }

    private function dataPenarikan(GeneralRequest $request): array
    {
        $q = Penarikan::query()->with(['hasGerai', 'hasKasir', 'hasAdmin']);
        if ($request->filled('dari')) {
            $q->whereDate('penarikan_tanggal', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $q->whereDate('penarikan_tanggal', '<=', $request->input('sampai'));
        }
        if ($request->filled('transaksi_id_gerai')) {
            $q->where('penarikan.penarikan_id_gerai', $request->input('transaksi_id_gerai'));
        }
        if ($request->filled('penarikan_status')) {
            $q->where('penarikan.penarikan_status', $request->input('penarikan_status'));
        }
        if (auth()->user()?->role === 'vendor') {
            $q->whereIn('penarikan.penarikan_id_gerai', Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id'));
        }
        $rows = (clone $q)->orderByDesc('penarikan.penarikan_id')->get();
        $perStatus = $rows->groupBy('penarikan_status')->map(fn ($g) => ['jml' => $g->count(), 'nominal' => (int) $g->sum('penarikan_nominal')]);

        return [
            'rows' => $rows,
            'perStatus' => $perStatus,
            'status' => PenarikanStatusEnum::getOptions(),
        ];
    }

    // === Cetak (web view tab baru): GET /laporan/cetak/{jenis} ===
    // Pola report_invoice: filter di halaman → tombol Cetak → tab baru berisi laporan siap Ctrl+P.
    public function getCetak(GeneralRequest $request, $jenis)
    {
        if (! in_array($jenis, ['transaksi', 'fee', 'vendor', 'penarikan'], true)) {
            abort(404);
        }
        $this->assertLaporanAkses($jenis);
        $judul = ['transaksi' => 'Laporan Transaksi', 'fee' => 'Laporan Fee', 'vendor' => 'Laporan Vendor', 'penarikan' => 'Laporan Penarikan'][$jenis];
        $data = ['judul' => $judul, 'jenis' => $jenis, 'model' => $this->model];
        if ($jenis === 'transaksi') {
            $query = $this->baseQuery($request);
            $data += [
                'data' => (clone $query)->cursorPaginate($request->input('per_page', 100))->withQueryString(),
                'ringkas' => $this->ringkas($query),
            ];
        } elseif ($jenis === 'fee') {
            $ringkas = $this->ringkas($this->baseQuery($request));
            $data += [
                'data' => $this->feeRows($request)->cursorPaginate($request->input('per_page', 100))->withQueryString(),
                'ringkas' => $ringkas,
                'feeDipilih' => $request->input('transaksi_fee'),
            ];
        } elseif ($jenis === 'vendor') {
            $data += $this->dataVendor($request);
        } else {
            $data += $this->dataPenarikan($request);
        }

        // Export Excel: HTML yang sama diunduh sebagai .xls (dibuka Microsoft Excel).
        if ($request->input('export') === 'xls') {
            return response()->view("pages.laporan.cetak.{$jenis}", $data, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="laporan-'.$jenis.'-'.now()->format('YmdHis').'.xls"',
            ]);
        }

        return $this->views("pages.laporan.cetak.{$jenis}", $data);
    }
    // PDF sengaja tidak ada — cetak lewat Ctrl+P di halaman web (lihat @media print di app.css).
    public function getEkspor(GeneralRequest $request)
    {
        $report = $request->input('report', 'transaksi');
        $this->assertLaporanAkses($report);
        $nama = "laporan-{$report}-".now()->format('YmdHis').'.csv';

        if ($report === 'vendor') {
            return $this->eksporVendor($request, $nama);
        }
        if ($report === 'penarikan') {
            return $this->eksporPenarikan($request, $nama);
        }

        // transaksi: kolom fee dinamis per jenis. fee: 1 kolom Fee (total + rincian).
        if ($report === 'fee') {
            $rows = $this->feeRows($request)->with('hasItems')->get();
            $feeCodes = [];
        } else {
            $rows = $this->baseQuery($request)->with('hasItems')->get();
            $ringkasFee = $this->ringkas($this->baseQuery($request));
            $feeCodes = array_keys($ringkasFee['fee_per']);
        }
        $feeMaster = array_keys(Fee::namaMap());

        if ($report === 'fee') {
            return response()->streamDownload(function () use ($rows, $request) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['kode', 'tanggal', 'pengguna', 'gerai', 'total', 'fee', 'tarif', 'nominal', 'status']);
                foreach ($rows as $r) {
                    foreach (feeTampil($r->feeRincian(), $request->input('transaksi_fee')) as $kode => $nominal) {
                        fputcsv($out, [
                            $r->transaksi_kode ?? '#'.$r->transaksi_id,
                            $r->created_at?->format('d/m/Y H:i'),
                            $r->hasKartu?->hasUser?->name ?? '-',
                            $this->namaGerai($r),
                            $r->transaksi_total,
                            $kode,
                            feeTarif($kode) ?: '-',
                            $nominal,
                            $r->transaksi_status,
                        ]);
                    }
                }
                fclose($out);
            }, $nama, ['Content-Type' => 'text/csv']);
        }

        return response()->streamDownload(function () use ($rows, $feeCodes, $feeMaster) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(
                ['tanggal', 'pengguna', 'gerai', 'jenis', 'total', 'fee_total'],
                array_map(fn ($c) => 'fee_'.$c, $feeCodes),
                ['bersih', 'saldo_akhir', 'status']
            ));
            foreach ($rows as $r) {
                $rincian = $r->feeRincian();
                $lain = collect($rincian)->except($feeMaster)->sum();
                fputcsv($out, array_merge([
                    $r->created_at?->format('d/m/Y H:i'),
                    $r->hasKartu?->hasUser?->name ?? '-',
                    $this->namaGerai($r),
                    $r->transaksi_jenis,
                    $r->transaksi_total,
                    $r->feeTotal(),
                ],
                    array_map(fn ($c) => $c === 'lainnya' ? $lain : ($rincian[$c] ?? 0), $feeCodes),
                    [$r->transaksi_bersih, $r->transaksi_saldo_akhir, $r->transaksi_status]));
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv']);
    }

    private function eksporVendor(GeneralRequest $request, string $nama)
    {
        // Bangun ulang rekap yang sama dengan getVendor agar CSV = isi layar.
        $sub = $this->getVendor($request);
        $rekap = $sub->getData()['rekap'] ?? [];

        return response()->streamDownload(function () use ($rekap) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['gerai', 'vendor', 'bank', 'no_rekening', 'atas_nama', 'omzet', 'fee', 'bersih', 'transaksi', 'penarikan_diajukan', 'penarikan_selesai']);
            foreach ($rekap as $r) {
                fputcsv($out, [
                    $r['gerai']->gerai_nama, $r['gerai']->hasVendor?->name ?? '-',
                    $r['gerai']->gerai_bank ?? '', $r['gerai']->gerai_no_rekening ?? '', $r['gerai']->gerai_nama_rekening ?? '',
                    $r['omzet'], $r['fee'], $r['bersih'], $r['trx'], $r['diajukan'], $r['selesai'],
                ]);
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv']);
    }

    private function eksporPenarikan(GeneralRequest $request, string $nama)
    {
        $sub = $this->getPenarikan($request);
        $rows = $sub->getData()['rows'] ?? [];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['tanggal', 'gerai', 'bank', 'no_rekening', 'atas_nama', 'nominal', 'status', 'kasir', 'admin']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->penarikan_tanggal?->format('d/m/Y'),
                    $r->hasGerai?->gerai_nama ?? '-',
                    $r->hasGerai?->gerai_bank ?? '',
                    $r->hasGerai?->gerai_no_rekening ?? '',
                    $r->hasGerai?->gerai_nama_rekening ?? '',
                    $r->penarikan_nominal,
                    $r->penarikan_status,
                    $r->hasKasir?->name ?? '-',
                    $r->hasAdmin?->name ?? '-',
                ]);
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv']);
    }
}
