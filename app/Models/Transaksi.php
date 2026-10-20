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
        'transaksi_kode',
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
        'transaksi_bersih',
        'transaksi_saldo_akhir',
        'transaksi_limit_snapshot',
        'transaksi_idempotency',
        'transaksi_id_reversal_of',
        'transaksi_alasan',
        'transaksi_id_kasir',
    ];

    public static $filterColumns = [
        'transaksi_kode' => 'Kode',
        'transaksi_jenis' => 'Jenis',
        'transaksi_status' => 'Status',
        'nama_pengguna' => 'Nama Pengguna',
        'barcode' => 'Barcode',
        'created_at' => 'Tanggal',
    ];

    public static $sortColumns = [
        'transaksi_kode',
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
            'transaksi_bersih' => 'integer',
            'transaksi_saldo_akhir' => 'integer',
            'transaksi_limit_snapshot' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'transaksi_id';
    }

    // Kode struk TRX-YYYYMMDD-XXXXXX: acak tanpa cek DB (52^6 kombinasi/hari),
    // unique index sebagai jaring pengaman. Otomatis terisi untuk semua penulis.
    public static function kodeBaru(): string
    {
        return 'TRX-'.now()->format('Ymd').'-'.unicString(6);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->transaksi_kode)) {
                $model->transaksi_kode = self::kodeBaru();
            }
        });
    }

    public function rules(): array
    {
        return [
            'transaksi_kode' => 'nullable|string|max:20',
            'transaksi_jenis' => 'required|in:topup_web,topup_tunai,beli,refund,koreksi,withdraw',
            'transaksi_status' => 'required|in:menunggu,berhasil,gagal,dibatalkan',
            'transaksi_metode' => 'required|in:kartu,tunai,qris',
            'transaksi_total' => 'required|integer|min:0',
            'transaksi_idempotency' => 'nullable|string|max:64',
            'transaksi_alasan' => 'nullable|string|max:255',
        ];
    }

    // Total fee: baris relasi dulu, fallback total/kolom lama (riwayat).
    public function feeTotal(): int
    {
        $fees = $this->feeRows();
        if ($fees->isNotEmpty()) {
            return (int) $fees->sum('tfee_nominal');
        }
        if ((int) $this->transaksi_fee_total > 0) {
            return (int) $this->transaksi_fee_total;
        }

        return (int) $this->transaksi_fee_kebersihan + (int) $this->transaksi_fee_keamanan
            + (int) $this->transaksi_fee_pengelolaan + (int) $this->transaksi_fee_sistem;
    }

    // Rincian fee: baris relasi [code => nominal], fallback kolom lama yang > 0.
    public function feeRincian(): array
    {
        $fees = $this->feeRows();
        if ($fees->isNotEmpty()) {
            return $fees->pluck('tfee_nominal', 'tfee_code')->map(fn ($v) => (int) $v)->all();
        }
        $lama = [
            'kebersihan' => (int) $this->transaksi_fee_kebersihan,
            'keamanan' => (int) $this->transaksi_fee_keamanan,
            'pengelolaan' => (int) $this->transaksi_fee_pengelolaan,
            'sistem' => (int) $this->transaksi_fee_sistem,
        ];

        return array_filter($lama, fn ($v) => $v > 0);
    }

    // Koleksi baris transaksi_fee (memakai eager-load bila sudah ada).
    private function feeRows()
    {
        return $this->relationLoaded('hasFees') ? $this->hasFees : $this->hasFees()->get();
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

    // Baris fee relasional (tabel transaksi_fee).
    public function hasFees(): HasMany
    {
        return $this->hasMany(TransaksiFee::class, 'tfee_id_transaksi', 'transaksi_id');
    }

    // Refund (pembatalan) dari transaksi ini, jika ada.
    public function hasRefund(): HasOne
    {
        return $this->hasOne(Transaksi::class, 'transaksi_id_reversal_of', 'transaksi_id');
    }

    // Transaksi asal bila baris ini adalah refund.
    public function hasAsal(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id_reversal_of', 'transaksi_id');
    }
}
