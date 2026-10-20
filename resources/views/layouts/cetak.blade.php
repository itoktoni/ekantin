<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'Laporan' }} — {{ config('website.name', config('app.name')) }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #111; margin: 24px; }
        h1 { font-size: 18pt; margin: 0; }
        h2 { font-size: 13pt; margin: 4px 0 0; font-weight: normal; color: #333; }
        .meta { font-size: 10pt; color: #444; margin: 8px 0 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #555; padding: 5px 8px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr.total td { font-weight: bold; background: #f5f5f5; }
        .toolbar { margin-bottom: 12px; }
        .toolbar button { padding: 8px 20px; font-size: 12pt; cursor: pointer; }
        /* Tombol cetak mengambang kanan bawah. */
        .toolbar-float { position: fixed; right: 16px; bottom: 16px; margin: 0; z-index: 50; display: flex; gap: 8px; }
        .toolbar-float button {
            border: none; border-radius: 9999px; padding: 14px 30px;
            background: #00288e; color: #fff; font-size: 13pt; font-weight: bold;
            box-shadow: 0 4px 14px rgba(0,0,0,.3); cursor: pointer;
        }
        .toolbar-float .btn-back {
            border-radius: 9999px; padding: 14px 24px; text-decoration: none;
            background: #fff; color: #00288e; font-size: 13pt; font-weight: bold;
            border: 1px solid #00288e; box-shadow: 0 4px 14px rgba(0,0,0,.2);
            display: inline-flex; align-items: center;
        }
        .toolbar-float .btn-excel {
            border-radius: 9999px; padding: 14px 24px; text-decoration: none;
            background: #16a34a; color: #fff; font-size: 13pt; font-weight: bold;
            box-shadow: 0 4px 14px rgba(0,0,0,.2);
            display: inline-flex; align-items: center;
        }
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media print {
            body { margin: 0; }
            .toolbar, .toolbar-float { display: none !important; }
        }
        /* HP: kecilkan huruf + padding agar tabel muat, geser sisanya. */
        @media (max-width: 640px) {
            body { margin: 12px; font-size: 10pt; }
            h1 { font-size: 14pt; }
            h2 { font-size: 11pt; }
            th, td { padding: 4px 5px; font-size: 9pt; }
            .toolbar-float { right: 12px; bottom: 12px; left: 12px; }
            .toolbar-float .btn-back, .toolbar-float .btn-excel, .toolbar-float button { width: auto; flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
    <h1>{{ config('website.name', config('app.name')) }}</h1>
    <h2>{{ $judul ?? 'Laporan' }}</h2>
    <div class="meta">
        Periode: {{ request('dari') ? formatDate(request('dari')) : 'awal' }} &ndash; {{ request('sampai') ? formatDate(request('sampai')) : 'hari ini' }}
        &nbsp;|&nbsp; Dicetak: {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()?->name ?? '-' }}
    </div>
    @if(request()->input('export') !== 'xls')
    <div class="toolbar-float no-print">
        <a href="{{ route('laporan.'.($jenis ?? 'index'), request()->query()) }}" class="btn-back">Kembali</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'xls']) }}" class="btn-excel">Excel</a>
        <button onclick="window.print()">Cetak</button>
    </div>
    @endif
    {{ $slot }}
</body>
</html>
