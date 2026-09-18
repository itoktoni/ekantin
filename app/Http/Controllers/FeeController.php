<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\AuditLog;
use App\Models\Fee;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    use ControllerTrait {
        postCreate as traitPostCreate;
        postUpdate as traitPostUpdate;
        postDelete as traitPostDelete;
    }

    public function __construct(Fee $model)
    {
        $this->model = $model::getModel();
    }

    private function validateCode(string $code, ?int $kecuali = null): void
    {
        if (! preg_match('/^[a-z0-9_\-]+$/', $code)) {
            throw ValidationException::withMessages(['code_fee' => 'Kode hanya huruf kecil, angka, underscore, dash.']);
        }
        $ada = Fee::where('code_fee', $code)->when($kecuali, fn ($q) => $q->where('fee_id', '!=', $kecuali))->exists();
        if ($ada) {
            throw ValidationException::withMessages(['code_fee' => 'Kode fee sudah dipakai.']);
        }
    }

    private function audit(string $aksi, $lama, $baru, $id): void
    {
        AuditLog::create([
            'audit_aksi' => $aksi,
            'audit_model' => 'fee',
            'audit_id_record' => $id,
            'audit_lama' => $lama,
            'audit_baru' => $baru,
            'audit_id_user' => auth()->id(),
        ]);
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->validateCode((string) $request->input('code_fee'));
        $response = $this->traitPostCreate($request);
        if ($response['status']) {
            $this->audit('create_fee', null, $request->only(['code_fee', 'nama_fee', 'value_fee']), $response['data']->fee_id ?? null);
        }

        return $this->response($response);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $lama = $this->model->findOrFail($id)->only(['code_fee', 'nama_fee', 'value_fee']);
        $this->validateCode((string) $request->input('code_fee', $lama['code_fee']), (int) $id);
        $response = $this->traitPostUpdate($request, $id);
        if ($response['status']) {
            $this->audit('update_fee', $lama, $this->model->findOrFail($id)->only(['code_fee', 'nama_fee', 'value_fee']), $id);
        }

        return $this->response($response);
    }

    public function postDelete(GeneralRequest $request)
    {
        $response = $this->traitPostDelete($request);
        if ($response['status']) {
            $this->audit('delete_fee', $request->input('ids'), null, null);
        }

        return $this->response($response);
    }
}
