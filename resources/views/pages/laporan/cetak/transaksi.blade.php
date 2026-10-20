<x-layouts::cetak :judul="$judul">
    <div class="table-wrap">
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Pengguna</th>
            <th>Gerai</th>
            <th>Jenis</th>
            <th class="num">Total</th>
            <th class="num">Fee</th>
            <th>Status</th>
        </tr>
        @foreach($data as $row)
        <tr>
            <td>{{ $row->transaksi_kode ?? '#'.$row->transaksi_id }}</td>
            <td>{{ formatDate($row->created_at, true) }}</td>
            <td>{{ $row->hasKartu?->hasUser?->name ?? '-' }}<br><small>{{ $row->hasKartu?->kartu_barcode ?? '' }}</small></td>
            <td>{{ $row->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($row->hasGerai?->gerai_nama ?? '-') }}</td>
            <td>{{ $row->transaksi_jenis }}</td>
            <td class="num">{{ formatAngka((int) $row->transaksi_total, 'Rp') }}</td>
            <td class="num">{{ $row->feeTotal() ? formatAngka($row->feeTotal(), 'Rp') : '-' }}</td>
            <td>{{ $row->transaksi_status }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td colspan="5">Total ({{ $ringkas['jumlah'] }} transaksi berhasil)</td>
            <td class="num">{{ formatAngka($ringkas['total'], 'Rp') }}</td>
            <td class="num">{{ formatAngka($ringkas['fee_kelola'], 'Rp') }}</td>
            <td></td>
        </tr>
    </table>
    </div>
</x-layouts::cetak>
