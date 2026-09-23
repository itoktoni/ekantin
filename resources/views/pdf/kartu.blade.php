<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Pengguna</title>
    <style>
        @page { margin: 10mm 8mm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            color: #191c1e;
            margin: 0;
            padding: 0;
        }

        .judul { text-align: center; margin-bottom: 6mm; }
        .judul h1 { margin: 0; font-size: 13pt; color: #00288e; }
        .judul p { margin: 1mm 0 0; font-size: 7.5pt; color: #444653; }

        table.grid { width: 100%; border-collapse: collapse; }
        td.cell { width: 50%; padding: 2mm; vertical-align: top; }

        /* Tinggi kartu tidak dipatok: dompdf menangani box-sizing height secara
           tidak konsisten, jadi isi kartu dibiarkan mengalir agar tidak meluber. */
        .kartu {
            border: 1px solid #c4c5d5;
            border-radius: 4mm;
            background: #ffffff;
        }
        .kartu-head {
            background: #00288e;
            color: #ffffff;
            padding: 2mm 3mm;
        }
        .kartu-body { padding: 3mm; }
        .kartu-foot {
            border-top: 1px dashed #c4c5d5;
            padding: 2mm 3mm;
            text-align: center;
        }

        table.isi { width: 100%; border-collapse: collapse; }
        td.info { vertical-align: top; padding-right: 2mm; }
        td.qr { width: 21mm; vertical-align: top; text-align: right; }
        td.qr img { width: 21mm; height: 21mm; }

        .brand {
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .jenis {
            font-size: 6.5pt;
            color: #b8c4ff;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .nama {
            font-size: 11pt;
            font-weight: bold;
            color: #191c1e;
            margin-bottom: 1mm;
        }
        .baris { font-size: 7.5pt; color: #444653; margin-top: 0.6mm; }
        .baris b { color: #191c1e; }
        .badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            color: #00288e;
            border: 1px solid #00288e;
            border-radius: 2mm;
            padding: 0.3mm 2mm;
        }
        .badge-off { color: #ba1a1a; border-color: #ba1a1a; }

        .kartu-foot img { height: 9mm; }
        .kode {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            color: #191c1e;
            margin-top: 1mm;
        }

        .footer {
            margin-top: 6mm;
            padding-top: 2mm;
            border-top: 1px solid #c4c5d5;
            text-align: center;
            font-size: 7pt;
            color: #757684;
        }
    </style>
</head>
<body>
    <div class="judul">
        <h1>Kartu Pengguna {{ $sekolah }}</h1>
        @if ($kelas)
            <p>Kelas {{ $kelas }} &middot; {{ count($items) }} kartu</p>
        @else
            <p>Semua kelas &middot; {{ count($items) }} kartu &middot; dicetak {{ $tanggal }}</p>
        @endif
    </div>

    <table class="grid">
        @foreach (array_chunk($items, 2) as $pasangan)
        <tr>
            @foreach ($pasangan as $k)
            <td class="cell">
                <div class="kartu">
                    <div class="kartu-head">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="text-align:left;"><span class="brand">{{ $sekolah }}</span></td>
                                <td style="text-align:right;"><span class="jenis">Kartu Pengguna</span></td>
                            </tr>
                        </table>
                    </div>

                    <div class="kartu-body">
                        <table class="isi">
                            <tr>
                                <td class="info">
                                    <div class="nama">{{ $k['nama'] }}</div>
                                    <div class="baris">NIS: <b>{{ $k['nis'] ?? '-' }}</b></div>
                                    <div class="baris">Kelas: <b>{{ $k['kelas'] ?? '-' }}</b></div>
                                    <div class="baris">
                                        @if ($k['status'] === 'aktif')
                                            <span class="badge">AKTIF</span>
                                        @else
                                            <span class="badge badge-off">NONAKTIF</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="qr">
                                    <img src="data:image/png;base64,{{ $k['qr_png'] }}" alt="qr {{ $k['barcode'] }}">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="kartu-foot">
                        <img src="data:image/png;base64,{{ $k['barcode_png'] }}" alt="barcode {{ $k['barcode'] }}">
                        <div class="kode">{{ $k['barcode'] }}</div>
                    </div>
                </div>
            </td>
            @endforeach
            @if (count($pasangan) === 1)
                <td class="cell"></td>
            @endif
        </tr>
        @endforeach
    </table>

    <div class="footer">
        Kartu ini adalah alat pembayaran di kantin sekolah. Tidak untuk dipindahtangankan.
    </div>
</body>
</html>
