<?php

namespace App\Models;

use App\Properties\KartuEntity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kartu extends BaseModel
{
    use KartuEntity;

    protected $table = 'kartu';

    protected $primaryKey = 'kartu_id';

    protected $fillable = [
        'kartu_barcode',
        'kartu_id_user',
        'kartu_id_orangtua',
        'kartu_nis',
        'kartu_kelas',
        'kartu_saldo',
        'kartu_status',
        'kartu_limit_harian',
    ];

    public static $filterColumns = [
        'kartu_barcode' => 'Barcode',
        'kartu_nis' => 'NIS',
        'kartu_kelas' => 'Kelas',
        'kartu_status' => 'Status',
    ];

    public static $sortColumns = [
        'kartu_barcode',
        'kartu_nis',
        'kartu_kelas',
        'kartu_saldo',
        'kartu_status',
    ];

    protected function casts(): array
    {
        return [
            'kartu_saldo' => 'integer',
            'kartu_limit_harian' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'kartu_barcode';
    }

    public function rules(): array
    {
        return [
            'kartu_barcode' => 'required|string|max:50',
            'kartu_id_user' => 'required|exists:users,id',
            'kartu_id_orangtua' => 'nullable|exists:users,id',
            'kartu_nis' => 'nullable|string|max:30',
            'kartu_kelas' => 'nullable|string|max:20',
            'kartu_saldo' => 'integer|min:0',
            'kartu_status' => 'required|in:aktif,nonaktif',
            'kartu_limit_harian' => 'nullable|integer|min:0',
        ];
    }

    public function hasUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kartu_id_user', 'id');
    }

    public function hasOrangtua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kartu_id_orangtua', 'id');
    }

    public function hasTransaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'transaksi_id_kartu', 'kartu_id');
    }
}
