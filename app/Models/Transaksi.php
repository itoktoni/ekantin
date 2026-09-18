<?php

namespace App\Models;

use App\Properties\TransaksiEntity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends BaseModel
{
    use TransaksiEntity;

    protected $table = 'transaksi';

    protected $primaryKey = 'transaksi_id';

    protected $fillable = [
        'transaksi_jenis',
        'transaksi_status',
        'transaksi_metode',
        'transaksi_id_kartu',
        'transaksi_id_gerai',
        'transaksi_total',
        'transaksi_fee_kebersihan',
        'transaksi_fee_keamanan',
        'transaksi_fee_pengelolaan',
        'transaksi_fee_sistem',
        'transaksi_fee_total',
        'transaksi_fee_rincian',
        'transaksi_bersih',
        'transaksi_saldo_akhir',
        'transaksi_limit_snapshot',
        'transaksi_idempotency',
        'transaksi_id_reversal_of',
        'transaksi_alasan',
        'transaksi_id_kasir',
    ];

    public static $filterColumns = [
        'transaksi_jenis' => 'Jenis',
        'transaksi_status' => 'Status',
        'nama_siswa' => 'Nama Siswa',
        'barcode' => 'Barcode',
        'created_at' => 'Tanggal',
    ];

    public static $sortColumns = [
        'transaksi_jenis',
        'transaksi_status',
        'transaksi_total',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'transaksi_total' => 'integer',
            'transaksi_fee_kebersihan' => 'integer',
            'transaksi_fee_keamanan' => 'integer',
            'transaksi_fee_pengelolaan' => 'integer',
            'transaksi_fee_sistem' => 'integer',
            'transaksi_fee_total' => 'integer',
            'transaksi_fee_rincian' => 'array',
            'transaksi_bersih' => 'integer',
            'transaksi_saldo_akhir' => 'integer',
            'transaksi_limit_snapshot' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'transaksi_id';
    }

    public function rules(): array
    {
        return [
            'transaksi_jenis' => 'required|in:topup_web,topup_tunai,beli,refund,koreksi,withdraw',
            'transaksi_status' => 'required|in:menunggu,berhasil,gagal,dibatalkan',
            'transaksi_metode' => 'required|in:kartu,tunai,qris',
            'transaksi_total' => 'required|integer|min:0',
            'transaksi_idempotency' => 'nullable|string|max:64',
            'transaksi_alasan' => 'nullable|string|max:255',
        ];
    }

    // Total fee: kolom baru dulu, fallback jumlah 4 kolom lama (riwayat).
    public function feeTotal(): int
    {
        if (is_array($this->transaksi_fee_rincian) && $this->transaksi_fee_rincian !== []) {
            return (int) array_sum($this->transaksi_fee_rincian);
        }
        if ((int) $this->transaksi_fee_total > 0) {
            return (int) $this->transaksi_fee_total;
        }

        return (int) $this->transaksi_fee_kebersihan + (int) $this->transaksi_fee_keamanan
            + (int) $this->transaksi_fee_pengelolaan + (int) $this->transaksi_fee_sistem;
    }

    // Rincian fee: JSON baru dulu, fallback 4 kolom lama yang > 0.
    public function feeRincian(): array
    {
        if (is_array($this->transaksi_fee_rincian) && $this->transaksi_fee_rincian !== []) {
            return $this->transaksi_fee_rincian;
        }
        $lama = [
            'kebersihan' => (int) $this->transaksi_fee_kebersihan,
            'keamanan' => (int) $this->transaksi_fee_keamanan,
            'pengelolaan' => (int) $this->transaksi_fee_pengelolaan,
            'sistem' => (int) $this->transaksi_fee_sistem,
        ];

        return array_filter($lama, fn ($v) => $v > 0);
    }

    public function hasKartu(): BelongsTo
    {
        return $this->belongsTo(Kartu::class, 'transaksi_id_kartu', 'kartu_id');
    }

    public function hasGerai(): BelongsTo
    {
        return $this->belongsTo(Gerai::class, 'transaksi_id_gerai', 'gerai_id');
    }

    public function hasItems(): HasMany
    {
        return $this->hasMany(TransaksiItem::class, 'item_id_transaksi', 'transaksi_id');
    }

    public function hasKasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transaksi_id_kasir', 'id');
    }

    public function hasNotif(): HasOne
    {
        return $this->hasOne(NotifikasiLog::class, 'notif_id_transaksi', 'transaksi_id');
    }
}
