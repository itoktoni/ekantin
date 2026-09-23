<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\ItemStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\TransaksiItem;

class GeraiPesananController extends Controller
{
    use ControllerTrait;

    public function __construct(TransaksiItem $model)
    {
        $this->model = $model::getModel();
    }

    protected function geraiIds(): array
    {
        $q = Gerai::query();
        if (auth()->user()?->role === 'vendor') {
            $q->where('gerai_id_vendor', auth()->id());
        }

        return $q->pluck('gerai_id')->all();
    }

    // Pool notifikasi: item beli yang belum selesai, scope gerai milik vendor.
    public function getPool(GeneralRequest $request)
    {
        $status = $request->input('status', 'baru');
        $ids = $this->geraiIds();
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

    // Tandai item disiapkan/selesai. Vendor hanya boleh ubah item gerainya.
    public function postStatus(GeneralRequest $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:disiapkan,selesai']);
        $item = TransaksiItem::whereKey($id)->whereIn('item_id_gerai', $this->geraiIds())->firstOrFail();
        $item->update(['item_status' => $data['status']]);

        if ($request->expectsJson()) {
            return response()->json(['status' => true, 'message' => TOAST_SUCCESS, 'data' => $item->fresh()]);
        }
        flash()->success(TOAST_SUCCESS);

        return redirect()->back();
    }
}
