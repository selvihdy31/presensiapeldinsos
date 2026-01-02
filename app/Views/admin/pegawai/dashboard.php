<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// DEBUGGING - Hapus setelah masalah teratasi
echo "<!-- DEBUG INFO:\n";
echo "has_presensi_today: " . ($has_presensi_today ? 'TRUE' : 'FALSE') . "\n";
echo "today_presensi exists: " . (isset($today_presensi) ? 'YES' : 'NO') . "\n";
if (isset($today_presensi)) {
    echo "today_presensi data:\n";
    print_r($today_presensi);
}
echo "-->\n";
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-light border-0 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-person-circle text-primary" style="font-size: 50px;"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5 class="mb-1">Selamat Datang, <?= session()->get('nama') ?? 'Pegawai' ?>! 👋</h5>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-calendar3"></i> <?= strftime('%A, %d %B %Y', strtotime(date('Y-m-d'))) ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-clock"></i> <span id="live-clock"><?= date('H:i:s') ?></span> WIB
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Presensi Hari Ini -->
<?php
// Pastikan variabel ada
$has_presensi_today = $has_presensi_today ?? false;
$today_presensi = $today_presensi ?? null;

// Konfigurasi tampilan berdasarkan keterangan
$statusConfig = [
    'hadir' => [
        'gradient' => '#43e97b 0%, #38f9d7',
        'icon' => 'check-circle',
        'title' => '✓ Anda Sudah Presensi - Hadir',
        'message' => 'Terima kasih sudah hadir tepat waktu! ✨',
        'badge_bg' => 'success'
    ],
    'ijin' => [
        'gradient' => '#4facfe 0%, #00f2fe',
        'icon' => 'file-earmark-text',
        'title' => '📋 Anda Sudah Presensi - Ijin',
        'message' => 'Ijin Anda telah dicatat. Semoga lekas sehat/lancar urusannya.',
        'badge_bg' => 'info'
    ],
    'alpha' => [
        'gradient' => '#fa709a 0%, #fee140',
        'icon' => 'x-circle',
        'title' => '❌ Presensi Tercatat - Alpha',
        'message' => 'Anda tercatat alpha hari ini. Hubungi admin jika ada kesalahan.',
        'badge_bg' => 'danger'
    ]
];

// Tentukan status saat ini
$keterangan = null;
if ($has_presensi_today && is_array($today_presensi)) {
    $keterangan = $today_presensi['keterangan'] ?? null;
}

$currentStatus = null;
if ($has_presensi_today && $keterangan && isset($statusConfig[$keterangan])) {
    $currentStatus = $statusConfig[$keterangan];
}

