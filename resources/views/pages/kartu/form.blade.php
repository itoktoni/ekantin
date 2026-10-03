<?php /** @var App\Models\Kartu $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="kartu_barcode" id="kartu_barcode" autofocus autocomplete="off" spellcheck="false" placeholder="Tempelkan kartu NFC…" helper="Tempelkan kartu NFC — UID masuk & tersimpan otomatis" />
                <x-select col="6" name="kartu_id_user" label="Pengguna" :options="$pengguna" />
                <x-select col="6" name="kartu_id_orangtua" label="Orang Tua" :options="$ortu" />
                <x-input col="3" name="kartu_nis" label="NIS" />
                <x-input col="3" name="kartu_kelas" label="Kelas" />
                <x-select col="3" name="kartu_status" label="Status" :options="$status" />
                <x-input col="3" type="number" name="kartu_limit_harian" label="Limit Harian (Rp)" />

                @if(isset($model) && $model->exists)
                <div class="col-span-12">
                    <p class="text-sm">Saldo saat ini: <strong>Rp{{ number_format((int) $model->kartu_saldo, 0, ',', '.') }}</strong> (berubah hanya via top up / transaksi)</p>
                </div>
                @endif

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script>
    (function () {
        const input = document.querySelector('input[name="kartu_barcode"]');
        if (!input) return;
        input.classList.add('nfc-input');

        const style = document.createElement('style');
        style.textContent = '.nfc-input{caret-color:var(--color-primary,#1a28a2)}.nfc-input.nfc-ok{border-color:var(--color-success,#15803d);box-shadow:0 0 0 1px var(--color-success,#15803d)}.nfc-input.nfc-err{border-color:var(--color-error,#b3261e);box-shadow:0 0 0 1px var(--color-error,#b3261e)}';
        document.head.appendChild(style);

        const info = document.createElement('p');
        info.id = 'nfcKartuStatus';
        info.setAttribute('aria-live', 'polite');
        info.className = 'col-span-12 text-xs font-semibold text-on-surface-variant';
        info.textContent = 'Siap scan — tempelkan kartu NFC di input barcode.';
        input.closest('div').appendChild(info);

        function beep(ok = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.frequency.value = ok ? 880 : 220;
                o.type = 'sine';
                g.gain.setValueAtTime(0.15, ctx.currentTime);
                o.start(); o.stop(ctx.currentTime + (ok ? 0.12 : 0.3));
            } catch (e) {}
        }
        function setInfo(kind, msg) {
            info.className = 'col-span-12 text-xs font-semibold ' + (kind === 'ok' ? 'text-success' : kind === 'err' ? 'text-error' : 'text-on-surface-variant');
            info.textContent = msg;
            input.classList.toggle('nfc-ok', kind === 'ok');
            input.classList.toggle('nfc-err', kind === 'err');
        }

        function nfcClean(s) { return (s || '').replace(/^;+|\?+$/g, '').trim(); }
        function nfcUid(s) {
            const h = (s || '').replace(/[^0-9a-fA-F]/g, '');
            return h.length >= 8 ? h.toUpperCase() : null;
        }
        function nfcSame(a, b) {
            const ua = nfcUid(a), ub = nfcUid(b);
            if (ua && ub) return ua === ub;
            return (a || '').toLowerCase() === (b || '').toLowerCase();
        }

        let busy = false, lastUid = '', lastAt = 0, firstKeyAt = 0;
        const currentId = @json(isset($model) && $model->exists ? (int) $model->kartu_id : null);
        const originalBarcode = @json(isset($model) && $model->exists ? (string) $model->kartu_barcode : '');
        let tapKartuId = null; // kartu_id pemilik barcode yang ditempel (null = kartu baru)

        // Auto-proses setelah scan selesai — reader NFC yang tidak mengirim Enter tetap tersimpan.
        let idleTimer = null;
        function scheduleScan() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                const code = nfcClean(input.value);
                if (code.length < 4) return;
                // Hanya wedge (ketikan cepat); ketik manual tetap lewat Enter.
                const wedge = firstKeyAt && (performance.now() - firstKeyAt) < (code.length * 60 + 250);
                if (wedge) handleTap();
            }, 220);
        }

        // Fokus = select-all supaya ketikan wedge MENGGANTI barcode lama, bukan nempel di belakangnya.
        input.addEventListener('focus', () => { try { input.select(); } catch (e) {} });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(idleTimer); handleTap(); return; }
            if (e.key.length === 1 && !firstKeyAt) firstKeyAt = performance.now();
        });
        input.addEventListener('input', () => {
            if (!input.value) firstKeyAt = 0;
            scheduleScan();
        });
        // Lepas-pasang ulang supaya tidak menumpuk tiap wire:navigate.
        if (window.__kartuNfcDoc) document.removeEventListener('focusout', window.__kartuNfcDoc);
        window.__kartuNfcDoc = () => {
            setTimeout(() => {
                const el = document.querySelector('input[name="kartu_barcode"]');
                const a = document.activeElement;
                const typing = a && (a.tagName === 'INPUT' || a.tagName === 'TEXTAREA' || a.tagName === 'SELECT' || a.isContentEditable);
                if (el && !typing) el.focus({ preventScroll: true });
            }, 400);
        };
        document.addEventListener('focusout', window.__kartuNfcDoc);

        async function handleTap() {
            if (busy) return;
            let code = nfcClean(input.value);
            // Kupas sisa barcode lama kalau wedge menempel di depan/belakang (mis. "SW-100104A3…" → "04A3…").
            if (originalBarcode && code.length > originalBarcode.length) {
                const low = code.toLowerCase(), old = originalBarcode.toLowerCase();
                if (low.startsWith(old)) code = code.slice(originalBarcode.length).trim();
                else if (low.endsWith(old)) code = code.slice(0, code.length - originalBarcode.length).trim();
            }
            if (code.length < 2) { input.focus(); return; }
            const now = Date.now();
            if (nfcSame(code, lastUid) && now - lastAt < 2000) {
                // Auto-proses idle lalu Enter telat → jangan tampilkan error ganda.
                if (now - lastAt > 700) { setInfo('err', 'Kartu sudah diproses, angkat dulu kartunya.'); beep(false); }
                return;
            }
            lastUid = code; lastAt = now;
            busy = true;
            firstKeyAt = 0;
            setInfo('info', 'Mencari ' + code + ' …');
            let rows = [];
            try {
                const res = await fetch('{{ route('kartu.getSearch') }}?q=' + encodeURIComponent(code), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                rows = await res.json();
            } catch (e) {
                setInfo('err', 'Gagal cari kartu, periksa koneksi lalu tempel ulang.');
                beep(false); busy = false; input.focus(); input.select();
                return;
            }
            const pas = rows.find((r) => nfcSame(r.barcode, code));
            if (pas) {
                input.value = pas.barcode;
                tapKartuId = pas.kartu_id ?? null;
                if (currentId && tapKartuId && Number(tapKartuId) === Number(currentId)) {
                    // Tempel kartu sendiri saat edit → aman.
                    setInfo('ok', 'Kartu ini milik ' + pas.nama + ' — tidak ada perubahan.');
                } else if (currentId) {
                    // Mode GANTI KARTU ditolak jika UID milik siswa lain → cegah rebut kartu orang.
                    setInfo('err', 'BLOKIR: ' + pas.barcode + ' milik ' + pas.nama + ' — pakai kartu kosong baru untuk ganti, bukan kartu orang lain.');
                    beep(false);
                } else {
                    // Mode buat baru tapi UID sudah dipakai → tawarkan link/update record itu saja.
                    setInfo('ok', 'Kartu sudah terdaftar milik ' + pas.nama + ' (' + pas.barcode + (pas.nis ? ' • ' + pas.nis : '') + ') — buka Edit record itu untuk link ulang, jangan buat duplikat.');
                }
                beep(tapKartuId && currentId && Number(tapKartuId) !== Number(currentId) ? false : true);
                const sel = document.querySelector('select[name="kartu_id_user"]');
                if (sel) sel.focus({ preventScroll: true });
            } else {
                // UID baru belum terdaftar → isi barcode kanonis (Uppercase tanpa separator).
                tapKartuId = null;
                input.value = nfcUid(code) || code;
                const selectUser = document.querySelector('select[name="kartu_id_user"]');
                const selectStatus = document.querySelector('select[name="kartu_status"]');
                const kurang = [];
                if (selectUser && !selectUser.value) kurang.push('Pengguna');
                if (selectStatus && !selectStatus.value) kurang.push('Status');
                if (kurang.length) {
                    // Field wajib belum diisi → jangan submit (cegah error "required" dari server).
                    setInfo('err', 'Kartu ' + code + ' terbaca — pilih ' + kurang.join(' & ') + ' dulu, lalu tempel ulang.');
                    beep(false);
                    const target = (selectUser && !selectUser.value) ? selectUser : selectStatus;
                    if (target) target.focus({ preventScroll: true });
                    busy = false;
                    return;
                }
                // Mode baru / ganti kartu: langsung simpan otomatis, tanpa klik Save.
                setInfo('ok', (currentId ? 'Mengganti ke ' : 'Menyimpan ') + code + ' … menyimpan otomatis.');
                beep(true);
                busy = false;
                const frm = input.closest('form');
                setTimeout(() => { if (frm) frm.requestSubmit(); }, 600);
                return;
            }
            busy = false;
        }

        // Normalisasi + cegah save jika barcode milik record lain (mode edit).
        const kartuForm = input.closest('form');
        if (kartuForm) {
            kartuForm.addEventListener('submit', (e) => {
                const uid = nfcUid(nfcClean(input.value));
                if (uid) input.value = uid; // simpan selalu format kanonis
                if (currentId && tapKartuId && Number(tapKartuId) !== Number(currentId)) {
                    e.preventDefault();
                    setInfo('err', 'BLOKIR: barcode ini milik siswa lain. Tempel kartu kosong baru.');
                    beep(false);
                    input.focus(); input.select();
                }
            });
        }

        input.focus({ preventScroll: true });
    })();
    </script>
</x-layouts::app>
