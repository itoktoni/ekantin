<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penarikan extends BaseModel
{
    protected $table = 'penarikan';

    protected $primaryKey = 'penarikan_id';

    protected $fillable = [
        'penarikan_id_gerai',
        'penarikan_nominal',
        'penarikan_tanggal',
        'penarikan_status',
        'penarikan_bukti',
        'penarikan_id_admin',
        'penarikan_id_kasir',
    ];

    public static $filterColumns = [
        'penarikan_tanggal' => 'Tanggal',
        'penarikan_status' => 'Status',
    ];

    public static $sortColumns = [
        'penarikan_nominal',
        'penarikan_status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'penarikan_nominal' => 'integer',
            'penarikan_tanggal' => 'date',
        ];
    }

    public static function field_name(): string
    {
        return 'penarikan_id';
    }

    public function rules(): array
    {
        return [
            'penarikan_id_gerai' => 'required|exists:gerai,gerai_id',
            'penarikan_nominal' => 'required|integer|min:1',
            'penarikan_tanggal' => 'nullable|date',
            'penarikan_status' => 'required|in:diajukan,diselesaikan,ditolak,dibatalkan',
            'penarikan_bukti' => 'nullable|string|max:255',
            'penarikan_id_admin' => 'nullable|exists:users,id',
            'penarikan_id_kasir' => 'nullable|exists:users,id',
        ];
    }

    public function hasGerai(): BelongsTo
    {
        return $this->belongsTo(Gerai::class, 'penarikan_id_gerai', 'gerai_id');
    }

    public function hasAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penarikan_id_admin', 'id');
    }

    public function hasKasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penarikan_id_kasir', 'id');
    }
}
