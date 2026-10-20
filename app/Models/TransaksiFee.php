<?php

namespace App\Models;

use App\Properties\TransaksiFeeEntity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiFee extends BaseModel
{
    use TransaksiFeeEntity;

    protected $table = 'transaksi_fee';

    protected $primaryKey = 'tfee_id';

    public $timestamps = false;

    protected $fillable = [
        'tfee_id_transaksi',
        'tfee_id_fee',
        'tfee_code',
        'tfee_tipe',
        'tfee_persen',
        'tfee_nominal',
    ];

    public static $filterColumns = [
        'tfee_code' => 'Kode Fee',
        'tfee_tipe' => 'Tipe',
    ];

    public static $sortColumns = [
        'tfee_code',
        'tfee_tipe',
        'tfee_nominal',
    ];

    protected function casts(): array
    {
        return [
            'tfee_persen' => 'float',
            'tfee_nominal' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'tfee_code';
    }

    public function rules(): array
    {
        return [
            'tfee_id_transaksi' => 'required|exists:transaksi,transaksi_id',
            'tfee_id_fee' => 'nullable|exists:fee,fee_id',
            'tfee_code' => 'required|string|max:30',
            'tfee_tipe' => 'required|in:persen,nominal',
            'tfee_persen' => 'required|numeric|min:0|max:100',
            'tfee_nominal' => 'required|integer|min:0',
        ];
    }

    public function hasTransaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'tfee_id_transaksi', 'transaksi_id');
    }

    public function hasFee(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'tfee_id_fee', 'fee_id');
    }
}
