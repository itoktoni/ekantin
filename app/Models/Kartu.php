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

    // Default saat form create: status langsung "aktif" supaya scan NFC bisa simpan otomatis.
    protected $attributes = [
        'kartu_status' => 'aktif',
    ];

    protected $fillable = [
        'kartu_barcode',
        'kartu_id_user',
        'kartu_id_orangtua',
        'kartu_nis',
        'kartu_kelas',
        'kartu_saldo',
        'kartu_status',
        'kartu_limit_harian',
        'kartu_label_dilarang',
        'kartu_produk_dilarang',
        'kartu_catatan_kesehatan',
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
            'kartu_label_dilarang' => 'array',
            'kartu_produk_dilarang' => 'array',
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
            'kartu_label_dilarang' => 'nullable|array',
            'kartu_label_dilarang.*' => 'string|max:50|exists:label,label_nama',
            'kartu_produk_dilarang' => 'nullable|array',
            'kartu_produk_dilarang.*' => 'integer|exists:produk,produk_id',
            'kartu_catatan_kesehatan' => 'nullable|string|max:255',
        ];
    }

    // Daftar label yang dilarang ortu, ternormalisasi (string unik dari master label).
    public function labelDilarang(): array
    {
        return array_values(array_unique(array_filter(array_map('strval', (array) ($this->kartu_label_dilarang ?? [])))));
    }

    // Daftar produk_id spesifik yang dilarang ortu, ternormalisasi int.
    public function produkDilarang(): array
    {
        return array_map('intval', (array) ($this->kartu_produk_dilarang ?? []));
    }

    // Cek satu produk: null = boleh, string = alasan penolakan untuk struk/kasir.
    public function alasanBlokir(Produk $produk): ?string
    {
        if (in_array((int) $produk->produk_id, $this->produkDilarang(), true)) {
            return "dilarang orang tua ({$produk->produk_nama})";
        }
        if (in_array((string) $produk->produk_label, $this->labelDilarang(), true)) {
            return "label {$produk->produk_label} dibatasi ({$produk->produk_nama})";
        }

        return null;
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
