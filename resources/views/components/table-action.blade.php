@props(['model' => null, 'id' => null, 'print' => null, 'detail' => false])
<td class="w-24 whitespace-nowrap">
    <div class="flex gap-2">
        @can('update', $model ?? null)
        <a href="{{ moduleRoute('getUpdate', ['id' => $id]) }}" wire:navigate title="{{ $detail ? 'Detail' : 'Edit' }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
            <span class="material-symbols-outlined text-lg">{{ $detail ? 'visibility' : 'edit' }}</span>
        </a>
        @endcan
        @can('delete', $model ?? null)
        <a onclick="return confirm('Are you sure you want to delete?')" href="{{ moduleRoute('getDelete', ['id' => $id]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
            <span class="material-symbols-outlined text-lg">delete</span>
        </a>
        @endcan
        @if (! empty($print))
        <a href="{{ $print }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors" aria-label="Cetak barcode">
            <span class="material-symbols-outlined text-lg">qr_code_2</span>
        </a>
        @endif
        {{ $slot }}
    </div>
</td>
