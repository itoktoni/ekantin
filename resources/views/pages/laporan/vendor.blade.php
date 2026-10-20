<?php /** @var App\Models\Transaksi $model */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('laporan.index'), 'label' => 'Laporan'], ['url' => '', 'label' => 'Laporan Vendor']]" />
    <div class="content mt-4 lg:mt-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">store</span> Laporan Vendor</h2>
                <p class="text-xs text-on-surface-variant">Omzet, fee, bersih, dan penarikan per gerai — ekspor CSV</p>
            </div>
            <div class="flex gap-2 no-print">
                <a href="{{ route('laporan.ekspor', array_merge(request()->query(), ['report' => 'vendor', 'format' => 'csv'])) }}" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high"><span class="material-symbols-outlined text-sm">download</span> CSV</a>
                <a href="{{ route('laporan.cetak', array_merge(['jenis' => 'vendor'], request()->query())) }}" target="_blank" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">print</span> Cetak</a>
            </div>
        </div>

        <x-form :model="$model" :action="route('laporan.vendor')" :method="'GET'">
            <x-card label="Filter" icon="filter_list">
                <x-input col="4" type="date" name="dari" label="Dari Tanggal" :value="request('dari')" />
                <x-input col="4" type="date" name="sampai" label="Sampai Tanggal" :value="request('sampai')" />
                <x-select col="4" name="transaksi_id_gerai" label="Gerai" :options="['' => 'Semua'] + ($geraiOptions ?? [])" :default="request('transaksi_id_gerai')" />
                <div class="col-span-12 flex flex-wrap gap-2 mt-2">
                    <x-button variant="primary" type="submit" icon="search">Tampilkan</x-button>
                    <a href="{{ route('laporan.vendor') }}" class="inline-flex items-center h-10 px-4 text-sm rounded-xl border border-outline-variant">Reset</a>
                    <span class="text-xs text-on-surface-variant self-center ml-2">{{ request('dari') ? formatDate(request('dari')) : 'awal' }} → {{ request('sampai') ? formatDate(request('sampai')) : 'hari ini' }}</span>
                </div>
            </x-card>
        </x-form>

        <x-table>
            <x-slot:head>
                <th>Gerai</th>
                <th>Vendor</th>
                <th>Bank</th>
                <th>No. Rekening</th>
                <th>Atas Nama</th>
                <th>Omzet</th>
                <th>Fee</th>
                <th>Bersih</th>
                <th>Trx</th>
                <th>Diajukan</th>
                <th>Selesai</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($rekap as $r)
                <tr class="hover:bg-surface-container/50">
                    <td class="font-semibold text-sm">{{ $r['gerai']->gerai_nama }}</td>
                    <td class="text-xs">{{ $r['gerai']->hasVendor?->name ?? '-' }}</td>
                    <td class="text-xs">{{ $r['gerai']->gerai_bank ?? '-' }}</td>
                    <td class="font-mono text-xs">{{ $r['gerai']->gerai_no_rekening ?? '-' }}</td>
                    <td class="text-xs">{{ $r['gerai']->gerai_nama_rekening ?? '-' }}</td>
                    <td class="font-mono font-semibold">{{ formatAngka($r['omzet'],'Rp') }}</td>
                    <td class="font-mono text-xs">{{ formatAngka($r['fee'],'Rp') }}</td>
                    <td class="font-mono font-semibold text-primary">{{ formatAngka($r['bersih'],'Rp') }}</td>
                    <td class="font-mono">{{ $r['trx'] }}×</td>
                    <td class="font-mono text-xs">{{ $r['diajukan'] ? formatAngka($r['diajukan'],'Rp') : '-' }}</td>
                    <td class="font-mono text-xs">{{ $r['selesai'] ? formatAngka($r['selesai'],'Rp') : '-' }}</td>
                </tr>
                @endforeach
                <tr class="font-bold">
                    <td colspan="5">Total</td>
                    <td class="font-mono">{{ formatAngka($tot['omzet'],'Rp') }}</td>
                    <td class="font-mono">{{ formatAngka($tot['fee'],'Rp') }}</td>
                    <td class="font-mono text-primary">{{ formatAngka($tot['bersih'],'Rp') }}</td>
                    <td class="font-mono">{{ $tot['trx'] }}×</td>
                    <td class="font-mono">{{ formatAngka($tot['diajukan'],'Rp') }}</td>
                    <td class="font-mono">{{ formatAngka($tot['selesai'],'Rp') }}</td>
                </tr>
            </x-slot:body>
            <x-slot:mobile>
                <div class="p-3 space-y-3">
                    @foreach($rekap as $r)
                    <div class="border border-outline-variant rounded-xl p-3 bg-surface-container-lowest">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-sm">{{ $r['gerai']->gerai_nama }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $r['gerai']->hasVendor?->name ?? '-' }} • {{ $r['trx'] }}× transaksi</div>
                                <div class="text-xs font-mono text-primary mt-1">{{ $r['gerai']->gerai_bank ?? '-' }} • {{ $r['gerai']->gerai_no_rekening ?? '-' }} • {{ $r['gerai']->gerai_nama_rekening ?? '-' }}</div>
                            </div>
                            <span class="font-mono font-bold text-sm text-primary">{{ formatAngka($r['bersih'],'Rp') }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-2 pt-2 border-t border-outline-variant/50 text-xs">
                            <div><p class="text-[10px] uppercase text-on-surface-variant">Omzet</p><p class="font-mono font-semibold">{{ formatAngka($r['omzet'],'Rp') }}</p></div>
                            <div><p class="text-[10px] uppercase text-on-surface-variant">Fee</p><p class="font-mono">{{ formatAngka($r['fee'],'Rp') }}</p></div>
                            <div><p class="text-[10px] uppercase text-on-surface-variant">Ditarik</p><p class="font-mono">{{ formatAngka($r['selesai'],'Rp') }}</p></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>
        </x-table>
    </div>
</x-layouts::app>
