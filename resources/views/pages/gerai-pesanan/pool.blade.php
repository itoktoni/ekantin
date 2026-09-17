<?php /** @var App\Models\TransaksiItem $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Pesanan Gerai']]" />

    <x-card label="Pool Pesanan — Makanan & Minuman yang Harus Disiapkan">
        <div class="col-span-12">
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                @foreach($statusOptions as $key => $label)
                <a href="{{ route('gerai.getPesanan', ['status' => $key]) }}"
                    class="inline-flex items-center h-9 px-4 text-sm rounded-lg {{ $status === $key ? 'bg-primary text-on-primary' : 'border border-outline-variant' }}">{{ $label }}</a>
                @endforeach
                <span id="poolBadge" class="inline-flex items-center h-9 px-4 text-sm rounded-lg bg-surface-container {{ ($baruCount ?? 0) > 0 ? 'font-bold' : '' }}">
                    Baru: {{ $baruCount ?? 0 }}
                </span>
                <span class="text-xs text-on-surface-variant ml-2">Jika struk hilang, cetak ulang per gerai (2 gerai) via tombol Print di tiap baris</span>
            </div>
            @php $grouped = $items->groupBy(fn($x) => $x->hasGerai?->gerai_nama ?? '-'); @endphp
            @if($grouped->count() > 1)
            <div class="flex gap-2 mb-3 flex-wrap">
                @foreach($grouped as $gName => $gItems)
                @php $firstTrx = $gItems->first()?->item_id_transaksi; @endphp
                <a href="{{ $firstTrx ? route('gerai.getPesananCetak', ['id' => $firstTrx]) : '#' }}" target="_blank" class="inline-flex items-center gap-1 h-8 px-3 text-xs rounded-lg border border-outline-variant bg-amber-50">
                    <span class="material-symbols-outlined text-sm">print</span> Print {{ $gName }} ({{ $gItems->count() }})
                </a>
                @endforeach
            </div>
            @endif

            <div id="poolList" class="space-y-2">
                @forelse($items as $i)
                <div class="flex items-center justify-between border rounded-lg px-3 py-2" data-id="{{ $i->item_id }}">
                    <span class="text-sm">
                        <strong>{{ $i->item_qty }}x {{ $i->item_nama }}</strong>
                        <span class="text-on-surface-variant">— {{ $i->hasTransaksi?->hasKartu?->hasUser?->name ?? '-' }}</span>
                        <span class="text-on-surface-variant">({{ $i->hasGerai?->gerai_nama ?? '-' }} · {{ $i->hasTransaksi?->created_at?->format('H:i') }})</span>
                        <span class="ml-1 text-xs px-2 py-0.5 rounded bg-surface-container">{{ $i->item_status }}</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <a href="{{ route('gerai.getPesananCetak', ['id' => $i->item_id_transaksi]) }}" target="_blank" class="inline-flex items-center gap-1 h-8 px-3 text-xs rounded-lg border border-secondary/30 text-secondary" title="Cetak ulang struk gerai ini (jika struk hilang)">
                            <span class="material-symbols-outlined text-sm">print</span> Print
                        </a>
                        @if($i->item_status === 'baru')
                        <button class="h-9 px-4 text-sm rounded-lg bg-primary text-on-primary" onclick="setStatus({{ $i->item_id }}, 'disiapkan')">Siapkan</button>
                        @elseif($i->item_status === 'disiapkan')
                        <button class="h-9 px-4 text-sm rounded-lg bg-primary text-on-primary" onclick="setStatus({{ $i->item_id }}, 'selesai')">Selesai</button>
                        @endif
                    </span>
                </div>
                @empty
                <p class="text-sm text-on-surface-variant">Belum ada pesanan {{ $status }}.</p>
                @endforelse
            </div>
        </div>
    </x-card>

    <script>
    let lastBaru = {{ (int) ($baruCount ?? 0) }};
    async function setStatus(id, status) {
        const res = await fetch(`{{ url('/gerai/pesanan') }}/${id}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''},
            body: JSON.stringify({status}),
        });
        if (res.ok) location.reload();
    }
    setInterval(async () => {
        try {
            const res = await fetch(`{{ route('gerai.getPesanan', ['status' => $status, 'format' => 'json']) }}`, {headers: {'Accept': 'application/json'}});
            if (!res.ok) return;
            const json = await res.json();
            const badge = document.getElementById('poolBadge');
            if (badge) badge.textContent = `Baru: ${json.baru_count}`;
            if (json.baru_count !== lastBaru) location.reload();
        } catch (e) {}
    }, 10000);
    </script>
</x-layouts::app>
