<?php

namespace App\Http\Controllers;

use App\Charts\DashboardChart;
use App\Models\Gerai;
use App\Models\Notification;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(DashboardChart $chart)
    {
        $user = auth()->user();
        $role = $user?->role;

        // Orang tua: dashboard per anak
        if ($role === 'orang_tua') {
            $kartus = \App\Models\Kartu::where('kartu_id_orangtua', $user->id)->with('hasUser')->get();
            $kartuIds = $kartus->pluck('kartu_id')->all();
            $hariIni = today();
            $belanjaHariIni = Transaksi::whereIn('transaksi_id_kartu', $kartuIds)->whereDate('created_at', $hariIni)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')->sum('transaksi_total');
            $feeHariIni = Transaksi::whereIn('transaksi_id_kartu', $kartuIds)->whereDate('created_at', $hariIni)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')->get()->sum(fn($t) => $t->feeTotal());
            $transaksiHariIni = Transaksi::whereIn('transaksi_id_kartu', $kartuIds)->whereDate('created_at', $hariIni)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')->count();
            $recentTransaksi = Transaksi::with(['hasKartu.hasUser','hasItems.hasGerai'])->whereIn('transaksi_id_kartu', $kartuIds)->latest('transaksi_id')->limit(5)->get();
            $statsOrtu = [
                'anak_count' => $kartus->count(),
                'saldo_total' => (int) $kartus->sum('kartu_saldo'),
                'belanja_hari_ini' => (int) $belanjaHariIni,
                'fee_hari_ini' => (int) $feeHariIni,
                'transaksi_hari_ini' => $transaksiHariIni,
                'limit_terpakai' => $kartus->map(fn($k)=>[
                    'nama' => $k->hasUser?->name ?? $k->kartu_barcode,
                    'limit' => $k->kartu_limit_harian,
                    'pakai' => Transaksi::where('transaksi_id_kartu', $k->kartu_id)->whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->sum('transaksi_total'),
                ]),
            ];
            // chart spending 7 hari
            $spendingChart = $chart->ortuSpendingHarian($kartuIds);
            return view('dashboard', [
                'isOrtu' => true,
                'kartus' => $kartus,
                'statsOrtu' => $statsOrtu,
                'recentTransaksi' => $recentTransaksi,
            ])->with('spendingChart', $spendingChart)->with('isGerai', false)->with('isPengguna', false);
        }

        // Pengguna: dashboard kartu sendiri
        if ($role === 'pengguna') {
            $kartu = \App\Models\Kartu::where('kartu_id_user', $user->id)->with('hasUser')->first();
            if (!$kartu) {
                return view('dashboard', ['isPengguna'=>true, 'kartu'=>null, 'statsPengguna'=>null, 'recentTransaksi'=>collect(), 'isGerai'=>false, 'isOrtu'=>false]);
            }
            $hariIni = today();
            $pakaiHariIni = Transaksi::where('transaksi_id_kartu', $kartu->kartu_id)->whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->sum('transaksi_total');
            $feeHariIni = Transaksi::where('transaksi_id_kartu', $kartu->kartu_id)->whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->get()->sum(fn($t) => $t->feeTotal());
            $recentTransaksi = Transaksi::with(['hasItems.hasGerai'])->where('transaksi_id_kartu', $kartu->kartu_id)->latest('transaksi_id')->limit(5)->get();
            $statsPengguna = [
                'saldo' => (int) $kartu->kartu_saldo,
                'limit' => $kartu->kartu_limit_harian,
                'pakai_hari_ini' => (int) $pakaiHariIni,
                'sisa_limit' => $kartu->kartu_limit_harian ? max((int)$kartu->kartu_limit_harian - (int)$pakaiHariIni, 0) : null,
                'transaksi_hari_ini' => Transaksi::where('transaksi_id_kartu', $kartu->kartu_id)->whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->count(),
                'fee_hari_ini' => (int) $feeHariIni,
            ];
            $spendingChart = $chart->ortuSpendingHarian([$kartu->kartu_id]);
            return view('dashboard', [
                'isPengguna' => true,
                'kartu' => $kartu,
                'statsPengguna' => $statsPengguna,
                'recentTransaksi' => $recentTransaksi,
            ])->with('spendingChart', $spendingChart)->with('isGerai', false)->with('isOrtu', false);
        }

        // Vendor: dashboard per gerai
        if ($role === 'vendor') {
            $geraiIds = Gerai::where('gerai_id_vendor', $user->id)->pluck('gerai_id')->all();
            $gerais = Gerai::whereIn('gerai_id', $geraiIds)->get();
            $hariIni = today();
            $pendapatanHariIni = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->whereIn('transaksi_item.item_id_gerai', $geraiIds)
                ->whereDate('transaksi.created_at', $hariIni)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
                ->sum('transaksi_item.item_subtotal');
            // fee hari ini proporsional sederhana: total fee transaksi hari ini * ratio
            $feeHariIni = Transaksi::whereDate('created_at', $hariIni)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
                ->where(function ($q) use ($geraiIds) { $q->whereIn('transaksi_id_gerai', $geraiIds)->orWhereHas('hasItems', fn ($qq) => $qq->whereIn('item_id_gerai', $geraiIds)); })
                ->get()->sum(fn ($t) => $t->feeTotal());
            // ratio untuk pendapatan bersih gerai hari ini
            $totalSubHariIni = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->whereDate('transaksi.created_at', $hariIni)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')->sum('transaksi_item.item_subtotal');
            $feeGeraiHariIni = $totalSubHariIni > 0 ? (int) round($feeHariIni * ($pendapatanHariIni / $totalSubHariIni)) : 0;
            $bersihHariIni = max($pendapatanHariIni - $feeGeraiHariIni, 0);

            $statsGerai = [
                'gerai_count' => $gerais->count(),
                'saldo_total' => (int) $gerais->sum('gerai_saldo'),
                'produk_tersedia' => Produk::whereIn('produk_id_gerai', $geraiIds)->where('produk_status', 'tersedia')->count(),
                'produk_total' => Produk::whereIn('produk_id_gerai', $geraiIds)->count(),
                'pesanan_baru' => TransaksiItem::whereIn('item_id_gerai', $geraiIds)->where('item_status', 'baru')->count(),
                'pesanan_disiapkan' => TransaksiItem::whereIn('item_id_gerai', $geraiIds)->where('item_status', 'disiapkan')->count(),
                'transaksi_hari_ini' => Transaksi::whereDate('created_at', $hariIni)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')->where(function ($q) use ($geraiIds) { $q->whereIn('transaksi_id_gerai', $geraiIds)->orWhereHas('hasItems', fn ($qq) => $qq->whereIn('item_id_gerai', $geraiIds)); })->count(),
                'pendapatan_hari_ini' => $bersihHariIni,
                'omzet_hari_ini' => (int) $pendapatanHariIni,
                'fee_hari_ini' => $feeGeraiHariIni,
            ];
            $recentTransaksi = Transaksi::with(['hasKartu.hasUser', 'hasItems.hasGerai'])->where(function ($q) use ($geraiIds) { $q->whereIn('transaksi_id_gerai', $geraiIds)->orWhereHas('hasItems', fn ($qq) => $qq->whereIn('item_id_gerai', $geraiIds)); })->latest('transaksi_id')->limit(5)->get();

            return view('dashboard', [
                'isGerai' => true,
                'gerais' => $gerais,
                'statsGerai' => $statsGerai,
                'recentTransaksi' => $recentTransaksi,
            ])->with('pendapatanChart', $chart->geraiPendapatanHarian($geraiIds))
              ->with('pesananChart', $chart->geraiPesananStatus($geraiIds));
        }

        // Kasir sekolah: dashboard operasional
        if ($role === 'kasir_sekolah') {
            $hariIni = today();
            $transaksiHariIni = Transaksi::whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->count();
            $omzetHariIni = Transaksi::whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->sum('transaksi_total');
            $feeHariIni = Transaksi::whereDate('created_at', $hariIni)->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->get()->sum(fn($t) => $t->feeTotal());
            $topupMenunggu = Transaksi::where('transaksi_jenis','topup_web')->where('transaksi_status','menunggu')->count();
            $topupHariIni = Transaksi::whereDate('created_at',$hariIni)->whereIn('transaksi_jenis',['topup_web','topup_tunai'])->where('transaksi_status','berhasil')->count();
            $pesananBaru = TransaksiItem::where('item_status','baru')->count();
            $statsKasir = [
                'transaksi_hari_ini' => $transaksiHariIni,
                'omzet_hari_ini' => (int) $omzetHariIni,
                'fee_hari_ini' => (int) $feeHariIni,
                'bersih_hari_ini' => max((int)$omzetHariIni - (int)$feeHariIni, 0),
                'topup_menunggu' => $topupMenunggu,
                'topup_hari_ini' => $topupHariIni,
                'pesanan_baru' => $pesananBaru,
                'produk_tersedia' => Produk::where('produk_status','tersedia')->count(),
            ];
            $recentTransaksi = Transaksi::with(['hasKartu.hasUser','hasItems.hasGerai'])->latest('transaksi_id')->limit(5)->get();
            return view('dashboard', [
                'isKasir' => true,
                'statsKasir' => $statsKasir,
                'recentTransaksi' => $recentTransaksi,
            ])->with('kasirChart', $chart->kasirTransaksiHarian())
              ->with('isGerai', false)->with('isOrtu', false)->with('isPengguna', false);
        }

        // Super admin: dashboard ekantin menyeluruh
        $stats = [
            'total_users' => User::count(),
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::where('read', false)->count(),
            'total_gerai' => Gerai::count(),
            'total_produk' => Produk::count(),
            'total_kartu' => \App\Models\Kartu::count(),
            'total_transaksi' => Transaksi::where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->count(),
            'topup_menunggu' => Transaksi::where('transaksi_jenis','topup_web')->where('transaksi_status','menunggu')->count(),
            'pesanan_baru' => TransaksiItem::where('item_status','baru')->count(),
            'saldo_kartu_total' => (int) \App\Models\Kartu::sum('kartu_saldo'),
            'saldo_gerai_total' => (int) Gerai::sum('gerai_saldo'),
        ];
        $recentTransaksiAdmin = Transaksi::with(['hasKartu.hasUser','hasItems.hasGerai'])->latest('transaksi_id')->limit(5)->get();
        $recentPembagian = \App\Models\Pembagian::with('hasGerai')->latest('pembagian_id')->limit(5)->get();
        $pendapatanPerGerai = $chart->pendapatanPerGerai();
        $ekantinChart = $chart->kasirTransaksiHarian();
        $fees = \App\Models\Fee::query()->orderBy('fee_id')->get();
        $trxBulan = Transaksi::where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
            ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();
        $feeBulanTotal = (int) $trxBulan->sum(fn ($t) => $t->feeTotal());
        $feeBulanRincian = [];
        foreach ($trxBulan as $t) {
            foreach ($t->feeRincian() as $kode => $nominal) {
                $feeBulanRincian[$kode] = ($feeBulanRincian[$kode] ?? 0) + (int) $nominal;
            }
        }
        $feeBulan = [
            'total' => $feeBulanTotal,
            'rincian' => $feeBulanRincian,
            'count' => $trxBulan->count(),
            'omzet' => (int) $trxBulan->sum('transaksi_total'),
        ];

        return view('dashboard', compact('stats'))
            ->with('recentTransaksiAdmin', $recentTransaksiAdmin)
            ->with('recentPembagian', $recentPembagian)
            ->with('pendapatanPerGeraiChart', $pendapatanPerGerai)
            ->with('ekantinChart', $ekantinChart)
            ->with('fees', $fees)
            ->with('feeBulan', $feeBulan)
            ->with('isGerai', false)->with('isOrtu', false)->with('isPengguna', false)->with('isKasir', false);
    }
}
