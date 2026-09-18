<?php

namespace App\Http\Controllers;

use App\Actions\Ekantin\KonfirmasiTopupWebAction;
use App\Actions\Ekantin\TopupTunaiAction;
use App\Actions\Ekantin\TopupWebAction;
use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\FeeConfig;
use App\Models\Kartu;
use App\Models\Transaksi;

class TopupController extends Controller
{
    use ControllerTrait;

    public function __construct(Kartu $model)
    {
        $this->model = $model::getModel();
    }

    protected function cariKartu(?string $barcode): ?Kartu
    {
        if (empty($barcode)) {
            return null;
        }

        return Kartu::where('kartu_barcode', $barcode)->first();
    }

    public function getTunai(GeneralRequest $request)
    {
        $barcode = $request->input('kartu_barcode') ?? $request->input('kartu') ?? $request->input('barcode');
        if ($barcode && !$request->filled('kartu_barcode')) $request->merge(['kartu_barcode' => $barcode]);
        $kartuQ = Kartu::query()->with('hasUser');
        $role = auth()->user()?->role;
        if ($role === 'orang_tua') $kartuQ->where('kartu_id_orangtua', auth()->id());
        elseif ($role === 'siswa') $kartuQ->where('kartu_id_user', auth()->id());
        $kartuOptions = $kartuQ->orderBy('kartu_barcode')->get()->mapWithKeys(fn($k)=>[$k->kartu_barcode => ($k->hasUser?->name ?? $k->kartu_barcode).' — '.$k->kartu_barcode.' ('.$k->kartu_kelas.')'])->toArray();
        return $this->views('pages.topup.tunai', [
            'model' => $this->model,
            'kartu' => $this->cariKartu($barcode),
            'kartuOptions' => $kartuOptions,
        ]);
    }

    public function postTunai(GeneralRequest $request)
    {
        $data = $request->validate([
            'kartu_barcode' => 'required|string|max:50',
            'nominal' => 'required|integer|min:1',
        ]);
        $response = TopupTunaiAction::run([
            'kartu_barcode' => $data['kartu_barcode'],
            'nominal' => $data['nominal'],
            'id_kasir' => auth()->id(),
        ]);

        return $this->response($response, redirect()->route('topup.tunai', ['kartu_barcode' => $data['kartu_barcode']]));
    }

    public function getWeb(GeneralRequest $request)
    {
        // support ?kartu=SW-xxx dan ?kartu_barcode=SW-xxx — langsung ter-select
        $barcode = $request->input('kartu_barcode') ?? $request->input('kartu') ?? $request->input('barcode');
        if ($barcode && !$request->filled('kartu_barcode')) {
            $request->merge(['kartu_barcode' => $barcode]);
        }
        $kartu = $this->cariKartu($barcode);
        $trx = $request->filled('trx') ? Transaksi::with('hasKartu')->find($request->input('trx')) : null;

        // options untuk pilih siswa (scan atau select)
        $kartuQ = Kartu::query()->with('hasUser');
        $role = auth()->user()?->role;
        if ($role === 'orang_tua') {
            $kartuQ->where('kartu_id_orangtua', auth()->id());
        } elseif ($role === 'siswa') {
            $kartuQ->where('kartu_id_user', auth()->id());
        }
        $kartuOptions = $kartuQ->orderBy('kartu_barcode')->get()->mapWithKeys(fn($k)=>[$k->kartu_barcode => ($k->hasUser?->name ?? $k->kartu_barcode).' — '.$k->kartu_barcode.' ('.$k->kartu_kelas.')'])->toArray();

        // history topup
        $historyQ = Transaksi::whereIn('transaksi_jenis', ['topup_web','topup_tunai'])->with(['hasKartu.hasUser'])->latest('transaksi_id');
        if ($kartu) {
            $historyQ->where('transaksi_id_kartu', $kartu->kartu_id);
        } elseif ($role === 'orang_tua') {
            $ids = Kartu::where('kartu_id_orangtua', auth()->id())->pluck('kartu_id');
            $historyQ->whereIn('transaksi_id_kartu', $ids);
        } elseif ($role === 'siswa') {
            $ids = Kartu::where('kartu_id_user', auth()->id())->pluck('kartu_id');
            $historyQ->whereIn('transaksi_id_kartu', $ids);
        }
        $history = $historyQ->limit(10)->get();

        return $this->views('pages.topup.web', [
            'model' => $this->model,
            'kartu' => $kartu,
            'trx' => $trx,
            'qris' => $this->qris($trx),
            'minTopup' => FeeConfig::minTopup(),
            'idempotency' => 'TOPUPWEB-'.str()->random(16),
            'kartuOptions' => $kartuOptions,
            'history' => $history,
        ]);
    }

    public function postWeb(GeneralRequest $request)
    {
        $data = $request->validate([
            'kartu_barcode' => 'required|string|max:50',
            'nominal' => 'required|integer|min:1',
            'idempotency' => 'nullable|string|max:64',
        ]);
        $response = TopupWebAction::run($data);

        if ($response['status']) {
            return redirect()->route('topup.web', [
                'kartu_barcode' => $data['kartu_barcode'],
                'trx' => $response['data']->transaksi_id,
            ]);
        }

        return $this->response($response, redirect()->route('topup.web', ['kartu_barcode' => $data['kartu_barcode']]));
    }

    /**
     * Dipanggil polling halaman top up setiap 5 detik.
     */
    public function getWebStatus(GeneralRequest $request, $id)
    {
        $trx = Transaksi::with('hasKartu')->find($id);
        if (! $trx) {
            return response()->json(['status' => 'tidak_ditemukan', 'paid' => false], 404);
        }

        return response()->json([
            'id' => $trx->transaksi_id,
            'status' => $trx->transaksi_status,
            'paid' => $trx->transaksi_status === 'berhasil',
            'total' => (int) $trx->transaksi_total,
            'saldo' => $trx->transaksi_saldo_akhir !== null ? (int) $trx->transaksi_saldo_akhir : null,
            'saldo_sekarang' => (int) ($trx->hasKartu?->kartu_saldo ?? 0),
            'diperiksa_pada' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Titik konfirmasi pembayaran QRIS (dipakai tombol konfirmasi kasir maupun
     * callback payment gateway).
     */
    public function postWebBayar(GeneralRequest $request, $id)
    {
        $response = KonfirmasiTopupWebAction::run((int) $id, auth()->id());

        if ($request->expectsJson()) {
            return response()->json($response, $response['status'] ? 200 : 422);
        }

        return $this->response($response, redirect()->route('topup.web', [
            'kartu_barcode' => $request->input('kartu_barcode'),
            'trx' => $id,
        ]));
    }

    /**
     * Bangun payload QRIS dinamis (nominal) + gambar QR untuk transaksi menunggu.
     */
    protected function qris(?Transaksi $trx): ?array
    {
        $statis = (string) config('website.qris', '');
        if (! $trx || $trx->transaksi_status !== 'menunggu' || $statis === '') {
            return null;
        }

        $nominal = (int) $trx->transaksi_total;
        $payload = nominalQRIS($statis, $nominal);

        return [
            'payload' => $payload,
            'qr_png' => (new \Milon\Barcode\DNS2D)->getBarcodePNG($payload, 'QRCODE', 6, 6, [0, 0, 0], [255, 255, 255]),
        ];
    }
}
