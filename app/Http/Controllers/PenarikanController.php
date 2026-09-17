<?php

namespace App\Http\Controllers;

use App\Actions\Ekantin\WithdrawAction;
use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\PenarikanStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Penarikan;
use Illuminate\Validation\ValidationException;

class PenarikanController extends Controller
{
    use ControllerTrait {
        postUpdate as traitPostUpdate;
        getUpdate as traitGetUpdate;
        getDelete as traitGetDelete;
        postDelete as traitPostDelete;
        getShow as traitGetShow;
    }

    public function __construct(Penarikan $model)
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
            'status' => PenarikanStatusEnum::getOptions(),
            'gerai' => $gerai->pluck('gerai_nama', 'gerai_id'),
        ], $data);
    }

    protected function getData()
    {
        $q = $this->model->leftJoinRelationship('hasGerai')->filter()->sort();
        if (auth()->user()?->role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $q->whereIn('penarikan.penarikan_id_gerai', $ids);
        }

        return $q;
    }

    public function postCreate(GeneralRequest $request)
    {
        $data = $request->validate([
            'penarikan_id_gerai' => 'required|exists:gerai,gerai_id',
            'penarikan_nominal' => 'required|integer|min:1',
        ]);
        if (auth()->user()?->role === 'vendor') {
            $own = Gerai::where('gerai_id', $data['penarikan_id_gerai'])->where('gerai_id_vendor', auth()->id())->exists();
            if (! $own) {
                abort(403, 'Bukan gerai Anda.');
            }
        }
        $response = WithdrawAction::run([
            'gerai_id' => $data['penarikan_id_gerai'],
            'nominal' => $data['penarikan_nominal'],
            'id_admin' => auth()->id(),
        ]);
        if ($response['status'] && $request->hasFile('penarikan_bukti')) {
            try {
                $response['data']->update(['penarikan_bukti' => uploadFile($request->file('penarikan_bukti'), 'penarikan', ['max_size' => 2048])]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['penarikan_bukti' => $e->getMessage()]);
            }
        }

        return $this->response($response);
    }

    // Vendor hanya boleh kelola penarikan gerainya sendiri (cek IDOR: ganti ID di URL).
    private function assertPenarikanMilik(int $id): void
    {
        if (auth()->user()?->role !== 'vendor') {
            return;
        }
        $penarikan = Penarikan::findOrFail($id);
        $milik = Gerai::where('gerai_id', $penarikan->penarikan_id_gerai)->where('gerai_id_vendor', auth()->id())->exists();
        if (! $milik) {
            abort(403, 'Bukan penarikan gerai Anda');
        }
    }

    public function getUpdate(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);

        return $this->traitGetUpdate($request, $id);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);

        return $this->traitGetDelete($request, $id);
    }

    public function getShow(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);

        return $this->traitGetShow($request, $id);
    }

    public function postDelete(GeneralRequest $request)
    {
        if (auth()->user()?->role === 'vendor') {
            $ids = array_map('intval', (array) $request->input('ids', []));
            $milik = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id')->all();
            $asing = Penarikan::whereIn('penarikan_id', $ids)->whereNotIn('penarikan_id_gerai', $milik)->exists();
            if ($asing) {
                abort(403, 'Ada penarikan bukan milik Anda');
            }
        }

        return $this->traitPostDelete($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);
        if ($request->hasFile('penarikan_bukti')) {
            try {
                $request->merge(['penarikan_bukti' => uploadFile($request->file('penarikan_bukti'), 'penarikan', ['max_size' => 2048])]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['penarikan_bukti' => $e->getMessage()]);
            }
        }

        return $this->traitPostUpdate($request, $id);
    }
}