// Warna gradient
$gradient = ($has_presensi_today && $currentStatus) 
    ? $currentStatus['gradient'] 
    : '#fa709a 0%, #fee140';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, <?= $gradient ?> 100%);">
            <div class="card-body text-white py-4 position-relative">
                <!-- Background Pattern -->
                <div style="position: absolute; top: 0; right: 0; opacity: 0.1; font-size: 200px;">
                    <i class="bi bi-<?= $has_presensi_today && $currentStatus ? $currentStatus['icon'] : 'exclamation-circle' ?>"></i>
                </div>
                
                <div class="text-center position-relative">
                    <i class="bi bi-<?= $has_presensi_today && $currentStatus ? $currentStatus['icon'] : 'exclamation-circle' ?>-fill" 
                       style="font-size: 70px; animation: pulse 2s infinite;"></i>
                    
                    <h3 class="mt-3 mb-2 fw-bold">
                        <?= $has_presensi_today && $currentStatus ? $currentStatus['title'] : '⚠️ Anda Belum Presensi Hari Ini' ?>
                    </h3>
                    
                    <p class="mb-3 fs-5"><?= strftime('%A, %d %B %Y', strtotime(date('Y-m-d'))) ?></p>
                    
                    <?php if(!$has_presensi_today): ?>
                        <!-- Belum Presensi -->
                        <a href="<?= base_url('pegawai/presensi/scan') ?>" class="btn btn-light btn-lg px-5 shadow">
                            <i class="bi bi-qr-code-scan"></i> Scan QR Code Sekarang
                        </a>
                        <p class="mt-3 mb-0 small">Jangan lupa untuk melakukan presensi tepat waktu!</p>
                        
                    <?php else: ?>
                        <!-- Sudah Presensi -->
                        <div class="bg-white bg-opacity-25 rounded p-4 d-inline-block" style="min-width: 300px;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <p class="mb-2 fw-bold text-white-50 small text-uppercase">Waktu Check-In</p>
                                    <h4 class="mb-0 fw-bold">
                                        <i class="bi bi-clock-fill"></i> 
                                        <?php if(is_array($today_presensi) && isset($today_presensi['waktu'])): ?>
                                            <?= date('H:i:s', strtotime($today_presensi['waktu'])) ?> WIB
                                        <?php else: ?>
                                            07:00:00 WIB
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                
                                <div class="col-12">
                                    <p class="mb-2 fw-bold text-white-50 small text-uppercase">Status Kehadiran</p>
                                    <span class="badge bg-<?= $currentStatus ? $currentStatus['badge_bg'] : 'secondary' ?> px-4 py-2 fs-6">
                                        <i class="bi bi-<?= $currentStatus ? $currentStatus['icon'] : 'question-circle' ?>-fill"></i>
                                        <?= ucfirst($keterangan ?? 'Tidak Diketahui') ?>
                                    </span>
                                </div>
                                
                                <?php if(is_array($today_presensi) && !empty($today_presensi['lokasi'])): ?>
                                <div class="col-12">
                                    <p class="mb-2 fw-bold text-white-50 small text-uppercase">Lokasi Presensi</p>
                                    <p class="mb-0 small">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <?= strlen($today_presensi['lokasi']) > 50 
                                            ? substr($today_presensi['lokasi'], 0, 50) . '...' 
                                            : $today_presensi['lokasi'] ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p class="mt-3 mb-0 small" style="max-width: 500px; margin: 0 auto;">
                            <?= $currentStatus ? $currentStatus['message'] : 'Terima kasih sudah melakukan presensi hari ini!' ?>
                        </p>
                        
                        <?php if($keterangan === 'alpha'): ?>
                        <div class="mt-3">
                            <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="btn btn-light btn-sm">
                                <i class="bi bi-eye"></i> Lihat Detail
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Menu -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="<?= base_url('pegawai/presensi/scan') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift h-100">
            <div class="card-body text-center py-4">
                <div class="icon-box mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <h5 class="mb-2 text-dark fw-bold">Scan Presensi</h5>
                <p class="text-muted small mb-0">Scan QR Code untuk melakukan presensi harian</p>
            </div>
        </a>
    </div>
    
    <div class="col-md-4">
        <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift h-100">
            <div class="card-body text-center py-4">
                <div class="icon-box mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h5 class="mb-2 text-dark fw-bold">Riwayat Presensi</h5>
                <p class="text-muted small mb-0">Lihat riwayat dan history presensi Anda</p>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="<?= base_url('pegawai/presensi/ijin') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift h-100">
            <div class="card-body text-center py-4">
                <div class="icon-box mb-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h5 class="mb-2 text-dark fw-bold">Pengajuan Ijin</h5>
                <p class="text-muted small mb-0">Ajukan ijin atau sakit secara online</p>
            </div>
        </a>
    </div>
</div>

