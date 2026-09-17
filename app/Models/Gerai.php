<?php

namespace App\Models;

use App\Properties\GeraiEntity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gerai extends BaseModel
{
    use GeraiEntity;

    protected $table = 'gerai';

    protected $primaryKey = 'gerai_id';

    protected $fillable = [
        'gerai_nama',
        'gerai_id_vendor',
        'gerai_saldo',
        'gerai_status',
    ];

    public static $filterColumns = [
        'gerai_nama' => 'Nama Gerai',
        'gerai_status' => 'Status',
    ];

    public static $sortColumns = [
        'gerai_nama',
        'gerai_saldo',
        'gerai_status',
    ];

    protected function casts(): array
    {
        return [
            'gerai_saldo' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'gerai_nama';
    }

    public function rules(): array
    {
        return [
            'gerai_nama' => 'required|string|max:100',
            'gerai_id_vendor' => 'required|exists:users,id',
            'gerai_saldo' => 'integer|min:0',
            'gerai_status' => 'required|in:buka,tutup',
        ];
    }

    public function hasVendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerai_id_vendor', 'id');
    }

    public function hasProduks(): HasMany
    {
        return $this->hasMany(Produk::class, 'produk_id_gerai', 'gerai_id');
    }

    public function hasTransaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'transaksi_id_gerai', 'gerai_id');
    }
}
