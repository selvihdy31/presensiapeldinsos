<!DOCTYPE html>
<html>
<head>
    <title>Laporan Presensi</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px;
            margin: 20px;
        }
        h2 { 
            text-align: center; 
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info {
            margin-bottom: 15px;
            font-size: 10px;
        }
        .info p {
            margin: 3px 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            border: 1px solid #333; 
            padding: 6px 4px; 
            text-align: left;
            font-size: 10px;
        }
        th { 
            background-color: #4f46e5; 
            color: white;
            font-weight: bold;
            text-align: center;
        }
        tr:nth-child(even) { 
            background-color: #f9f9f9; 
        }
        td.center {
            text-align: center;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-success { background-color: #28a745; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-primary { background-color: #007bff; color: white; }
        .badge-secondary { background-color: #6c757d; color: white; }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <h2>LAPORAN PRESENSI PEGAWAI</h2>
    
    <div class="info">
        <p><strong>Tanggal Cetak:</strong> <?= date('d F Y H:i') ?> WIB</p>
        
        <?php if (!empty($filters)): ?>
            <?php if (isset($filters['bagian'])): ?>
                <?php 
                $bagianLabels = [
                    'sekretariat' => 'Sekretariat',
                    'rehlinjamsos' => 'Rehlin Jamsos',
                    'dayasos' => 'Daya Sos'
                ];
                ?>
                <p><strong>Bagian:</strong> <?= $bagianLabels[$filters['bagian']] ?></p>
            <?php endif; ?>
            
            <?php if (isset($filters['tahun'])): ?>
                <p><strong>Tahun:</strong> <?= $filters['tahun'] ?></p>
            <?php endif; ?>
            
            <?php if (isset($filters['tanggal_mulai']) && isset($filters['tanggal_selesai'])): ?>
                <p><strong>Periode:</strong> 
                    <?= date('d-m-Y', strtotime($filters['tanggal_mulai'])) ?> 
                    s/d 
                    <?= date('d-m-Y', strtotime($filters['tanggal_selesai'])) ?>
                </p>
            <?php endif; ?>
            
            <?php if (isset($filters['keterangan'])): ?>
                <p><strong>Keterangan:</strong> <?= ucfirst($filters['keterangan']) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="10%">NIP</th>
                <th width="16%">Nama</th>
                <th width="12%">Bidang</th>
                <th width="9%">Tanggal</th>
                <th width="7%">Waktu</th>
                <th width="10%">Keterangan</th>
                <th width="14%">Koordinat</th>
                <th width="18%">Lokasi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($presensi)): ?>
                <tr>
                    <td colspan="9" class="center">Tidak ada data presensi</td>
                </tr>
            <?php else: ?>
                <?php 
                $no = 1;
                $bagianLabels = [
                    'sekretariat' => 'Sekretariat',
                    'rehlinjamsos' => 'Rehlin Jamsos',
                    'dayasos' => 'Daya Sos'
                ];
                
                foreach($presensi as $p): 
                    // Tentukan warna badge keterangan
                    $keterangan = strtolower($p['keterangan']);
                    switch ($keterangan) {
                        case 'hadir':
                            $badgeClass = 'badge-success';
                            break;
                        case 'alpha':
                            $badgeClass = 'badge-danger';
                            break;
                        case 'ijin':
                            $badgeClass = 'badge-info';
                            break;
                        case 'terlambat':
                            $badgeClass = 'badge-warning';
                            break;
                        default:
                            $badgeClass = 'badge-secondary';
                    }
                    
                    // Tentukan warna badge bagian
                    $bagianBadge = 'badge-secondary';
                    if (isset($p['bagian'])) {
                        switch ($p['bagian']) {
                            case 'sekretariat':
                                $bagianBadge = 'badge-primary';
                                break;
                            case 'rehlinjamsos':
                                $bagianBadge = 'badge-info';
                                break;
                            case 'dayasos':
                                $bagianBadge = 'badge-warning';
                                break;
                        }
                    }
                ?>
                    <tr>
                        <td class="center"><?= $no++ ?></td>
                        <td><?= $p['nip'] ?></td>
                        <td><?= $p['nama'] ?></td>
                        <td class="center">
                            <span class="badge <?= $bagianBadge ?>">
                                <?= isset($p['bagian']) && isset($bagianLabels[$p['bagian']]) ? $bagianLabels[$p['bagian']] : '-' ?>
                            </span>
                        </td>
                        <td class="center"><?= date('d/m/Y', strtotime($p['waktu'])) ?></td>
                        <td class="center"><?= date('H:i', strtotime($p['waktu'])) ?></td>
                        <td class="center">
                            <span class="badge <?= $badgeClass ?>">
                                <?= ucfirst($keterangan) ?>
                            </span>
                        </td>
                        <td class="center" style="font-size: 8px;"><?= $p['latitude'] ?>, <?= $p['longitude'] ?></td>
                        <td><?= $p['lokasi'] ?? '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <i>Dokumen ini dicetak secara otomatis dari Sistem Presensi</i>
    </div>
</body>
</html>