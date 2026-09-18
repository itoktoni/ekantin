<?php

namespace App\Models;

class FeeConfig extends BaseModel
{
    protected $table = 'fee_config';

    protected $primaryKey = 'fee_id';

    protected $fillable = [
        'fee_min_topup',
        'fee_aktif',
    ];

    public static $filterColumns = [
        'fee_aktif' => 'Aktif',
    ];

    public static $sortColumns = [
        'fee_min_topup',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
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
            'fee_min_topup' => 'required|integer|min:0',
            'fee_aktif' => 'boolean',
        ];
    }

    public static function aktif(): self
    {
        return static::where('fee_aktif', true)->latest('fee_id')->firstOrFail();
    }

    public static function minTopup(): int
    {
        // Sumber utama: .env FEE_MIN_TOPUP via config website. Fallback DB lama.
        $dariEnv = (int) config('website.fee_min_topup', 0);
        if ($dariEnv > 0) {
            return $dariEnv;
        }
        try {
            return (int) static::aktif()->fee_min_topup;
        } catch (\Throwable $e) {
            return 10000;
        }
    }
}