<!-- Statistik Presensi -->
<!-- Statistik Presensi -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-bar-chart-fill text-primary"></i> Statistik Presensi
                    </h5>
                    <span class="badge bg-primary-subtle text-primary">30 Hari Terakhir</span>
                </div>
            </div>
            <div class="card-body">
                <?php 
                $hadir = 0;
                $ijin = 0;
                $alpha = 0;
                
                foreach($recent_presensi as $p) {
                    if(strtotime($p['waktu']) > strtotime('-30 days')) {
                        if($p['keterangan'] == 'hadir') $hadir++;
                        if($p['keterangan'] == 'ijin') $ijin++;
                        if($p['keterangan'] == 'alpha') $alpha++;
                    }
                }
                
                // Total presensi yang sudah dilakukan
                $total = $hadir + $ijin + $alpha;
                
                // Hitung tingkat kehadiran dari total presensi (hadir + alpha)
                // Ijin dan alpha tidak dihitung sebagai kehadiran
                $total_kehadiran = $hadir - $alpha;
                $hadir_percent = $total > 0 ? round(($total_kehadiran / $total) * 100, 1) : 0;
                ?>
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary-subtle">
                            <div class="stat-icon bg-primary">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="text-primary mb-1 fw-bold"><?= $total ?></h3>
                                <small class="text-muted">Total Presensi</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card bg-success-subtle">
                            <div class="stat-icon bg-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="text-success mb-1 fw-bold"><?= $hadir ?></h3>
                                <small class="text-muted">Hadir</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: <?= $total > 0 ? round(($hadir / $total) * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card bg-danger-subtle">
                            <div class="stat-icon bg-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="text-danger mb-1 fw-bold"><?= $alpha ?></h3>
                                <small class="text-muted">Alpha</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-danger" style="width: <?= $total > 0 ? round(($alpha / $total) * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card bg-info-subtle">
                            <div class="stat-icon bg-info">
                                <i class="bi bi-file-earmark"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="text-info mb-1 fw-bold"><?= $ijin ?></h3>
                                <small class="text-muted">Ijin</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-info" style="width: <?= $total > 0 ? round(($ijin / $total) * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Performance Indicator -->
                <?php if($total > 0): ?>
                <div class="mt-4 p-4 bg-light rounded">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted fw-semibold">Tingkat Kehadiran</small>
                                    <small class="text-muted"><?= $total_kehadiran ?> dari <?= $total ?> presensi</small>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar <?= $hadir_percent >= 90 ? 'bg-success' : ($hadir_percent >= 75 ? 'bg-warning' : 'bg-danger') ?>" 
                                         role="progressbar" 
                                         style="width: <?= $hadir_percent ?>%;"
                                         aria-valuenow="<?= $hadir_percent ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <span class="fw-bold"><?= $hadir_percent ?>%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detail Breakdown -->
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <span class="text-muted">Hadir: <strong><?= $hadir ?></strong> (<?= $total > 0 ? round(($hadir / $total) * 100, 1) : 0 ?>%)</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-fill text-info me-2"></i>
                                        <span class="text-muted">Ijin: <strong><?= $ijin ?></strong> (<?= $total > 0 ? round(($ijin / $total) * 100, 1) : 0 ?>%)</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                        <span class="text-muted">Alpha: <strong><?= $alpha ?></strong> (<?= $total > 0 ? round(($alpha / $total) * 100, 1) : 0 ?>%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-center">
                            <?php if($hadir_percent >= 90): ?>
                                <div class="p-3">
                                    <i class="bi bi-trophy-fill text-success" style="font-size: 50px;"></i>
                                    <h4 class="mt-2 mb-1 text-success fw-bold"><?= $hadir_percent ?>%</h4>
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-star-fill"></i> Sangat Baik
                                    </span>
                                    <p class="small text-muted mt-2 mb-0">Pertahankan kedisiplinan Anda!</p>
                                </div>
                            <?php elseif($hadir_percent >= 75): ?>
                                <div class="p-3">
                                    <i class="bi bi-star-fill text-warning" style="font-size: 50px;"></i>
                                    <h4 class="mt-2 mb-1 text-warning fw-bold"><?= $hadir_percent ?>%</h4>
                                    <span class="badge bg-warning px-3 py-2">
                                        <i class="bi bi-hand-thumbs-up"></i> Baik
                                    </span>
                                    <p class="small text-muted mt-2 mb-0">Tingkatkan sedikit lagi!</p>
                                </div>
                            <?php else: ?>
                                <div class="p-3">
                                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 50px;"></i>
                                    <h4 class="mt-2 mb-1 text-danger fw-bold"><?= $hadir_percent ?>%</h4>
                                    <span class="badge bg-danger px-3 py-2">
                                        <i class="bi bi-arrow-up-circle"></i> Perlu Ditingkatkan
                                    </span>
                                    <p class="small text-muted mt-2 mb-0">Tingkatkan kehadiran Anda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Terbaru -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-clock-history text-primary"></i> Presensi Terbaru
                    </h5>
                    <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if(empty($recent_presensi)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 80px; opacity: 0.2;"></i>
                        <h5 class="mt-4 text-muted">Belum Ada Riwayat Presensi</h5>
                        <p class="mb-3">Mulai presensi dengan scan QR Code</p>
                        <a href="<?= base_url('pegawai/presensi/scan') ?>" class="btn btn-primary">
                            <i class="bi bi-qr-code-scan"></i> Scan Sekarang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="bi bi-calendar3"></i> Tanggal</th>
                                    <th><i class="bi bi-clock"></i> Waktu</th>
                                    <th><i class="bi bi-tag"></i> Keterangan</th>
                                    <th><i class="bi bi-geo-alt"></i> Lokasi</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach(array_slice($recent_presensi, 0, 5) as $p): ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('d/m/Y', strtotime($p['waktu'])) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= strftime('%A', strtotime($p['waktu'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark">
                                                <i class="bi bi-clock"></i> <?= date('H:i', strtotime($p['waktu'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $keterangan = strtolower($p['keterangan']);
                                            switch ($keterangan) {
                                                case 'hadir':
                                                    $badgeClass = 'bg-success';
                                                    $icon = 'check-circle';
                                                    break;
                                                case 'alpha':
                                                    $badgeClass = 'bg-danger';
                                                    $icon = 'x-circle';
                                                    break;
                                                case 'ijin':
                                                    $badgeClass = 'bg-info';
                                                    $icon = 'file-text';
                                                    break;
                                                default:
                                                    $badgeClass = 'bg-secondary';
                                                    $icon = 'question-circle';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?> px-3 py-2">
                                                <i class="bi bi-<?= $icon ?>"></i>
                                                <?= ucfirst($p['keterangan']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="bi bi-pin-map"></i>
                                                <?= strlen($p['lokasi'] ?? '') > 40 ? substr($p['lokasi'], 0, 40) . '...' : ($p['lokasi'] ?? 'Tidak ada data') ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('pegawai/presensi/detail/' . $p['id']) ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Tips -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm border-start border-primary border-4">
            <div class="card-body">
                <h6 class="text-primary mb-3">
                    <i class="bi bi-lightbulb"></i> Tips Presensi
                </h6>
                <ul class="mb-0 small">
                    <li class="mb-2">Pastikan GPS aktif saat melakukan presensi</li>
                    <li class="mb-2">Scan QR Code dalam jangkauan waktu yang ditentukan</li>
                    <li class="mb-2">Simpan bukti presensi untuk arsip pribadi</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body">
                <h6 class="text-warning mb-3">
                    <i class="bi bi-info-circle"></i> Informasi Penting
                </h6>
                <ul class="mb-0 small">
                    <li class="mb-2">Presensi dapat dilakukan dari lokasi manapun</li>
                    <li class="mb-2">Pastikan koneksi internet stabil</li>
                    <li class="mb-2">Hubungi admin jika ada kendala teknis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    /* Live Clock Animation */
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* Icon Box Styling */
    .icon-box {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .icon-box i {
        font-size: 40px;
        color: white;
    }
    
    /* Hover Lift Effect */
    .hover-lift {
        transition: all 0.3s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15) !important;
    }
    
    /* Stat Card */
    .stat-card {
        padding: 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .stat-icon i {
        font-size: 28px;
        color: white;
    }
    
    .stat-content {
        flex-grow: 1;
    }
    
    /* Table Styling */
    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    /* Badge Styling */
    .badge {
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    
    /* Card Shadow */
    .card {
        transition: all 0.3s ease;
    }
    /* Pulse Animation */
    @keyframes pulse {
        0%, 100% { 
            transform: scale(1); 
            opacity: 1;
        }
        50% { 
            transform: scale(1.05); 
            opacity: 0.9;
        }
    }
    
    /* Badge Styling */
    .badge {
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Live Clock Update
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('live-clock').textContent = `${hours}:${minutes}:${seconds}`;
    }
    
    // Update clock every second
    setInterval(updateClock, 1000);
    updateClock(); // Initial call
    
    // Set Indonesian locale for date (if strftime not available)
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
</script>
<?= $this->endSection() ?>