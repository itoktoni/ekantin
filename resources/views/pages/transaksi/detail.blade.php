<?php /** @var App\Models\Transaksi $model */ ?>

@php
    $uang = fn ($nilai) => 'Rp'.number_format((int) $nilai, 0, ',', '.');
    $badgeStatus = match ($model->transaksi_status) {
        'berhasil' => 'success',
        'menunggu' => 'warning',
        'gagal', 'dibatalkan' => 'error',
        default => 'default',
    };
    $totalDipotong = (int) $model->transaksi_total + (int) $feeTotal;
    $geraiUtama = $model->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ')
        ?: ($model->hasGerai?->gerai_nama ?? '-');
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[
        ['url' => '/dashboard', 'label' => 'Home'],
        ['url' => moduleRoute('getTable'), 'label' => moduleLabel()],
        ['url' => '', 'label' => 'Detail #'.$model->transaksi_id],
    ]" />

    <div class="content mt-4 lg:mt-0 pb-28">

        <div class="flex flex-wrap gap-2 mb-3">
            <a href="{{ route('kasir.struk', ['id' => $model->transaksi_id]) }}" target="_blank" class="inline-flex items-center gap-1.5 h-9 px-4 text-sm font-bold rounded-xl bg-primary text-on-primary shadow">
                <span class="material-symbols-outlined text-base">print</span> Reprint Struk 58mm
            </a>
            <a href="{{ route('kasir.struk', ['id' => $model->transaksi_id]) }}" target="_blank" class="inline-flex items-center gap-1.5 h-9 px-4 text-sm rounded-xl border border-outline-variant">Preview</a>
        </div>

        <x-card :label="'Transaksi #'.$model->transaksi_id" icon="receipt_long">
            <div class="col-span-12 flex flex-wrap items-center gap-2">
                <x-badge type="info">{{ $jenis[$model->transaksi_jenis] ?? $model->transaksi_jenis }}</x-badge>
                <x-badge :type="$badgeStatus">{{ $status[$model->transaksi_status] ?? $model->transaksi_status }}</x-badge>
                <x-badge>{{ $model->created_at?->format('d/m/Y H:i') }}</x-badge>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Kasir</p>
                <p class="text-sm text-on-surface">{{ $model->hasKasir?->name ?? '-' }}</p>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Gerai</p>
                <p class="text-sm text-on-surface">{{ $geraiUtama }}</p>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Limit Harian Saat Transaksi</p>
                <p class="text-sm text-on-surface">{{ $model->transaksi_limit_snapshot ? $uang($model->transaksi_limit_snapshot) : 'Tanpa limit' }}</p>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Kode Idempotensi</p>
                <p class="text-sm text-on-surface break-all">{{ $model->transaksi_idempotency ?? '-' }}</p>
            </div>

            @if ($model->transaksi_alasan)
            <div class="col-span-12">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Keterangan</p>
                <p class="text-sm text-on-surface">{{ $model->transaksi_alasan }}</p>
            </div>
            @endif

            @if ($model->transaksi_id_reversal_of)
            <div class="col-span-12">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Transaksi Asal</p>
                <a href="{{ moduleRoute('getUpdate', ['id' => $model->transaksi_id_reversal_of]) }}" wire:navigate class="text-sm font-semibold text-primary hover:underline">
                    Lihat transaksi #{{ $model->transaksi_id_reversal_of }}
                </a>
            </div>
            @endif
        </x-card>

        @if ($model->hasKartu)
        <x-card label="Pembayar — Saldo Kartu" icon="badge">
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Siswa</p>
                <p class="text-sm font-semibold text-on-surface">{{ $model->hasKartu->hasUser?->name ?? '-' }}</p>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Barcode Kartu</p>
                <p class="text-sm font-data-mono text-on-surface">{{ $model->hasKartu->kartu_barcode }}</p>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">NIS / Kelas</p>
                <p class="text-sm text-on-surface">{{ $model->hasKartu->kartu_nis ?? '-' }} / {{ $model->hasKartu->kartu_kelas ?? '-' }}</p>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <p class="text-xs text-on-surface-variant uppercase tracking-wide">Saldo Kartu Setelah Transaksi</p>
                <p class="text-sm font-data-mono font-bold text-primary">{{ $model->transaksi_saldo_akhir === null ? '-' : $uang($model->transaksi_saldo_akhir) }}</p>
            </div>
        </x-card>
        @endif

        @if ($model->hasItems->isNotEmpty())
        <x-card :label="'Rincian Item ('.$model->hasItems->count().')'" icon="shopping_bag">
            <div class="col-span-12">
                <x-table>
                    <x-slot:head>
                        <th>Gerai</th>
                        <th>Item</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Status</th>
                    </x-slot:head>
                    <x-slot:body>
                        @foreach ($model->hasItems as $item)
                        <tr>
                            <td>{{ $item->hasGerai?->gerai_nama ?? '-' }}</td>
                            <td>{{ $item->item_nama }}</td>
                            <td>{{ $uang($item->item_harga) }}</td>
                            <td>{{ formatQty($item->item_qty) }}</td>
                            <td>{{ $uang($item->item_subtotal) }}</td>
                            <td>{{ $item->item_status }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="font-bold">Total Belanja</td>
                            <td class="font-bold">{{ $uang($model->transaksi_total) }}</td>
                            <td></td>
                        </tr>
                    </x-slot:body>
                    <x-slot:mobile>
                        <div class="p-3 space-y-3">
                            @foreach ($model->hasItems as $item)
                            <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                                <p class="text-sm font-bold text-on-surface truncate mb-3">{{ $item->item_qty }}x {{ $item->item_nama }}</p>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Gerai</p>
                                        <p class="text-xs font-medium text-on-surface truncate">{{ $item->hasGerai?->gerai_nama ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                                        <p class="text-xs font-medium text-primary truncate">{{ $item->item_status }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Harga</p>
                                        <p class="text-xs font-mono text-on-surface">{{ $uang($item->item_harga) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Subtotal</p>
                                        <p class="text-xs font-mono font-semibold text-on-surface">{{ $uang($item->item_subtotal) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                                    <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">Total Belanja {{ $uang($model->transaksi_total) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </x-slot:mobile>
                </x-table>
            </div>
        </x-card>
        @endif

        <x-card label="Pembayaran & Fee" icon="payments">
            @if ($model->transaksi_jenis === 'beli')
            <div class="col-span-12 lg:col-span-6 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Total belanja</span>
                    <span class="font-semibold text-on-surface">{{ $uang($model->transaksi_total) }}</span>
                </div>
                @php
                    $feePersen = $feePersen ?? \App\Models\Fee::persenMap();
                    $feeNama = $feeNama ?? \App\Models\Fee::namaMap();
                @endphp
                @foreach ($feeRincian as $kode => $nominal)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Fee {{ $feeNama[$kode] ?? $kode }}@if(isset($feePersen[$kode])) ({{ rtrim(rtrim(number_format((float)$feePersen[$kode],2,',','.'),'0'),',') }}%)@endif</span>
                    <span class="text-on-surface">{{ $uang($nominal) }}</span>
                </div>
                @endforeach
                @if(empty($feeRincian) && $feeTotal > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Fee (riwayat)</span>
                    <span class="text-on-surface">{{ $uang($feeTotal) }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between text-sm border-t border-outline-variant pt-2">
                    <span class="font-semibold text-on-surface">Total fee</span>
                    <span class="font-semibold text-on-surface">{{ $uang($feeTotal) }}</span>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6 space-y-2 rounded-xl bg-surface-container px-4 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Fee ditanggung oleh</span>
                    <span class="font-semibold text-on-surface">Saldo kartu siswa</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Total dipotong dari saldo kartu</span>
                    <span class="font-data-mono font-bold text-on-surface">{{ $uang($totalDipotong) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Saldo kartu setelah transaksi</span>
                    <span class="font-data-mono font-bold text-primary">{{ $uang($model->transaksi_saldo_akhir) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Diteruskan ke gerai (bersih)</span>
                    <span class="font-data-mono font-bold text-on-surface">{{ $uang($model->transaksi_bersih) }}</span>
                </div>
                <p class="text-xs text-on-surface-variant pt-1 border-t border-outline-variant/60">
                    Siswa membayar total belanja ditambah fee penuh; penerimaan gerai dipotong fee sesuai proporsi belanjanya.
                </p>
            </div>
            @elseif ($model->transaksi_jenis === 'refund')
            <div class="col-span-12 lg:col-span-6 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Dana dikembalikan ke saldo kartu</span>
                    <span class="font-data-mono font-semibold text-on-surface">{{ $uang($model->transaksi_total) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Nominal termasuk fee pembelian asal</span>
                    <span class="text-on-surface">Ya</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Saldo kartu setelah refund</span>
                    <span class="font-data-mono font-bold text-primary">{{ $uang($model->transaksi_saldo_akhir) }}</span>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6 space-y-2 rounded-xl bg-surface-container px-4 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Fee ditanggung oleh</span>
                    <span class="font-semibold text-on-surface">Dikembalikan ke saldo kartu siswa</span>
                </div>
                @if ($model->transaksi_id_reversal_of)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Transaksi asal</span>
                    <a href="{{ moduleRoute('getUpdate', ['id' => $model->transaksi_id_reversal_of]) }}" wire:navigate class="font-semibold text-primary hover:underline">#{{ $model->transaksi_id_reversal_of }}</a>
                </div>
                @endif
                <p class="text-xs text-on-surface-variant pt-1 border-t border-outline-variant/60">
                    Refund mengembalikan total belanja beserta seluruh komponen fee ke saldo kartu, dan menarik kembali pembagian bersih dari gerai terkait.
                </p>
            </div>
            @elseif (in_array($model->transaksi_jenis, ['topup_web', 'topup_tunai']))
            <div class="col-span-12 lg:col-span-6 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Nominal top up</span>
                    <span class="font-data-mono font-semibold text-on-surface">{{ $uang($model->transaksi_total) }}</span>
                </div>
                @if ($model->transaksi_saldo_akhir === null)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Saldo kartu</span>
                    <span class="text-on-surface">Belum bertambah — menunggu pembayaran</span>
                </div>
                @else
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Saldo kartu setelah transaksi</span>
                    <span class="font-data-mono font-bold text-primary">{{ $uang($model->transaksi_saldo_akhir) }}</span>
                </div>
                @endif
            </div>

            <div class="col-span-12 lg:col-span-6 space-y-2 rounded-xl bg-surface-container px-4 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Fee ditanggung oleh</span>
                    <span class="font-semibold text-on-surface">Tidak ada fee</span>
                </div>
                <p class="text-xs text-on-surface-variant">
                    Nominal top up masuk penuh ke saldo kartu — tidak ada potongan fee pada transaksi top up.
                </p>
            </div>
            @else
            <div class="col-span-12 lg:col-span-6 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Nominal</span>
                    <span class="font-data-mono font-semibold text-on-surface">{{ $uang($model->transaksi_total) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Diteruskan (bersih)</span>
                    <span class="font-data-mono text-on-surface">{{ $uang($model->transaksi_bersih) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Saldo kartu setelah transaksi</span>
                    <span class="font-data-mono text-on-surface">{{ $model->transaksi_saldo_akhir === null ? '-' : $uang($model->transaksi_saldo_akhir) }}</span>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6 space-y-2 rounded-xl bg-surface-container px-4 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Fee ditanggung oleh</span>
                    <span class="font-semibold text-on-surface">{{ $feeTotal > 0 ? 'Saldo kartu siswa' : 'Tidak ada fee' }}</span>
                </div>
                @if ($feeTotal > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-on-surface-variant">Total fee</span>
                    <span class="font-data-mono font-bold text-on-surface">{{ $uang($feeTotal) }}</span>
                </div>
                @endif
            </div>
            @endif
        </x-card>

        @if (! empty($pembagian))
        <x-card label="Pembagian Dana ke Gerai" icon="store">
            <div class="col-span-12">
                <x-table>
                    <x-slot:head>
                        <th>Gerai</th>
                        <th>Subtotal Belanja</th>
                        <th>Fee Proporsional</th>
                        <th>Diterima Gerai</th>
                    </x-slot:head>
                    <x-slot:body>
                        @foreach ($pembagian as $baris)
                        <tr>
                            <td>{{ $baris['gerai'] }}</td>
                            <td>{{ $uang($baris['subtotal']) }}</td>
                            <td>-{{ $uang($baris['fee']) }}</td>
                            <td class="font-semibold">{{ $uang($baris['bersih']) }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td class="font-bold">Total</td>
                            <td class="font-bold">{{ $uang($model->transaksi_total) }}</td>
                            <td class="font-bold">-{{ $uang($feeTotal) }}</td>
                            <td class="font-bold">{{ $uang($model->transaksi_bersih) }}</td>
                        </tr>
                    </x-slot:body>
                    <x-slot:mobile>
                        <div class="p-3 space-y-3">
                            @foreach ($pembagian as $baris)
                            <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                                <p class="text-sm font-bold text-on-surface truncate mb-3">{{ $baris['gerai'] }}</p>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Subtotal</p>
                                        <p class="text-xs font-mono text-on-surface">{{ $uang($baris['subtotal']) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Fee</p>
                                        <p class="text-xs font-mono text-error">-{{ $uang($baris['fee']) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Diterima</p>
                                        <p class="text-xs font-mono font-semibold text-on-surface">{{ $uang($baris['bersih']) }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </x-slot:mobile>
                </x-table>

                <p class="mt-3 text-xs text-on-surface-variant">
                    Fee dibagi proporsional terhadap subtotal belanja tiap gerai (sisa pembulatan dibebankan ke gerai terakhir).
                </p>
            </div>
        </x-card>
        @endif

        <div class="mt-5">
            <a href="{{ moduleRoute('getTable') }}" wire:navigate class="inline-flex items-center justify-center gap-1 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke daftar transaksi
            </a>
        </div>

    </div>
</x-layouts::app>
