<?php /** @var App\Models\Transaksi $table */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 lg:mt-0">
        {{-- Filters --}}
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach

                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                <th>Siswa</th>
                <x-table-sort field="transaksi_jenis" label="Jenis" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="transaksi_status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="transaksi_total" label="Total" :sortField="$sortField" :sortDir="$sortDir" />
                <th>Fee</th>
                <th>Bersih</th>
                <x-table-sort field="created_at" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                @php
                    $feeTotal = $table->feeTotal();
                    $namaSiswa = $table->hasKartu?->hasUser?->name ?? $table->hasKartu?->kartu_nis ?? '-';
                @endphp
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" :detail="true">
                        <a href="{{ route('kasir.struk', ['id' => $table->field_primary]) }}" target="_blank" title="Reprint Struk 58mm" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors">
                            <span class="material-symbols-outlined text-lg">print</span>
                        </a>
                    </x-table-action>
                    <td>
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-on-surface truncate max-w-[160px]">{{ $namaSiswa }}</span>
                            @if($table->hasKartu)
                            <span class="text-[11px] text-on-surface-variant font-mono">{{ $table->hasKartu->kartu_barcode }} @if($table->hasKartu->kartu_nis) • {{ $table->hasKartu->kartu_nis }} @endif</span>
                            @endif
                        </div>
                    </td>
                    <td><x-badge type="info">{{ $table->transaksi_jenis }}</x-badge></td>
                    <td><x-badge :type="match($table->transaksi_status){'berhasil'=>'success','menunggu'=>'warning','gagal','dibatalkan'=>'error',default=>'default'}">{{ $table->transaksi_status }}</x-badge></td>
                    <td class="font-mono font-semibold">{{ formatAngka((int) $table->transaksi_total, 'Rp') }}</td>
                    <td class="font-mono text-on-surface-variant">{{ $feeTotal > 0 ? formatAngka($feeTotal, 'Rp') : '-' }}</td>
                    <td class="font-mono">{{ formatAngka((int) $table->transaksi_bersih, 'Rp') }}</td>
                    <td class="font-mono text-xs">{{ formatDate($table->created_at, true) }}</td>
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    @php
                        $feeTotalM = $table->feeTotal();
                        $namaSiswaM = $table->hasKartu?->hasUser?->name ?? $table->hasKartu?->kartu_nis ?? '-';
                    @endphp
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm hover:border-primary/40 cursor-pointer" data-id="{{ $table->field_primary }}" onclick="window.location='{{ moduleRoute('getUpdate', ['id' => $table->field_primary]) }}'">
                        <p class="text-sm font-bold text-on-surface truncate mb-1">{{ $namaSiswaM }}</p>
                        <p class="text-xs font-mono text-on-surface-variant truncate mb-3">{{ $table->transaksi_jenis }} • {{ formatAngka((int) $table->transaksi_total, 'Rp') }} @if($feeTotalM>0) <span class="text-error">-{{ formatAngka($feeTotalM, 'Rp') }} fee</span> @endif</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                                <p class="text-xs font-medium text-primary truncate">{{ $table->transaksi_status }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-on-surface">{{ formatDate($table->created_at, true) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Total</p>
                                <p class="text-xs font-mono font-semibold text-on-surface">{{ formatAngka((int) $table->transaksi_total, 'Rp') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Bersih</p>
                                <p class="text-xs font-mono text-on-surface">{{ formatAngka((int) $table->transaksi_bersih, 'Rp') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }} • {{ $table->hasKartu?->kartu_barcode ?? '-' }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary" :detail="true">
                                    <a href="{{ route('kasir.struk', ['id' => $table->field_primary]) }}" target="_blank" title="Reprint" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-secondary/10 text-secondary">
                                        <span class="material-symbols-outlined text-lg">print</span>
                                    </a>
                                </x-table-action>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>

        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['delete']"/>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
