<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Label;

class LabelController extends Controller
{
    use ControllerTrait;

    public function __construct(Label $model)
    {
        $this->model = $model::getModel();
    }
}
