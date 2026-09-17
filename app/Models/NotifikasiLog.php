<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiLog extends BaseModel
{
    protected $table = 'notifikasi_log';

    protected $primaryKey = 'notif_id';

    protected $fillable = [
        'notif_id_transaksi',
        'notif_saluran',
        'notif_status',
        'notif_percobaan',
        'notif_payload',
    ];

    public static $filterColumns = [
        'notif_saluran' => 'Saluran',
        'notif_status' => 'Status',
    ];

    public static $sortColumns = [
        'notif_saluran',
        'notif_status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'notif_percobaan' => 'integer',
            'notif_payload' => 'array',
        ];
    }

    public static function field_name(): string
    {
        return 'notif_id';
    }

    public function rules(): array
    {
        return [
            'notif_id_transaksi' => 'required|exists:transaksi,transaksi_id',
            'notif_saluran' => 'required|string|max:20',
            'notif_status' => 'required|in:menunggu,terkirim,gagal',
            'notif_percobaan' => 'integer|min:0',
            'notif_payload' => 'nullable|array',
        ];
    }

    public function hasTransaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'notif_id_transaksi', 'transaksi_id');
    }
}
