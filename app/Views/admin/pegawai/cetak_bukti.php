<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Presensi - <?= $presensi['nama'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .status-hadir {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .status-terlambat {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }
        
        .status-ijin {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #17a2b8;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .info-item .value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 50px 0 20px;
            text-align: center;
        }
        
        .signature-box {
            padding: 20px;
        }
        
        .signature-line {
            margin-top: 80px;
            border-top: 2px solid #333;
            padding-top: 10px;
            font-weight: bold;
        }
        
        .qr-code {
            text-align: center;
            margin: 30px 0;
        }
        
        .qr-code img {
            width: 150px;
            height: 150px;
            border: 2px solid #ddd;
            padding: 10px;
            border-radius: 8px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                padding: 20px;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px 0;
            display: block;
            width: 100%;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Button -->
        <button onclick="window.print()" class="print-button no-print">
            🖨️ Cetak Bukti Presensi
        </button>
        
        <!-- Header -->
        <div class="header">
            <h1>BUKTI PRESENSI</h1>
            <p>Sistem Presensi Digital</p>
            <p style="margin-top: 10px;">
                <strong>Tanggal Cetak:</strong> <?= date('d F Y, H:i') ?> WIB
            </p>
        </div>
        
        <!-- Status Badge -->
        <div style="text-align: center;">
            <div class="status-badge status-<?= $presensi['keterangan'] ?>">
                ✓ <?= strtoupper($presensi['keterangan']) ?>
            </div>
        </div>
        
        <!-- Employee Info -->
        <div class="info-grid">
            <div class="info-item full-width">
                <label>Nama Pegawai</label>
                <div class="value"><?= $presensi['nama'] ?></div>
            </div>
            
            <div class="info-item">
                <label>NIP</label>
                <div class="value"><?= $presensi['nip'] ?></div>
            </div>
            
            <div class="info-item">
                <label>Tanggal Presensi</label>
                <div class="value"><?= date('d F Y', strtotime($presensi['waktu'])) ?></div>
            </div>
            
            <div class="info-item">
                <label>Waktu Check-In</label>
                <div class="value"><?= date('H:i:s', strtotime($presensi['waktu'])) ?> WIB</div>
            </div>
            
            <div class="info-item">
                <label>Status Kehadiran</label>
                <div class="value"><?= ucfirst($presensi['keterangan']) ?></div>
            </div>
            
            <?php if($presensi['latitude'] && $presensi['longitude']): ?>
            <div class="info-item full-width">
                <label>Koordinat GPS</label>
                <div class="value"><?= $presensi['latitude'] ?>, <?= $presensi['longitude'] ?></div>
            </div>
            <?php endif; ?>
            
            <div class="info-item full-width">
                <label>Lokasi Presensi</label>
                <div class="value"><?= $presensi['lokasi'] ?? 'Lokasi tidak tersedia' ?></div>
            </div>
        </div>
        
        <!-- QR Code (Optional) -->
        <div class="qr-code">
            <p style="margin-bottom: 10px; color: #666; font-size: 12px;">
                Kode Verifikasi Bukti Presensi
            </p>
            <div style="background: #f8f9fa; display: inline-block; padding: 15px; border-radius: 8px;">
                <div style="font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold; color: #333;">
                    <?= strtoupper(substr(md5($presensi['id'] . $presensi['waktu']), 0, 16)) ?>
                </div>
            </div>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <p style="margin-bottom: 10px;">Mengetahui,</p>
                <p style="font-weight: bold;">Kepala Bagian</p>
                <div class="signature-line">
                    (..............................)
                </div>
            </div>
            
            <div class="signature-box">
                <p style="margin-bottom: 10px;">Yang Bersangkutan,</p>
                <p style="font-weight: bold;">Pegawai</p>
                <div class="signature-line">
                    <?= $presensi['nama'] ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>PERHATIAN:</strong></p>
            <p style="margin-top: 10px; line-height: 1.6;">
                Dokumen ini adalah bukti sah presensi yang dihasilkan secara digital.<br>
                Simpan dokumen ini sebagai arsip pribadi.<br>
                Untuk verifikasi, hubungi bagian kepegawaian dengan menyertakan kode verifikasi di atas.
            </p>
            <p style="margin-top: 20px; font-style: italic;">
                Dicetak pada: <?= date('l, d F Y H:i:s') ?> WIB
            </p>
        </div>
    </div>
    
    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>