<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan e-Kanteen</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    </style>
</head>
<body>
    <h2>Laporan e-Kanteen</h2>
    <p>Total: Rp{{ number_format($ringkas['total'], 0, ',', '.') }} | Fee Pengelola: Rp{{ number_format($ringkas['fee_kelola'], 0, ',', '.') }} | Fee Sistem: Rp{{ number_format($ringkas['fee_sistem'], 0, ',', '.') }} | Bersih: Rp{{ number_format($ringkas['bersih'], 0, ',', '.') }}</p>
    <table>
        <tr>
            <th>Tanggal</th>
            <th>Siswa</th>
            <th>Gerai</th>
            <th>Jenis</th>
            <th>Total</th>
            <th>Fee Kelola</th>
            <th>Fee Sistem</th>
            <th>Bersih</th>
            <th>Status</th>
        </tr>
        @foreach($rows as $r)
        <tr>
            <td>{{ $r->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $r->hasKartu?->hasUser?->name ?? '-' }}</td>
            <td>{{ $r->hasItems->map(fn ($i) => $i->hasGerai?->gerai_nama)->filter()->unique()->implode(', ') ?: ($r->hasGerai?->gerai_nama ?? '-') }}</td>
            <td>{{ $r->transaksi_jenis }}</td>
            <td>{{ $r->transaksi_total }}</td>
            <td>{{ $r->feeTotal() }}</td>
            <td>{{ $r->feeRincian()['sistem'] ?? 0 }}</td>
            <td>{{ $r->transaksi_bersih }}</td>
            <td>{{ $r->transaksi_status }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>
