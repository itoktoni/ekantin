<?php

namespace App\Http\Controllers;

use App\Actions\Ekantin\WithdrawAction;
use App\Concerns\ControllerTrait;
use App\Enums\Ekantin\PenarikanStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Penarikan;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenarikanController extends Controller
{
    use ControllerTrait {
        postUpdate as private traitPostUpdate;
        getUpdate as private traitGetUpdate;
        getDelete as private traitGetDelete;
        postDelete as private traitPostDelete;
        getShow as private traitGetShow;
    }

    public function __construct(Penarikan $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $gerai = Gerai::query();
        if (auth()->user()?->role === 'vendor') {
            $gerai->where('gerai_id_vendor', auth()->id());
        }

        return array_merge([
            'model' => $this->model,
            'status' => PenarikanStatusEnum::getOptions(),
            'gerai' => $gerai->pluck('gerai_nama', 'gerai_id'),
        ], $data);
    }

    protected function getData()
    {
        $q = $this->model->leftJoinRelationship('hasGerai')->filter()->sort();
        if (auth()->user()?->role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $q->whereIn('penarikan.penarikan_id_gerai', $ids);
        }

        return $q;
    }

    public function postCreate(GeneralRequest $request)
    {
        $data = $request->validate([
            'penarikan_id_gerai' => 'required|exists:gerai,gerai_id',
            'penarikan_nominal' => 'required|integer|min:1',
        ]);
        if (auth()->user()?->role === 'vendor') {
            $own = Gerai::where('gerai_id', $data['penarikan_id_gerai'])->where('gerai_id_vendor', auth()->id())->exists();
            if (! $own) {
                abort(403, 'Bukan gerai Anda.');
            }
        }
        $response = WithdrawAction::run([
            'gerai_id' => $data['penarikan_id_gerai'],
            'nominal' => $data['penarikan_nominal'],
            'id_admin' => auth()->id(),
        ]);
        if ($response['status'] && $request->hasFile('penarikan_bukti')) {
            try {
                $response['data']->update(['penarikan_bukti' => uploadFile($request->file('penarikan_bukti'), 'penarikan', ['max_size' => 2048])]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['penarikan_bukti' => $e->getMessage()]);
            }
        }

        return $this->response($response);
    }

    // === Penukaran uang: penagihan harian gerai → kasir ===
    // Rekap transaksi hari itu per gerai (pola sama PembagianController::getBagi).
    private function rekapHarian(Gerai $gerai, string $tanggal): array
    {
        $total = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->where('transaksi_item.item_id_gerai', $gerai->gerai_id)
            ->whereDate('transaksi.created_at', $tanggal)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
            ->sum('transaksi_item.item_subtotal');
        $feeHarian = Transaksi::whereDate('created_at', $tanggal)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
            ->where(fn ($q) => $q->where('transaksi_id_gerai', $gerai->gerai_id)->orWhereHas('hasItems', fn ($qq) => $qq->where('item_id_gerai', $gerai->gerai_id)))
            ->get()->sum(fn ($t) => $t->feeTotal());
        $totalSubHarian = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->whereDate('transaksi.created_at', $tanggal)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')->sum('transaksi_item.item_subtotal');
        $feeGerai = $totalSubHarian > 0 ? (int) round($feeHarian * ($total / max($totalSubHarian, 1))) : 0;
        if ($total == 0) {
            $feeGerai = 0;
        }

        return [
            'total' => (int) $total,
            'fee' => (int) $feeGerai,
            'bersih' => max((int) $total - $feeGerai, 0),
        ];
    }

    // GET /penarikan/ajukan — rekap transaksi hari itu per gerai + status pengajuan.
    public function getAjukan(GeneralRequest $request)
    {
        $tanggal = $request->input('tanggal', today()->toDateString());
        $qGerai = Gerai::query();
        if (auth()->user()?->role === 'vendor') {
            $qGerai->where('gerai_id_vendor', auth()->id());
        }
        $gerais = $qGerai->orderBy('gerai_nama')->get();
        $preview = [];
        foreach ($gerais as $g) {
            $rekap = $this->rekapHarian($g, $tanggal);
            $ajuan = Penarikan::where('penarikan_tanggal', $tanggal)
                ->where('penarikan_id_gerai', $g->gerai_id)
                ->whereIn('penarikan_status', ['diajukan', 'diselesaikan'])
                ->first();
            $preview[] = [
                'gerai' => $g,
                'total' => $rekap['total'],
                'fee' => $rekap['fee'],
                'bersih' => $rekap['bersih'],
                'ajuan' => $ajuan,
            ];
        }

        return $this->views('pages.penarikan.ajukan', [
            'tanggal' => $tanggal,
            'preview' => $preview,
        ]);
    }

    // POST /penarikan/ajukan — gerai mengajukan penagihan atas transaksi harian
    // (potong saldo gerai via WithdrawAction, status diajukan, menunggu penukaran kasir).
    public function postAjukan(GeneralRequest $request)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'gerai_id' => 'required|exists:gerai,gerai_id',
        ]);
        $tanggal = $data['tanggal'];
        $gerai = Gerai::findOrFail($data['gerai_id']);
        if (auth()->user()?->role === 'vendor' && (int) $gerai->gerai_id_vendor !== (int) auth()->id()) {
            abort(403, 'Bukan gerai Anda');
        }
        if (Penarikan::where('penarikan_tanggal', $tanggal)->where('penarikan_id_gerai', $gerai->gerai_id)
            ->whereIn('penarikan_status', ['diajukan', 'diselesaikan'])->exists()) {
            flash()->error('Sudah ada pengajuan penagihan untuk gerai ini pada tanggal tersebut');
            return redirect()->route('penarikan.getAjukan', ['tanggal' => $tanggal]);
        }
        $rekap = $this->rekapHarian($gerai, $tanggal);
        if ($rekap['bersih'] < 1) {
            flash()->error('Belum ada transaksi pada tanggal tersebut — tidak bisa diajukan');
            return redirect()->route('penarikan.getAjukan', ['tanggal' => $tanggal]);
        }
        $response = WithdrawAction::run([
            'gerai_id' => $gerai->gerai_id,
            'nominal' => $rekap['bersih'],
            'id_admin' => auth()->id(),
        ]);
        if ($response['status']) {
            $response['data']->update(['penarikan_tanggal' => $tanggal]);
        }

        return $this->response($response, redirect()->route('penarikan.getAjukan', ['tanggal' => $tanggal]));
    }

    // POST /penarikan/tukar/{id} — kasir menukar uang fisik atas pengajuan gerai.
    public function postTukar(GeneralRequest $request, $id)
    {
        $penarikan = Penarikan::findOrFail($id);
        if ($penarikan->penarikan_status !== 'diajukan') {
            flash()->error('Hanya pengajuan berstatus Diajukan yang bisa ditukar');
            return redirect()->route('penarikan.getTable');
        }
        $bukti = $penarikan->penarikan_bukti;
        if ($request->hasFile('penarikan_bukti')) {
            try {
                $bukti = uploadFile($request->file('penarikan_bukti'), 'penarikan', ['max_size' => 2048]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['penarikan_bukti' => $e->getMessage()]);
            }
        }
        $penarikan->update([
            'penarikan_status' => 'diselesaikan',
            'penarikan_id_kasir' => auth()->id(),
            'penarikan_bukti' => $bukti,
        ]);
        flash()->success('Penukaran uang ' . ($penarikan->hasGerai?->gerai_nama ?? '-') . ' ' . formatAngka((int) $penarikan->penarikan_nominal, 'Rp') . ' selesai ditukar');

        return redirect()->route('penarikan.getTable');
    }

    // POST /penarikan/batal/{id} — batalkan pengajuan yang masih diajukan; saldo gerai dikembalikan.
    public function postBatal(GeneralRequest $request, $id)
    {
        $penarikan = Penarikan::findOrFail($id);
        if (auth()->user()?->role === 'vendor') {
            $own = Gerai::where('gerai_id', $penarikan->penarikan_id_gerai)->where('gerai_id_vendor', auth()->id())->exists();
            if (! $own) {
                abort(403, 'Bukan pengajuan gerai Anda');
            }
        }
        if ($penarikan->penarikan_status !== 'diajukan') {
            flash()->error('Hanya pengajuan berstatus Diajukan yang bisa dibatalkan');
            return redirect()->route('penarikan.getTable');
        }
        DB::transaction(function () use ($penarikan) {
            $gerai = Gerai::whereKey($penarikan->penarikan_id_gerai)->lockForUpdate()->firstOrFail();
            $gerai->increment('gerai_saldo', (int) $penarikan->penarikan_nominal);
            $penarikan->update(['penarikan_status' => 'dibatalkan']);
            Transaksi::create([
                'transaksi_jenis' => 'koreksi',
                'transaksi_status' => 'berhasil',
                'transaksi_id_gerai' => $gerai->gerai_id,
                'transaksi_total' => (int) $penarikan->penarikan_nominal,
                'transaksi_bersih' => (int) $penarikan->penarikan_nominal,
                'transaksi_alasan' => "Pembatalan pengajuan penukaran #{$penarikan->penarikan_id} — saldo gerai dikembalikan",
            ]);
        });
        flash()->success('Pengajuan dibatalkan — saldo gerai dikembalikan');

        return redirect()->route('penarikan.getTable');
    }

    // Vendor hanya boleh kelola penarikan gerainya sendiri (cek IDOR: ganti ID di URL).
    private function assertPenarikanMilik(int $id): void
    {
        if (auth()->user()?->role !== 'vendor') {
            return;
        }
        $penarikan = Penarikan::findOrFail($id);
        $milik = Gerai::where('gerai_id', $penarikan->penarikan_id_gerai)->where('gerai_id_vendor', auth()->id())->exists();
        if (! $milik) {
            abort(403, 'Bukan penarikan gerai Anda');
        }
    }

    public function getUpdate(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);

        return $this->traitGetUpdate($request, $id);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);

        return $this->traitGetDelete($request, $id);
    }

    public function getShow(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);

        return $this->traitGetShow($request, $id);
    }

    public function postDelete(GeneralRequest $request)
    {
        if (auth()->user()?->role === 'vendor') {
            $ids = array_map('intval', (array) $request->input('ids', []));
            $milik = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id')->all();
            $asing = Penarikan::whereIn('penarikan_id', $ids)->whereNotIn('penarikan_id_gerai', $milik)->exists();
            if ($asing) {
                abort(403, 'Ada penarikan bukan milik Anda');
            }
        }

        return $this->traitPostDelete($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $this->assertPenarikanMilik((int) $id);
        if ($request->hasFile('penarikan_bukti')) {
            try {
                $request->merge(['penarikan_bukti' => uploadFile($request->file('penarikan_bukti'), 'penarikan', ['max_size' => 2048])]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['penarikan_bukti' => $e->getMessage()]);
            }
        }

        return $this->traitPostUpdate($request, $id);
    }
}
