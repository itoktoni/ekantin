<?php /** @var string $tanggal */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url'=>route('pembagian.getTable'),'label'=>'Pembagian'],['url'=>'','label'=>'Bagi Hari Ini']]" />
    <div class="content mt-4 lg:mt-0">
        <x-form :model="null" :action="route('pembagian.getBagi')" :method="'GET'">
            <x-card label="Pilih Tanggal">
                <x-input col="4" type="date" name="tanggal" label="Tanggal" :value="$tanggal" />
                <div class="col-span-2 flex items-end"><x-button variant="primary" type="submit">Tampilkan</x-button></div>
            </x-card>
        </x-form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            @foreach($preview as $p)
            <div class="border rounded-xl p-4 bg-surface-container-lowest {{ $p['sudah'] ? 'opacity-60' : '' }}">
                <div class="font-bold">{{ $p['gerai']->gerai_nama }}</div>
                <div class="text-xs text-on-surface-variant">{{ $p['gerai']->gerai_status }} • Saldo {{ formatAngka((int)$p['gerai']->gerai_saldo,'Rp') }}</div>
                <div class="mt-3 space-y-1 text-sm font-mono">
                    <div class="flex justify-between"><span>Total</span><span>{{ formatAngka($p['total'],'Rp') }}</span></div>
                    <div class="flex justify-between text-xs"><span>Fee</span><span>{{ formatAngka($p['fee'],'Rp') }}</span></div>
                    <div class="flex justify-between font-bold border-t pt-1"><span>Bersih</span><span>{{ formatAngka($p['bersih'],'Rp') }}</span></div>
                </div>
                @if($p['sudah'])
                <div class="mt-3 text-xs px-2 py-1 rounded bg-success/10 text-success border border-success/20">Sudah dibagi {{ formatDate($p['sudah']->pembagian_tanggal) }} • {{ formatAngka((int)$p['sudah']->pembagian_bersih,'Rp') }} oleh {{ $p['sudah']->hasKasir?->name ?? '-' }}</div>
                @elseif(in_array(auth()->user()?->role, ['kasir_sekolah','super_admin','admin']))
                <form action="{{ route('pembagian.postBagi') }}" method="POST" class="mt-3">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                    <input type="hidden" name="gerai_id" value="{{ $p['gerai']->gerai_id }}">
                    <button type="submit" class="w-full h-9 text-sm rounded-xl bg-primary text-on-primary font-semibold">Bagi {{ $p['gerai']->gerai_nama }} — {{ formatAngka($p['bersih'],'Rp') }}</button>
                </form>
                @else
                <div class="mt-3 text-xs px-2 py-1 rounded bg-surface-container border text-on-surface-variant">Menunggu kasir bagi — {{ formatAngka($p['bersih'],'Rp') }} bersih</div>
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-4 flex gap-2">
            <a href="{{ route('pembagian.getTable') }}" class="h-9 px-4 text-sm rounded-xl border inline-flex items-center">Lihat History</a>
        </div>
    </div>
</x-layouts::app>
