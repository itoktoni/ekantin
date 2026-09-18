<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\TransaksiJenisEnum;
use App\Enums\Ekantin\TransaksiStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Transaksi;
use App\Services\Ekantin\SplitDana;

class TransaksiController extends Controller
{
    use ControllerTrait;

    public function __construct(Transaksi $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'jenis' => TransaksiJenisEnum::getOptions(),
            'status' => TransaksiStatusEnum::getOptions(),
        ];

        return array_merge($default, $data);
    }

    // Vendor hanya boleh melihat transaksi yang menyentuh gerainya. Orang tua/siswa hanya transaksi anaknya.
    protected function scopedQuery()
    {
        $q = $this->model->query();
        $role = auth()->user()?->role;
        if ($role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $q->where(function ($w) use ($ids) {
                $w->whereIn('transaksi.transaksi_id_gerai', $ids)
                    ->orWhereHas('hasItems', fn ($i) => $i->whereIn('item_id_gerai', $ids));
            });
        } elseif ($role === 'siswa') {
            $q->where('transaksi.transaksi_id_kartu', function ($qq) {
                $qq->select('kartu_id')->from('kartu')->where('kartu_id_user', auth()->id())->limit(1);
            });
        } elseif ($role === 'orang_tua') {
            $q->whereIn('transaksi.transaksi_id_kartu', function ($qq) {
                $qq->select('kartu_id')->from('kartu')->where('kartu_id_orangtua', auth()->id());
            });
        }

        return $q;
    }

    protected function getData()
    {
        $q = $this->scopedQuery()->with(['hasKartu.hasUser']);
        // filter virtual: nama_siswa & barcode via hasKartu
        $filters = request()->input('filters', []);
        $virtualHandled = false;
        foreach (['nama_siswa','barcode'] as $vf) {
            if (isset($filters[$vf])) {
                $cond = $filters[$vf];
                $val = is_array($cond) ? ($cond['$contains'] ?? $cond['$eq'] ?? reset($cond)) : $cond;
                $val = is_string($val) ? trim($val) : $val;
                if ($val !== '' && $val !== null) {
                    if ($vf === 'nama_siswa') {
                        $q->whereHas('hasKartu.hasUser', fn($qq) => $qq->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($val).'%']));
                    } elseif ($vf === 'barcode') {
                        $q->whereHas('hasKartu', fn($qq) => $qq->whereRaw('LOWER(kartu_barcode) LIKE ?', ['%'.strtolower($val).'%']));
                    }
                    $virtualHandled = true;
                    unset($filters[$vf]);
                }
            }
        }
        if ($virtualHandled) {
            request()->merge(['filters' => $filters]);
        }
        $q = $q->filter()->sort();
        if (! request()->filled('sort.0')) {
            $q->orderByDesc('transaksi.transaksi_id');
        }

        return $q;
    }

    // Transaksi bersifat read-only: halaman update dipakai sebagai halaman detail
    // yang menampilkan rincian item, komponen fee, dan pembagian dana ke gerai.
    public function getUpdate(GeneralRequest $request, $id)
    {
        $trx = $this->scopedQuery()
            ->with(['hasKartu.hasUser', 'hasGerai', 'hasItems.hasGerai', 'hasKasir'])
            ->findOrFail($id);

        $feeTotal = $trx->feeTotal();

        $subtotalPerGerai = [];
        foreach ($trx->hasItems as $item) {
            if ($item->item_id_gerai) {
                $subtotalPerGerai[$item->item_id_gerai] = ($subtotalPerGerai[$item->item_id_gerai] ?? 0) + (int) $item->item_subtotal;
            }
        }

        $pembagian = [];
        if ($subtotalPerGerai !== []) {
            $bagi = SplitDana::bagi($subtotalPerGerai, min($feeTotal, (int) $trx->transaksi_total));
            foreach ($bagi as $geraiId => $bersih) {
                $pembagian[] = [
                    'gerai' => $trx->hasItems->firstWhere('item_id_gerai', $geraiId)?->hasGerai?->gerai_nama ?? "Gerai #{$geraiId}",
                    'subtotal' => $subtotalPerGerai[$geraiId],
                    'fee' => $subtotalPerGerai[$geraiId] - $bersih,
                    'bersih' => $bersih,
                ];
            }
        }

        return $this->views('pages.transaksi.detail', [
            'model' => $trx,
            'feeTotal' => $feeTotal,
            'feeRincian' => $trx->feeRincian(),
            'feePersen' => \App\Models\Fee::persenMap(),
            'feeNama' => \App\Models\Fee::namaMap(),
            'pembagian' => $pembagian,
        ]);
    }

    public function getCreate(GeneralRequest $request)
    {
        abort(404);
    }

    public function postCreate(GeneralRequest $request)
    {
        abort(404);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        abort(404);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        abort(404);
    }

    public function postDelete(GeneralRequest $request)
    {
        abort(404);
    }
}
