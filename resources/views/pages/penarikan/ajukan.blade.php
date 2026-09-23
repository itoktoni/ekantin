<?php /** @var array $preview */ /** @var string $tanggal */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url'=>route('penarikan.getTable'),'label'=>'Penarikan'],['url'=>'','label'=>'Ajukan Penagihan']]" />
    <div class="content mt-4 lg:mt-0">
        <div class="mb-4">
            <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">request_quote</span> Ajukan Penagihan (Penukaran Uang)</h2>
            <p class="text-xs text-on-surface-variant">Di akhir sesi, gerai mengajukan penagihan atas transaksi hari itu — kasir menukar dengan uang fisik.</p>
        </div>

        <x-form :model="null" :action="route('penarikan.getAjukan')" :method="'GET'">
            <x-card label="Pilih Tanggal">
                <x-input col="4" type="date" name="tanggal" label="Tanggal Transaksi" :value="$tanggal" />
                <div class="col-span-2 flex items-end"><x-button variant="primary" type="submit">Tampilkan</x-button></div>
            </x-card>
        </x-form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            @foreach($preview as $p)
            <div class="border rounded-xl p-4 bg-surface-container-lowest {{ $p['ajuan'] ? 'opacity-70' : '' }}">
                <div class="font-bold">{{ $p['gerai']->gerai_nama }}</div>
                <div class="text-xs text-on-surface-variant">{{ $p['gerai']->gerai_status }} • Saldo {{ formatAngka((int)$p['gerai']->gerai_saldo,'Rp') }}</div>
                <div class="mt-3 space-y-1 text-sm font-mono">
                    <div class="flex justify-between"><span>Total</span><span>{{ formatAngka($p['total'],'Rp') }}</span></div>
                    <div class="flex justify-between text-xs"><span>Fee</span><span>{{ formatAngka($p['fee'],'Rp') }}</span></div>
                    <div class="flex justify-between font-bold border-t pt-1"><span>Bersih</span><span>{{ formatAngka($p['bersih'],'Rp') }}</span></div>
                </div>
                @if($p['ajuan'])
                <div class="mt-3 text-xs px-2 py-1 rounded border {{ $p['ajuan']->penarikan_status === 'diselesaikan' ? 'bg-success/10 text-success border-success/20' : 'bg-warning/10 text-warning border-warning/20' }}">
                    @if($p['ajuan']->penarikan_status === 'diselesaikan')
                        Sudah ditukar {{ formatAngka((int)$p['ajuan']->penarikan_nominal,'Rp') }} oleh {{ $p['ajuan']->hasKasir?->name ?? '-' }}
                    @else
                        Menunggu penukaran kasir — {{ formatAngka((int)$p['ajuan']->penarikan_nominal,'Rp') }}
                    @endif
                </div>
                @elseif($p['bersih'] < 1)
                <div class="mt-3 text-xs px-2 py-1 rounded bg-surface-container border text-on-surface-variant">Belum ada transaksi hari ini</div>
                @else
                <form action="{{ route('penarikan.postAjukan') }}" method="POST" class="mt-3">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                    <input type="hidden" name="gerai_id" value="{{ $p['gerai']->gerai_id }}">
                    <button type="submit" class="w-full h-9 text-sm rounded-xl bg-primary text-on-primary font-semibold">Ajukan {{ $p['gerai']->gerai_nama }} — {{ formatAngka($p['bersih'],'Rp') }}</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-4 flex gap-2">
            <a href="{{ route('penarikan.getTable') }}" class="h-9 px-4 text-sm rounded-xl border inline-flex items-center">Lihat History</a>
        </div>
    </div>
</x-layouts::app>