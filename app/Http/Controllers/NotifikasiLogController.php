<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\NotifikasiLog;

class NotifikasiLogController extends Controller
{
    use ControllerTrait;

    public function __construct(NotifikasiLog $model)
    {
        $this->model = $model::getModel();
    }
}
