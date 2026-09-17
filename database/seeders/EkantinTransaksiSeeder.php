<?php

namespace Database\Seeders;

use App\Actions\Ekantin\ProcessPurchaseAction;
use App\Models\Kartu;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;

class EkantinTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::where('email', 'kasir@sekolah.id')->first()
            ?? User::where('role', 'kasir_sekolah')->first();
        $kartu = Kartu::where('kartu_status', 'aktif')->orderBy('kartu_barcode')->get();
        $produk = Produk::where('produk_status', 'tersedia')->orderBy('produk_nama')->get();
        if ($kartu->isEmpty() || $produk->isEmpty()) {
            return;
        }

        // [barcode, [nama produk => qty], status item akhir]
        $riwayat = [
            ['SW-1001', ['Nasi Goreng' => 1, 'Es Teh Manis' => 1], 'baru'],
            ['SW-1002', ['Ayam Geprek' => 1, 'Air Mineral' => 1], 'disiapkan'],
            ['SW-1003', ['Roti Bakar Coklat' => 2, 'Susu Jahe' => 1], 'disiapkan'],
            ['SW-1004', ['Jus Alpukat' => 1], 'selesai'],
            ['SW-1005', ['Pisang Goreng' => 2, 'Es Jeruk' => 2, 'Kopi Susu' => 1], 'baru'],
            ['SW-1004', ['Kopi Susu' => 1, 'Roti Bakar Coklat' => 1], 'selesai'],
        ];

        foreach ($riwayat as $i => [$barcode, $keranjang, $statusAkhir]) {
            $k = $kartu->firstWhere('kartu_barcode', $barcode) ?? $kartu->first();
            $items = [];
            foreach ($keranjang as $nama => $qty) {
                $p = $produk->firstWhere('produk_nama', $nama);
                if ($p) {
                    $items[] = ['produk_id' => $p->produk_id, 'qty' => $qty];
                }
            }
            if (empty($items)) {
                continue;
            }
            $response = ProcessPurchaseAction::run([
                'kartu_barcode' => $k->kartu_barcode,
                'items' => $items,
                'idempotency' => 'SEED-POS-'.($i + 1).'-'.$k->kartu_barcode,
                'id_kasir' => $kasir?->id,
            ]);
            if ($response['status'] && $statusAkhir !== 'baru') {
                /** @var Transaksi $trx */
                $trx = $response['data'];
                $trx->hasItems()->update(['item_status' => $statusAkhir]);
            }
        }
    }
}
