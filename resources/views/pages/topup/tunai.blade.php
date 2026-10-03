<?php /** @var App\Models\Kartu $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Top Up Tunai']]" />

    <x-form :model="$model" :action="route('topup.tunai')" :method="'GET'">
        <x-card label="Cari Kartu — Scan atau Pilih Pengguna">
            <x-select col="9" name="kartu_barcode" label="Pilih Pengguna / Scan Barcode" :options="['' => '-- Ketik atau Scan --'] + ($kartuOptions ?? [])" class="search" :default="$kartu?->kartu_barcode ?? request('kartu_barcode') ?? request('kartu')" />
            <div class="col-span-3 flex items-end gap-2">
                <button type="button" onclick="startScanTunai('kartu_barcode')" class="inline-flex items-center gap-1 h-10 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container"><span class="material-symbols-outlined text-sm">qr_code_scanner</span> Scan</button>
                <x-button variant="primary" class="flex-1" type="submit">Cari</x-button>
            </div>
            <div class="col-span-12">
                <div id="readerTunai" class="hidden mt-2 rounded-xl overflow-hidden border border-outline-variant" style="max-width:320px"></div>
                <p class="text-xs text-on-surface-variant mt-1">Pilih dari dropdown, ketik barcode, atau klik Scan untuk pakai kamera HP.</p>
            </div>
            <div class="col-span-12">
                <label for="nfcTopup" class="font-body-sm text-body-sm font-bold text-on-surface-variant flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-primary">nfc</span>
                    Tempel kartu NFC
                    <span id="nfcTopupDot" class="inline-block h-2.5 w-2.5 rounded-full bg-success animate-pulse" title="Siap scan"></span>
                </label>
                <input id="nfcTopup" type="text" autocomplete="off" spellcheck="false" placeholder="Tempelkan kartu…"
                    class="mt-1 h-12 w-full rounded-lg border border-outline-variant bg-white px-4 font-data-mono outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                <p id="nfcTopupStatus" aria-live="polite" class="mt-1 text-xs font-semibold text-on-surface-variant">Siap scan — tempelkan kartu NFC.</p>
            </div>
        </x-card>
    </x-form>
    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        window.html5QrTunai = window.html5QrTunai || null;
        window.startScanTunai = window.startScanTunai || function(inputName){
            if(typeof Html5Qrcode === 'undefined'){ alert('Scanner belum termuat'); return; }
            const reader=document.getElementById('readerTunai');
            reader.classList.remove('hidden');
            if(!window.html5QrTunai) window.html5QrTunai=new Html5Qrcode('readerTunai');
            window.html5QrTunai.start({facingMode:'environment'}, {fps:10, qrbox:250},
                (decoded)=>{
                    const sel=document.querySelector('select[name="'+inputName+'"]');
                    if(sel){ if(sel.tomselect) sel.tomselect.setValue(decoded); else { sel.value=decoded; sel.dispatchEvent(new Event('change',{bubbles:true})); } }
                    window.html5QrTunai.stop().then(()=>reader.classList.add('hidden'));
                }, ()=>{}
            ).catch((e)=>{ reader.classList.add('hidden'); alert('Kamera tidak tersedia: '+(e?.message||'')); });
        };
    </script>
    @endpush

    @if($kartu)
    <x-form :model="$model" :action="route('topup.postTunai')">
        <x-card label="Konfirmasi Top Up Tunai">
            <input type="hidden" name="kartu_barcode" value="{{ $kartu->kartu_barcode }}">
            <div class="col-span-12">
                <p class="text-sm">Pengguna: <strong>{{ $kartu->hasUser?->name ?? '-' }}</strong> ({{ $kartu->kartu_nis ?? '-' }} / {{ $kartu->kartu_kelas ?? '-' }})</p>
                <p class="text-sm">Saldo terkini: <strong>Rp{{ number_format((int) $kartu->kartu_saldo, 0, ',', '.') }}</strong></p>
            </div>
            <x-input col="6" type="number" name="nominal" id="nominalTunai" label="Nominal Tunai (Rp)" />
            <div class="col-span-12">
                <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Nominal Cepat</label>
                <div id="nominalCepat" class="flex flex-wrap gap-2" role="group" aria-label="Nominal cepat">
                    @foreach ([100000, 50000, 20000, 10000, 5000, 2000] as $nom)
                    <button type="button" data-nominal="{{ $nom }}"
                        class="inline-flex h-11 min-w-[4.75rem] items-center justify-center rounded-xl border border-outline-variant bg-surface-container px-4 text-sm font-semibold text-on-surface transition hover:border-primary hover:text-primary active:scale-95">
                        {{ number_format($nom / 1000, 0, ',', '.') }}rb
                    </button>
                    @endforeach
                    <button type="button" id="nominalReset"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-outline-variant bg-surface-container px-4 text-sm font-semibold text-error transition hover:border-error active:scale-95">
                        Reset
                    </button>
                </div>
                <p id="nominalTunaiText" class="mt-1 text-xs font-semibold text-primary"></p>
                <p class="text-xs text-on-surface-variant">Klik nominal untuk menambah (mis. 50rb + 20rb + 5rb = 75rb).</p>
            </div>
        </x-card>

        <x-action :model="$model" :action="['save']" />
    </x-form>
    @endif

    <script>
    (function () {
        const input = document.getElementById('nfcTopup');
        const status = document.getElementById('nfcTopupStatus');
        if (!input || !status) return;

        function nfcClean(s) { return (s || '').replace(/^;+|\?+$/g, '').trim(); }
        function nfcUid(s) {
            const h = (s || '').replace(/[^0-9a-fA-F]/g, '');
            return h.length >= 8 ? h.toUpperCase() : null;
        }
        function nfcSame(a, b) {
            const ua = nfcUid(a), ub = nfcUid(b);
            if (ua && ub) return ua === ub;
            return (a || '').toLowerCase() === (b || '').toLowerCase();
        }

        function beep(ok = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.frequency.value = ok ? 880 : 220;
                o.type = 'sine';
                g.gain.setValueAtTime(0.15, ctx.currentTime);
                o.start(); o.stop(ctx.currentTime + (ok ? 0.12 : 0.3));
            } catch (e) {}
        }
        function setStatus(kind, msg) {
            status.className = 'mt-1 text-xs font-semibold ' + (kind === 'ok' ? 'text-success' : kind === 'err' ? 'text-error' : 'text-on-surface-variant');
            status.textContent = msg;
        }

        let busy = false, lastUid = '', lastAt = 0;
        // Pengaman: tap nyasar saat fokus di nominal → alihkan ke alur NFC, jangan jadi nominal.
        // Listener document dilepas-pasang ulang supaya tidak menumpuk tiap wire:navigate.
        if (window.__topupTunaiNfcDoc) {
            document.removeEventListener('keydown', window.__topupTunaiNfcDoc.key, true);
            document.removeEventListener('focusout', window.__topupTunaiNfcDoc.focus);
        }
        let burstAt = 0;
        window.__topupTunaiNfcDoc = {
            key: (e) => {
                const el = document.getElementById('nfcTopup');
                if (e.key.length === 1 && (!document.activeElement || document.activeElement === document.body || document.activeElement === el)) burstAt = burstAt || performance.now();
                if (e.key === 'Enter' && document.activeElement && document.activeElement.name === 'nominal') {
                    const v = (document.activeElement.value || '').trim();
                    if (v.length >= 4 && burstAt && (performance.now() - burstAt) < (v.length * 60 + 200)) {
                        e.preventDefault(); e.stopPropagation();
                        document.activeElement.value = '';
                        burstAt = 0;
                        if (el) { el.value = v; el.focus(); handleTap(); }
                    }
                }
                if (e.key === 'Enter') burstAt = 0;
            },
            focus: () => {
                setTimeout(() => {
                    const el = document.getElementById('nfcTopup');
                    if (el && (!document.activeElement || document.activeElement === document.body)) el.focus({ preventScroll: true });
                }, 400);
            },
        };
        document.addEventListener('keydown', window.__topupTunaiNfcDoc.key, true);
        document.addEventListener('focusout', window.__topupTunaiNfcDoc.focus);

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); handleTap(); }
        });

        async function handleTap() {
            if (busy) return;
            const code = nfcClean(input.value);
            if (code.length < 2) { input.focus(); return; }
            const now = Date.now();
            if (nfcSame(code, lastUid) && now - lastAt < 2000) { setStatus('err', 'Kartu sudah diproses, angkat dulu kartunya.'); beep(false); return; }
            lastUid = code; lastAt = now;
            busy = true;
            setStatus('info', 'Mencari ' + code + ' …');
            try {
                const res = await fetch('{{ route('kartu.getSearch') }}?q=' + encodeURIComponent(code), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const rows = await res.json();
                const pas = rows.find((r) => nfcSame(r.barcode, code));
                if (!pas) { setStatus('err', 'Kartu ' + code + ' tidak terdaftar.'); beep(false); busy = false; input.focus(); input.select(); return; }
                setStatus('ok', pas.nama + ' terbaca — membuka…');
                beep(true);
                const url = new URL('{{ route('topup.tunai') }}', window.location.origin);
                url.searchParams.set('kartu_barcode', pas.barcode);
                window.location.href = url.toString();
            } catch (e) {
                setStatus('err', 'Gagal cari kartu, tempel ulang.');
                beep(false); busy = false; input.focus(); input.select();
            }
        }

        input.focus({ preventScroll: true });
    })();
    </script>

    <script>
    (function () {
        const nominal = document.getElementById('nominalTunai');
        const box = document.getElementById('nominalCepat');
        const teks = document.getElementById('nominalTunaiText');
        if (!nominal || !box) return;

        function sync() {
            const n = parseInt(nominal.value || '0', 10) || 0;
            if (teks) teks.textContent = n > 0 ? 'Total: Rp' + n.toLocaleString('id-ID') : '';
        }

        box.addEventListener('click', (e) => {
            const tombol = e.target.closest('[data-nominal]');
            if (tombol) {
                const tambah = parseInt(tombol.dataset.nominal, 10) || 0;
                nominal.value = String((parseInt(nominal.value || '0', 10) || 0) + tambah);
                nominal.dispatchEvent(new Event('input', { bubbles: true }));
                nominal.dispatchEvent(new Event('change', { bubbles: true }));
                sync();
                return;
            }
            if (e.target.closest('#nominalReset')) {
                nominal.value = '';
                nominal.dispatchEvent(new Event('input', { bubbles: true }));
                sync();
                nominal.focus();
            }
        });
        nominal.addEventListener('input', sync);
        sync();
    })();
    </script>
</x-layouts::app>
