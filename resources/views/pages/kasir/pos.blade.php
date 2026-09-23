<?php /** @var App\Models\Transaksi $model */ ?>

@php
    $ikonKategori = [
        'makanan' => 'restaurant',
        'minuman' => 'local_cafe',
        'snack' => 'cookie',
    ];

    $semuaProduk = $kelompok->flatten(1);
    $totalProduk = $semuaProduk->count();
    $adaKategori = $semuaProduk->pluck('produk_kategori')->filter()->unique()->all();

    $geraiList = $kelompok->map(fn ($items) => [
        'id' => (int) $items->first()->produk_id_gerai,
        'nama' => $items->first()->hasGerai?->gerai_nama ?? '-',
        'jumlah' => $items->count(),
    ])->values();
@endphp

<x-layouts::app>
    <div class="mx-auto max-w-6xl pb-36 md:pb-32">
        <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Kasir POS']]" />

        <form id="posForm" action="{{ route('kasir.postPos') }}" method="POST">
            @csrf
            <input type="hidden" name="idempotency" value="{{ $idempotency }}">
            <input type="hidden" name="metode" id="metodeInput" value="kartu">
            <div class="mb-2.5 flex gap-1.5" role="group" aria-label="Mode pembeli">
                <button type="button" id="modeKartu" class="chip" aria-pressed="true">
                    <span class="material-symbols-outlined">badge</span> Kartu Pembayaran
                </button>
                <button type="button" id="modeWalkin" class="chip" aria-pressed="false">
                    <span class="material-symbols-outlined">person</span> Walk-in
                </button>
            </div>

            {{-- ① Kartu pengguna + cari produk --}}
            <div class="grid gap-2.5 md:grid-cols-2 mt-4">
                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-3">
                    <label for="kartu_barcode" class="flex items-center gap-1.5 text-[13px] font-bold text-on-surface">
                        <span class="material-symbols-outlined text-[18px] text-primary">barcode_scanner</span>
                        Scan kartu
                    </label>
                    <input id="kartu_barcode" type="text" name="kartu_barcode" required autofocus autocomplete="off"
                        enterkeyhint="next" placeholder="SW-1001"
                        class="mt-2 h-12 w-full rounded-lg border border-outline-variant bg-white px-3 font-data-mono text-base text-on-surface outline-none placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary">
                    <div class="mt-2 flex gap-1.5">
                        <button type="button" id="cariKartuBtn" class="inline-flex h-11 items-center gap-1 rounded-lg border border-outline-variant px-3 text-[13px] font-semibold text-on-surface hover:border-primary hover:text-primary">
                            <span class="material-symbols-outlined text-[18px]">person_search</span> Cari Pengguna
                        </button>
                        <span id="kartuTerpilih" class="hidden min-w-0 flex-1 truncate self-center text-[13px] font-semibold text-primary"></span>
                    </div>
                    <div id="hasilKartu" class="mt-2 hidden max-h-56 space-y-1 overflow-y-auto"></div>
                    <div id="walkinBox" class="mt-2 hidden gap-1.5" role="group" aria-label="Metode bayar walk-in">
                        <label class="chip cursor-pointer"><input type="radio" name="metode_bayar" value="tunai" class="sr-only"> Tunai</label>
                        <label class="chip cursor-pointer"><input type="radio" name="metode_bayar" value="qris" class="sr-only"> QRIS</label>
                    </div>
                    @error('kartu_barcode')
                    <p class="mt-1.5 flex items-center gap-1 text-xs text-error">
                        <span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}
                    </p>
                    @enderror
                </div>

                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-3">
                    <label for="cariProduk" class="flex items-center gap-1.5 text-[13px] font-bold text-on-surface">
                        <span class="material-symbols-outlined text-[18px] text-primary">search</span>
                        Cari produk
                    </label>
                    <input id="cariProduk" type="search" autocomplete="off" placeholder="Nama produk atau kantin"
                        class="mt-2 h-12 w-full rounded-lg border border-outline-variant bg-white px-3 text-base text-on-surface outline-none placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
            </div>

            @if ($totalProduk === 0)
            {{-- ② Keadaan kosong: tidak ada produk untuk dijual --}}
            <div class="mt-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center">
                <span class="material-symbols-outlined text-[40px] text-on-surface-variant/40">no_food</span>
                @if ($cari)
                <p class="mt-2 text-sm font-semibold text-on-surface">Tidak ada produk untuk "{{ $cari }}"</p>
                <a href="{{ route('kasir.pos') }}" class="mt-4 inline-flex h-11 items-center rounded-lg border border-outline-variant px-4 text-[13px] font-semibold text-on-surface hover:border-primary hover:text-primary">
                    Tampilkan semua produk
                </a>
                @else
                <p class="mt-2 text-sm font-semibold text-on-surface">Belum ada produk tersedia</p>
                <p class="mt-1 text-[13px] text-on-surface-variant">
                    Produk muncul di sini setelah ditambahkan dan stoknya berstatus tersedia, di kantin yang berstatus buka.
                </p>
                @endif
            </div>
            @else
            {{-- ③ Rail filter: kategori & kantin, bisa di-scroll ke kanan --}}
            <div class="sticky top-16 z-30 -mx-4 mt-3 border-b border-outline-variant bg-surface/95 px-4 py-2 backdrop-blur-sm md:-mx-6 md:px-6">
                <div class="no-scrollbar flex gap-1.5 overflow-x-auto" role="group" aria-label="Filter kategori produk">
                    <button type="button" class="chip" data-filter-kategori="semua" aria-pressed="true">
                        <span class="material-symbols-outlined">apps</span> Semua
                    </button>
                    @foreach ($kategori as $nilai => $label)
                        @if (in_array($nilai, $adaKategori, true))
                        <button type="button" class="chip" data-filter-kategori="{{ $nilai }}" aria-pressed="false">
                            <span class="material-symbols-outlined">{{ $ikonKategori[$nilai] ?? 'fastfood' }}</span> {{ $label }}
                        </button>
                        @endif
                    @endforeach
                </div>

                @if ($geraiList->count() > 1)
                <div class="no-scrollbar mt-1.5 flex gap-1.5 overflow-x-auto" role="group" aria-label="Filter kantin">
                    <button type="button" class="chip" data-filter-gerai="semua" aria-pressed="true">
                        <span class="material-symbols-outlined">storefront</span> Semua kantin
                    </button>
                    @foreach ($geraiList as $g)
                    <button type="button" class="chip" data-filter-gerai="{{ $g['id'] }}" aria-pressed="false">
                        {{ $g['nama'] }}
                        <span class="chip-count">{{ $g['jumlah'] }}</span>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            <p id="hasilInfo" aria-live="polite" class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant">
                {{ $totalProduk }} produk · {{ $geraiList->count() }} kantin
            </p>

            {{-- ④ Kanvas produk, dikelompokkan per kantin --}}
            @foreach ($kelompok as $namaGerai => $items)
            <section data-gerai-section="{{ $items->first()->produk_id_gerai }}" class="mt-5">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-1.5 text-sm font-bold text-on-surface">
                        <span class="material-symbols-outlined text-[18px] text-primary">storefront</span>
                        {{ $namaGerai }}
                    </h3>
                    <span class="hidden rounded-full bg-surface-container px-2 py-0.5 text-[11px] font-semibold text-on-surface-variant" data-gerai-count></span>
                </div>

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($items as $p)
                    @php $ikon = $ikonKategori[$p->produk_kategori] ?? 'fastfood'; @endphp
                    <div class="pos-card group flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest"
                        data-card="{{ $p->produk_id }}"
                        data-harga="{{ (int) $p->produk_harga }}"
                        data-kategori="{{ $p->produk_kategori }}"
                        data-gerai="{{ $p->produk_id_gerai }}"
                        data-cari="{{ Str::lower($p->produk_nama.' '.$namaGerai) }}">

                        <input type="hidden" name="items[{{ $p->produk_id }}][produk_id]" value="{{ $p->produk_id }}">

                        {{-- Ketuk untuk menambah 1 --}}
                        <button type="button" data-add class="flex flex-1 flex-col text-left focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary focus-visible:outline-none"
                            aria-label="Tambah {{ $p->produk_nama }} ke keranjang">
                            <span class="relative block aspect-[16/10] w-full overflow-hidden bg-surface-container">
                                @if ($p->produk_foto_url)
                                <img src="{{ $p->produk_foto_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.03]">
                                @else
                                <span class="flex h-full w-full items-center justify-center text-on-surface-variant/30">
                                    <span class="material-symbols-outlined text-[40px]">{{ $ikon }}</span>
                                </span>
                                @endif
                                <span class="absolute top-1.5 left-1.5 inline-flex items-center gap-1 rounded-full bg-surface-container-lowest/90 px-2 py-0.5 text-[10px] font-semibold text-on-surface-variant backdrop-blur-sm">
                                    <span class="material-symbols-outlined text-[13px]">{{ $ikon }}</span>
                                    {{ $p->produk_kategori }}
                                </span>
                            </span>

                            <span class="block px-2.5 pt-2 pb-1">
                                <span class="block truncate text-[13px] font-semibold text-on-surface">{{ $p->produk_nama }}</span>
                                <span class="mt-0.5 block font-data-mono text-[15px] font-bold text-primary">Rp{{ number_format((int) $p->produk_harga, 0, ',', '.') }}</span>
                            </span>
                        </button>

                        {{-- Stepper jumlah --}}
                        <div class="flex items-center gap-1 px-2.5 pb-2.5">
                            <button type="button" data-minus
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-outline-variant text-lg font-bold text-on-surface-variant hover:border-primary hover:text-primary"
                                aria-label="Kurangi {{ $p->produk_nama }}">−</button>
                            <input type="number" name="items[{{ $p->produk_id }}][qty]" value="0" min="0" inputmode="numeric" data-qty
                                class="h-11 w-full min-w-0 rounded-lg border border-outline-variant bg-surface-container-lowest text-center font-data-mono text-[15px] font-bold text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                                aria-label="Jumlah {{ $p->produk_nama }}">
                            <button type="button" data-plus
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary text-lg font-bold text-on-primary hover:bg-primary-container"
                                aria-label="Tambah {{ $p->produk_nama }}">+</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach

            {{-- ⑤ Tidak ada hasil filter --}}
            <div id="noMatch" class="hidden mt-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center">
                <span class="material-symbols-outlined text-[40px] text-on-surface-variant/40">search_off</span>
                <p class="mt-2 text-sm font-semibold text-on-surface">Tidak ada produk yang cocok</p>
                <p class="mt-1 text-[13px] text-on-surface-variant">Ubah kata kunci, atau pilih kategori dan kantin lain.</p>
                <button type="button" id="resetFilter"
                    class="mt-4 inline-flex h-11 items-center rounded-lg border border-outline-variant px-4 text-[13px] font-semibold text-on-surface hover:border-primary hover:text-primary">
                    Reset filter
                </button>
            </div>
            @endif
        </form>
    </div>

    @if(isset($fees) && $fees->isNotEmpty())
    <div class="mt-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-3">
        <p class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Fee penjualan (otomatis dari subtotal)</p>
        <div class="mt-1.5 flex flex-wrap gap-1.5">
            @foreach($fees as $f)
            <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-1 text-[11px] font-semibold text-on-surface">Fee {{ $f->nama_fee }} ({{ rtrim(rtrim(number_format((float)$f->value_fee,2,',','.'),'0'),',') }}%)</span>
            @endforeach
        </div>
        <p class="mt-1.5 text-[11px] text-on-surface-variant">Nominal dihitung saat bayar: subtotal × persen, dibulatkan per jenis. Total fee + subtotal = potong saldo.</p>
    </div>
    @endif

    {{-- ⑥ Dock keranjang --}}
    <div class="fixed inset-x-0 bottom-16 z-40 border-t border-outline-variant bg-surface-container-lowest shadow-[0_-4px_12px_rgba(0,0,0,0.08)] md:bottom-0 md:left-72"
        style="padding-bottom: env(safe-area-inset-bottom)">
        <div class="mx-auto flex max-w-6xl items-center gap-2 px-3 py-2.5 md:px-4">
            <button type="button" id="resetCart"
                class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container"
                aria-label="Kosongkan keranjang">
                <span class="material-symbols-outlined">restart_alt</span>
            </button>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] leading-none text-on-surface-variant">
                    <span id="cartCount" class="font-bold text-on-surface">0</span> item ·
                    <span id="cartKantin" class="font-bold text-on-surface">0</span> kantin
                </p>
                <p id="cartTotal" class="mt-1 font-data-mono text-lg font-bold leading-none text-on-surface">Rp0</p>
                <p id="cartFee" class="mt-0.5 text-[11px] font-mono text-on-surface-variant">Fee Rp0 • Potong Rp0</p>
            </div>
            <button type="submit" form="posForm" id="bayarBtn" disabled
                class="inline-flex h-12 shrink-0 items-center gap-2 rounded-xl bg-primary px-6 text-sm font-bold text-on-primary hover:bg-primary-container disabled:opacity-40">
                <span class="material-symbols-outlined">payments</span> Bayar
            </button>
        </div>
    </div>

    <script>
    (function () {
        const form = document.getElementById('posForm');
        if (!form) return;

        const cards = Array.from(document.querySelectorAll('.pos-card'));
        const sections = Array.from(document.querySelectorAll('[data-gerai-section]'));
        const searchInput = document.getElementById('cariProduk');
        const hasilInfo = document.getElementById('hasilInfo');
        const noMatch = document.getElementById('noMatch');
        const cartCount = document.getElementById('cartCount');
        const cartKantin = document.getElementById('cartKantin');
        const cartTotal = document.getElementById('cartTotal');
        const cartFee = document.getElementById('cartFee');
        const bayarBtn = document.getElementById('bayarBtn');
        const feePersen = @json(isset($fees) ? $fees->pluck('value_fee', 'code_fee') : []);

        let filterKategori = 'semua';
        let filterGerai = 'semua';
        let timerCari;

        const fmt = (n) => 'Rp' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const qtyInput = (card) => card.querySelector('input[data-qty]');
        const qtyOf = (card) => Math.max(0, parseInt(qtyInput(card).value || '0', 10) || 0);
        const setQty = (card, qty) => { qtyInput(card).value = Math.max(0, qty); };

        function refreshCart() {
            let jumlah = 0;
            let total = 0;
            const geraiTerpilih = new Set();

            cards.forEach((card) => {
                const qty = qtyOf(card);
                card.toggleAttribute('data-qty-active', qty > 0);
                jumlah += qty;
                total += qty * parseInt(card.dataset.harga || '0', 10);
                if (qty > 0) geraiTerpilih.add(card.dataset.gerai);
            });

            sections.forEach((section) => {
                let qty = 0;
                section.querySelectorAll('.pos-card').forEach((c) => { qty += qtyOf(c); });
                const badge = section.querySelector('[data-gerai-count]');
                if (badge) {
                    badge.textContent = qty > 0 ? qty + ' dipilih' : '';
                    badge.classList.toggle('hidden', qty === 0);
                }
            });

            cartCount.textContent = jumlah;
            cartKantin.textContent = geraiTerpilih.size;
            cartTotal.textContent = fmt(total);
            let feeTotal = 0;
            Object.values(feePersen).forEach((p) => { feeTotal += Math.round(total * parseFloat(p) / 100); });
            if (cartFee) cartFee.textContent = 'Fee ' + fmt(feeTotal) + ' • Potong ' + fmt(total + feeTotal);
            bayarBtn.disabled = jumlah === 0;
        }

        function applyFilter() {
            const kata = (searchInput.value || '').trim().toLowerCase();
            let terlihat = 0;
            const geraiTerlihat = new Set();

            cards.forEach((card) => {
                const cocok =
                    (filterKategori === 'semua' || card.dataset.kategori === filterKategori) &&
                    (filterGerai === 'semua' || card.dataset.gerai === filterGerai) &&
                    (kata === '' || card.dataset.cari.includes(kata));

                card.classList.toggle('hidden', !cocok);

                if (cocok) {
                    terlihat++;
                    geraiTerlihat.add(card.dataset.gerai);
                }
            });

            sections.forEach((section) => {
                section.classList.toggle('hidden', !section.querySelector('.pos-card:not(.hidden)'));
            });

            if (hasilInfo) hasilInfo.textContent = `${terlihat} produk · ${geraiTerlihat.size} kantin`;
            if (noMatch) noMatch.classList.toggle('hidden', terlihat > 0);
        }

        function setAktif(selector, attr, nilai) {
            document.querySelectorAll(selector).forEach((chip) => {
                chip.setAttribute('aria-pressed', String(chip.dataset[attr] === String(nilai)));
            });
        }

        document.querySelectorAll('[data-filter-kategori]').forEach((chip) => {
            chip.addEventListener('click', () => {
                filterKategori = chip.dataset.filterKategori;
                setAktif('[data-filter-kategori]', 'filterKategori', filterKategori);
                applyFilter();
            });
        });

        document.querySelectorAll('[data-filter-gerai]').forEach((chip) => {
            chip.addEventListener('click', () => {
                filterGerai = chip.dataset.filterGerai;
                setAktif('[data-filter-gerai]', 'filterGerai', filterGerai);
                applyFilter();
            });
        });

        // Pencarian berjalan di sisi klien supaya keranjang tidak hilang saat memfilter.
        searchInput.addEventListener('input', () => {
            clearTimeout(timerCari);
            timerCari = setTimeout(applyFilter, 120);
        });
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') e.preventDefault();
        });

        // Satu handler untuk tambah / kurang, supaya kartu hasil filter tetap bekerja.
        form.addEventListener('click', (e) => {
            const tombol = e.target.closest('[data-add], [data-minus], [data-plus]');
            if (!tombol) return;

            const card = tombol.closest('.pos-card');
            if (!card) return;

            if (tombol.hasAttribute('data-minus')) {
                setQty(card, qtyOf(card) - 1);
            } else {
                setQty(card, qtyOf(card) + 1);
            }
            refreshCart();
        });

        form.addEventListener('input', (e) => {
            if (e.target.matches('input[data-qty]')) refreshCart();
        });

        // Rapikan angka hanya setelah fokus pindah, supaya mengetik tidak mengganggu.
        form.addEventListener('focusout', (e) => {
            if (e.target.matches('input[data-qty]')) {
                e.target.value = qtyOf(e.target.closest('.pos-card'));
                refreshCart();
            }
        });

        document.getElementById('resetCart').addEventListener('click', () => {
            cards.forEach((card) => setQty(card, 0));
            refreshCart();
        });

        const resetFilter = document.getElementById('resetFilter');
        if (resetFilter) {
            resetFilter.addEventListener('click', () => {
                filterKategori = 'semua';
                filterGerai = 'semua';
                searchInput.value = '';
                setAktif('[data-filter-kategori]', 'filterKategori', 'semua');
                setAktif('[data-filter-gerai]', 'filterGerai', 'semua');
                applyFilter();
            });
        }

        const metodeInput = document.getElementById('metodeInput');
        const kartuInput = document.getElementById('kartu_barcode');
        const walkinBox = document.getElementById('walkinBox');
        const hasilKartu = document.getElementById('hasilKartu');
        const kartuTerpilih = document.getElementById('kartuTerpilih');
        let mode = 'kartu';

        function setMode(m) {
            mode = m;
            metodeInput.value = m === 'walkin' ? (document.querySelector('input[name="metode_bayar"]:checked')?.value || 'tunai') : 'kartu';
            document.getElementById('modeKartu').setAttribute('aria-pressed', String(m === 'kartu'));
            document.getElementById('modeWalkin').setAttribute('aria-pressed', String(m === 'walkin'));
            kartuInput.required = m === 'kartu';
            kartuInput.closest('div.rounded-xl').classList.toggle('hidden', m === 'walkin');
            walkinBox.classList.toggle('hidden', m === 'kartu');
            walkinBox.classList.toggle('flex', m === 'walkin');
        }
        document.getElementById('modeKartu').addEventListener('click', () => setMode('kartu'));
        document.getElementById('modeWalkin').addEventListener('click', () => setMode('walkin'));
        walkinBox.addEventListener('change', () => {
            metodeInput.value = document.querySelector('input[name="metode_bayar"]:checked')?.value || 'tunai';
        });

        document.getElementById('cariKartuBtn').addEventListener('click', async () => {
            const q = kartuInput.value.trim();
            if (q.length < 2) { kartuInput.focus(); return; }
            const res = await fetch('{{ route('kartu.getSearch') }}?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const rows = await res.json();
            hasilKartu.innerHTML = '';
            hasilKartu.classList.remove('hidden');
            if (!rows.length) {
                hasilKartu.innerHTML = '<p class="p-2 text-[13px] text-on-surface-variant">Tidak ketemu.</p>';
                return;
            }
            rows.forEach((r) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'flex w-full items-center justify-between gap-2 rounded-lg border border-outline-variant px-3 py-2 text-left hover:border-primary';
                b.innerHTML = "<span class='min-w-0'><span class='block truncate text-[13px] font-bold'>" + r.nama + "</span><span class='block font-data-mono text-[11px] text-on-surface-variant'>" + r.barcode + (r.nis ? ' • ' + r.nis : '') + "</span></span><span class='font-data-mono text-[12px] font-bold text-primary'>Rp" + r.saldo.toLocaleString('id-ID') + '</span>';
                b.addEventListener('click', () => {
                    kartuInput.value = r.barcode;
                    kartuTerpilih.textContent = r.nama + ' • Rp' + r.saldo.toLocaleString('id-ID');
                    kartuTerpilih.classList.remove('hidden');
                    hasilKartu.classList.add('hidden');
                });
                hasilKartu.appendChild(b);
            });
        });

        form.addEventListener('submit', (e) => {
            if (mode === 'kartu') {
                const barcode = document.getElementById('kartu_barcode');
                if (barcode && !barcode.value.trim()) {
                    e.preventDefault();
                    barcode.focus();
                    barcode.classList.add('border-error');
                    return;
                }
            } else {
                const bayar = document.querySelector('input[name="metode_bayar"]:checked');
                if (!bayar) {
                    e.preventDefault();
                    return;
                }
                metodeInput.value = bayar.value;
            }
            bayarBtn.disabled = true;
        });

        refreshCart();
    })();
    </script>
</x-layouts::app>
