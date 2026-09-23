<?php /** @var App\Models\Penarikan $table */ ?>

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
                <th>Gerai</th>
                <th>Tgl</th>
                @foreach ($model::$sortColumns as $column)
                <x-table-sort field="{{ $column }}" label="{{ formatLabel($column) }}" :sortField="$sortField" :sortDir="$sortDir" />
                @endforeach
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        @include('pages.penarikan._aksi', ['p' => $table])
                    </x-table-action>
                    <td class="text-sm">{{ $table->hasGerai?->gerai_nama ?? '-' }}</td>
                    <td class="text-xs font-mono">{{ $table->penarikan_tanggal ? formatDate($table->penarikan_tanggal) : '-' }}</td>
                    @foreach ($model::$sortColumns as $column)
                    <td>
                        @if($column === 'penarikan_nominal')
                            {{ formatAngka((int)$table->$column, 'Rp') }}
                        @elseif($column === 'created_at')
                            {{ formatDate($table->$column, true) }}
                        @else
                            <x-badge :type="$column === 'penarikan_status' ? match($table->$column){'diselesaikan'=>'success','diajukan'=>'warning','default'=>'error'} : 'default'">{{ $table->$column }}</x-badge>
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
                        <p class="text-sm font-bold text-on-surface truncate mb-1">{{ $table->hasGerai?->gerai_nama ?? '-' }}</p>
                        <p class="text-lg font-semibold text-primary mb-3">Rp{{ number_format((int) $table->penarikan_nominal, 0, ',', '.') }}</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                                <p class="text-xs font-medium text-primary truncate">{{ $table->penarikan_status }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tgl</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->penarikan_tanggal ? formatDate($table->penarikan_tanggal) : '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary">
                                    @include('pages.penarikan._aksi', ['p' => $table])
                                </x-table-action>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>

        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']">
            @if (in_array(auth()->user()?->role, ['vendor', 'admin', 'super_admin', 'developer']))
            <a href="{{ route('penarikan.getAjukan') }}" wire:navigate class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-secondary text-on-secondary hover:bg-secondary/90 shadow-sm transition-all active:scale-95 shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">request_quote</span>
                <span class="hidden sm:inline">Ajukan Penagihan</span>
            </a>
            @endif
        </x-action>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
