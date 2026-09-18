<?php

namespace App\Models;

class Fee extends BaseModel
{
    protected $table = 'fee';

    protected $primaryKey = 'fee_id';

    protected $fillable = [
        'code_fee',
        'nama_fee',
        'value_fee',
    ];

    public static $filterColumns = [
        'code_fee' => 'Kode',
        'nama_fee' => 'Nama',
    ];

    public static $sortColumns = [
        'code_fee',
        'nama_fee',
        'value_fee',
    ];

    protected function casts(): array
    {
        return [
            'value_fee' => 'float',
        ];
    }

    public static function field_name(): string
    {
        return 'nama_fee';
    }

    public function rules(): array
    {
        return [
            'code_fee' => 'required|string|max:30',
            'nama_fee' => 'required|string|max:100',
            'value_fee' => 'required|numeric|min:0|max:100',
        ];
    }

    public static function totalPersen(): float
    {
        return (float) static::query()->sum('value_fee');
    }

    public static function persenMap(): array
    {
        return static::query()->orderBy('fee_id')->pluck('value_fee', 'code_fee')->toArray();
    }

    public static function namaMap(): array
    {
        return static::query()->orderBy('fee_id')->pluck('nama_fee', 'code_fee')->toArray();
    }

    public static function rincian(int $subtotal): array
    {
        $out = [];
        foreach (static::query()->orderBy('fee_id')->get() as $fee) {
            $nominal = (int) round($subtotal * (float) $fee->value_fee / 100);
            if ($nominal > 0) {
                $out[$fee->code_fee] = $nominal;
            }
        }

        return $out;
    }
}
