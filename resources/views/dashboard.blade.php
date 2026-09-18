<x-layouts::app title="Dashboard">
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-on-surface">Dashboard</h2>
            @if(!empty($isGerai) && $isGerai)
                <span class="text-xs px-3 py-1 rounded-full bg-primary/10 text-primary font-semibold">{{ $gerais->pluck('gerai_nama')->implode(', ') ?: 'Gerai' }}</span>
            @endif
        </div>

        @if(!empty($isGerai) && $isGerai)
        {{-- Gerai dashboard --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary text-lg">store</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">{{ $gerais->count() === 1 ? 'Gerai' : 'Gerai' }}</span></div>
                <div class="text-lg font-bold truncate">{{ $gerais->pluck('gerai_nama')->implode(', ') }}</div>
                <div class="text-xs text-on-surface-variant">{{ $statsGerai['gerai_count'] }} gerai • {{ $statsGerai['produk_total'] }} produk</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-success text-lg">account_balance_wallet</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Saldo Gerai</span></div>
                <div class="text-lg font-bold text-success">{{ formatAngka($statsGerai['saldo_total'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">{{ $statsGerai['produk_tersedia'] }} produk tersedia</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary text-lg">today</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Pendapatan Hari Ini</span></div>
                <div class="text-lg font-bold text-primary">{{ formatAngka($statsGerai['pendapatan_hari_ini'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">Omzet {{ formatAngka($statsGerai['omzet_hari_ini'],'Rp') }} • Fee {{ formatAngka($statsGerai['fee_hari_ini'],'Rp') }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-warning text-lg">receipt_long</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Transaksi Hari Ini</span></div>
                <div class="text-lg font-bold">{{ $statsGerai['transaksi_hari_ini'] }}</div>
                <div class="text-xs text-on-surface-variant">Baru {{ $statsGerai['pesanan_baru'] }} • Siap {{ $statsGerai['pesanan_disiapkan'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 min-w-0 overflow-hidden">
                <h3 class="font-semibold text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2"><span class="material-symbols-outlined text-primary">trending_up</span> Pendapatan per Hari (7 hari)</h3>
                <div class="min-w-0">{!! $pendapatanChart->container() !!}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 min-w-0 overflow-hidden">
                <h3 class="font-semibold text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2"><span class="material-symbols-outlined text-primary">pie_chart</span> Status Pesanan</h3>
                <div class="bg-surface-container rounded-lg p-4 min-w-0 overflow-hidden">{!! $pesananChart->container() !!}</div>
                <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                    <div><div class="text-lg font-bold text-warning">{{ $statsGerai['pesanan_baru'] }}</div><div class="text-[10px] uppercase text-on-surface-variant">Baru</div></div>
                    <div><div class="text-lg font-bold text-info">{{ $statsGerai['pesanan_disiapkan'] }}</div><div class="text-[10px] uppercase text-on-surface-variant">Disiapkan</div></div>
                    <div><div class="text-lg font-bold text-success">{{ $statsGerai['gerai_count'] ? \App\Models\TransaksiItem::whereIn('item_id_gerai',$gerais->pluck('gerai_id'))->where('item_status','selesai')->count() : 0 }}</div><div class="text-[10px] uppercase text-on-surface-variant">Selesai</div></div>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 mb-5">
            <h3 class="font-semibold text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2"><span class="material-symbols-outlined text-primary">history</span> Transaksi Terbaru Gerai</h3>
            @if($recentTransaksi->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs uppercase text-on-surface-variant border-b"><th class="pb-2">Waktu</th><th class="pb-2">Siswa</th><th class="pb-2">Gerai</th><th class="pb-2">Total</th><th class="pb-2">Fee</th><th class="pb-2">Bersih</th></tr></thead>
                    <tbody>
                        @foreach($recentTransaksi as $tr)
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-2 text-xs">{{ formatDate($tr->created_at,true) }}</td>
                            <td class="py-2">{{ $tr->hasKartu?->hasUser?->name ?? '-' }}</td>
                            <td class="py-2 text-xs">{{ $tr->hasItems->map(fn($i)=>$i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') }}</td>
                            <td class="py-2 font-mono">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</td>
                            <td class="py-2 font-mono text-xs">{{ formatAngka($tr->feeTotal(),'Rp') }}</td>
                            <td class="py-2 font-mono font-semibold">{{ formatAngka((int)$tr->transaksi_bersih,'Rp') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex gap-2"><a href="{{ route('gerai.getPesanan') }}" class="h-9 px-4 text-sm rounded-lg bg-primary text-on-primary inline-flex items-center">Lihat Pesanan</a><a href="{{ route('transaksi.getTable') }}" class="h-9 px-4 text-sm rounded-lg border inline-flex items-center">Semua Transaksi</a></div>
            @else
            <p class="text-sm text-on-surface-variant">Belum ada transaksi.</p>
            @endif
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
            <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">store</span> Gerai Saya</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($gerais as $g)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface-container">
                    <div class="font-bold">{{ $g->gerai_nama }}</div>
                    <div class="text-xs text-on-surface-variant">{{ $g->gerai_status }} • Saldo {{ formatAngka((int)$g->gerai_saldo,'Rp') }}</div>
                    <div class="text-xs mt-2">{{ $g->hasProduks()->where('produk_status','tersedia')->count() }} produk tersedia / {{ $g->hasProduks()->count() }} total</div>
                    <a href="{{ route('produk.getTable') }}?filters[produk_id_gerai][\$eq]={{ $g->gerai_id }}" class="inline-flex mt-2 h-8 px-3 text-xs rounded-lg border">Lihat Produk</a>
                </div>
                @endforeach
            </div>
        </div>

        @push('scripts')
            {!! $pendapatanChart->script() !!}
            {!! $pesananChart->script() !!}
        @endpush

        @elseif(!empty($isOrtu) && $isOrtu)
        {{-- Orang tua dashboard --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">family_restroom</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Anak</span></div>
                <div class="text-lg font-bold">{{ $statsOrtu['anak_count'] }} Anak</div>
                <div class="text-xs text-on-surface-variant">Total saldo {{ formatAngka($statsOrtu['saldo_total'],'Rp') }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-success">payments</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Belanja Hari Ini</span></div>
                <div class="text-lg font-bold text-success">{{ formatAngka($statsOrtu['belanja_hari_ini'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">Fee {{ formatAngka($statsOrtu['fee_hari_ini'],'Rp') }} • {{ $statsOrtu['transaksi_hari_ini'] }} transaksi</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-warning">account_balance_wallet</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Saldo Total Anak</span></div>
                <div class="text-lg font-bold text-primary">{{ formatAngka($statsOrtu['saldo_total'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">Untuk jajan</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-info">add_card</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Top Up</span></div>
                <a href="{{ route('topup.web') }}" class="inline-flex h-8 px-3 text-xs rounded-lg bg-primary text-center inline-flex items-center text-on-primary">Top Up Web</a>
                <div class="text-xs text-on-surface-variant mt-1">Minimal {{ formatAngka(\App\Models\FeeConfig::minTopup(),'Rp') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 min-w-0 overflow-hidden">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">trending_up</span> Belanja Anak per Hari (7 hari)</h3>
                <div class="min-w-0">{!! $spendingChart->container() !!}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">badge</span> Kartu Anak</h3>
                <div class="space-y-3">
                    @foreach($kartus as $k)
                    <div class="border rounded-xl p-3 bg-surface-container">
                        <div class="font-bold text-sm">{{ $k->hasUser?->name ?? $k->kartu_barcode }}</div>
                        <div class="text-xs font-mono">{{ $k->kartu_barcode }} • {{ $k->kartu_kelas }} • {{ $k->kartu_status }}</div>
                        <div class="flex justify-between text-sm mt-2"><span>Saldo</span><span class="font-bold">{{ formatAngka((int)$k->kartu_saldo,'Rp') }}</span></div>
                        <div class="flex justify-between text-xs"><span>Limit harian</span><span>{{ $k->kartu_limit_harian ? formatAngka((int)$k->kartu_limit_harian,'Rp') : 'Tanpa limit' }}</span></div>
                        @php $pakai = collect($statsOrtu['limit_terpakai'])->firstWhere('nama', $k->hasUser?->name); @endphp
                        @if($pakai && $pakai['limit'])
                        <div class="w-full bg-surface-container-lowest rounded-full h-2 mt-2"><div class="bg-primary h-2 rounded-full" style="width: {{ min(100, ($pakai['pakai']/$pakai['limit'])*100) }}%"></div></div>
                        <div class="text-[10px] text-on-surface-variant">Terpakai {{ formatAngka((int)$pakai['pakai'],'Rp') }} / {{ formatAngka((int)$pakai['limit'],'Rp') }}</div>
                        @endif
                        <a href="{{ route('kartu.getAnak') }}" class="inline-flex mt-2 h-7 px-3 text-xs inline-flex items-center rounded-lg border">Lihat Anak</a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 mb-5">
            <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">history</span> Transaksi Terbaru Anak</h3>
            @if($recentTransaksi->isNotEmpty())
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs uppercase text-on-surface-variant border-b"><th class="pb-2">Waktu</th><th class="pb-2">Anak</th><th class="pb-2">Gerai</th><th class="pb-2">Total</th><th class="pb-2">Status</th></tr></thead>
                    <tbody>
                        @foreach($recentTransaksi as $tr)
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-2 text-xs">{{ formatDate($tr->created_at,true) }}</td>
                            <td class="py-2">{{ $tr->hasKartu?->hasUser?->name ?? '-' }}</td>
                            <td class="py-2 text-xs">{{ $tr->hasItems->map(fn($i)=>$i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') }}</td>
                            <td class="py-2 font-mono">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</td>
                            <td class="py-2"><x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="md:hidden space-y-3">
                @foreach($recentTransaksi as $tr)
                <div class="border border-outline-variant rounded-xl p-3 bg-surface-container">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-xs text-on-surface-variant">{{ formatDate($tr->created_at,'d/m H:i') }}</div>
                            <div class="font-bold text-sm">{{ $tr->hasKartu?->hasUser?->name ?? '-' }}</div>
                            <div class="text-xs text-on-surface-variant">{{ $tr->hasItems->map(fn($i)=>$i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: '-' }}</div>
                        </div>
                        <x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-outline-variant/50">
                        <span class="text-xs text-on-surface-variant">Total</span>
                        <span class="font-mono font-bold text-sm">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-3 flex gap-2"><a href="{{ route('transaksi.getTable') }}" class="h-9 px-4 text-sm rounded-lg bg-primary text-on-primary inline-flex items-center">Semua Transaksi</a><a href="{{ route('laporan.index') }}" class="h-9 px-4 text-sm rounded-lg border inline-flex items-center">Laporan</a></div>
            @else
            <p class="text-sm text-on-surface-variant">Belum ada transaksi.</p>
            @endif
        </div>

        @push('scripts')
            {!! $spendingChart->script() !!}
        @endpush

        @elseif(!empty($isSiswa) && $isSiswa)
        {{-- Siswa dashboard --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">badge</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Kartu</span></div>
                <div class="text-sm font-bold">{{ $kartu->kartu_barcode ?? '-' }}</div>
                <div class="text-xs text-on-surface-variant">{{ $kartu->kartu_kelas ?? '' }} • {{ $kartu->kartu_status ?? '' }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-success">account_balance_wallet</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Saldo</span></div>
                <div class="text-lg font-bold text-success">{{ formatAngka((int)($statsSiswa['saldo'] ?? 0),'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">Sisa limit {{ isset($statsSiswa['sisa_limit']) && $statsSiswa['sisa_limit']!==null ? formatAngka((int)$statsSiswa['sisa_limit'],'Rp') : 'tanpa limit' }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-warning">today</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Jajan Hari Ini</span></div>
                <div class="text-lg font-bold">{{ $statsSiswa['transaksi_hari_ini'] ?? 0 }}x</div>
                <div class="text-xs text-on-surface-variant">{{ formatAngka((int)($statsSiswa['pakai_hari_ini'] ?? 0),'Rp') }} • Fee {{ formatAngka((int)($statsSiswa['fee_hari_ini'] ?? 0),'Rp') }}</div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 min-w-0 overflow-hidden">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">trending_up</span> Jajan per Hari (7 hari)</h3>
                <div class="min-w-0">{!! $spendingChart->container() !!}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">badge</span> Kartu Saya</h3>
                @if(!empty($kartu))
                <div class="border rounded-xl p-3 bg-surface-container">
                    <div class="font-bold text-sm">{{ $kartu->hasUser?->name ?? $kartu->kartu_barcode }}</div>
                    <div class="text-xs font-mono">{{ $kartu->kartu_barcode }} • {{ $kartu->kartu_kelas }}</div>
                    <div class="flex justify-between text-sm mt-2"><span>Saldo</span><span class="font-bold">{{ formatAngka((int)$kartu->kartu_saldo,'Rp') }}</span></div>
                    <div class="flex justify-between text-xs"><span>Limit</span><span>{{ $kartu->kartu_limit_harian ? formatAngka((int)$kartu->kartu_limit_harian,'Rp') : 'Tanpa limit' }}</span></div>
                    @if($kartu->kartu_limit_harian)
                    <div class="w-full bg-surface-container-lowest rounded-full h-2 mt-2"><div class="bg-primary h-2 rounded-full" style="width: {{ min(100, ($statsSiswa['pakai_hari_ini']/$kartu->kartu_limit_harian)*100) }}%"></div></div>
                    <div class="text-[10px] text-on-surface-variant">Pakai {{ formatAngka((int)$statsSiswa['pakai_hari_ini'],'Rp') }} / {{ formatAngka((int)$kartu->kartu_limit_harian,'Rp') }}</div>
                    @endif
                </div>
                @else
                <p class="text-sm text-on-surface-variant">Kartu belum terhubung.</p>
                @endif
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 mb-5">
            <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">history</span> Transaksi Terbaru</h3>
            @if($recentTransaksi->isNotEmpty())
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-on-surface-variant border-b"><th class="pb-2">Waktu</th><th class="pb-2">Gerai</th><th class="pb-2">Total</th><th class="pb-2">Status</th></tr></thead><tbody>
                    @foreach($recentTransaksi as $tr)
                    <tr class="border-b border-outline-variant/50"><td class="py-2 text-xs">{{ formatDate($tr->created_at,true) }}</td><td class="py-2 text-xs">{{ $tr->hasItems->map(fn($i)=>$i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') }}</td><td class="py-2 font-mono">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</td><td class="py-2"><x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge></td></tr>
                    @endforeach
                </tbody></table>
            </div>
            <div class="md:hidden space-y-3">
                @foreach($recentTransaksi as $tr)
                <div class="border rounded-xl p-3 bg-surface-container"><div class="flex justify-between"><div class="text-xs">{{ formatDate($tr->created_at,'d/m H:i') }}</div><x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge></div><div class="text-xs mt-1">{{ $tr->hasItems->map(fn($i)=>$i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') }}</div><div class="font-mono font-bold text-sm mt-1">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</div></div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-on-surface-variant">Belum ada transaksi.</p>
            @endif
        </div>
        @push('scripts')
            {!! $spendingChart->script() !!}
        @endpush

        @elseif(!empty($isKasir) && $isKasir)
        {{-- Kasir dashboard --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">point_of_sale</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Transaksi Hari Ini</span></div>
                <div class="text-xl font-bold">{{ $statsKasir['transaksi_hari_ini'] }}</div>
                <div class="text-xs text-on-surface-variant">Omzet {{ formatAngka($statsKasir['omzet_hari_ini'],'Rp') }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-success">payments</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Bersih Hari Ini</span></div>
                <div class="text-lg font-bold text-success">{{ formatAngka($statsKasir['bersih_hari_ini'],'Rp') }}</div>
                <div class="text-xs text-on-surface-variant">Fee {{ formatAngka($statsKasir['fee_hari_ini'],'Rp') }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-warning">pending</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Top Up Menunggu</span></div>
                <div class="text-xl font-bold text-warning">{{ $statsKasir['topup_menunggu'] }}</div>
                <div class="text-xs text-on-surface-variant">Top up hari ini {{ $statsKasir['topup_hari_ini'] }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-info">notifications</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Pesanan Baru</span></div>
                <div class="text-xl font-bold">{{ $statsKasir['pesanan_baru'] }}</div>
                <div class="text-xs text-on-surface-variant">{{ $statsKasir['produk_tersedia'] }} produk tersedia</div>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <div class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary">payments</span> Pembagian Harian</div>
                <div class="text-xs text-on-surface-variant">Vendor minta uang bersih per hari — kasir bagi & history tercatat</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('pembagian.getBagi') }}" class="h-9 px-4 text-sm rounded-xl bg-primary text-on-primary inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">account_balance</span> Bagi Hari Ini</a>
                <a href="{{ route('pembagian.getTable') }}" class="h-9 px-4 text-sm rounded-xl border inline-flex items-center">History</a>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 min-w-0 overflow-hidden">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">bar_chart</span> Transaksi Harian (7 hari)</h3>
                <div class="min-w-0">{!! $kasirChart->container() !!}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">bolt</span> Aksi Cepat</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('kasir.pos') }}" class="h-20 rounded-xl bg-primary text-on-primary flex flex-col items-center justify-center gap-1"><span class="material-symbols-outlined">point_of_sale</span><span class="text-xs font-bold">Kasir POS</span></a>
                    <a href="{{ route('topup.tunai') }}" class="h-20 rounded-xl bg-surface-container border flex flex-col items-center justify-center gap-1"><span class="material-symbols-outlined">payments</span><span class="text-xs font-bold">Top Up Tunai</span></a>
                    <a href="{{ route('gerai.getPesanan') }}" class="h-20 rounded-xl bg-surface-container border flex flex-col items-center justify-center gap-1"><span class="material-symbols-outlined">notifications</span><span class="text-xs">Pesanan</span></a>
                    <a href="{{ route('transaksi.getTable') }}" class="h-20 rounded-xl bg-surface-container border flex flex-col items-center justify-center gap-1"><span class="material-symbols-outlined">receipt_long</span><span class="text-xs">Transaksi</span></a>
                </div>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 mb-5">
            <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">history</span> Transaksi Terbaru</h3>
            @if($recentTransaksi->isNotEmpty())
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-on-surface-variant border-b"><th class="pb-2">Waktu</th><th class="pb-2">Siswa</th><th class="pb-2">Total</th><th class="pb-2">Status</th></tr></thead><tbody>
                    @foreach($recentTransaksi as $tr)
                    <tr class="border-b border-outline-variant/50"><td class="py-2 text-xs">{{ formatDate($tr->created_at,true) }}</td><td class="py-2">{{ $tr->hasKartu?->hasUser?->name ?? '-' }}</td><td class="py-2 font-mono">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</td><td class="py-2"><x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge></td></tr>
                    @endforeach
                </tbody></table>
            </div>
            <div class="md:hidden space-y-3">
                @foreach($recentTransaksi as $tr)
                <div class="border rounded-xl p-3 bg-surface-container"><div class="flex justify-between"><div class="text-xs">{{ formatDate($tr->created_at,'d/m H:i') }}</div><x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge></div><div class="font-bold text-sm mt-1">{{ $tr->hasKartu?->hasUser?->name ?? '-' }}</div><div class="font-mono font-bold text-sm mt-1">{{ formatAngka((int)$tr->transaksi_total,'Rp') }}</div></div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-on-surface-variant">Belum ada transaksi.</p>
            @endif
        </div>
        @push('scripts')
            {!! $kasirChart->script() !!}
        @endpush

        @else
        {{-- Super Admin dashboard — Ekantin --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">store</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Gerai</span></div>
                <div class="text-xl font-bold">{{ $stats['total_gerai'] }}</div>
                <div class="text-xs text-on-surface-variant">{{ $stats['total_produk'] }} produk</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-success">badge</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Kartu</span></div>
                <div class="text-xl font-bold">{{ $stats['total_kartu'] }}</div>
                <div class="text-xs text-on-surface-variant">Saldo kartu {{ formatAngka($stats['saldo_kartu_total'],'Rp') }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-primary">receipt_long</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Transaksi</span></div>
                <div class="text-xl font-bold">{{ $stats['total_transaksi'] }}</div>
                <div class="text-xs text-on-surface-variant">Pesanan baru {{ $stats['pesanan_baru'] }}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-2 mb-1"><span class="material-symbols-outlined text-warning">pending</span><span class="text-[11px] font-semibold uppercase text-on-surface-variant">Top Up Menunggu</span></div>
                <div class="text-xl font-bold text-warning">{{ $stats['topup_menunggu'] }}</div>
                <div class="text-xs text-on-surface-variant">Saldo gerai {{ formatAngka($stats['saldo_gerai_total'],'Rp') }}</div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">percent</span> Fee Produk (master)</h3>
                @if(!empty($fees) && $fees->isNotEmpty())
                <div class="space-y-2">
                    @foreach($fees as $f)
                    <div class="flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm">
                        <span class="min-w-0 truncate"><strong>{{ $f->nama_fee }}</strong> <span class="text-xs text-on-surface-variant font-mono">{{ $f->code_fee }}</span> • {{ rtrim(rtrim(number_format((float)$f->value_fee,2,',','.'),'0'),',') }}%</span>
                        <span class="flex gap-1 shrink-0">
                            <a href="{{ route('fee.getUpdate', ['id' => $f->fee_id]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors" title="Edit"><span class="material-symbols-outlined text-lg">edit</span></a>
                            <a href="{{ route('fee.getDelete', ['id' => $f->fee_id]) }}" onclick="return confirm('Hapus fee {{ $f->nama_fee }}?')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors" title="Hapus"><span class="material-symbols-outlined text-lg">delete</span></a>
                        </span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Total</span>
                    <span class="font-bold">{{ rtrim(rtrim(number_format((float)$fees->sum('value_fee'),2,',','.'),'0'),',') }}%</span>
                </div>
                @else
                <p class="text-sm text-on-surface-variant">Belum ada fee. Fee kosong = tanpa potongan.</p>
                @endif
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('fee.getTable') }}" class="inline-flex h-8 px-3 text-xs rounded-lg border items-center gap-1"><span class="material-symbols-outlined text-base">settings</span>Kelola Fee</a>
                    <a href="{{ route('fee.getCreate') }}" class="inline-flex h-8 px-3 text-xs rounded-lg bg-primary text-on-primary items-center gap-1"><span class="material-symbols-outlined text-base">add</span>Tambah</a>
                </div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">payments</span> Fee Bulan Ini ({{ now()->format('M Y') }})</h3>
                @if(!empty($feeBulan))
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-on-surface-variant">{{ $feeBulan['count'] }} transaksi • Omzet {{ formatAngka($feeBulan['omzet'],'Rp') }}</span>
                    <span class="font-bold font-mono">{{ formatAngka($feeBulan['total'],'Rp') }}</span>
                </div>
                @if(!empty($feeBulan['rincian']))
                <div class="space-y-2">
                    @foreach($feeBulan['rincian'] as $kode => $nominal)
                    <div class="flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm">
                        <span class="min-w-0 truncate">Fee {{ $fees->firstWhere('code_fee',$kode)?->nama_fee ?? $kode }}</span>
                        <span class="font-mono shrink-0">{{ formatAngka((int)$nominal,'Rp') }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-on-surface-variant">Belum ada fee bulan ini.</p>
                @endif
                @endif
                <a href="{{ route('laporan.index') }}" class="inline-flex mt-3 h-8 px-3 text-xs rounded-lg border items-center gap-1"><span class="material-symbols-outlined text-base">assessment</span>Lihat Laporan</a>
            </div>
        </div>
        @if(!empty($pendapatanPerGeraiChart))
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 mb-5">
            <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">store</span> Omzet per Gerai (7 hari)</h3>
            <div>{!! $pendapatanPerGeraiChart->container() !!}</div>
        </div>
        @endif
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 min-w-0 overflow-hidden">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">bar_chart</span> Transaksi Harian Ekantin (7 hari)</h3>
                <div class="min-w-0">{!! $ekantinChart->container() !!}</div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">payments</span> Pembagian Terbaru</h3>
                @if(!empty($recentPembagian) && $recentPembagian->isNotEmpty())
                <div class="space-y-2">
                    @foreach($recentPembagian as $pb)
                    <div class="flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm">
                        <span class="min-w-0 truncate">{{ $pb->hasGerai?->gerai_nama ?? '-' }} • {{ formatDate($pb->pembagian_tanggal) }} • {{ formatAngka((int)$pb->pembagian_bersih,'Rp') }}</span>
                        <span class="text-xs shrink-0">{{ $pb->hasKasir?->name ?? '-' }}</span>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('pembagian.getTable') }}" class="inline-flex mt-3 h-8 px-3 text-xs rounded-lg border items-center gap-1"><span class="material-symbols-outlined text-base">payments</span>Lihat Pembagian</a>
                @else
                <p class="text-sm text-on-surface-variant">Belum ada pembagian.</p>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">history</span> Transaksi Terbaru</h3>
                @if(!empty($recentTransaksiAdmin) && $recentTransaksiAdmin->isNotEmpty())
                <div class="space-y-2">
                    @foreach($recentTransaksiAdmin as $tr)
                    <div class="flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm">
                        <span class="min-w-0 truncate"><strong>{{ $tr->hasKartu?->hasUser?->name ?? '-' }}</strong> • {{ formatAngka((int)$tr->transaksi_total,'Rp') }} • {{ formatDate($tr->created_at,'d/m H:i') }}</span>
                        <span class="shrink-0"><x-badge :type="$tr->transaksi_status==='berhasil'?'success':'warning'">{{ $tr->transaksi_status }}</x-badge></span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-on-surface-variant">Belum ada transaksi.</p>
                @endif
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                <h3 class="font-semibold pb-3 mb-3 border-b flex items-center gap-2"><span class="material-symbols-outlined text-primary">group</span> Gerai & Saldo</h3>
                <div class="space-y-2">
                    @foreach(\App\Models\Gerai::with('hasVendor')->orderBy('gerai_nama')->get() as $g)
                    <div class="flex items-center justify-between gap-2 border rounded-lg px-3 py-2">
                        <span class="min-w-0 truncate text-sm"><strong>{{ $g->gerai_nama }}</strong> • {{ $g->hasVendor?->name ?? '-' }} • {{ $g->gerai_status }}</span>
                        <span class="font-mono text-sm shrink-0">{{ formatAngka((int)$g->gerai_saldo,'Rp') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @push('scripts')
            {!! $ekantinChart->script() !!}
            @if(!empty($pendapatanPerGeraiChart)) {!! $pendapatanPerGeraiChart->script() !!} @endif
        @endpush
        @endif
    </div>
</x-layouts::app>
