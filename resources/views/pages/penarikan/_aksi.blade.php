@php
    // Tombol aksi penukaran per baris: tukar (kasir/admin) + batal (vendor milik/kasir/admin).
    // Hanya untuk pengajuan berstatus Diajukan.
    $rolePenukaran = auth()->user()?->role;
@endphp
@if (($p->penarikan_status ?? null) === 'diajukan')
    @if (in_array($rolePenukaran, ['kasir_sekolah', 'admin', 'super_admin', 'developer']))
    <form method="POST" action="{{ route('penarikan.postTukar', ['id' => $p->penarikan_id]) }}"
        onsubmit="return confirm('Tukar uang {{ formatAngka((int) $p->penarikan_nominal, 'Rp') }} untuk {{ $p->hasGerai?->gerai_nama ?? '-' }} sekarang?')">
        @csrf
        <button type="submit" title="Tukar Uang" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success/10 text-success hover:bg-success/20 transition-colors">
            <span class="material-symbols-outlined text-lg">currency_exchange</span>
        </button>
    </form>
    @endif
    @if (in_array($rolePenukaran, ['vendor', 'kasir_sekolah', 'admin', 'super_admin', 'developer']))
    <form method="POST" action="{{ route('penarikan.postBatal', ['id' => $p->penarikan_id]) }}"
        onsubmit="return confirm('Batalkan pengajuan? saldo gerai akan dikembalikan.')">
        @csrf
        <button type="submit" title="Batalkan Pengajuan" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-warning/10 text-warning hover:bg-warning/20 transition-colors">
            <span class="material-symbols-outlined text-lg">cancel</span>
        </button>
    </form>
    @endif
@endif