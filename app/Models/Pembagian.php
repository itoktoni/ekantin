<?php

namespace App\Models;

use App\Properties\PembagianEntity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembagian extends BaseModel
{
    use PembagianEntity;

    protected $table = 'pembagian';
    protected $primaryKey = 'pembagian_id';
    protected $fillable = [
        'pembagian_tanggal',
        'pembagian_id_gerai',
        'pembagian_total',
        'pembagian_fee',
        'pembagian_bersih',
        'pembagian_status',
        'pembagian_id_kasir',
        'pembagian_catatan',
    ];

    public static $filterColumns = [
        'pembagian_tanggal' => 'Tanggal',
        'pembagian_status' => 'Status',
    ];

    public static $sortColumns = [
        'pembagian_tanggal',
        'pembagian_total',
        'pembagian_bersih',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'pembagian_tanggal' => 'date',
            'pembagian_total' => 'integer',
            'pembagian_fee' => 'integer',
            'pembagian_bersih' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'pembagian_id';
    }

    public function rules(): array
    {
        return [
            'pembagian_tanggal' => 'required|date',
            'pembagian_id_gerai' => 'required|exists:gerai,gerai_id',
            'pembagian_total' => 'required|integer|min:0',
            'pembagian_fee' => 'required|integer|min:0',
            'pembagian_bersih' => 'required|integer|min:0',
            'pembagian_status' => 'required|in:selesai,dibatalkan',
        ];
    }

    public function hasGerai(): BelongsTo
    {
        return $this->belongsTo(Gerai::class, 'pembagian_id_gerai', 'gerai_id');
    }

    public function hasKasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembagian_id_kasir', 'id');
    }
}
