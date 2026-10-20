<?php /** @var App\Models\Transaksi $model */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('laporan.index'), 'label' => 'Laporan'], ['url' => '', 'label' => 'Laporan Penarikan']]" />
    <div class="content mt-4 lg:mt-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">savings</span> Laporan Penarikan</h2>
                <p class="text-xs text-on-surface-variant">Penarikan/penukaran uang gerai per status — ekspor CSV</p>
            </div>
            <div class="flex gap-2 no-print">
                <a href="{{ route('laporan.ekspor', array_merge(request()->query(), ['report' => 'penarikan', 'format' => 'csv'])) }}" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high"><span class="material-symbols-outlined text-sm">download</span> CSV</a>
                <a href="{{ route('laporan.cetak', array_merge(['jenis' => 'penarikan'], request()->query())) }}" target="_blank" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">print</span> Cetak</a>
            </div>
        </div>

        <x-form :model="$model" :action="route('laporan.penarikan')" :method="'GET'">
            <x-card label="Filter" icon="filter_list">
                <x-input col="3" type="date" name="dari" label="Dari Tanggal" :value="request('dari')" />
                <x-input col="3" type="date" name="sampai" label="Sampai Tanggal" :value="request('sampai')" />
                <x-select col="3" name="transaksi_id_gerai" label="Gerai" :options="['' => 'Semua'] + ($geraiOptions ?? [])" :default="request('transaksi_id_gerai')" />
                <x-select col="3" name="penarikan_status" label="Status" :options="['' => 'Semua'] + $status" :default="request('penarikan_status')" />
                <div class="col-span-12 flex flex-wrap gap-2 mt-2">
                    <x-button variant="primary" type="submit" icon="search">Tampilkan</x-button>
                    <a href="{{ route('laporan.penarikan') }}" class="inline-flex items-center h-10 px-4 text-sm rounded-xl border border-outline-variant">Reset</a>
                </div>
            </x-card>
        </x-form>

        {{-- Ringkasan per status --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($perStatus as $st => $s)
            <span class="inline-flex items-center gap-2 h-9 px-3 text-xs rounded-full bg-surface-container-lowest border border-outline-variant"><x-badge :type="match($st){'diselesaikan'=>'success','diajukan'=>'warning',default=>'error'}">{{ $st }}</x-badge><span class="font-mono font-bold">{{ $s['jml'] }}× • {{ formatAngka($s['nominal'],'Rp') }}</span></span>
            @endforeach
        </div>

        <x-table>
            <x-slot:head>
                <th>Tanggal</th>
                <th>Gerai</th>
                <th>Bank</th>
                <th>Nominal</th>
                <th>Status</th>
                <th>Kasir</th>
                <th>Admin</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($rows as $r)
                <tr class="hover:bg-surface-container/50">
                    <td class="font-mono text-xs">{{ $r->penarikan_tanggal?->format('d/m/Y') }}</td>
                    <td class="font-semibold text-sm">{{ $r->hasGerai?->gerai_nama ?? '-' }}</td>
                    <td class="font-mono text-xs">{{ $r->hasGerai?->gerai_bank ?? '-' }} • {{ $r->hasGerai?->gerai_no_rekening ?? '-' }}<br><span class="text-on-surface-variant">{{ $r->hasGerai?->gerai_nama_rekening ?? '' }}</span></td>
                    <td class="font-mono font-semibold">{{ formatAngka((int)$r->penarikan_nominal,'Rp') }}</td>
                    <td><x-badge :type="match($r->penarikan_status){'diselesaikan'=>'success','diajukan'=>'warning',default=>'error'}">{{ $r->penarikan_status }}</x-badge></td>
                    <td class="text-xs">{{ $r->hasKasir?->name ?? '-' }}</td>
                    <td class="text-xs">{{ $r->hasAdmin?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </x-slot:body>
            <x-slot:mobile>
                <div class="p-3 space-y-3">
                    @foreach($rows as $r)
                    <div class="border border-outline-variant rounded-xl p-3 bg-surface-container-lowest">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-sm">{{ $r->hasGerai?->gerai_nama ?? '-' }}</div>
                                <div class="text-xs font-mono text-on-surface-variant">{{ $r->penarikan_tanggal?->format('d/m/Y') }}</div>
                                <div class="text-xs font-mono text-primary mt-1">{{ $r->hasGerai?->gerai_bank ?? '-' }} • {{ $r->hasGerai?->gerai_no_rekening ?? '-' }} • {{ $r->hasGerai?->gerai_nama_rekening ?? '-' }}</div>
                            </div>
                            <x-badge :type="match($r->penarikan_status){'diselesaikan'=>'success','diajukan'=>'warning',default=>'error'}">{{ $r->penarikan_status }}</x-badge>
                        </div>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-outline-variant/50">
                            <span class="text-xs text-on-surface-variant">Kasir: {{ $r->hasKasir?->name ?? '-' }}</span>
                            <span class="font-mono font-bold text-sm">{{ formatAngka((int)$r->penarikan_nominal,'Rp') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>
        </x-table>
    </div>
</x-layouts::app>
