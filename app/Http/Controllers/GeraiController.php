<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\GeraiStatusEnum;
use App\Enums\Ekantin\ItemStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\TransaksiItem;
use App\Models\User;

class GeraiController extends Controller
{
    use ControllerTrait {
        getUpdate as traitGetUpdate;
        postUpdate as traitPostUpdate;
        getDelete as traitGetDelete;
        postDelete as traitPostDelete;
    }

    public function __construct(Gerai $model)
    {
        $this->model = $model::getModel();
    }

    // Vendor hanya boleh ubah gerainya sendiri; hapus tidak boleh sama sekali (policy).
    private function assertGeraiAccess(int $id): void
    {
        if (auth()->user()?->role !== 'vendor') {
            return;
        }
        $milik = Gerai::where('gerai_id', $id)->where('gerai_id_vendor', auth()->id())->exists();
        if (! $milik) {
            abort(403, 'Bukan gerai Anda');
        }
    }

    public function getUpdate(GeneralRequest $request, $id)
    {
        $this->assertGeraiAccess((int) $id);

        return $this->traitGetUpdate($request, $id);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->assertGeraiAccess((int) $id);

        return $this->traitPostUpdate($request, $id);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        // Lapis kedua setelah policy: vendor tidak boleh hapus gerai apa pun.
        if (auth()->user()?->role === 'vendor') {
            abort(403, 'Vendor tidak boleh menghapus gerai');
        }
        $this->assertGeraiAccess((int) $id);

        return $this->traitGetDelete($request, $id);
    }

    public function postDelete(GeneralRequest $request)
    {
        if (auth()->user()?->role === 'vendor') {
            abort(403, 'Vendor tidak boleh menghapus gerai');
        }

        return $this->traitPostDelete($request);
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'status' => GeraiStatusEnum::getOptions(),
            'vendor' => User::where('role', 'vendor')->pluck('name', 'id'),
        ];

        return array_merge($default, $data);
    }

    protected function getData()
    {
        $q = $this->model->query()->filter()->sort();
        if (auth()->user()?->role === 'vendor') {
            $q->where('gerai.gerai_id_vendor', auth()->id());
        }

        return $q;
    }

    // === Pesanan Gerai (non-CRUD) — di-handle via Route::auto('/gerai') ===
    // GET /gerai/pesanan → gerai.getPesanan  |  POST /gerai/pesanan/{id} → gerai.postPesanan
    private function geraiIdsPesanan(): array
    {
        $q = Gerai::query();
        if (auth()->user()?->role === 'vendor') {
            $q->where('gerai_id_vendor', auth()->id());
        }
        return $q->pluck('gerai_id')->all();
    }

    public function getPesanan(GeneralRequest $request)
    {
        $status = $request->input('status', 'baru');
        $ids = $this->geraiIdsPesanan();
        $items = TransaksiItem::query()
            ->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->leftJoin('kartu', 'kartu.kartu_id', '=', 'transaksi.transaksi_id_kartu')
            ->leftJoin('users as pengguna', 'pengguna.id', '=', 'kartu.kartu_id_user')
            ->leftJoin('gerai', 'gerai.gerai_id', '=', 'transaksi_item.item_id_gerai')
            ->where('transaksi.transaksi_jenis', 'beli')
            ->where('transaksi.transaksi_status', 'berhasil')
            ->whereIn('transaksi_item.item_id_gerai', $ids)
            ->when($status !== 'semua', fn ($q) => $q->where('transaksi_item.item_status', $status))
            ->orderBy('transaksi.created_at')
            ->select('transaksi_item.*')
            ->with(['hasTransaksi.hasKartu.hasUser', 'hasGerai'])
            ->get();

        $data = [
            'model' => $this->model,
            'items' => $items,
            'status' => $status,
            'statusOptions' => array_merge(['semua' => 'Semua'], ItemStatusEnum::getOptions()),
            'baruCount' => TransaksiItem::query()
                ->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
                ->whereIn('transaksi_item.item_id_gerai', $ids)->where('transaksi_item.item_status', 'baru')->count(),
        ];

        if ($request->expectsJson() || $request->input('format') === 'json') {
            return response()->json([
                'baru_count' => $data['baruCount'],
                'items' => $items->map(fn ($i) => [
                    'id' => $i->item_id,
                    'nama' => $i->item_nama,
                    'qty' => $i->item_qty,
                    'status' => $i->item_status,
                    'pengguna' => $i->hasTransaksi?->hasKartu?->hasUser?->name ?? '-',
                    'gerai' => $i->hasGerai?->gerai_nama ?? '-',
                    'waktu' => $i->hasTransaksi?->created_at?->format('H:i'),
                ]),
            ]);
        }

        return $this->views('pages.gerai-pesanan.pool', $data);
    }

    public function postPesanan(GeneralRequest $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:disiapkan,selesai']);
        $item = TransaksiItem::whereKey($id)->whereIn('item_id_gerai', $this->geraiIdsPesanan())->firstOrFail();
        $item->update(['item_status' => $data['status']]);

        if ($request->expectsJson()) {
            return response()->json(['status' => true, 'message' => TOAST_SUCCESS, 'data' => $item->fresh()]);
        }
        flash()->success(TOAST_SUCCESS);
        return redirect()->back();
    }

    // Reprint per gerai jika struk hilang — GET /gerai/pesanan-cetak/{id}
    // $id = transaksi_id, filter item hanya gerai milik vendor (2 gerai terpisah)
    public function getPesananCetak(GeneralRequest $request, $id)
    {
        $ids = $this->geraiIdsPesanan();
        $trx = \App\Models\Transaksi::with(['hasKartu.hasUser', 'hasItems.hasGerai', 'hasKasir'])->findOrFail($id);
        // filter item hanya untuk gerai yang boleh dilihat
        $filtered = $trx->hasItems->whereIn('item_id_gerai', $ids);
        if ($filtered->isEmpty() && auth()->user()?->role === 'vendor') {
            abort(403, 'Transaksi tidak mengandung pesanan gerai Anda.');
        }
        $trx->setRelation('hasItems', $filtered);
        $kelompok = $filtered->groupBy(fn ($i) => $i->hasGerai?->gerai_nama ?? '-');

        return $this->views('pages.gerai-pesanan.cetak', [
            'trx' => $trx,
            'kelompok' => $kelompok,
            'model' => $this->model,
        ]);
    }
}
