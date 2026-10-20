<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\KartuStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Kartu;
use App\Models\Label;
use App\Models\Produk;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class KartuController extends Controller
{
    use ControllerTrait;

    public function __construct(Kartu $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $model = $data['model'] ?? $this->model;

        // Pemilik kartu saat edit harus selalu ada di opsi — kalau role-nya bukan
        // 'pengguna' lagi, dropdown jadi kosong sehingga pemilik tidak bisa dipilih.
        $pengguna = User::where('role', 'pengguna')->pluck('name', 'id')->all();
        $ownerId = $model?->kartu_id_user;
        if ($ownerId && ! isset($pengguna[$ownerId])) {
            $owner = User::find($ownerId);
            if ($owner) {
                $pengguna = [$ownerId => $owner->name] + $pengguna;
            }
        }

        $default = [
            'model' => $this->model,
            'status' => KartuStatusEnum::getOptions(),
            'pengguna' => $pengguna,
            'ortu' => User::where('role', 'orang_tua')->pluck('name', 'id'),
        ];

        return array_merge($default, $data);
    }

    protected function getData()
    {
        $q = $this->model->query()->with(['hasUser', 'hasOrangtua'])->filter()->sort();
        $role = auth()->user()?->role;
        if ($role === 'pengguna') {
            $q->where('kartu.kartu_id_user', auth()->id());
        } elseif ($role === 'orang_tua') {
            $q->where('kartu.kartu_id_orangtua', auth()->id());
        }
        return $q;
    }

    // Orang tua: list anak + transaksi per anak (via Route::auto '/kartu' → kartu.getAnak)
    public function getAnak(GeneralRequest $request)
    {
        $kartus = $this->getData()->with(['hasUser'])->get();
        $transaksiPerAnak = [];
        foreach ($kartus as $k) {
            $transaksiPerAnak[$k->kartu_id] = \App\Models\Transaksi::with(['hasItems.hasGerai'])
                ->where('transaksi_id_kartu', $k->kartu_id)
                ->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
                ->latest('transaksi_id')->limit(5)->get();
        }
        return $this->views('pages.kartu.anak', [
            'kartus' => $kartus,
            'transaksiPerAnak' => $transaksiPerAnak,
        ]);
    }

    // Orang tua: atur batasan jajan per anak (label dilarang + produk spesifik + catatan).
    // Via Route::auto '/kartu' → kartu.getBatas (form) & kartu.postBatas (simpan).
    public function getBatas(GeneralRequest $request)
    {
        $kartus = $this->getData()->with(['hasUser'])->get();
        $produk = Produk::with('hasGerai')->where('produk_status', 'tersedia')
            ->orderBy('produk_nama')->get()
            ->groupBy(fn ($p) => $p->hasGerai?->gerai_nama ?? '-');

        return $this->views('pages.kartu.batas', [
            'kartus' => $kartus,
            'kelompok' => $produk,
            'labelOptions' => Label::orderBy('label_nama')->pluck('label_nama', 'label_nama')->all(),
        ]);
    }

    public function postBatas(GeneralRequest $request)
    {
        $data = $request->validate([
            'kartu_id' => 'required|integer|exists:kartu,kartu_id',
            'kartu_limit_harian' => 'nullable|integer|min:0',
            'kartu_label_dilarang' => 'nullable|array',
            'kartu_label_dilarang.*' => 'string|max:50|exists:label,label_nama',
            'kartu_produk_dilarang' => 'nullable|array',
            'kartu_produk_dilarang.*' => 'integer|exists:produk,produk_id',
            'kartu_catatan_kesehatan' => 'nullable|string|max:255',
        ]);
        $kartu = Kartu::findOrFail($data['kartu_id']);
        // IDOR: ortu hanya boleh ubah kartu anaknya sendiri.
        if (auth()->user()?->role === 'orang_tua' && (int) $kartu->kartu_id_orangtua !== (int) auth()->id()) {
            abort(403, 'Bukan anak Anda');
        }
        $kartu->update([
            'kartu_limit_harian' => $data['kartu_limit_harian'] ?? null,
            'kartu_label_dilarang' => ! empty($data['kartu_label_dilarang']) ? array_values($data['kartu_label_dilarang']) : null,
            'kartu_produk_dilarang' => ! empty($data['kartu_produk_dilarang']) ? array_map('intval', $data['kartu_produk_dilarang']) : null,
            'kartu_catatan_kesehatan' => $data['kartu_catatan_kesehatan'] ?? null,
        ]);

        return $this->response($this->payload(TOAST_SUCCESS, $kartu), redirect()->route('kartu.getBatas'));
    }

    // Search kartu untuk picker POS vendor + topup kasir (via Route::auto '/kartu' → kartu.getSearch).
    // Vendor/kasir/admin saja — orang_tua/pengguna ditolak via config/permision.php.
    public function getSearch(GeneralRequest $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        // UID NFC read-only bisa datang beda format (04:A3:B2:1C vs 04a3b21c) —
        // samakan versi kupas-separator supaya tap tetap ketemu walau format beda.
        $norm = strtoupper((string) preg_replace('/[^0-9a-fA-F]/', '', $q));
        $isUid = strlen($norm) >= 8 && ctype_xdigit($norm);
        $rows = Kartu::query()->with('hasUser:id,name')
            ->where('kartu_status', 'aktif')
            ->where(function ($w) use ($q, $norm, $isUid) {
                $w->where('kartu_barcode', 'like', "%{$q}%")
                    ->orWhere('kartu_nis', 'like', "%{$q}%")
                    ->orWhereHas('hasUser', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                if ($isUid) {
                    $w->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(kartu_barcode, ':', ''), ' ', ''), '-', '')) = ?", [$norm]);
                }
            })
            ->limit(10)->get()
            ->map(fn ($k) => [
                'kartu_id' => $k->kartu_id,
                'barcode' => $k->kartu_barcode,
                'nis' => $k->kartu_nis,
                'nama' => $k->hasUser?->name ?? '-',
                'saldo' => (int) $k->kartu_saldo,
            ])->values();

        return response()->json($rows);
    }

    public function getCetak(GeneralRequest $request)
    {
        $ids = $request->input('ids', []);

        $kartu = $this->model->query()
            ->when(! empty($ids), fn ($q) => $q->whereIn($this->model->field_primary(), $ids))
            ->when($request->input('kartu_kelas'), fn ($q, $kelas) => $q->where('kartu_kelas', $kelas))
            ->orderBy('kartu_barcode')->get();

        // ponytail: getBarcodePNG() sudah mengembalikan base64 PNG — jangan di-encode dua kali
        // (kalau di-encode lagi, data URI berisi teks base64 dan gambarnya rusak di PDF).
        $barcode1D = new \Milon\Barcode\DNS1D;
        $barcode2D = new \Milon\Barcode\DNS2D;
        $warna = [25, 40, 142];

        $items = $kartu->map(fn ($k) => [
            'nama' => $k->hasUser?->name ?? 'Pengguna',
            'nis' => $k->kartu_nis,
            'kelas' => $k->kartu_kelas,
            'barcode' => $k->kartu_barcode,
            'status' => $k->kartu_status,
            'barcode_png' => $barcode1D->getBarcodePNG($k->kartu_barcode, 'C128', 3, 60, $warna, false, [255, 255, 255]),
            'qr_png' => $barcode2D->getBarcodePNG($k->kartu_barcode, 'QRCODE', 8, 8, $warna, [255, 255, 255]),
        ])->all();

        $filename = 'kartu-pengguna'
            . ($request->filled('kartu_kelas') ? '-'.$request->input('kartu_kelas') : '')
            . '-'.now()->format('YmdHis').'.pdf';

        $pdf = Pdf::loadView('pdf.kartu', [
            'items' => $items,
            'kelas' => $request->input('kartu_kelas'),
            'sekolah' => config('website.name', config('app.name')),
            'tanggal' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }
}
