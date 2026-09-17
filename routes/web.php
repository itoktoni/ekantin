<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KartuController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\TopupController;
use App\Http\Controllers\WebsiteSettingController;
use App\Models\Notification;
use App\Services\CentrifugoService;
use Buki\AutoRoute\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth')->post('/centrifugo/token', function (Request $request) {
    if (! config('langkahkecil.notification_enable')) {
        return response()->json(['token' => 'disabled']);
    }

    $centrifugo = app(CentrifugoService::class);
    $user = Auth::user();

    if ($request->input('channel')) {
        return response()->json([
            'token' => $centrifugo->generateSubscriptionToken((string) $user->id, $request->input('channel')),
        ]);
    }

    return response()->json([
        'token' => $centrifugo->generateConnectionToken((string) $user->id),
    ]);
});

Route::middleware(['auth', 'verified', 'access'])->group(function () {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::auto('/user', 'UsersController', ['name' => 'user']);

    Route::auto('/kartu', 'KartuController', ['name' => 'kartu']);
    Route::auto('/gerai', 'GeraiController', ['name' => 'gerai']);
    Route::auto('/produk', 'ProdukController', ['name' => 'produk']);
    Route::auto('/transaksi', 'TransaksiController', ['name' => 'transaksi']);
    Route::auto('/fee-config', 'FeeConfigController', ['name' => 'fee-config']);
    Route::auto('/penarikan', 'PenarikanController', ['name' => 'penarikan']);
    Route::auto('/pembagian', 'PembagianController', ['name' => 'pembagian']);
    Route::auto('/notifikasi-log', 'NotifikasiLogController', ['name' => 'notifikasi-log']);
    Route::auto('/audit-log', 'AuditLogController', ['name' => 'audit-log']);

    Route::get('/kasir/pos', [KasirController::class, 'getPos'])->name('kasir.pos');
    Route::post('/kasir/pos', [KasirController::class, 'postPos'])->name('kasir.postPos');
    Route::get('/kasir/struk/{id}', [KasirController::class, 'getStruk'])->name('kasir.struk');
    // Pesanan Gerai via Route::auto('/gerai', GeraiController) → getPesanan/postPesanan (AGENTS.md: Route::auto)
    // Route::auto sudah expose GET /gerai/pesanan (gerai.getPesanan) & POST /gerai/pesanan/{id} (gerai.postPesanan)
    Route::get('/topup/tunai', [TopupController::class, 'getTunai'])->name('topup.tunai');
    Route::post('/topup/tunai', [TopupController::class, 'postTunai'])->name('topup.postTunai');
    Route::get('/topup/web', [TopupController::class, 'getWeb'])->name('topup.web');
    Route::post('/topup/web', [TopupController::class, 'postWeb'])->name('topup.postWeb');
    Route::get('/topup/web/status/{id}', [TopupController::class, 'getWebStatus'])->name('topup.web.status');
    Route::post('/topup/web/bayar/{id}', [TopupController::class, 'postWebBayar'])->name('topup.web.bayar');
    Route::get('/laporan', [LaporanController::class, 'getIndex'])->name('laporan.index');
    Route::get('/laporan/ekspor', [LaporanController::class, 'getEkspor'])->name('laporan.ekspor');
    Route::get('/kartu-cetak', [KartuController::class, 'getCetak'])->name('kartu.cetak');

    Route::get('/native-bridge-test', function () {
        return view('pages.settings.native-bridge-test');
    })->name('native-bridge-test');

    Route::get('/settings/website', [WebsiteSettingController::class, 'index'])->name('settings.website');
    Route::post('/settings/website', [WebsiteSettingController::class, 'save'])->name('settings.website.save');

    Route::prefix('notifications-web')->group(function () {
        Route::get('/', function (Request $request) {
            $notifications = Notification::where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->limit($request->input('limit', 50))
                ->get();

            $unreadCount = Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->count();

            return response()->json([
                'notifications' => $notifications->map(fn ($n) => [
                    'id' => $n->id,
                    'icon' => $n->icon,
                    'iconColor' => $n->icon_color,
                    'title' => $n->title,
                    'body' => $n->body,
                    'url' => $n->url,
                    'type' => $n->type,
                    'read' => $n->read,
                    'time' => $n->created_at?->diffForHumans() ?? '',
                    'created_at' => $n->created_at->toIso8601String(),
                ]),
                'unread_count' => $unreadCount,
            ]);
        });

        Route::put('/{id}/read', function (int $id) {
            $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
            $notification->update(['read' => true]);

            return response()->json(['message' => 'Marked as read']);
        });

        Route::put('/read-all', function () {
            Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json(['message' => 'All marked as read']);
        });
    });
});

require __DIR__.'/settings.php';
