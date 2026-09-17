@php
    $role = auth()->user()?->role;
    $bottomNav = match($role){
        'vendor' => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'produk.getTable', 'icon' => 'fastfood', 'label' => 'Produk'],
            ['route' => 'gerai.getPesanan', 'icon' => 'notifications', 'label' => 'Pesanan'],
            ['route' => 'transaksi.getTable', 'icon' => 'receipt_long', 'label' => 'Transaksi'],
            ['route' => 'penarikan.getTable', 'icon' => 'savings', 'label' => 'Tarik'],
        ],
        'kasir_sekolah' => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'produk.getTable', 'icon' => 'fastfood', 'label' => 'Produk'],
            ['route' => 'kasir.pos', 'icon' => 'point_of_sale', 'label' => 'Kasir'],
            ['route' => 'transaksi.getTable', 'icon' => 'receipt_long', 'label' => 'Transaksi'],
            ['route' => 'topup.tunai', 'icon' => 'payments', 'label' => 'TopUp'],
        ],
        'orang_tua' => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'kartu.getAnak', 'icon' => 'family_restroom', 'label' => 'Anak'],
            ['route' => 'transaksi.getTable', 'icon' => 'receipt_long', 'label' => 'Transaksi'],
            ['route' => 'laporan.index', 'icon' => 'assessment', 'label' => 'Laporan'],
            ['route' => 'topup.web', 'icon' => 'account_balance_wallet', 'label' => 'TopUp'],
        ],
        'siswa' => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
            ['route' => 'kartu.getTable', 'icon' => 'badge', 'label' => 'Kartu'],
            ['route' => 'transaksi.getTable', 'icon' => 'receipt_long', 'label' => 'Jajan'],
            ['route' => 'laporan.index', 'icon' => 'assessment', 'label' => 'Laporan'],
            ['route' => 'topup.web', 'icon' => 'account_balance_wallet', 'label' => 'TopUp'],
        ],
        default => config('menu.bottom_nav'),
    };
@endphp

<nav class="md:hidden fixed inset-x-0 bottom-0 z-50 h-16 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_10px_rgba(0,0,0,0.05)]">
    <div class="flex items-center justify-around h-16 px-2">
        @foreach($bottomNav as $index => $item)
            @php
                $routeName = $item['route'];
                $url = route($routeName);
                $isActive = request()->routeIs($routeName) || request()->routeIs($routeName . '.*');
                $isCenter = $index === 2;
            @endphp

            @if($isCenter)
                <div class="flex items-center justify-center flex-1 -mt-4">
                    <a
                        href="{{ $url }}"
                        wire:navigate
                        class="flex items-center justify-center bg-primary text-on-primary w-14 h-14 rounded-2xl shadow-lg ring-4 ring-surface-container-lowest active:scale-90 transition-all"
                    >
                        <span class="material-symbols-outlined text-[28px]">{{ $item['icon'] }}</span>
                    </a>
                </div>
            @else
                <a href="{{ $url }}" wire:navigate class="flex flex-col items-center justify-center transition-all flex-1 {{ $isActive ? 'text-primary opacity-100' : 'text-on-surface-variant opacity-60 hover:opacity-100' }}">
                    <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
