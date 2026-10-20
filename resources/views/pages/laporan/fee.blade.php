<?php /** @var App\Models\Transaksi $model */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('laporan.index'), 'label' => 'Laporan'], ['url' => '', 'label' => 'Laporan Fee']]" />
    <div class="content mt-4 lg:mt-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">percent</span> Laporan Fee</h2>
                <p class="text-xs text-on-surface-variant">Fee transaksi berhasil, dipisah per jenis fee — ekspor CSV/PDF</p>
            </div>
            <div class="flex gap-2 no-print">
                <a href="{{ route('laporan.ekspor', array_merge(request()->query(), ['report' => 'fee', 'format' => 'csv'])) }}" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high"><span class="material-symbols-outlined text-sm">download</span> CSV</a>
                <a href="{{ route('laporan.cetak', array_merge(['jenis' => 'fee'], request()->query())) }}" target="_blank" class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">print</span> Cetak</a>
            </div>
        </div>

        <x-form :model="$model" :action="route('laporan.fee')" :method="'GET'">
            <x-card label="Filter" icon="filter_list">
                <x-input col="2" type="date" name="dari" label="Dari Tanggal" :value="request('dari')" />
                <x-input col="2" type="date" name="sampai" label="Sampai Tanggal" :value="request('sampai')" />
                <x-select col="3" name="transaksi_fee" label="Fee" :options="['' => 'Semua Fee'] + ($feeOptions ?? [])" :default="request('transaksi_fee')" />
                <x-select col="2" name="transaksi_id_gerai" label="Gerai" :options="['' => 'Semua'] + ($geraiOptions ?? [])" :default="request('transaksi_id_gerai')" />
                <x-select col="3" name="transaksi_jenis" label="Jenis" :options="['' => 'Semua'] + $jenis" :default="request('transaksi_jenis')" />
                <div class="col-span-12 flex flex-wrap gap-2 mt-2">
                    <x-button variant="primary" type="submit" icon="search">Tampilkan</x-button>
                    <a href="{{ route('laporan.fee') }}" class="inline-flex items-center h-10 px-4 text-sm rounded-xl border border-outline-variant">Reset</a>
                    @if(!empty($feeDipilih))
                    <span class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">Fee: {{ $feeOptions[$feeDipilih] ?? $feeDipilih }} • {{ $ringkas['fee_hit'][$feeDipilih] ?? 0 }} transaksi • {{ formatAngka((int)($ringkas['fee_per'][$feeDipilih] ?? 0),'Rp') }}</span>
                    @endif
                </div>
            </x-card>
        </x-form>

        {{-- Rincian per Fee --}}
        <x-card label="Rincian per Fee" icon="percent">
            <div class="col-span-12 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase text-on-surface-variant border-b border-outline-variant">
                            <th class="py-2 pr-3">Fee</th>
                            <th class="py-2 pr-3 text-right">Tarif</th>
                            <th class="py-2 pr-3 text-right">Transaksi</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ringkas['fee_per'] as $code => $nominal)
                        <tr class="border-b border-outline-variant/50 {{ !empty($feeDipilih) && $feeDipilih === (string)$code ? 'bg-primary/5' : '' }}">
                            <td class="py-2 pr-3 font-semibold">{{ $ringkas['fee_nama'][$code] ?? ucfirst($code) }} <span class="text-xs font-mono font-normal text-on-surface-variant">{{ $code }}</span></td>
                            <td class="py-2 pr-3 text-right font-mono">{{ feeTarif($code) ?: '-' }}</td>
                            <td class="py-2 pr-3 text-right font-mono"><a href="{{ route('laporan.fee', array_merge(request()->query(), ['transaksi_fee' => $code])) }}" class="text-primary hover:underline" title="Lihat transaksinya">{{ $ringkas['fee_hit'][$code] ?? 0 }}×</a></td>
                            <td class="py-2 text-right font-mono font-semibold">{{ formatAngka((int)$nominal,'Rp') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-3 text-center text-on-surface-variant text-xs">Belum ada fee pada periode ini.</td></tr>
                        @endforelse
                        @if(!empty($ringkas['fee_per']))
                        <tr>
                            <td class="py-2 pr-3 font-bold">Total Fee</td>
                            <td></td>
                            <td></td>
                            <td class="py-2 text-right font-mono font-bold text-primary">{{ formatAngka($ringkas['fee_kelola'],'Rp') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Tabel: 1 baris per fee, info transaksi diulang penuh tiap baris --}}
        <x-table>
            <x-slot:head>
                <th>No</th>
                <th>Tanggal</th>
                <th>Pengguna</th>
                <th>Gerai</th>
                <th>Total</th>
                <th>Fee</th>
                <th>Nominal</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($data as $row)
                @foreach(feeTampil($row->feeRincian(), $feeDipilih ?? null) as $kode => $nominal)
                <tr class="hover:bg-surface-container/50">
                    <td class="font-mono text-xs"><a href="{{ route('transaksi.getUpdate', ['id' => $row->transaksi_id]) }}" wire:navigate class="text-primary hover:underline" title="Lihat transaksi">{{ $row->transaksi_kode ?? '#'.$row->transaksi_id }}</a></td>
                    <td class="font-mono text-xs">{{ formatDate($row->created_at,true) }}</td>
                    <td>
                        <div class="font-semibold text-sm">{{ $row->hasKartu?->hasUser?->name ?? '-' }}</div>
                        <div class="text-xs font-mono text-on-surface-variant">{{ $row->hasKartu?->kartu_barcode ?? '' }}</div>
                    </td>
                    <td class="text-xs">{{ $row->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($row->hasGerai?->gerai_nama ?? '-') }}</td>
                    <td class="font-mono font-semibold">{{ formatAngka((int)$row->transaksi_total,'Rp') }}</td>
                    <td class="font-mono text-xs">{{ $ringkas['fee_nama'][$kode] ?? ucfirst($kode) }}@if(feeTarif($kode)) ({{ feeTarif($kode) }})@endif</td>
                    <td class="font-mono font-semibold">{{ formatAngka((int)$nominal,'Rp') }}</td>
                </tr>
                @endforeach
                @endforeach
            </x-slot:body>
            <x-slot:mobile>
                <div class="p-3 space-y-3">
                    @foreach($data as $row)
                    @foreach(feeTampil($row->feeRincian(), $feeDipilih ?? null) as $kode => $nominal)
                    <div class="border border-outline-variant rounded-xl p-3 bg-surface-container-lowest">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-sm">{{ $row->hasKartu?->hasUser?->name ?? '-' }}</div>
                                <div class="text-xs font-mono text-on-surface-variant">{{ $row->transaksi_kode ?? '#'.$row->transaksi_id }} • {{ formatDate($row->created_at,'d/m H:i') }} • {{ formatAngka((int)$row->transaksi_total,'Rp') }}</div>
                                <div class="text-xs text-on-surface-variant mt-1">{{ $ringkas['fee_nama'][$kode] ?? ucfirst($kode) }}@if(feeTarif($kode)) ({{ feeTarif($kode) }})@endif</div>
                            </div>
                            <span class="font-mono font-bold text-sm text-primary">{{ formatAngka((int)$nominal,'Rp') }}</span>
                        </div>
                    </div>
                    @endforeach
                    @endforeach
                </div>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
    </div>
</x-layouts::app>
