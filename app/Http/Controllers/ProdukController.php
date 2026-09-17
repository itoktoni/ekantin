<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\ProdukKategoriEnum;
use App\Enums\Ekantin\ProdukStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Produk;
use Illuminate\Validation\ValidationException;

class ProdukController extends Controller
{
    use ControllerTrait {
        postCreate as traitPostCreate;
        postUpdate as traitPostUpdate;
        getUpdate as traitGetUpdate;
        getDelete as traitGetDelete;
        postDelete as traitPostDelete;
        getShow as traitGetShow;
    }

    public function __construct(Produk $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $gerai = Gerai::query();
        if (auth()->user()?->role === 'vendor') {
            $gerai->where('gerai_id_vendor', auth()->id());
        }

        return array_merge([
            'model' => $this->model,
            'status' => ProdukStatusEnum::getOptions(),
            'kategori' => ProdukKategoriEnum::getOptions(),
            'gerai' => $gerai->pluck('gerai_nama', 'gerai_id'),
        ], $data);
    }

    protected function getData()
    {
        $q = $this->model->leftJoinRelationship('hasGerai')->filter()->sort();
        if (auth()->user()?->role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $q->whereIn('produk.produk_id_gerai', $ids);
        }

        return $q;
    }

    // Vendor hanya boleh kelola produk gerainya sendiri (cek IDOR: ganti ID di URL/form).
    private function assertGeraiMilik(int $geraiId): void
    {
        if (auth()->user()?->role !== 'vendor') {
            return;
        }
        $milik = Gerai::where('gerai_id', $geraiId)->where('gerai_id_vendor', auth()->id())->exists();
        if (! $milik) {
            abort(403, 'Bukan gerai Anda');
        }
    }

    private function assertProdukMilik(int $id): void
    {
        if (auth()->user()?->role !== 'vendor') {
            return;
        }
        $produk = Produk::findOrFail($id);
        $this->assertGeraiMilik((int) $produk->produk_id_gerai);
    }

    public function getUpdate(GeneralRequest $request, $id)
    {
        $this->assertProdukMilik((int) $id);

        return $this->traitGetUpdate($request, $id);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        $this->assertProdukMilik((int) $id);

        return $this->traitGetDelete($request, $id);
    }

    public function getShow(GeneralRequest $request, $id)
    {
        $this->assertProdukMilik((int) $id);

        return $this->traitGetShow($request, $id);
    }

    public function postDelete(GeneralRequest $request)
    {
        if (auth()->user()?->role === 'vendor') {
            $ids = array_map('intval', (array) $request->input('ids', []));
            $milik = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id')->all();
            $asing = Produk::whereIn('produk_id', $ids)->whereNotIn('produk_id_gerai', $milik)->exists();
            if ($asing) {
                abort(403, 'Ada produk bukan milik Anda');
            }
        }

        return $this->traitPostDelete($request);
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->assertGeraiMilik((int) $request->input('produk_id_gerai'));
        $this->mergeFoto($request);

        return $this->traitPostCreate($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->assertProdukMilik((int) $id);
        // Cegah pindah produk sendiri ke gerai orang lain via ganti produk_id_gerai.
        if ($request->filled('produk_id_gerai')) {
            $this->assertGeraiMilik((int) $request->input('produk_id_gerai'));
        }
        $this->mergeFoto($request);

        return $this->traitPostUpdate($request, $id);
    }

    private function mergeFoto(GeneralRequest $request): void
    {
        if ($request->hasFile('produk_foto')) {
            try {
                $request->merge(['produk_foto' => uploadFile($request->file('produk_foto'), 'produk', ['max_size' => 2048])]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['produk_foto' => $e->getMessage()]);
            }
        }
    }
}
