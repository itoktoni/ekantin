<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiItem extends BaseModel
{
    protected $table = 'transaksi_item';

    protected $primaryKey = 'item_id';

    public $timestamps = false;

    protected $fillable = [
        'item_id_transaksi',
        'item_id_gerai',
        'item_nama',
        'item_harga',
        'item_qty',
        'item_subtotal',
        'item_status',
    ];

    public static $filterColumns = [
        'item_nama' => 'Nama',
        'item_status' => 'Status',
    ];

    public static $sortColumns = [
        'item_nama',
        'item_harga',
        'item_qty',
        'item_subtotal',
        'item_status',
    ];

    protected function casts(): array
    {
        return [
            'item_harga' => 'integer',
            'item_qty' => 'integer',
            'item_subtotal' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'item_nama';
    }

    public function rules(): array
    {
        return [
            'item_id_transaksi' => 'required|exists:transaksi,transaksi_id',
            'item_id_gerai' => 'nullable|exists:gerai,gerai_id',
            'item_nama' => 'required|string|max:100',
            'item_harga' => 'required|integer|min:1',
            'item_qty' => 'required|integer|min:1',
            'item_subtotal' => 'required|integer|min:0',
            'item_status' => 'required|in:baru,disiapkan,selesai',
        ];
    }

    public function hasTransaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'item_id_transaksi', 'transaksi_id');
    }

    public function hasGerai(): BelongsTo
    {
        return $this->belongsTo(Gerai::class, 'item_id_gerai', 'gerai_id');
    }
}
