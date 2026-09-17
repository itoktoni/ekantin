<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    use ControllerTrait;

    public function __construct(AuditLog $model)
    {
        $this->model = $model::getModel();
    }
}
