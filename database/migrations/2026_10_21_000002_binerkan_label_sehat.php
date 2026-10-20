<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Label biner: manis/lainnya dihapus. Manis kemasan + gorengan = tidak_sehat;
    // masakan segar (nasi/ayam) = sehat; sisa "lainnya" yang tak dikenal = tidak_sehat (aman).
    public function up(): void
    {
        DB::table('produk')->where('produk_label', 'manis')->update(['produk_label' => 'tidak_sehat']);
        DB::table('produk')->where('produk_label', 'lainnya')
            ->whereIn('produk_nama', ['Nasi Goreng', 'Ayam Geprek'])
            ->update(['produk_label' => 'sehat']);
        DB::table('produk')->where('produk_label', 'lainnya')->update(['produk_label' => 'tidak_sehat']);

        foreach (DB::table('kartu')->whereNotNull('kartu_label_dilarang')->orderBy('kartu_id')->get() as $row) {
            $label = json_decode((string) $row->kartu_label_dilarang, true);
            if (! is_array($label)) {
                continue;
            }
            $baru = array_values(array_unique(array_map(
                fn ($v) => in_array($v, ['manis', 'lainnya'], true) ? 'tidak_sehat' : $v,
                $label
            )));
            DB::table('kartu')->where('kartu_id', $row->kartu_id)->update(['kartu_label_dilarang' => json_encode($baru)]);
        }
    }

    public function down(): void
    {
        DB::table('produk')->whereIn('produk_nama', ['Es Teh Manis', 'Roti Bakar Coklat', 'Es Jeruk', 'Kopi Susu'])->update(['produk_label' => 'manis']);
        DB::table('produk')->whereIn('produk_nama', ['Nasi Goreng', 'Ayam Geprek', 'Pisang Goreng'])->update(['produk_label' => 'lainnya']);

        foreach (DB::table('kartu')->whereNotNull('kartu_label_dilarang')->orderBy('kartu_id')->get() as $row) {
            $label = json_decode((string) $row->kartu_label_dilarang, true);
            if (! is_array($label)) {
                continue;
            }
            $lama = array_values(array_unique(array_map(
                fn ($v) => $v === 'tidak_sehat' ? 'manis' : $v,
                $label
            )));
            DB::table('kartu')->where('kartu_id', $row->kartu_id)->update(['kartu_label_dilarang' => json_encode($lama)]);
        }
    }
};
