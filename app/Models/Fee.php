<?php

namespace App\Models;

class Fee extends BaseModel
{
    protected $table = 'fee';

    protected $primaryKey = 'fee_id';

    protected $fillable = [
        'code_fee',
        'nama_fee',
        'fee_tipe',
        'value_fee',
    ];

    public static $filterColumns = [
        'code_fee' => 'Kode',
        'nama_fee' => 'Nama',
        'fee_tipe' => 'Tipe',
    ];

    public static $sortColumns = [
        'code_fee',
        'nama_fee',
        'fee_tipe',
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
            'fee_tipe' => 'required|in:persen,nominal',
            'value_fee' => ['required', 'numeric', 'min:0', function ($attr, $val, $fail) {
                $tipe = request()->input('fee_tipe', $this->fee_tipe ?? 'persen');
                if ($tipe !== 'nominal' && $val > 100) {
                    $fail('Persen maksimal 100.');
                }
            }],
        ];
    }

    public static function totalPersen(): float
    {
        return (float) static::query()->where('fee_tipe', 'persen')->sum('value_fee');
    }

    // Hanya fee persen (nominal tidak punya persen).
    public static function persenMap(): array
    {
        return static::query()->where('fee_tipe', 'persen')->orderBy('fee_id')->pluck('value_fee', 'code_fee')->toArray();
    }

    public static function tipeMap(): array
    {
        return static::query()->orderBy('fee_id')->pluck('fee_tipe', 'code_fee')->toArray();
    }

    public static function namaMap(): array
    {
        return static::query()->orderBy('fee_id')->pluck('nama_fee', 'code_fee')->toArray();
    }

    public static function rincian(int $subtotal): array
    {
        $out = [];
        foreach (static::query()->orderBy('fee_id')->get() as $fee) {
            $nominal = $fee->fee_tipe === 'nominal'
                ? (int) $fee->value_fee
                : (int) round($subtotal * (float) $fee->value_fee / 100);
            if ($nominal > 0) {
                $out[$fee->code_fee] = $nominal;
            }
        }

        return $out;
    }
}
