<?php

namespace App\Models;

use App\Properties\LabelEntity;

class Label extends BaseModel
{
    use LabelEntity;

    protected $table = 'label';

    protected $primaryKey = 'label_id';

    protected $fillable = [
        'label_nama',
        'label_keterangan',
    ];

    public static $filterColumns = [
        'label_nama' => 'Nama Label',
    ];

    public static $sortColumns = [
        'label_nama',
        'label_keterangan',
    ];

    public static function field_name(): string
    {
        return 'label_nama';
    }

    public function rules(): array
    {
        return [
            'label_nama' => 'required|string|max:50|unique:label,label_nama,'.$this->getKey().',label_id',
            'label_keterangan' => 'nullable|string|max:255',
        ];
    }
}
