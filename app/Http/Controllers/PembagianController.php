<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Gerai;
use App\Models\Pembagian;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class PembagianController extends Controller
{
    use ControllerTrait;

    public function __construct(Pembagian $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        $q = $this->model->leftJoinRelationship('hasGerai')->filter()->sort();
        $role = auth()->user()?->role;
        if ($role === 'vendor') {
            $ids = Gerai::where('gerai_id_vendor', auth()->id())->pluck('gerai_id');
            $q->whereIn('pembagian.pembagian_id_gerai', $ids);
        } elseif ($role === 'kasir_sekolah') {
            // kasir lihat semua, tapi filter tanggal hari ini default
        }
        return $q;
    }

    // Kasir: bagi hari ini per gerai — hitung bersih hari ini via transaksi_item (vendor hanya kantinnya sendiri)
    public function getBagi(GeneralRequest $request)
    {
        $tanggal = $request->input('tanggal', today()->toDateString());
        $qGerai = Gerai::where('gerai_status', 'buka');
        if (auth()->user()?->role === 'vendor') {
            $qGerai->where('gerai_id_vendor', auth()->id());
        }
        $gerais = $qGerai->orderBy('gerai_nama')->get();
        $preview = [];
        foreach ($gerais as $g) {
            $total = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->where('transaksi_item.item_id_gerai', $g->gerai_id)
                ->whereDate('transaksi.created_at', $tanggal)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
                ->sum('transaksi_item.item_subtotal');
            $feeHarian = Transaksi::whereDate('created_at', $tanggal)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
                ->where(function ($q) use ($g) { $q->where('transaksi_id_gerai', $g->gerai_id)->orWhereHas('hasItems', fn($qq) => $qq->where('item_id_gerai', $g->gerai_id)); })
                ->get()->sum(fn($t) => (int)$t->transaksi_fee_kebersihan + (int)$t->transaksi_fee_keamanan + (int)$t->transaksi_fee_pengelolaan + (int)$t->transaksi_fee_sistem);
            $totalSubHarian = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->whereDate('transaksi.created_at', $tanggal)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')->sum('transaksi_item.item_subtotal');
            $feeGerai = $totalSubHarian > 0 ? (int) round($feeHarian * ($total / max($totalSubHarian, 1))) : 0;
            // jika total 0, fee 0
            if ($total == 0) $feeGerai = 0;
            $bersih = max($total - $feeGerai, 0);
            $sudah = Pembagian::where('pembagian_tanggal', $tanggal)->where('pembagian_id_gerai', $g->gerai_id)->first();
            $preview[] = [
                'gerai' => $g,
                'total' => (int) $total,
                'fee' => (int) $feeGerai,
                'bersih' => (int) $bersih,
                'sudah' => $sudah,
            ];
        }
        return $this->views('pages.pembagian.bagi', [
            'tanggal' => $tanggal,
            'preview' => $preview,
        ]);
    }

    public function postBagi(GeneralRequest $request)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'gerai_id' => 'required|exists:gerai,gerai_id',
        ]);
        $tanggal = $data['tanggal'];
        $gerai = Gerai::findOrFail($data['gerai_id']);
        if (auth()->user()?->role === 'vendor' && (int)$gerai->gerai_id_vendor !== (int)auth()->id()) {
            abort(403, 'Bukan gerai Anda');
        }
        if (Pembagian::where('pembagian_tanggal', $tanggal)->where('pembagian_id_gerai', $gerai->gerai_id)->exists()) {
            flash()->error('Sudah dibagi untuk tanggal ini');
            return redirect()->back();
        }
        $total = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->where('transaksi_item.item_id_gerai', $gerai->gerai_id)
            ->whereDate('transaksi.created_at', $tanggal)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
            ->sum('transaksi_item.item_subtotal');
        $feeHarian = Transaksi::whereDate('created_at', $tanggal)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
            ->where(function ($q) use ($gerai) { $q->where('transaksi_id_gerai', $gerai->gerai_id)->orWhereHas('hasItems', fn($qq) => $qq->where('item_id_gerai', $gerai->gerai_id)); })
            ->get()->sum(fn($t) => (int)$t->transaksi_fee_kebersihan + (int)$t->transaksi_fee_keamanan + (int)$t->transaksi_fee_pengelolaan + (int)$t->transaksi_fee_sistem);
        $totalSubHarian = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
            ->whereDate('transaksi.created_at', $tanggal)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')->sum('transaksi_item.item_subtotal');
        $feeGerai = $totalSubHarian > 0 ? (int) round($feeHarian * ($total / max($totalSubHarian, 1))) : 0;
        if ($total == 0) $feeGerai = 0;
        $bersih = max($total - $feeGerai, 0);

        $pembagian = Pembagian::create([
            'pembagian_tanggal' => $tanggal,
            'pembagian_id_gerai' => $gerai->gerai_id,
            'pembagian_total' => $total,
            'pembagian_fee' => $feeGerai,
            'pembagian_bersih' => $bersih,
            'pembagian_status' => 'selesai',
            'pembagian_id_kasir' => auth()->id(),
        ]);
        // vendor bisa tarik: tidak auto kurangi saldo gerai, hanya history pembagian; penarikan terpisah
        flash()->success('Pembagian '. $gerai->gerai_nama .' '.formatDate($tanggal).' bersih '.formatAngka($bersih,'Rp').' tercatat');
        return redirect()->route('pembagian.getTable');
    }
}
