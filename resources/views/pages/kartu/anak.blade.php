<?php /** @var \Illuminate\Support\Collection $kartus */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url'=>'/dashboard','label'=>'Home'],['url'=>'','label'=>'Anak Saya']]" />
    <div class="content mt-4 lg:mt-0">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($kartus as $k)
                @php
                $trans = $transaksiPerAnak[$k->kartu_id] ?? collect();
                $pakaiHariIni = \App\Models\Transaksi::where('transaksi_id_kartu',$k->kartu_id)->whereDate('created_at',today())->where('transaksi_jenis','beli')->where('transaksi_status','berhasil')->sum('transaksi_total');
                // fee sudah dipotong di saldo, tampil hanya total transaksi hari ini
            @endphp
            <x-card :label="$k->hasUser?->name ?? $k->kartu_barcode" icon="family_restroom">
                <div class="col-span-12 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center"><span class="material-symbols-outlined text-primary">badge</span></div>
                    <div>
                        <div class="text-sm font-bold">{{ $k->hasUser?->name ?? '-' }} — {{ $k->kartu_barcode }}</div>
                        <div class="text-xs text-on-surface-variant">{{ $k->kartu_nis }} • {{ $k->kartu_kelas }} • {{ $k->kartu_status }}</div>
                    </div>
                    <span class="ml-auto text-xs px-2 py-1 rounded-full bg-surface-container">{{ $k->kartu_status }}</span>
                </div>
                <div class="col-span-6">
                    <p class="text-xs uppercase text-on-surface-variant">Saldo</p>
                    <p class="text-lg font-bold text-primary">{{ formatAngka((int)$k->kartu_saldo,'Rp') }}</p>
                </div>
                <div class="col-span-6">
                    <p class="text-xs uppercase text-on-surface-variant">Limit Harian</p>
                    <p class="text-sm font-semibold">{{ $k->kartu_limit_harian ? formatAngka((int)$k->kartu_limit_harian,'Rp') : 'Tanpa limit' }}</p>
                    @if($k->kartu_limit_harian)
                    <div class="w-full bg-surface-container rounded-full h-2 mt-1"><div class="bg-primary h-2 rounded-full" style="width: {{ min(100, $k->kartu_limit_harian ? ($pakaiHariIni/$k->kartu_limit_harian)*100 : 0) }}%"></div></div>
                    <p class="text-[10px] text-on-surface-variant">Transaksi Hari ini {{ formatAngka((int)$pakaiHariIni,'Rp') }}</p>
                    @endif
                </div>
                <div class="col-span-12 flex gap-2 mt-2">
                    <a href="{{ route('transaksi.getTable') }}?filters[transaksi_id_kartu][\$eq]={{ $k->kartu_id }}" class="h-8 px-3 text-xs rounded-lg bg-primary text-on-primary inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">receipt_long</span> {{ $k->hasUser?->name }}</a>
                    <a href="{{ route('topup.web') }}?kartu={{ $k->kartu_barcode }}" class="h-8 px-3 text-xs rounded-lg border inline-flex items-center gap-1">Top Up</a>
                    <a href="{{ route('laporan.index') }}?transaksi_id_kartu={{ $k->kartu_id }}" class="h-8 px-3 inline-flex items-center  text-xs rounded-lg border">Laporan</a>
                </div>
                <div class="col-span-12 mt-3">
                    <p class="text-xs font-semibold uppercase text-on-surface-variant mb-2">Transaksi Terbaru — {{ $k->hasUser?->name }}</p>
                    @if($trans->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($trans as $tr)
                        <div class="flex items-center justify-between border rounded-lg px-3 py-2 text-sm">
                            <span>
                                <strong>{{ formatDate($tr->created_at,'d/m H:i') }}</strong> • {{ $tr->transaksi_jenis }} • {{ formatAngka((int)$tr->transaksi_total,'Rp') }}
                                <span class="text-xs text-on-surface-variant">({{ $tr->hasItems->map(fn($i)=>$i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') }})</span>
                            </span>
                            <a href="{{ route('transaksi.getUpdate',['id'=>$tr->transaksi_id]) }}" class="h-7 px-3 text-xs rounded-lg border inline-flex items-center">Detail</a>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-on-surface-variant">Belum ada transaksi untuk anak ini.</p>
                    @endif
                </div>
            </x-card>
            @empty
            <x-card label="Anak Saya" icon="family_restroom">
                <div class="col-span-12 text-sm text-on-surface-variant">Belum ada kartu anak terhubung. Hubungi admin.</div>
            </x-card>
            @endforelse
        </div>
    </div>
</x-layouts::app>
