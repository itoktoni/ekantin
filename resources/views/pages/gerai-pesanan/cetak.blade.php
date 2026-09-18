<?php /** @var App\Models\Transaksi $trx */ ?>
@php
    $feeTotal = $trx->feeTotal();
    $feeRincian = $trx->feeRincian();
    $feePersen = \App\Models\Fee::persenMap();
    $feeNama = \App\Models\Fee::namaMap();
    $totalPotong = (int) $trx->transaksi_total + $feeTotal;
    $namaSiswa = $trx->hasKartu?->hasUser?->name ?? '-';
    $barcodeSiswa = $trx->hasKartu?->kartu_barcode ?? '-';
    $nis = $trx->hasKartu?->kartu_nis ?? null;
    $kelas = $trx->hasKartu?->kartu_kelas ?? null;
    $kasirNama = $trx->hasKasir?->name ?? auth()->user()?->name ?? '-';
    $bluetoothPayload = [
        'id' => $trx->transaksi_id,
        'siswa' => $namaSiswa,
        'barcode' => $barcodeSiswa,
        'tanggal' => formatDate($trx->created_at, 'd/m/Y H:i'),
        'kasir' => $kasirNama,
        'items' => $trx->hasItems->map(function ($i) {
            return ['name' => $i->item_nama.' @'.($i->hasGerai?->gerai_nama ?? '-'), 'qty' => (float) $i->item_qty, 'price' => (int) $i->item_harga];
        })->values()->all(),
        'total' => (int) $trx->transaksi_total,
        'fee' => $feeTotal,
        'bersih' => (int) $trx->transaksi_bersih,
    ];
    $geraiLabel = $kelompok->keys()->implode(', ') ?: '-';
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('gerai.getPesanan'), 'label' => 'Pesanan Gerai'], ['url' => '', 'label' => 'Cetak Ulang #'.$trx->transaksi_id]]" class="no-print" />

    <div class="content mt-4 lg:mt-0 flex flex-col items-center">
        <div class="no-print w-full max-w-[380px] flex flex-wrap gap-2 mb-3">
            <a href="{{ route('gerai.getPesanan') }}" class="inline-flex items-center gap-1 h-8 px-3 text-xs font-semibold rounded-lg border border-outline-variant">← Pesanan</a>
            <button onclick="window.print()" class="inline-flex items-center gap-1 h-8 px-4 text-xs font-bold rounded-lg bg-primary text-on-primary">Cetak 58mm</button>
            <button id="btnBluetooth" class="inline-flex items-center gap-1 h-8 px-3 text-xs rounded-lg border">Bluetooth</button>
            <span class="inline-flex items-center h-8 px-3 text-[10px] rounded-lg bg-amber-100 text-amber-800 border border-amber-200">Reprint Gerai: {{ $geraiLabel }}</span>
        </div>

        <div class="receipt-wrap">
        <div class="receipt-58mm bg-white text-black">
            <div class="r-head">
                <div class="r-logo">E-KANTIN SEKOLAH</div>
                <div class="r-sub">Kantin Digital • Cashless — REPRINT GERAI</div>
                <div class="r-addr">Jl. Pendidikan No.1 — (021) 000-0000</div>
            </div>
            <div class="r-line">--------------------------------</div>
            <div class="r-meta">
                <div><span>No</span><b>#{{ str_pad($trx->transaksi_id,6,'0',STR_PAD_LEFT) }} • REPRINT</b></div>
                <div><span>Tgl</span><b>{{ formatDate($trx->created_at,'d/m/y H:i') }}</b></div>
                <div><span>Kasir</span><b class="r-trunc">{{ $kasirNama }}</b></div>
                <div><span>Gerai</span><b class="r-trunc">{{ $geraiLabel }}</b></div>
                @if($trx->transaksi_idempotency)
                <div><span>Ref</span><b class="r-mono r-trunc2">{{ $trx->transaksi_idempotency }}</b></div>
                @endif
            </div>
            <div class="r-line">--------------------------------</div>
            <div class="r-siswa">
                <div class="r-siswa-name">{{ $namaSiswa }}</div>
                <div class="r-siswa-meta">{{ $barcodeSiswa }}@if($nis) • {{ $nis }}@endif @if($kelas) • {{ $kelas }}@endif</div>
                @if($trx->hasKartu)
                <div class="r-barcode">
                    @php try{ if(class_exists(\Milon\Barcode\Facades\DNS1D::class)) echo \Milon\Barcode\Facades\DNS1D::getBarcodeHTML($barcodeSiswa,'C128',1,16); }catch(Throwable $e){} @endphp
                </div>
                @endif
            </div>
            <div class="r-line">--------------------------------</div>

            @foreach($kelompok as $namaGerai => $items)
            <div class="r-gerai">▶ {{ strtoupper($namaGerai) }} — {{ $items->count() }} item</div>
            <div class="r-items">
                <div class="r-items-head"><span>ITEM</span><span>QTY×HARGA</span><span>SUB</span></div>
                @foreach($items as $i)
                <div class="r-item">
                    <div class="r-item-name">{{ $i->item_nama }} — <span class="text-black/60">{{ $i->item_status }}</span></div>
                    <div class="r-item-row">
                        <span class="r-qty">{{ formatQty($i->item_qty) }}×{{ formatAngka((int)$i->item_harga) }}</span>
                        <span class="r-subt">{{ formatAngka((int)$i->item_subtotal,'Rp') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @if(!$loop->last)<div class="r-line light">- - - - - - - - - - - - - - - -</div>@endif
            @endforeach
            <div class="r-line">--------------------------------</div>

            <div class="r-sum">
                <div><span>Subtotal (semua gerai)</span><span>{{ formatAngka((int)$trx->transaksi_total,'Rp') }}</span></div>
                <div><span>Subtotal gerai ini</span><span>{{ formatAngka((int)$trx->hasItems->sum('item_subtotal'),'Rp') }}</span></div>
                @if($feeTotal>0)
                @foreach($feeRincian as $kode => $nominal)
                <div class="r-fee"><span>Fee {{ $feeNama[$kode] ?? $kode }}@if(isset($feePersen[$kode])) ({{ rtrim(rtrim(number_format((float)$feePersen[$kode],2,',','.'),'0'),',') }}%)@endif</span><span>{{ formatAngka((int)$nominal,'Rp') }}</span></div>
                @endforeach
                <div class="r-sum-total"><span>Total fee (transaksi)</span><span>{{ formatAngka($feeTotal,'Rp') }}</span></div>
                <div class="r-sum-pay"><span>POTONG SALDO</span><span>{{ formatAngka($totalPotong,'Rp') }}</span></div>
                @endif
                <div class="r-saldo"><span>Saldo akhir</span><span>{{ $trx->transaksi_saldo_akhir!==null ? formatAngka((int)$trx->transaksi_saldo_akhir,'Rp') : '-' }}</span></div>
            </div>

            <div class="r-line">--------------------------------</div>
            <div class="r-lunas"><span>CETAK ULANG GERAI</span></div>
            <div class="r-qr">
                @php $qrValue=$trx->transaksi_idempotency ?? ('TRX-'.$trx->transaksi_id); try{ if(class_exists(\Milon\Barcode\Facades\DNS2D::class)) echo \Milon\Barcode\Facades\DNS2D::getBarcodeHTML($qrValue,'QRCODE',1.4,1.4); }catch(Throwable $e){} @endphp
                <div class="r-qr-text">{{ $qrValue }} • {{ $geraiLabel }}</div>
            </div>
            <div class="r-line">--------------------------------</div>
            <div class="r-foot">
                <div>Reprint — jika struk hilang, tunjukkan ke gerai</div>
                <div class="r-foot-mono">cetak ulang {{ formatDate(now(),'d/m/y H:i:s') }}</div>
                <div class="r-foot-small">ECS 58mm • 2 gerai terpisah</div>
            </div>
            <div class="r-cut">✄ - - - - - - - - - - - - - - - - - -</div>
        </div>
        </div>
    </div>

    @push('scripts')
    <x-printer-js />
    <script>
        document.getElementById('btnBluetooth')?.addEventListener('click', () => {
            const trx=@json($bluetoothPayload);
            const lines=[];
            lines.push({text:'E-KANTIN GERAI REPRINT',style:'large',align:'center'});
            lines.push({divider:true});
            lines.push({text:'No #'+String(trx.id).padStart(6,'0')+'  '+trx.tanggal,style:'normal'});
            lines.push({text:'Siswa: '+trx.siswa+' ('+trx.barcode+')',style:'bold'});
            lines.push({divider:true});
            trx.items.forEach(it=>{lines.push({text:it.name,style:'normal'});lines.push({text:'  '+it.qty+' x '+new Intl.NumberFormat('id-ID').format(it.price)+' = '+new Intl.NumberFormat('id-ID').format(it.qty*it.price),style:'normal'});});
            lines.push({divider:true});
            lines.push({text:'Subtotal: Rp'+new Intl.NumberFormat('id-ID').format(trx.total),style:'normal'});
            if(trx.fee) lines.push({text:'Fee: Rp'+new Intl.NumberFormat('id-ID').format(trx.fee),style:'normal'});
            lines.push({text:'TOTAL POTONG: Rp'+new Intl.NumberFormat('id-ID').format(trx.total+trx.fee),style:'bold'});
            lines.push({text:'REPRINT GERAI',style:'center'});
            if(window.BluetoothPrinter&&BluetoothPrinter.isNative()) BluetoothPrinter.printReceipt(lines,{paper_width:58,cut:true}); else window.print();
        });
    </script>
    @endpush

    <style>
        .receipt-wrap{display:flex;justify-content:center;width:100%;min-height:200px}
        .receipt-58mm{width:58mm;max-width:58mm;min-height:80mm;padding:4mm 3.5mm 5mm;background:#fff;color:#000;font-family:'Consolas','Courier New',monospace;line-height:1.35;border:1px solid #e5e7eb}
        .r-head{text-align:center;line-height:1.3}
        .r-logo{font-size:14px;font-weight:900;letter-spacing:0.12em}
        .r-sub{font-size:10px;font-weight:700;letter-spacing:0.06em;margin-top:1px}
        .r-addr{font-size:9px;color:#444;margin-top:1px}
        .r-line{font-size:10px;letter-spacing:0.02em;color:#000;text-align:center;margin:2.2mm 0;overflow:hidden;white-space:nowrap}
        .r-line.light{color:#888}
        .r-meta{font-size:10px;line-height:1.6}
        .r-meta div{display:flex;justify-content:space-between;gap:3mm}
        .r-meta span{color:#666;min-width:10mm}
        .r-meta b{font-weight:700;font-size:10px;text-align:right}
        .r-mono{font-family:monospace;font-size:9px}
        .r-trunc{max-width:30mm;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block}
        .r-trunc2{max-width:34mm;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block}
        .r-siswa{text-align:center;margin:0}
        .r-siswa-name{font-size:14px;font-weight:900;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .r-siswa-meta{font-size:9px;color:#444;margin-top:1px}
        .r-barcode{margin:1.5mm 0 0;display:flex;justify-content:center}
        .r-barcode svg{max-width:48mm !important;height:14mm !important}
        .r-gerai{font-size:9px;font-weight:800;background:#f0f0f0;padding:1mm 1.5mm;margin:1.5mm -1.5mm 1.2mm;letter-spacing:0.04em}
        .r-items{font-size:10px}
        .r-items-head{display:flex;justify-content:space-between;font-size:8.5px;color:#777;border-bottom:0.2mm solid #ddd;padding-bottom:0.8mm;margin-bottom:1mm;letter-spacing:0.06em}
        .r-items-head span:nth-child(2){margin-left:auto;margin-right:4mm}
        .r-item{margin-bottom:1.2mm}
        .r-item-name{font-size:10.5px;font-weight:700;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .r-item-row{display:flex;justify-content:space-between;font-size:10px;color:#333}
        .r-qty{color:#555}
        .r-subt{font-weight:700}
        .r-sum{font-size:10px;line-height:1.6}
        .r-sum div{display:flex;justify-content:space-between}
        .r-fee{color:#666;font-size:9px}
        .r-sum-total{font-weight:700;border-top:0.2mm dashed #aaa;margin-top:1mm;padding-top:0.8mm}
        .r-sum-pay{font-size:12px;font-weight:900;background:#000;color:#fff;padding:1mm 1.5mm;margin:1.2mm -1.5mm}
        .r-saldo{font-weight:800;border-top:0.2mm solid #ddd;margin-top:1.2mm;padding-top:0.9mm}
        .r-lunas{text-align:center;margin:1.5mm 0 0}
        .r-lunas span{border:0.35mm solid #000;padding:0.8mm 4mm;font-size:10px;font-weight:900;letter-spacing:0.2em;display:inline-block}
        .r-status{text-align:center;font-size:9px;color:#c00;font-weight:700;margin-top:1.2mm}
        .r-qr{text-align:center;margin:1.5mm 0 0}
        .r-qr svg, .r-qr img{max-width:28mm !important}
        .r-qr-text{font-size:8px;color:#666;word-break:break-all;margin-top:0.8mm}
        .r-foot{text-align:center;font-size:9px;line-height:1.4;color:#555}
        .r-foot-mono{font-family:monospace;font-size:8.5px}
        .r-foot-small{font-size:8px;color:#888;margin-top:0.6mm}
        .r-cut{text-align:center;font-size:9px;color:#888;margin-top:2mm;letter-spacing:0.15em}
        @media screen{.receipt-wrap{padding:16px;background:#e5e7eb;border-radius:10px;min-height:300px}.receipt-58mm{box-shadow:0 4px 20px rgba(0,0,0,.18);border-radius:3px}}
        @media print{
            @page{size:58mm auto;margin:0}
            html,body{background:#fff!important;margin:0!important;padding:0!important}
            header,nav,aside,.no-print,x-toast{display:none!important}
            main{margin:0!important;padding:0!important}
            .content{margin:0!important;padding:0!important}
            .receipt-wrap{padding:0!important;background:none!important;min-height:auto!important}
            .receipt-58mm{box-shadow:none!important;border:none!important;width:48mm!important;max-width:48mm!important;padding:1.8mm 1.8mm 2.2mm!important;margin:0 auto!important;font-size:6px!important}
            .receipt-58mm .r-logo{font-size:8px!important}
            .receipt-58mm .r-sub{font-size:6px!important}
            .receipt-58mm .r-addr{font-size:5.5px!important}
            .receipt-58mm .r-line{font-size:6px!important;margin:1.2mm 0!important}
            .receipt-58mm .r-meta,.receipt-58mm .r-meta b{font-size:6px!important}
            .receipt-58mm .r-siswa-name{font-size:9px!important}
            .receipt-58mm .r-siswa-meta{font-size:5.5px!important}
            .receipt-58mm .r-barcode svg{max-width:44mm!important;height:11mm!important}
            .receipt-58mm .r-gerai{font-size:5.5px!important}
            .receipt-58mm .r-items,.receipt-58mm .r-item-name{font-size:6px!important}
            .receipt-58mm .r-item-name{font-size:6.5px!important}
            .receipt-58mm .r-item-row{font-size:6px!important}
            .receipt-58mm .r-sum{font-size:6px!important}
            .receipt-58mm .r-sum-pay{font-size:7.5px!important}
            .receipt-58mm .r-qr svg{max-width:22mm!important}
            .receipt-58mm .r-foot{font-size:5.5px!important}
        }
    </style>
</x-layouts::app>
