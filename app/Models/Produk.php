<?php

namespace App\Models;

use App\Properties\ProdukEntity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends BaseModel
{
    use ProdukEntity;

    protected $table = 'produk';

    protected $primaryKey = 'produk_id';

    protected $fillable = [
        'produk_id_gerai',
        'produk_nama',
        'produk_kategori',
        'produk_harga',
        'produk_status',
        'produk_foto',
    ];

    public static $filterColumns = [
        'produk_nama' => 'Nama',
        'produk_kategori' => 'Kategori',
        'produk_status' => 'Status',
    ];

    public static $sortColumns = [
        'produk_nama',
        'produk_kategori',
        'produk_harga',
        'produk_status',
    ];

    protected function casts(): array
    {
        return [
            'produk_harga' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'produk_nama';
    }

    public function rules(): array
    {
        return [
            'produk_id_gerai' => 'required|exists:gerai,gerai_id',
            'produk_nama' => 'required|string|max:100',
            'produk_kategori' => 'required|in:makanan,minuman,snack',
            'produk_harga' => 'required|integer|min:1',
            'produk_status' => 'required|in:tersedia,tidak',
            'produk_foto' => 'nullable|string|max:255',
        ];
    }

    public function getProdukFotoUrlAttribute(): string
    {
        return fileUrl($this->produk_foto);
    }

    public function hasGerai(): BelongsTo
    {
        return $this->belongsTo(Gerai::class, 'produk_id_gerai', 'gerai_id');
    }
}
