<x-layouts::cetak :judul="$judul">
    <div class="table-wrap">
    <table>
        <tr>
            <th>Gerai</th>
            <th>Vendor</th>
            <th>Bank</th>
            <th>No. Rekening</th>
            <th>Atas Nama</th>
            <th class="num">Omzet</th>
            <th class="num">Fee</th>
            <th class="num">Bersih</th>
            <th class="num">Trx</th>
            <th class="num">Diajukan</th>
            <th class="num">Selesai</th>
        </tr>
        @foreach($rekap as $r)
        <tr>
            <td>{{ $r['gerai']->gerai_nama }}</td>
            <td>{{ $r['gerai']->hasVendor?->name ?? '-' }}</td>
            <td>{{ $r['gerai']->gerai_bank ?? '-' }}</td>
            <td>{{ $r['gerai']->gerai_no_rekening ?? '-' }}</td>
            <td>{{ $r['gerai']->gerai_nama_rekening ?? '-' }}</td>
            <td class="num">{{ formatAngka($r['omzet'], 'Rp') }}</td>
            <td class="num">{{ formatAngka($r['fee'], 'Rp') }}</td>
            <td class="num">{{ formatAngka($r['bersih'], 'Rp') }}</td>
            <td class="num">{{ $r['trx'] }}&times;</td>
            <td class="num">{{ $r['diajukan'] ? formatAngka($r['diajukan'], 'Rp') : '-' }}</td>
            <td class="num">{{ $r['selesai'] ? formatAngka($r['selesai'], 'Rp') : '-' }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td colspan="5">Total</td>
            <td class="num">{{ formatAngka($tot['omzet'], 'Rp') }}</td>
            <td class="num">{{ formatAngka($tot['fee'], 'Rp') }}</td>
            <td class="num">{{ formatAngka($tot['bersih'], 'Rp') }}</td>
            <td class="num">{{ $tot['trx'] }}&times;</td>
            <td class="num">{{ formatAngka($tot['diajukan'], 'Rp') }}</td>
            <td class="num">{{ formatAngka($tot['selesai'], 'Rp') }}</td>
        </tr>
    </table>
    </div>
</x-layouts::cetak>
