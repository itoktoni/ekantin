<?php /** @var App\Models\Kartu $table */ ?>

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
                @foreach ($model::$sortColumns as $column)
                <x-table-sort field="{{ $column }}" label="{{ formatLabel($column) }}" :sortField="$sortField" :sortDir="$sortDir" />
                @endforeach
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" :print="route('kartu.cetak', ['ids[]' => $table->field_primary])" />
                    <td>
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold truncate max-w-[160px]">{{ $table->hasUser?->name ?? '-' }}</span>
                        </div>
                    </td>
                    @foreach ($model::$sortColumns as $column)
                    <td>
                        @if($column === 'kartu_saldo')
                            {{ formatAngka((int)$table->$column,'Rp') }}
                        @elseif($column === 'kartu_barcode')
                            <span class="font-mono text-xs">{{ $table->$column }}</span>
                        @else
                            {{ $table->$column }}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <p class="text-sm font-bold text-on-surface truncate mb-1">{{ $table->hasUser?->name ?? $table->field_name }}</p>
                        <p class="text-xs font-mono text-on-surface-variant truncate mb-3">{{ $table->kartu_barcode }}</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">NIS</p>
                                <p class="text-xs font-medium text-primary truncate">{{ $table->kartu_nis ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Kelas</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kartu_kelas ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Saldo</p>
                                <p class="text-xs font-mono font-semibold text-on-surface">{{ formatAngka((int)$table->kartu_saldo,'Rp') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kartu_status }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary" :print="route('kartu.cetak', ['ids[]' => $table->field_primary])" />
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>

        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']">
            <button type="button" onclick="cetakBarcode()" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-secondary text-on-secondary hover:bg-secondary/90 shadow-sm transition-all active:scale-95 shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">qr_code_2</span>
                <span class="hidden sm:inline">Cetak Barcode</span>
            </button>
        </x-action>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
    <script>
    function cetakBarcode() {
        const ids = [...document.querySelectorAll('tbody input[type="checkbox"]:checked')].map(c => c.value);
        const mobile = window.mSelected ? Array.from(window.mSelected) : [];
        const all = ids.length ? ids : mobile;
        const base = '{{ route('kartu.cetak') }}';
        if (!all.length) {
            window.location.href = base;
            return;
        }
        window.location.href = base + '?' + all.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
    }
    </script>
</x-layouts::app>
