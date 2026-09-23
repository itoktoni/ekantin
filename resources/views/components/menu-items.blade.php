@props(['items', 'mobile' => false])

@php
    $menu = config('menu.sidebar');
@endphp

@foreach($menu as $section)
    @php
        // hide empty sections for kasir_sekolah, vendor, orang_tua, pengguna
        $roleTmp = auth()->user()?->role;
        if (in_array($roleTmp, ['kasir_sekolah','vendor','orang_tua','pengguna']) && !empty($section['label'])) {
            $allowedTmp = match($roleTmp){
                // vendor boleh POS (jual produk gerai sendiri) + kelola gerai/produk/transaksi/penarikan/pembagian/laporan
                'vendor' => ['dashboard','kasir.','gerai.getPesanan','gerai.','produk.','transaksi.','penarikan.','pembagian.','laporan.'],
                'orang_tua' => ['dashboard','kartu.getAnak','transaksi.','laporan.','topup.web'],
                'pengguna' => ['dashboard','kartu.getTable','transaksi.','laporan.','topup.web'],
                // kasir_sekolah: top up kartu + penarikan (penukaran) + pembagian harian
                default => ['dashboard','topup.tunai','topup.web','penarikan.','pembagian.'],
            };
            $hasVisibleTmp = collect($section['items'])->filter(function($it) use ($allowedTmp){
                $rn = $it['route'];
                // ponytail: 'roles' di config/menu.php menang untuk role terbatas
                if (isset($it['roles']) && in_array(auth()->user()?->role, ['vendor','kasir_sekolah','orang_tua','pengguna'], true) && ! in_array(auth()->user()?->role, $it['roles'], true)) return false;
                foreach($allowedTmp as $pref){ if($rn===$pref || str_starts_with($rn,$pref)) return true; }
                return false;
            })->isNotEmpty();
            if(!$hasVisibleTmp) continue;
        }
    @endphp
    @if($section['label'])
        <div class="px-4 pt-4 pb-1 font-label-caps text-label-caps text-on-surface-variant uppercase tracking-widest">{{ $section['label'] }}</div>
    @endif
    @foreach($section['items'] as $item)
        @php
            $routeName = $item['route'];
            $role = auth()->user()?->role;
            // ponytail: 'roles' di config/menu.php menang — role terbatas yang tidak
            // terdaftar langsung disembunyikan (admin/editor/user tak difilter di sini).
            if (isset($item['roles']) && in_array($role, ['vendor','kasir_sekolah','orang_tua','pengguna'], true) && ! in_array($role, $item['roles'], true)) { continue; }
            if ($role === 'kasir_sekolah') {
                // kasir: top up kartu + penarikan (penukaran) + pembagian harian
                $allowedPrefixes = ['dashboard','topup.tunai','topup.web','penarikan.','pembagian.'];
                $isAllowed = false;
                foreach ($allowedPrefixes as $pref) {
                    if ($routeName === $pref || str_starts_with($routeName, $pref)) { $isAllowed = true; break; }
                }
                if (! $isAllowed) { continue; }
            } elseif ($role === 'vendor') {
                // vendor boleh POS (produk gerai sendiri) + pesanan + kelola gerai/produk
                $allowedVendor = ['dashboard','kasir.','gerai.getPesanan','gerai.','produk.','transaksi.','penarikan.','pembagian.','laporan.'];
                $isAllowedV = false;
                foreach ($allowedVendor as $pref) {
                    if ($routeName === $pref || str_starts_with($routeName, $pref)) { $isAllowedV = true; break; }
                }
                if (! $isAllowedV) { continue; }
            } elseif ($role === 'orang_tua') {
                $allowedOrtu = ['dashboard','kartu.getAnak','transaksi.','laporan.','topup.web'];
                $isAllowedO = false;
                foreach ($allowedOrtu as $pref) {
                    if ($routeName === $pref || str_starts_with($routeName, $pref)) { $isAllowedO = true; break; }
                }
                if (! $isAllowedO) { continue; }
            } elseif ($role === 'pengguna') {
                $allowedPengguna = ['dashboard','kartu.getTable','transaksi.','laporan.','topup.web'];
                $isAllowedS = false;
                foreach ($allowedPengguna as $pref) {
                    if ($routeName === $pref || str_starts_with($routeName, $pref)) { $isAllowedS = true; break; }
                }
                if (! $isAllowedS) { continue; }
            }
            $url = route($routeName);
            $matchRoutes = $item['match'] ?? [];
            $isActive = request()->routeIs($routeName)
                || request()->routeIs($routeName . '.*')
                || collect($matchRoutes)->contains(fn ($m) => request()->routeIs($m));
        @endphp
        <a
            href="{{ $url }}"
            wire:navigate
            @if($mobile) @click="drawerOpen = false" @endif
            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ $mobile ? '' : 'group' }} {{ $isActive ? 'bg-primary text-on-primary font-semibold' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}"
        >
            <span class="material-symbols-outlined {{ $isActive ? 'text-on-primary' : 'text-on-surface-variant' . ($mobile ? '' : ' group-hover:text-on-surface') }}">{{ $item['icon'] }}</span>
            <span class="font-body-sm">{{ $item['label'] }}</span>
        </a>
    @endforeach
@endforeach
