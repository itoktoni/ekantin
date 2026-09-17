<?php

namespace App\Models;

class FeeConfig extends BaseModel
{
    protected $table = 'fee_config';

    protected $primaryKey = 'fee_id';

    protected $fillable = [
        'fee_sistem',
        'fee_kebersihan',
        'fee_keamanan',
        'fee_pengelolaan',
        'fee_min_topup',
        'fee_aktif',
    ];

    public static $filterColumns = [
        'fee_aktif' => 'Aktif',
    ];

    public static $sortColumns = [
        'fee_sistem',
        'fee_min_topup',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_sistem' => 'integer',
            'fee_kebersihan' => 'integer',
            'fee_keamanan' => 'integer',
            'fee_pengelolaan' => 'integer',
            'fee_min_topup' => 'integer',
            'fee_aktif' => 'boolean',
        ];
    }

    public static function field_name(): string
    {
        return 'fee_id';
    }

    public function rules(): array
    {
        return [
            'fee_sistem' => 'required|integer|min:0',
            'fee_kebersihan' => 'required|integer|min:0',
            'fee_keamanan' => 'required|integer|min:0',
            'fee_pengelolaan' => 'required|integer|min:0',
            'fee_min_topup' => 'required|integer|min:0',
            'fee_aktif' => 'boolean',
        ];
    }

    public static function aktif(): self
    {
        return static::where('fee_aktif', true)->latest('fee_id')->firstOrFail();
    }
}
