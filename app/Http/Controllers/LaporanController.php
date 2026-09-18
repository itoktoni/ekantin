<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\TransaksiJenisEnum;
use App\Enums\Ekantin\TransaksiStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Kartu;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    use ControllerTrait;

    public function __construct(Transaksi $model)
    {
        $this->model = $model::getModel();
    }

    protected function baseQuery(GeneralRequest $request)
    {
        $q = Transaksi::query()->with(['hasKartu.hasUser', 'hasGerai', 'hasItems.hasGerai']);
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
        } elseif (in_array($role, ['orang_tua', 'siswa'])) {
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

    protected function ringkas($query): array
    {
        $rows = (clone $query)->where('transaksi.transaksi_status', 'berhasil')->get();

        return [
            'total' => (int) $rows->sum('transaksi_total'),
            'fee_kelola' => (int) $rows->sum(fn ($r) => $r->feeTotal()),
            'fee_sistem' => 0,
            'bersih' => (int) $rows->sum('transaksi_bersih'),
            'jumlah' => $rows->count(),
        ];
    }

    public function getIndex(GeneralRequest $request)
    {
        $query = $this->baseQuery($request);
        // kartu filter options scoped per role
        $kartuQ = Kartu::query()->with('hasUser');
        $role = auth()->user()?->role;
        if ($role === 'orang_tua') {
            $kartuQ->where('kartu_id_orangtua', auth()->id());
        } elseif ($role === 'siswa') {
            $kartuQ->where('kartu_id_user', auth()->id());
        } elseif ($role === 'vendor') {
            // vendor tidak filter kartu, tapi tetap tampil siswa yang pernah transaksi di gerainya
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $kartuIds = Transaksi::where(function($w) use ($ids){ $w->whereIn('transaksi_id_gerai',$ids)->orWhereHas('hasItems', fn($q)=>$q->whereIn('item_id_gerai',$ids)); })->pluck('transaksi_id_kartu')->unique()->filter();
            if ($kartuIds->isNotEmpty()) $kartuQ->whereIn('kartu_id', $kartuIds);
        }
        $kartuOptions = $kartuQ->get()->mapWithKeys(fn($k)=>[$k->kartu_id => ($k->hasUser?->name ?? $k->kartu_barcode).' — '.$k->kartu_barcode])->toArray();
        $filterKartu = null;
        if ($request->filled('transaksi_id_kartu')) {
            $filterKartu = Kartu::with('hasUser')->find($request->input('transaksi_id_kartu'));
        }

        return $this->views('pages.laporan.index', [
            'model' => $this->model,
            'data' => $query->cursorPaginate($request->input('per_page', 25))->withQueryString(),
            'ringkas' => $this->ringkas($query),
            'jenis' => TransaksiJenisEnum::getOptions(),
            'status' => TransaksiStatusEnum::getOptions(),
            'kartuOptions' => $kartuOptions,
            'filterKartu' => $filterKartu,
        ]);
    }

    public function getEkspor(GeneralRequest $request)
    {
        $rows = $this->baseQuery($request)->with('hasItems')->get();
        $format = $request->input('format', 'csv');
        if ($format === 'pdf') {
            return Pdf::loadView('pdf.laporan', ['rows' => $rows, 'ringkas' => $this->ringkas($this->baseQuery($request))])
                ->download('laporan-ekantin-'.now()->format('YmdHis').'.pdf');
        }
        $nama = 'laporan-ekantin-'.now()->format('YmdHis').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['tanggal', 'siswa', 'gerai', 'jenis', 'total', 'fee_kelola', 'fee_sistem', 'bersih', 'saldo_akhir', 'status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->created_at?->format('d/m/Y H:i'),
                    $r->hasKartu?->hasUser?->name ?? '-',
                    $this->namaGerai($r),
                    $r->transaksi_jenis,
                    $r->transaksi_total,
                    $r->feeTotal(),
                    0,
                    $r->transaksi_bersih,
                    $r->transaksi_saldo_akhir,
                    $r->transaksi_status,
                ]);
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv']);
    }
}
