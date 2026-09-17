<?php /** @var App\Models\Kartu $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Top Up Tunai']]" />

    <x-form :model="$model" :action="route('topup.tunai')" :method="'GET'">
        <x-card label="Cari Kartu — Scan atau Pilih Siswa">
            <x-select col="9" name="kartu_barcode" label="Pilih Siswa / Scan Barcode" :options="['' => '-- Ketik atau Scan --'] + ($kartuOptions ?? [])" class="search" :default="$kartu?->kartu_barcode ?? request('kartu_barcode') ?? request('kartu')" />
            <div class="col-span-3 flex items-end gap-2">
                <button type="button" onclick="startScanTunai('kartu_barcode')" class="inline-flex items-center gap-1 h-10 px-4 text-xs font-semibold rounded-xl border border-outline-variant bg-surface-container"><span class="material-symbols-outlined text-sm">qr_code_scanner</span> Scan</button>
                <x-button variant="primary" class="flex-1" type="submit">Cari</x-button>
            </div>
            <div class="col-span-12">
                <div id="readerTunai" class="hidden mt-2 rounded-xl overflow-hidden border border-outline-variant" style="max-width:320px"></div>
                <p class="text-xs text-on-surface-variant mt-1">Pilih dari dropdown, ketik barcode, atau klik Scan untuk pakai kamera HP.</p>
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
                <p class="text-sm">Siswa: <strong>{{ $kartu->hasUser?->name ?? '-' }}</strong> ({{ $kartu->kartu_nis ?? '-' }} / {{ $kartu->kartu_kelas ?? '-' }})</p>
                <p class="text-sm">Saldo terkini: <strong>Rp{{ number_format((int) $kartu->kartu_saldo, 0, ',', '.') }}</strong></p>
            </div>
            <x-input col="6" type="number" name="nominal" label="Nominal Tunai (Rp)" />
        </x-card>

        <x-action :model="$model" :action="['save']" />
    </x-form>
    @endif
</x-layouts::app>
