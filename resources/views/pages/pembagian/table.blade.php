<?php /** @var App\Models\Pembagian $table */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url'=>'/dashboard','label'=>'Home'],['url'=>'','label'=>'Pembagian Harian']]" />
    <div class="content mt-4 lg:mt-0">
        @can('bagi', $model)
        <div class="flex gap-2 mb-3">
            <a href="{{ route('pembagian.getBagi') }}" class="inline-flex items-center gap-1 h-9 px-4 text-sm rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">payments</span> Bagi Hari Ini</a>
        </div>
        @endcan
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach($fields as $key=>$label)
                <x-filter-item :label="$label" :name="$key"/>
                @endforeach
                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>
        @php
            $currentSort = request('sort.0','');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp
        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                <x-table-sort field="pembagian_tanggal" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="pembagian_total" label="Total" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="pembagian_bersih" label="Bersih" :sortField="$sortField" :sortDir="$sortDir" />
                <th>Gerai</th>
                <th>Kasir</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    <td>{{ formatDate($table->pembagian_tanggal) }}</td>
                    <td class="font-mono">{{ formatAngka((int)$table->pembagian_total,'Rp') }}</td>
                    <td class="font-mono font-bold">{{ formatAngka((int)$table->pembagian_bersih,'Rp') }}</td>
                    <td>{{ $table->hasGerai?->gerai_nama ?? '-' }}</td>
                    <td>{{ $table->hasKasir?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border rounded-xl p-3 bg-surface-container-lowest">
                        <div class="font-bold text-sm">{{ $table->hasGerai?->gerai_nama ?? '-' }} • {{ formatDate($table->pembagian_tanggal) }}</div>
                        <div class="text-xs font-mono">Total {{ formatAngka((int)$table->pembagian_total,'Rp') }} • Bersih {{ formatAngka((int)$table->pembagian_bersih,'Rp') }}</div>
                        <div class="text-xs">Fee {{ formatAngka((int)$table->pembagian_fee,'Rp') }} • Kasir {{ $table->hasKasir?->name ?? '-' }}</div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>
        </x-table>
        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['delete']" />
    </div>
    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}','{{ $sortDir }}');</script>
</x-layouts::app>
