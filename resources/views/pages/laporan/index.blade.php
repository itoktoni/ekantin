<?php /** @var App\Models\Transaksi $model */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Laporan']]" />
    <div class="content mt-4 lg:mt-0">
        {{-- Header + Export --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">assessment</span> Laporan Transaksi</h2>
                <p class="text-xs text-on-surface-variant">Filter periode, jenis & status — ekspor CSV/PDF ≤30 detik</p>
            </div>
            <div class="flex gap-2 no-print">
                <a href="{{ route('laporan.ekspor', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high"><span class="material-symbols-outlined text-sm">download</span> CSV</a>
                <a href="{{ route('laporan.ekspor', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF</a>
            </div>
        </div>

        <x-form :model="$model" :action="route('laporan.index')" :method="'GET'">
            <x-card label="Filter" icon="filter_list">
                <x-input col="2" type="date" name="dari" label="Dari Tanggal" />
                <x-input col="2" type="date" name="sampai" label="Sampai Tanggal" />
                <x-select col="2" name="transaksi_jenis" label="Jenis" :options="['' => 'Semua'] + $jenis" />
                <x-select col="2" name="transaksi_status" label="Status" :options="['' => 'Semua'] + $status" />
                <x-select col="4" name="transaksi_id_kartu" label="Siswa" :options="['' => 'Semua Siswa'] + ($kartuOptions ?? [])" />
                <div class="col-span-12 flex flex-wrap gap-2 mt-2">
                    <x-button variant="primary" type="submit" icon="search">Tampilkan</x-button>
                    <a href="{{ route('laporan.index') }}" class="inline-flex items-center h-10 px-4 text-sm rounded-xl border border-outline-variant">Reset</a>
                    <span class="text-xs text-on-surface-variant self-center ml-2">{{ $ringkas['jumlah'] }} data • {{ request('dari') ? formatDate(request('dari')) : 'awal' }} → {{ request('sampai') ? formatDate(request('sampai')) : 'hari ini' }}</span>
                    @if(!empty($filterKartu))
                    <span class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">Siswa: {{ $filterKartu->hasUser?->name ?? $filterKartu->kartu_barcode }} • {{ $filterKartu->kartu_barcode }}@if($filterKartu->kartu_nis) • {{ $filterKartu->kartu_nis }}@endif</span>
                    @endif
                </div>
            </x-card>
        </x-form>

        {{-- Ringkasan — role based --}}
        @php $isVendorLaporan = in_array(auth()->user()?->role, ['vendor','super_admin','admin']); @endphp
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
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
                <th>Siswa</th>
                <th>Gerai</th>
                <th>Jenis</th>
                <th>Total</th>
                <th>Status</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($data as $row)
                <tr class="hover:bg-surface-container/50">
                    <td class="font-mono text-xs">{{ formatDate($row->created_at,true) }}</td>
                    <td>
                        <div class="font-semibold text-sm">{{ $row->hasKartu?->hasUser?->name ?? '-' }}</div>
                        <div class="text-xs font-mono text-on-surface-variant">{{ $row->hasKartu?->kartu_barcode ?? '' }}</div>
                    </td>
                    <td class="text-xs">{{ $row->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($row->hasGerai?->gerai_nama ?? '-') }}</td>
                    <td><x-badge type="info">{{ $row->transaksi_jenis }}</x-badge></td>
                    <td class="font-mono font-semibold">{{ formatAngka((int)$row->transaksi_total,'Rp') }}</td>
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
                            <span class="text-xs"><x-badge type="info">{{ $row->transaksi_jenis }}</x-badge></span>
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
