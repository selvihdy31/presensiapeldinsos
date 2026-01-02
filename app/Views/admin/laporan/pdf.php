<!DOCTYPE html>
<html>
<head>
    <title>Laporan Presensi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4f46e5; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>LAPORAN PRESENSI PEGAWAI</h2>
    <p>Tanggal Cetak: <?= date('d F Y H:i') ?></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Keterangan</th>
                <th>Koordinat</th>
                <th>Lokasi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach($presensi as $p): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $p['nip'] ?></td>
                    <td><?= $p['nama'] ?></td>
                    <td><?= date('d/m/Y', strtotime($p['waktu'])) ?></td>
                    <td><?= date('H:i', strtotime($p['waktu'])) ?></td>
                    <td><?= ucfirst($p['keterangan']) ?></td>
                    <td><?= $p['latitude'] ?>, <?= $p['longitude'] ?></td>
                    <td><?= $p['lokasi'] ?? '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>