<?php

namespace App\Charts;

use App\Models\Gerai;
use App\Models\Notification;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Models\User;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardChart
{
    /**
     * User registrations over the last 7 days.
     */
    public function userRegistrations(): LarapexChart
    {
        $days = collect(range(6, 0))->map(function ($i) {
            $date = Carbon::today()->subDays($i);

            return [
                'label' => $date->format('d M'),
                'count' => User::whereDate('created_at', $date)->count(),
            ];
        });

        return (new LarapexChart)->areaChart()
            ->setTitle('User Registrations')
            ->setSubtitle('New users — last 7 days')
            ->addData($days->pluck('count')->toArray())
            ->setXAxis($days->pluck('label')->toArray())
            ->setColors(['#3755c3'])
            ->setGrid()
            ->setMarkers(['#3755c3'], 4, 6);
    }

    /**
     * Notifications: read vs unread.
     */
    public function notificationStats(): LarapexChart
    {
        $read = Notification::where('read', true)->count();
        $unread = Notification::where('read', false)->count();

        return (new LarapexChart)->donutChart()
            ->setTitle('Notifications')
            ->setSubtitle('Read / Unread')
            ->addData([$read, $unread])
            ->setLabels(['Read', 'Unread'])
            ->setColors(['#16a34a', '#d97706']);
    }

    /**
     * Gerai: pendapatan bersih per hari 7 hari terakhir (scope gerai ids).
     */
    public function geraiPendapatanHarian(array $geraiIds): LarapexChart
    {
        $days = collect(range(6, 0))->map(function ($i) use ($geraiIds) {
            $date = Carbon::today()->subDays($i);
            // transaksi beli berhasil yang menyentuh gerai tsb
            $ids = $geraiIds;
            $q = Transaksi::whereDate('transaksi.created_at', $date)
                ->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
                ->where(function ($w) use ($ids) {
                    $w->whereIn('transaksi_id_gerai', $ids)
                        ->orWhereHas('hasItems', fn ($q) => $q->whereIn('item_id_gerai', $ids));
                });
            // hitung bersih proporsional: jika multi-gerai, bagi via SplitDana sudah di transaksi_bersih total, ambil proporsi via item
            // sederhanakan: sum bersih via transaksi yang hanya 1 gerai, untuk multi ambil sum subtotal - fee proporsional
            // untuk dashboard pakai sum bersih langsung jika transaksi bersih sudah dihitung total; jika multi gerai, vendor tetap lihat total bersih transaksi (akan overcount). Lebih akurat pakai sum item bersih via transaksiItem?
            // Simplifikasi: sum transaksi_bersih untuk transaksi yang sepenuhnya milik gerai ini (single gerai), plus untuk multi via item proporsional dihitung terpisah.
            // Untuk MVP: sum item subtotal bersih via TransaksiItem join
            $bersih = DB::table('transaksi_item')
                ->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->whereIn('transaksi_item.item_id_gerai', $ids)
                ->whereDate('transaksi.created_at', $date)
                ->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
                ->sum('transaksi_item.item_subtotal');
            // kurangi fee proporsional harian: total fee harian * (subtotal gerai / total harian)
            // sederhanakan: pakai bersih = subtotal - fee proporsional (hitung fee harian total)
            // ambil fee total harian
            $feeHarian = Transaksi::whereDate('created_at', $date)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')
                ->where(function ($w) use ($ids) { $w->whereIn('transaksi_id_gerai', $ids)->orWhereHas('hasItems', fn ($q) => $q->whereIn('item_id_gerai', $ids)); })
                ->get()->sum(fn ($t) => (int) $t->transaksi_fee_kebersihan + (int) $t->transaksi_fee_keamanan + (int) $t->transaksi_fee_pengelolaan + (int) $t->transaksi_fee_sistem);
            // proporsi: jika ada multi gerai transaksi, fee dibagi proporsional oleh SplitDana — untuk hari ini pakai ratio subtotal gerai / total subtotal hari
            $totalSubHarian = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->whereDate('transaksi.created_at', $date)->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')->sum('transaksi_item.item_subtotal');
            $feeGerai = $totalSubHarian > 0 ? (int) round($feeHarian * ($bersih / $totalSubHarian)) : 0;
            $pendapatan = max($bersih - $feeGerai, 0);

            return ['label' => $date->format('d M'), 'pendapatan' => $pendapatan];
        });

        return (new LarapexChart)->areaChart()
            ->setTitle('Pendapatan Bersih')
            ->setSubtitle('7 hari terakhir — per gerai')
            ->addData($days->pluck('pendapatan')->toArray())
            ->setXAxis($days->pluck('label')->toArray())
            ->setColors(['#059669'])
            ->setGrid()
            ->setMarkers(['#059669'], 4, 6);
    }

    /**
     * Gerai: status pesanan (baru/disiapkan/selesai).
     */
    public function geraiPesananStatus(array $geraiIds): LarapexChart
    {
        $baru = TransaksiItem::whereIn('item_id_gerai', $geraiIds)->where('item_status', 'baru')->count();
        $siap = TransaksiItem::whereIn('item_id_gerai', $geraiIds)->where('item_status', 'disiapkan')->count();
        $selesai = TransaksiItem::whereIn('item_id_gerai', $geraiIds)->where('item_status', 'selesai')->count();

        return (new LarapexChart)->donutChart()
            ->setTitle('Pesanan')
            ->setSubtitle('Status item')
            ->addData([$baru, $siap, $selesai])
            ->setLabels(['Baru', 'Disiapkan', 'Selesai'])
            ->setColors(['#d97706', '#2563eb', '#16a34a']);
    }

    /**
     * Orang tua: spending anak per hari 7 hari.
     */
    public function ortuSpendingHarian(array $kartuIds): LarapexChart
    {
        $days = collect(range(6, 0))->map(function ($i) use ($kartuIds) {
            $date = Carbon::today()->subDays($i);
            $total = Transaksi::whereIn('transaksi_id_kartu', $kartuIds)->whereDate('created_at', $date)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')->sum('transaksi_total');
            return ['label' => $date->format('d M'), 'total' => (int) $total];
        });
        return (new LarapexChart)->areaChart()
            ->setTitle('Belanja Anak')
            ->setSubtitle('7 hari terakhir')
            ->addData($days->pluck('total')->toArray())
            ->setXAxis($days->pluck('label')->toArray())
            ->setColors(['#7c3aed'])
            ->setGrid()
            ->setMarkers(['#7c3aed'], 4, 6);
    }

    /**
     * Kasir: transaksi & topup per hari 7 hari.
     */
    public function kasirTransaksiHarian(): LarapexChart
    {
        $days = collect(range(6, 0))->map(function ($i) {
            $date = Carbon::today()->subDays($i);
            $beli = Transaksi::whereDate('created_at', $date)->where('transaksi_jenis', 'beli')->where('transaksi_status', 'berhasil')->count();
            $topup = Transaksi::whereDate('created_at', $date)->whereIn('transaksi_jenis', ['topup_web','topup_tunai'])->where('transaksi_status', 'berhasil')->count();
            return ['label' => $date->format('d M'), 'beli' => $beli, 'topup' => $topup];
        });
        return (new LarapexChart)->barChart()
            ->setTitle('Transaksi Harian')
            ->setSubtitle('Beli vs Topup — 7 hari')
            ->addData($days->pluck('beli')->toArray())
            ->addData($days->pluck('topup')->toArray())
            ->setXAxis($days->pluck('label')->toArray())
            ->setColors(['#3755c3','#059669'])
            ->setGrid();
    }

    /**
     * Admin: pendapatan per gerai (7 hari).
     */
    public function pendapatanPerGerai(): LarapexChart
    {
        $gerais = Gerai::orderBy('gerai_nama')->get();
        $labels = $gerais->pluck('gerai_nama')->toArray();
        $data = $gerais->map(function ($g) {
            $bersih = DB::table('transaksi_item')->join('transaksi', 'transaksi.transaksi_id', '=', 'transaksi_item.item_id_transaksi')
                ->where('transaksi_item.item_id_gerai', $g->gerai_id)
                ->where('transaksi.transaksi_jenis', 'beli')->where('transaksi.transaksi_status', 'berhasil')
                ->whereDate('transaksi.created_at', '>=', Carbon::today()->subDays(6))
                ->sum('transaksi_item.item_subtotal');
            return (int) $bersih;
        })->toArray();

        return (new LarapexChart)->barChart()
            ->setTitle('Omzet per Gerai')
            ->setSubtitle('Subtotal 7 hari')
            ->addData($data)
            ->setXAxis($labels)
            ->setColors(['#3755c3']);
    }
}
