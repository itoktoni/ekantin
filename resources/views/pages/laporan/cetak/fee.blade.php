<x-layouts::cetak :judul="$judul">
    <h3>Ringkasan per Fee</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Fee</th>
            <th>Tarif</th>
            <th class="num">Transaksi</th>
            <th class="num">Total</th>
        </tr>
        @foreach($ringkas['fee_per'] as $code => $nominal)
        <tr>
            <td>{{ $ringkas['fee_nama'][$code] ?? ucfirst($code) }} ({{ $code }})</td>
            <td class="num">{{ feeTarif($code) ?: '-' }}</td>
            <td class="num">{{ $ringkas['fee_hit'][$code] ?? 0 }}&times;</td>
            <td class="num">{{ formatAngka((int) $nominal, 'Rp') }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td colspan="3">Total Fee</td>
            <td class="num">{{ formatAngka($ringkas['fee_kelola'], 'Rp') }}</td>
        </tr>
    </table>
    </div>

    <h3 style="margin-top:20px">Rincian ({{ !empty($feeDipilih) ? 'fee: '.($ringkas['fee_nama'][$feeDipilih] ?? $feeDipilih) : 'semua fee' }})</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Pengguna</th>
            <th>Gerai</th>
            <th class="num">Total</th>
            <th>Fee</th>
            <th>Tarif</th>
            <th class="num">Nominal</th>
        </tr>
        @foreach($data as $row)
        @foreach(feeTampil($row->feeRincian(), $feeDipilih ?? null) as $kode => $nominal)
        <tr>
            <td>{{ $row->transaksi_kode ?? '#'.$row->transaksi_id }}</td>
            <td>{{ formatDate($row->created_at, true) }}</td>
            <td>{{ $row->hasKartu?->hasUser?->name ?? '-' }}<br><small>{{ $row->hasKartu?->kartu_barcode ?? '' }}</small></td>
            <td>{{ $row->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($row->hasGerai?->gerai_nama ?? '-') }}</td>
            <td class="num">{{ formatAngka((int) $row->transaksi_total, 'Rp') }}</td>
            <td>{{ $ringkas['fee_nama'][$kode] ?? ucfirst($kode) }} ({{ $kode }})</td>
            <td class="num">{{ feeTarif($kode) ?: '-' }}</td>
            <td class="num">{{ formatAngka((int) $nominal, 'Rp') }}</td>
        </tr>
        @endforeach
        @endforeach
    </table>
    </div>
</x-layouts::cetak>
