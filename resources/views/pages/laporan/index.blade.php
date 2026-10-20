<?php /** @var App\Models\Transaksi $model */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Laporan']]" />
    <div class="content mt-4 lg:mt-0">
        <div class="mb-4">
            <h2 class="text-lg font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">assessment</span> Laporan</h2>
            <p class="text-xs text-on-surface-variant">Pilih jenis laporan — {{ $ringkas['jumlah'] }} transaksi berhasil • {{ request('dari') ? formatDate(request('dari')) : 'awal' }} → {{ request('sampai') ? formatDate(request('sampai')) : 'hari ini' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @if($boleh('transaksi'))
            <a href="{{ route('laporan.transaksi') }}" wire:navigate class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 hover:border-primary transition-colors text-left">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">receipt_long</span><span class="font-bold">Laporan Transaksi</span></div>
                <p class="text-xs text-on-surface-variant mb-2">Semua transaksi (top up, jajan, refund) + kolom fee per transaksi.</p>
                <p class="text-sm font-mono font-bold text-primary">{{ formatAngka($ringkas['total'],'Rp') }} • {{ $ringkas['jumlah'] }} transaksi</p>
            </a>
            @endif
            @if($boleh('fee'))
            <a href="{{ route('laporan.fee') }}" wire:navigate class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 hover:border-primary transition-colors text-left">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">percent</span><span class="font-bold">Laporan Fee</span></div>
                <p class="text-xs text-on-surface-variant mb-2">Rincian fee dipisah per jenis (sistem, kebersihan, …).</p>
                <p class="text-sm font-mono font-bold text-primary">{{ formatAngka($ringkas['fee_kelola'],'Rp') }} total fee</p>
            </a>
            @endif
            @if($boleh('vendor'))
            <a href="{{ route('laporan.vendor') }}" wire:navigate class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 hover:border-primary transition-colors text-left">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">store</span><span class="font-bold">Laporan Vendor</span></div>
                <p class="text-xs text-on-surface-variant mb-2">Omzet, fee, dan bersih per gerai + penarikan uang.</p>
                <p class="text-sm font-mono font-bold text-primary">{{ $jmlGerai }} gerai</p>
            </a>
            @endif
            @if($boleh('penarikan'))
            <a href="{{ route('laporan.penarikan') }}" wire:navigate class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 hover:border-primary transition-colors text-left">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">savings</span><span class="font-bold">Laporan Penarikan</span></div>
                <p class="text-xs text-on-surface-variant mb-2">Penarikan/penukaran uang gerai per status.</p>
                <p class="text-sm font-mono font-bold text-primary">{{ $tarikAjuan }} diajukan • {{ formatAngka($tarikNominal,'Rp') }}</p>
            </a>
            @endif
        </div>
    </div>
</x-layouts::app>
