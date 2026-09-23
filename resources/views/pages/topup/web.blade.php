<?php /** @var App\Models\Kartu $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Top Up Web']]" />

    {{-- Cari kartu — scan atau pilih pengguna --}}
    <x-form :model="$model" :action="route('topup.web')" :method="'GET'">
        <x-card label="Cari Kartu — Scan atau Pilih Pengguna">
            <x-select col="9" name="kartu_barcode" label="Pilih Pengguna / Scan Barcode" :options="['' => '-- Ketik atau Scan --'] + ($kartuOptions ?? [])" class="search" :default="$kartu?->kartu_barcode ?? request('kartu_barcode') ?? request('kartu')" />
            <div class="col-span-3 flex items-end gap-2">
                <button type="button" onclick="startScan('kartu_barcode')" class="inline-flex items-center gap-1 h-10 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container"><span class="material-symbols-outlined text-sm">qr_code_scanner</span> Scan</button>
                <x-button variant="primary" class="flex-1" type="submit">Cari</x-button>
            </div>
            <div class="col-span-12">
                <div id="reader" class="hidden mt-2 rounded-xl overflow-hidden border border-outline-variant" style="max-width:320px"></div>
                <p class="text-xs text-on-surface-variant mt-1">Pilih dari dropdown, ketik barcode, atau klik Scan untuk pakai kamera HP.</p>
            </div>
        </x-card>
    </x-form>
    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        // Livewire navigate re-executes scripts — guard redeclaration
        window.html5Qr = window.html5Qr || null;
        window.startScan = window.startScan || function(inputName){
            if(typeof Html5Qrcode === 'undefined'){
                alert('Scanner belum termuat, periksa koneksi internet atau coba refresh');
                return;
            }
            const reader=document.getElementById('reader');
            reader.classList.remove('hidden');
            if(!window.html5Qr) window.html5Qr=new Html5Qrcode('reader');
            window.html5Qr.start({facingMode:'environment'}, {fps:10, qrbox:250},
                (decoded)=>{
                    const sel=document.querySelector('select[name="'+inputName+'"]');
                    if(sel){
                        if(sel.tomselect){ sel.tomselect.setValue(decoded); }
                        else { sel.value=decoded; sel.dispatchEvent(new Event('change',{bubbles:true})); }
                    } else {
                        const el=document.querySelector('[name="'+inputName+'"]');
                        if(el){ el.value=decoded; el.dispatchEvent(new Event('change',{bubbles:true})); }
                    }
                    window.html5Qr.stop().then(()=>reader.classList.add('hidden'));
                },
                ()=>{}
            ).catch((e)=>{ reader.classList.add('hidden'); alert('Kamera tidak tersedia: '+(e?.message||'')); console.error(e); });
        };
    </script>
    @endpush

    @if ($kartu && ! $trx)
    {{-- Input nominal --}}
    <x-form :model="$model" :action="route('topup.postWeb')">
        <x-card label="Top Up via QRIS">
            <input type="hidden" name="kartu_barcode" value="{{ $kartu->kartu_barcode }}">
            <input type="hidden" name="idempotency" value="{{ $idempotency }}">

            <div class="col-span-12">
                <p class="text-sm text-on-surface">
                    Pengguna: <strong>{{ $kartu->hasUser?->name ?? '-' }}</strong>
                    <span class="text-on-surface-variant">({{ $kartu->kartu_nis ?? '-' }} / {{ $kartu->kartu_kelas ?? '-' }})</span>
                </p>
                <p class="text-sm text-on-surface mt-1">
                    Saldo sekarang: <strong class="font-data-mono">Rp{{ number_format((int) $kartu->kartu_saldo, 0, ',', '.') }}</strong>
                </p>
                <p class="text-xs text-on-surface-variant mt-1">Minimal top up Rp{{ number_format($minTopup, 0, ',', '.') }}.</p>
            </div>

            <x-input col="6" type="number" name="nominal" label="Nominal Top Up (Rp)" />

            <div class="col-span-12">
                <p class="flex items-start gap-1.5 text-xs text-on-surface-variant">
                    <span class="material-symbols-outlined text-[16px] text-primary">qr_code_2</span>
                    Kode QRIS dengan nominal akan muncul di langkah berikutnya.
                </p>
            </div>
        </x-card>

        <x-action :model="$model" :action="['save']" />
    </x-form>
    @endif

    @if ($trx)
    @php $lunas = $trx->transaksi_status === 'berhasil'; @endphp

    <div class="mt-5 space-y-5">

        {{-- Pembayaran QRIS / menunggu — full width --}}
        <div id="panelMenunggu" class="{{ $lunas ? 'hidden' : '' }} w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_2</span>
                Pembayaran QRIS
            </h3>

            @if ($qris)
            <div class="text-center">
                <p class="text-xs uppercase tracking-wide text-on-surface-variant">Nominal</p>
                <p class="font-data-mono text-3xl font-bold text-primary mt-1">
                    Rp{{ number_format((int) $trx->transaksi_total, 0, ',', '.') }}
                </p>

                <div class="mt-4 inline-block rounded-xl border border-outline-variant bg-white p-3">
                    <img src="data:image/png;base64,{{ $qris['qr_png'] }}" alt="Kode QRIS"
                        class="w-56 h-56 sm:w-60 sm:h-60" style="image-rendering: pixelated;">
                </div>

                <p class="mt-3 text-sm text-on-surface-variant">
                    Buka aplikasi bank / e-wallet, pilih <strong>Scan QRIS</strong>, lalu scan kode di atas.
                </p>
            </div>

            <div class="mt-5 rounded-lg bg-surface-container px-4 py-3 flex items-center gap-3">
                <span class="material-symbols-outlined text-primary animate-spin">progress_activity</span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-on-surface" id="teksStatus">Menunggu pembayaran…</p>
                    <p class="text-xs text-on-surface-variant">Memeriksa otomatis setiap 5 detik &middot; <span id="teksCek">—</span></p>
                </div>
            </div>

            @if(in_array(auth()->user()?->role, ['super_admin','admin','kasir_sekolah']))
            <div class="mt-4 border-t border-outline-variant pt-4">
                <p class="text-xs text-on-surface-variant">
                    Sudah dibayar lewat QRIS tapi status belum berubah? Konfirmasi di sini (langkah yang sama
                    dengan callback payment gateway).
                </p>
                <form action="{{ route('topup.web.bayar', ['id' => $trx->transaksi_id]) }}" method="POST" class="mt-2">
                    @csrf
                    <input type="hidden" name="kartu_barcode" value="{{ $kartu?->kartu_barcode ?? $trx->hasKartu?->kartu_barcode }}">
                    <x-button variant="primary" type="submit">Tandai Sudah Dibayar</x-button>
                </form>
            </div>
            @else
            <div class="mt-4 border-t border-outline-variant pt-4">
                <p class="text-xs text-on-surface-variant bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    Menunggu konfirmasi kasir/admin. Jika sudah bayar tapi belum lunas, hubungi kasir untuk konfirmasi.
                </p>
            </div>
            @endif
            @else
            <div class="text-center py-6">
                <span class="material-symbols-outlined text-[40px] text-warning">warning</span>
                <p class="mt-2 text-sm font-semibold text-on-surface">QRIS belum dikonfigurasi</p>
                <p class="mt-1 text-sm text-on-surface-variant">
                    Isi <code class="font-mono text-xs bg-surface-container px-1 rounded">QRIS=</code> di file <code class="font-mono text-xs bg-surface-container px-1 rounded">.env</code>
                    dengan string QRIS statis milik sekolah, lalu muat ulang halaman.
                </p>
                <p class="mt-3 font-data-mono text-lg font-bold text-on-surface">
                    Nominal menunggu: Rp{{ number_format((int) $trx->transaksi_total, 0, ',', '.') }}
                </p>
            </div>
            @endif
        </div>

        {{-- Lunas — full width --}}
        <div id="panelLunas" class="{{ $lunas ? '' : 'hidden' }} w-full bg-surface-container-lowest border-2 border-success rounded-xl p-6 form-card">
            <div class="text-center py-2">
                <span class="material-symbols-outlined text-[56px] text-success">check_circle</span>
                <p class="mt-2 text-2xl font-bold text-success tracking-wide">PAID</p>
                <p class="mt-1 text-sm text-on-surface-variant">Pembayaran QRIS diterima</p>

                <div class="mt-5 rounded-xl bg-surface-container px-4 py-4 text-left space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-on-surface-variant">Nominal</span>
                        <span class="font-data-mono font-bold text-on-surface" id="lunasTotal">
                            Rp{{ number_format((int) $trx->transaksi_total, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-on-surface-variant">Saldo kartu sekarang</span>
                        <span class="font-data-mono font-bold text-primary" id="lunasSaldo">
                            Rp{{ number_format((int) ($trx->transaksi_saldo_akhir ?? $trx->hasKartu?->kartu_saldo ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-on-surface-variant">Pengguna</span>
                        <span class="font-semibold text-on-surface">{{ $trx->hasKartu?->hasUser?->name ?? $kartu?->hasUser?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-on-surface-variant">No. Transaksi</span>
                        <span class="font-data-mono text-on-surface">#{{ $trx->transaksi_id }}</span>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                    <a href="{{ route('kasir.struk', ['id' => $trx->transaksi_id]) }}"
                        class="inline-flex items-center justify-center gap-1 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container">
                        <span class="material-symbols-outlined text-xl">receipt_long</span> Lihat struk
                    </a>
                    <a href="{{ route('topup.web', ['kartu_barcode' => $kartu?->kartu_barcode ?? $trx->hasKartu?->kartu_barcode]) }}"
                        class="inline-flex items-center justify-center gap-1 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90">
                        <span class="material-symbols-outlined text-xl">add</span> Top up lagi
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (! $lunas && $qris)
    <script>
    (function () {
        const POLL_MS = 5000;
        const url = @json(route('topup.web.status', ['id' => $trx->transaksi_id]));
        const panelMenunggu = document.getElementById('panelMenunggu');
        const panelLunas = document.getElementById('panelLunas');
        const teksStatus = document.getElementById('teksStatus');
        const teksCek = document.getElementById('teksCek');
        const lunasTotal = document.getElementById('lunasTotal');
        const lunasSaldo = document.getElementById('lunasSaldo');
        const rupiah = (n) => 'Rp' + Number(n).toLocaleString('id-ID');

        let selesai = false;
        let gagalBerturut = 0;

        function tampilkanLunas(data) {
            selesai = true;
            if (data.total != null) lunasTotal.textContent = rupiah(data.total);
            lunasSaldo.textContent = rupiah(data.saldo ?? data.saldo_sekarang ?? 0);
            panelMenunggu.classList.add('hidden');
            panelLunas.classList.remove('hidden');
        }

        async function cekStatus() {
            if (selesai) return;
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                gagalBerturut = 0;
                teksCek.textContent = 'terakhir dicek ' + (data.diperiksa_pada ?? '');

                if (data.paid) {
                    tampilkanLunas(data);
                    return;
                }
                if (data.status && data.status !== 'menunggu') {
                    teksStatus.textContent = 'Pembayaran ' + data.status;
                    return;
                }
            } catch (e) {
                gagalBerturut++;
                teksCek.textContent = 'gagal memeriksa, mencoba lagi…';
                if (gagalBerturut >= 6) return;
            }
            setTimeout(cekStatus, POLL_MS);
        }

        setTimeout(cekStatus, POLL_MS);
    })();
    </script>
    @endif
    @endif

    @if(isset($history) && $history->isNotEmpty())
    <x-card label="Riwayat Top Up" icon="history" class="mt-5">
        <div class="col-span-12">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs uppercase text-on-surface-variant border-b"><th class="pb-2">Waktu</th><th class="pb-2">Pengguna</th><th class="pb-2">Jenis</th><th class="pb-2">Nominal</th><th class="pb-2">Status</th><th class="pb-2">Saldo Akhir</th><th class="pb-2">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($history as $h)
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-2 text-xs font-mono">{{ formatDate($h->created_at,true) }}</td>
                            <td class="py-2"><div class="font-semibold text-sm">{{ $h->hasKartu?->hasUser?->name ?? $h->hasKartu?->kartu_barcode ?? '-' }}</div><div class="text-xs font-mono text-on-surface-variant">{{ $h->hasKartu?->kartu_barcode ?? '' }}</div></td>
                            <td class="py-2"><x-badge type="info">{{ \App\Enums\Ekantin\TransaksiJenisEnum::getDescription($h->transaksi_jenis) }}</x-badge></td>
                            <td class="py-2 font-mono font-semibold">{{ formatAngka((int)$h->transaksi_total,'Rp') }}</td>
                            <td class="py-2"><x-badge :type="$h->transaksi_status==='berhasil'?'success':($h->transaksi_status==='menunggu'?'warning':'error')">{{ $h->transaksi_status }}</x-badge></td>
                            <td class="py-2 font-mono text-xs">{{ $h->transaksi_saldo_akhir !== null ? formatAngka((int)$h->transaksi_saldo_akhir,'Rp') : '-' }}</td>
                            <td class="py-2">
                                @if($h->transaksi_status==='menunggu' && in_array(auth()->user()?->role, ['super_admin','admin','kasir_sekolah']))
                                <form action="{{ route('topup.web.bayar', ['id'=>$h->transaksi_id]) }}" method="POST" onsubmit="return confirm('Approve top up Rp{{ number_format((int)$h->transaksi_total,0,',','.') }} untuk {{ $h->hasKartu?->hasUser?->name ?? $h->hasKartu?->kartu_barcode }}?')">
                                    @csrf
                                    <input type="hidden" name="kartu_barcode" value="{{ $h->hasKartu?->kartu_barcode }}">
                                    <button type="submit" class="h-7 px-3 text-xs rounded-lg bg-success text-on-success">Approve</button>
                                </form>
                                @else
                                <span class="text-xs text-on-surface-variant">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="md:hidden space-y-3">
                @foreach($history as $h)
                <div class="border rounded-xl p-3 bg-surface-container">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-bold text-sm">{{ $h->hasKartu?->hasUser?->name ?? $h->hasKartu?->kartu_barcode ?? '-' }}</div>
                            <div class="text-xs font-mono text-on-surface-variant">{{ $h->hasKartu?->kartu_barcode ?? '' }} • {{ formatDate($h->created_at,'d/m H:i') }}</div>
                        </div>
                        <x-badge :type="$h->transaksi_status==='berhasil'?'success':($h->transaksi_status==='menunggu'?'warning':'error')">{{ $h->transaksi_status }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between mt-2 pt-2 border-t">
                        <span class="text-xs"><x-badge type="info">{{ \App\Enums\Ekantin\TransaksiJenisEnum::getDescription($h->transaksi_jenis) }}</x-badge></span>
                        <span class="font-mono font-bold">{{ formatAngka((int)$h->transaksi_total,'Rp') }}</span>
                    </div>
                    @if($h->transaksi_status==='menunggu' && in_array(auth()->user()?->role, ['super_admin','admin','kasir_sekolah']))
                    <form action="{{ route('topup.web.bayar', ['id'=>$h->transaksi_id]) }}" method="POST" class="mt-2" onsubmit="return confirm('Approve?')">
                        @csrf
                        <input type="hidden" name="kartu_barcode" value="{{ $h->hasKartu?->kartu_barcode }}">
                        <button type="submit" class="w-full h-8 text-xs rounded-lg bg-success text-on-success">Approve Top Up</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </x-card>
    @endif
</x-layouts::app>
