<?php /** @var \Illuminate\Support\Collection $kartus */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url'=>'/dashboard','label'=>'Home'],['url'=>route('kartu.getAnak'),'label'=>'Anak Saya'],['url'=>'','label'=>'Batasan Jajan']]" />
    <div class="content mt-4 lg:mt-0">
        <p class="text-xs text-on-surface-variant mb-3">Contoh: anak alergi / sedang sakit (flu, batuk) — centang label <strong>tidak sehat</strong> sebagai dilarang, atau blokir produk tertentu. Kasir otomatis menolak saat scan kartu.</p>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($kartus as $k)
            <x-card :label="($k->hasUser?->name ?? $k->kartu_barcode) . ' — Batasan'" icon="tune">
                <form action="{{ route('kartu.postBatas') }}" method="POST" class="col-span-12 grid grid-cols-12 gap-3">
                    @csrf
                    <input type="hidden" name="kartu_id" value="{{ $k->kartu_id }}" />

                    <div class="col-span-6">
                        <label class="text-xs font-semibold uppercase text-on-surface-variant">Limit Harian (Rp)</label>
                        <input type="number" name="kartu_limit_harian" value="{{ old('kartu_limit_harian', $k->kartu_limit_harian) }}" min="0" placeholder="Tanpa limit"
                            class="mt-1 h-11 w-full rounded-lg border border-outline-variant bg-white px-3 text-sm outline-none focus:border-primary" />
                    </div>
                    <div class="col-span-6">
                        <label class="text-xs font-semibold uppercase text-on-surface-variant">Catatan Kesehatan</label>
                        <input type="text" name="kartu_catatan_kesehatan" value="{{ old('kartu_catatan_kesehatan', $k->kartu_catatan_kesehatan) }}" placeholder="Mis. alergi kacang, sedang flu"
                            class="mt-1 h-11 w-full rounded-lg border border-outline-variant bg-white px-3 text-sm outline-none focus:border-primary" />
                    </div>

                    <div class="col-span-12">
                        <p class="text-xs font-semibold uppercase text-on-surface-variant mb-1">Label Dilarang</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($labelOptions as $nilai => $nama)
                            <label class="chip cursor-pointer">
                                <input type="checkbox" name="kartu_label_dilarang[]" value="{{ $nilai }}" class="sr-only"
                                    @checked(in_array($nilai, (array) old('kartu_label_dilarang', $k->labelDilarang()))) />
                                {{ $nama }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-span-12">
                        <p class="text-xs font-semibold uppercase text-on-surface-variant mb-1">Produk Spesifik Dilarang</p>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-outline-variant p-2 space-y-1">
                            @foreach($kelompok as $namaGerai => $items)
                            <p class="text-[11px] font-bold text-on-surface-variant mt-1">{{ $namaGerai }}</p>
                            @foreach($items as $p)
                            <label class="flex items-center gap-2 text-sm rounded-lg px-2 py-1 hover:bg-surface-container cursor-pointer">
                                <input type="checkbox" name="kartu_produk_dilarang[]" value="{{ $p->produk_id }}" data-produk="{{ $p->produk_label }}"
                                    @checked(in_array((int) $p->produk_id, (array) old('kartu_produk_dilarang', $k->produkDilarang()))) />
                                <span class="flex-1 truncate">{{ $p->produk_nama }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-surface-container border">{{ $p->produk_label }}</span>
                            </label>
                            @endforeach
                            @endforeach
                        </div>
                    </div>

                    <div class="col-span-12">
                        <button class="h-11 px-4 rounded-lg bg-primary text-on-primary text-sm font-bold w-full">Simpan Batasan</button>
                    </div>
                </form>
            </x-card>
            @endforeach
        </div>
    </div>

    <script>
    (function () {
        // Chip aktif dibaca dari aria-pressed (lihat app.css) — sinkronkan dari checkbox.
        // Pilih label (mis. Tidak Sehat) = otomatis centang semua produk berlabel sama di form anak ini.
        document.querySelectorAll('label.chip input[type="checkbox"]').forEach(function (box) {
            var chip = box.closest('label.chip');
            var form = box.closest('form');
            if (!chip || !form) return;
            var sync = function () { chip.setAttribute('aria-pressed', String(box.checked)); };
            var terapkan = function () {
                form.querySelectorAll('input[name="kartu_produk_dilarang[]"][data-produk="' + box.value + '"]').forEach(function (p) {
                    p.checked = box.checked;
                });
            };
            box.addEventListener('change', function () { sync(); terapkan(); });
            sync();
            if (box.checked) terapkan();
        });
    })();
    </script>
</x-layouts::app>
