<?php

namespace App\Http\Controllers;

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\ProdukKategoriEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Produk;
use App\Models\Transaksi;

class KasirController extends Controller
{
    use ControllerTrait;

    public function __construct(Transaksi $model)
    {
        $this->model = $model::getModel();
    }

    // POS: admin/kasir = semua gerai; vendor = HANYA produk gerai miliknya sendiri.
    public function getPos(GeneralRequest $request)
    {
        $cari = $request->input('cari');
        $produk = Produk::query()
            ->join('gerai', 'gerai.gerai_id', '=', 'produk.produk_id_gerai')
            ->where('produk.produk_status', 'tersedia')
            ->where('gerai.gerai_status', 'buka')
            ->when(auth()->user()?->role === 'vendor', fn ($q) => $q->where('gerai.gerai_id_vendor', auth()->id()))
            ->when($cari, fn ($q) => $q->where('produk.produk_nama', 'like', "%{$cari}%"))
            ->orderBy('gerai.gerai_nama')
            ->orderBy('produk.produk_kategori')
            ->orderBy('produk.produk_nama')
            ->select('produk.*')
            ->with('hasGerai')
            ->get()
            ->groupBy(fn ($p) => $p->hasGerai?->gerai_nama ?? '-');

        return $this->views('pages.kasir.pos', [
            'model' => $this->model,
            'kelompok' => $produk,
            'cari' => $cari,
            'kategori' => ProdukKategoriEnum::getOptions(),
            'idempotency' => 'POS-'.str()->random(16),
            'fees' => \App\Models\Fee::query()->orderBy('fee_id')->get(),
        ]);
    }

    public function postPos(GeneralRequest $request)
    {
        $data = $request->validate([
            'metode' => 'required|in:kartu,tunai,qris',
            'kartu_barcode' => 'required_if:metode,kartu|nullable|string|max:50',
            'idempotency' => 'required|string|max:64',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:0',
        ]);
        $items = collect($data['items'])
            ->map(fn ($i) => ['produk_id' => (int) $i['produk_id'], 'qty' => (int) $i['qty']])
            ->filter(fn ($i) => $i['qty'] > 0)->values()->all();
        if (empty($items)) {
            flash()->error('Pilih minimal satu produk.');

            return redirect()->route('kasir.pos');
        }
        // ponytail: vendor hanya boleh jual produk gerai miliknya — cegah tembak produk gerai lain via ID.
        if (auth()->user()?->role === 'vendor') {
            $milik = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id')->all();
            $asing = Produk::whereIn('produk_id', collect($items)->pluck('produk_id'))->whereNotIn('produk_id_gerai', $milik)->exists();
            if ($asing) {
                abort(403, 'Ada produk bukan milik gerai Anda');
            }
        }
        $response = ProcessPurchaseAction::run([
            'metode' => $data['metode'],
            'kartu_barcode' => $data['kartu_barcode'] ?? null,
            'items' => $items,
            'idempotency' => $data['idempotency'],
            'id_kasir' => auth()->id(),
        ]);
        if ($response['status']) {
            return redirect()->route('kasir.struk', ['id' => $response['data']->transaksi_id]);
        }

        return $this->response($response, redirect()->route('kasir.pos'));
    }

    // Struk: item dikelompokkan per gerai untuk diambil pengguna.
    // Cek IDOR: vendor/orang_tua/pengguna hanya boleh lihat struk transaksinya sendiri.
    public function getStruk(GeneralRequest $request, $id)
    {
        $trx = Transaksi::with(['hasKartu.hasUser', 'hasItems.hasGerai'])->findOrFail($id);
        $role = auth()->user()?->role;
        if ($role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $punya = ((int) $trx->transaksi_id_gerai && $ids->contains((int) $trx->transaksi_id_gerai))
                || $trx->hasItems->contains(fn ($i) => $ids->contains((int) $i->item_id_gerai));
            if (! $punya) {
                abort(403, 'Bukan transaksi gerai Anda');
            }
        } elseif ($role === 'pengguna') {
            $kartuId = \App\Models\Kartu::where('kartu_id_user', auth()->id())->value('kartu_id');
            if ((int) $trx->transaksi_id_kartu !== (int) $kartuId) {
                abort(403, 'Bukan transaksi Anda');
            }
        } elseif ($role === 'orang_tua') {
            $anak = \App\Models\Kartu::where('kartu_id_orangtua', auth()->id())->pluck('kartu_id');
            if (! $anak->contains((int) $trx->transaksi_id_kartu)) {
                abort(403, 'Bukan transaksi anak Anda');
            }
        }

        return $this->views('pages.kasir.struk', [
            'model' => $this->model,
            'trx' => $trx,
            'kelompok' => $trx->hasItems->groupBy(fn ($i) => $i->hasGerai?->gerai_nama ?? '-'),
            'feeRincian' => $trx->feeRincian(),
            'feeTotal' => $trx->feeTotal(),
            'feePersen' => \App\Models\Fee::persenMap(),
            'feeNama' => \App\Models\Fee::namaMap(),
        ]);
    }
}
