<?php /** @var App\Models\Transaksi $model */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('laporan.index'), 'label' => 'Laporan'], ['url' => '', 'label' => 'Laporan Transaksi']]" />
    <div class="content mt-4 lg:mt-0">
        {{-- Header + Export --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">receipt_long</span> Laporan Transaksi</h2>
                <p class="text-xs text-on-surface-variant">Filter periode, gerai, jenis & status — ekspor CSV/PDF</p>
            </div>
            <div class="flex gap-2 no-print">
                <a href="{{ route('laporan.ekspor', array_merge(request()->query(), ['report' => 'transaksi', 'format' => 'csv'])) }}" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high"><span class="material-symbols-outlined text-sm">download</span> CSV</a>
                <a href="{{ route('laporan.cetak', array_merge(['jenis' => 'transaksi'], request()->query())) }}" target="_blank" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">print</span> Cetak</a>
            </div>
        </div>

        <x-form :model="$model" :action="route('laporan.transaksi')" :method="'GET'">
            <x-card label="Filter" icon="filter_list">
                <x-input col="2" type="date" name="dari" label="Dari Tanggal" :value="request('dari')" />
                <x-input col="2" type="date" name="sampai" label="Sampai Tanggal" :value="request('sampai')" />
                <x-select col="2" name="transaksi_id_gerai" label="Gerai" :options="['' => 'Semua'] + ($geraiOptions ?? [])" :default="request('transaksi_id_gerai')" />
                <x-select col="2" name="transaksi_jenis" label="Jenis" :options="['' => 'Semua'] + $jenis" :default="request('transaksi_jenis')" />
                <x-select col="2" name="transaksi_status" label="Status" :options="['' => 'Semua'] + $status" :default="request('transaksi_status')" />
                <x-select col="2" name="transaksi_id_kartu" label="Pengguna" :options="['' => 'Semua Pengguna'] + ($kartuOptions ?? [])" :default="request('transaksi_id_kartu')" />
                <div class="col-span-12 flex flex-wrap gap-2 mt-2">
                    <x-button variant="primary" type="submit" icon="search">Tampilkan</x-button>
                    <a href="{{ route('laporan.transaksi') }}" class="inline-flex items-center h-10 px-4 text-sm rounded-xl border border-outline-variant">Reset</a>
                    <span class="text-xs text-on-surface-variant self-center ml-2">{{ $ringkas['jumlah'] }} data • {{ request('dari') ? formatDate(request('dari')) : 'awal' }} → {{ request('sampai') ? formatDate(request('sampai')) : 'hari ini' }}</span>
                    @if(!empty($filterKartu))
                    <span class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">Pengguna: {{ $filterKartu->hasUser?->name ?? $filterKartu->kartu_barcode }} • {{ $filterKartu->kartu_barcode }}@if($filterKartu->kartu_nis) • {{ $filterKartu->kartu_nis }}@endif</span>
                    @endif
                </div>
            </x-card>
        </x-form>

        {{-- Ringkasan --}}
        @php $isVendorLaporan = in_array(auth()->user()?->role, ['vendor','super_admin','admin']); @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary text-lg">receipt_long</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Transaksi</span></div>
                <div class="text-xl font-bold">{{ $ringkas['jumlah'] }}</div>
                <div class="text-xs text-on-surface-variant">berhasil</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-info text-lg">payments</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Total</span></div>
                <div class="text-lg font-bold">{{ formatAngka($ringkas['total'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">{{ $isVendorLaporan ? 'omzet kotor' : 'total jajan' }}</div>
            </div>
            <a href="{{ route('laporan.fee', request()->query()) }}" wire:navigate class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 hover:border-primary transition-colors">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-warning text-lg">percent</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Fee → Rincian</span></div>
                <div class="text-lg font-bold">{{ formatAngka($ringkas['fee_kelola'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">klik untuk pisah per fee</div>
            </a>
            @if($isVendorLaporan)
            <div class="bg-primary text-on-primary rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-on-primary text-lg">account_balance_wallet</span><span class="text-[11px] font-semibold uppercase text-on-primary/80">Bersih Vendor</span></div>
                <div class="text-xl font-bold">{{ formatAngka($ringkas['bersih'],'Rp') }}</div>
                <div class="text-xs text-on-primary/70">diteruskan ke gerai</div>
            </div>
            @else
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-success text-lg">trending_up</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Rata-rata</span></div>
                <div class="text-lg font-bold">{{ $ringkas['jumlah'] ? formatAngka((int)round($ringkas['total']/$ringkas['jumlah']),'Rp') : 'Rp0' }}</div>
                <div class="text-xs text-on-surface-variant">per transaksi</div>
            </div>
            @endif
        </div>

        {{-- Tabel --}}
        <x-table>
            <x-slot:head>
                <th>Tanggal</th>
                <th>Pengguna</th>
                <th>Gerai</th>
                <th>Jenis</th>
                <th>Total</th>
                <th>Fee</th>
                <th>Status</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($data as $row)
                @php $rincianFee = $row->feeRincian(); @endphp
                <tr class="hover:bg-surface-container/50">
                    <td class="font-mono text-xs">{{ formatDate($row->created_at,true) }}</td>
                    <td>
                        <div class="font-semibold text-sm">{{ $row->hasKartu?->hasUser?->name ?? '-' }}</div>
                        <div class="text-xs font-mono text-on-surface-variant">{{ $row->hasKartu?->kartu_barcode ?? '' }}</div>
                    </td>
                    <td class="text-xs">{{ $row->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($row->hasGerai?->gerai_nama ?? '-') }}</td>
                    <td><x-badge type="info">{{ $row->transaksi_jenis }}</x-badge></td>
                    <td class="font-mono font-semibold">{{ formatAngka((int)$row->transaksi_total,'Rp') }}</td>
                    <td class="font-mono text-xs" title="{{ collect($rincianFee)->map(fn($v,$k)=>$k.': '.formatAngka((int)$v,'Rp'))->implode(', ') ?: '-' }}">{{ $row->feeTotal() ? formatAngka($row->feeTotal(),'Rp') : '-' }}</td>
                    <td><x-badge :type="match($row->transaksi_status){'berhasil'=>'success','menunggu'=>'warning',default=>'error'}">{{ $row->transaksi_status }}</x-badge></td>
                </tr>
                @endforeach
            </x-slot:body>
            <x-slot:mobile>
                <div class="p-3 space-y-3">
                    @foreach($data as $row)
                    <div class="border border-outline-variant rounded-xl p-3 bg-surface-container-lowest">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-sm">{{ $row->hasKartu?->hasUser?->name ?? '-' }}</div>
                                <div class="text-xs font-mono text-on-surface-variant">{{ $row->hasKartu?->kartu_barcode ?? '-' }} • {{ formatDate($row->created_at,'d/m H:i') }}</div>
                                <div class="text-xs text-on-surface-variant mt-1">{{ $row->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($row->hasGerai?->gerai_nama ?? '-') }}</div>
                            </div>
                            <x-badge :type="match($row->transaksi_status){'berhasil'=>'success','menunggu'=>'warning',default=>'error'}">{{ $row->transaksi_status }}</x-badge>
                        </div>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-outline-variant/50">
                            <span class="text-xs"><x-badge type="info">{{ $row->transaksi_jenis }}</x-badge> <span class="font-mono text-on-surface-variant">fee {{ $row->feeTotal() ? formatAngka($row->feeTotal(),'Rp') : '-' }}</span></span>
                            <span class="font-mono font-bold text-sm">{{ formatAngka((int)$row->transaksi_total,'Rp') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
    </div>
</x-layouts::app>
