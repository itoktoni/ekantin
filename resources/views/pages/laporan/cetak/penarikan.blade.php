<x-layouts::cetak :judul="$judul">
    <h3>Ringkasan per Status</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Status</th>
            <th class="num">Jumlah</th>
            <th class="num">Nominal</th>
        </tr>
        @foreach($perStatus as $st => $s)
        <tr>
            <td>{{ $status[$st] ?? $st }} ({{ $st }})</td>
            <td class="num">{{ $s['jml'] }}&times;</td>
            <td class="num">{{ formatAngka($s['nominal'], 'Rp') }}</td>
        </tr>
        @endforeach
    </table>
    </div>

    <h3 style="margin-top:20px">Rincian</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Tanggal</th>
            <th>Gerai</th>
            <th>Bank</th>
            <th>No. Rekening</th>
            <th>Atas Nama</th>
            <th class="num">Nominal</th>
            <th>Status</th>
            <th>Kasir</th>
            <th>Admin</th>
        </tr>
        @foreach($rows as $r)
        <tr>
            <td>{{ $r->penarikan_tanggal?->format('d/m/Y') }}</td>
            <td>{{ $r->hasGerai?->gerai_nama ?? '-' }}</td>
            <td>{{ $r->hasGerai?->gerai_bank ?? '-' }}</td>
            <td>{{ $r->hasGerai?->gerai_no_rekening ?? '-' }}</td>
            <td>{{ $r->hasGerai?->gerai_nama_rekening ?? '-' }}</td>
            <td class="num">{{ formatAngka((int) $r->penarikan_nominal, 'Rp') }}</td>
            <td>{{ $status[$r->penarikan_status] ?? $r->penarikan_status }}</td>
            <td>{{ $r->hasKasir?->name ?? '-' }}</td>
            <td>{{ $r->hasAdmin?->name ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    </div>
</x-layouts::cetak>
