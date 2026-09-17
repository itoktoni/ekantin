<?php

namespace App\Http\Controllers;

use App\Actions\UpdateAction;
use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\AuditLog;
use App\Models\FeeConfig;
use App\Models\FeeHistory;

class FeeConfigController extends Controller
{
    use ControllerTrait;

    protected array $feeFields = ['fee_sistem', 'fee_kebersihan', 'fee_keamanan', 'fee_pengelolaan', 'fee_min_topup'];

    public function __construct(FeeConfig $model)
    {
        $this->model = $model::getModel();
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $lama = $this->model->findOrFail($id)->only($this->feeFields);
        $response = UpdateAction::run($request, $id, $this->model);
        if ($response['status']) {
            $baru = $this->model->findOrFail($id)->only($this->feeFields);
            foreach ($this->feeFields as $field) {
                if ((int) $lama[$field] !== (int) $baru[$field]) {
                    FeeHistory::create([
                        'history_field' => $field,
                        'history_lama' => $lama[$field],
                        'history_baru' => $baru[$field],
                        'history_id_admin' => auth()->id(),
                    ]);
                }
            }
            AuditLog::create([
                'audit_aksi' => 'update_fee',
                'audit_model' => 'fee_config',
                'audit_id_record' => $id,
                'audit_lama' => $lama,
                'audit_baru' => $baru,
                'audit_id_user' => auth()->id(),
            ]);
        }

        return $this->response($response);
    }
}
