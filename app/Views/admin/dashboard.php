<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-3 mb-4">
    <!-- Total Pegawai -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2 opacity-75">Total Pegawai</h6>
                        <h2 class="mb-0 fw-bold"><?= $total_pegawai ?></h2>
                    </div>
                    <div style="font-size: 50px; opacity: 0.3;">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Presensi Hari Ini -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2 opacity-75">Presensi Hari Ini</h6>
                        <h2 class="mb-0 fw-bold"><?= $presensi_hari_ini ?></h2>
                    </div>
                    <div style="font-size: 50px; opacity: 0.3;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- QR Code Aktif -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2 opacity-75">QR Code Aktif</h6>
                        <h2 class="mb-0 fw-bold"><?= $qr_aktif ?></h2>
                    </div>
                    <div style="font-size: 50px; opacity: 0.3;">
                        <i class="bi bi-qr-code"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="<?= base_url('admin/pegawai/create') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-plus-fill text-primary" style="font-size: 40px;"></i>
                <h6 class="mt-3 mb-0 text-dark">Tambah Pegawai</h6>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <a href="<?= base_url('admin/qrcode/create') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift">
            <div class="card-body text-center py-4">
                <i class="bi bi-qr-code text-success" style="font-size: 40px;"></i>
                <h6 class="mt-3 mb-0 text-dark">Buat QR Code</h6>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <a href="<?= base_url('admin/ijin') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift position-relative">
            <div class="card-body text-center py-4">
                <i class="bi bi-file-earmark-check text-danger" style="font-size: 40px;"></i>
                <h6 class="mt-3 mb-0 text-dark">Kelola Ijin</h6>
                <?php if(isset($ijin_pending) && $ijin_pending > 0): ?>
                    <span class="position-absolute top-0 end-0 m-2 badge bg-danger">
                        <?= $ijin_pending ?>
                    </span>
                <?php endif; ?>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <a href="<?= base_url('admin/laporan') ?>" class="card border-0 shadow-sm text-decoration-none hover-lift">
            <div class="card-body text-center py-4">
                <i class="bi bi-file-earmark-text text-warning" style="font-size: 40px;"></i>
                <h6 class="mt-3 mb-0 text-dark">Lihat Laporan</h6>
            </div>
        </a>
    </div>
</div>

<!-- Recent Presensi -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-clock-history"></i> Presensi Terbaru
        </h5>
    </div>
    <div class="card-body">
        <?php if(empty($recent_presensi)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size: 60px; opacity: 0.3;"></i>
                <p class="mt-3">Belum ada data presensi</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama</th>
                            <th>Waktu</th>
                            <th>Keterangan</th>
                            <th>Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($recent_presensi, 0, 5) as $p): ?>
                            <tr>
                                <td><?= $p['nip'] ?></td>
                                <td><?= $p['nama'] ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($p['waktu'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    // Tentukan warna badge berdasarkan keterangan
                                    $keterangan = strtolower($p['keterangan']);
                                    switch ($keterangan) {
                                        case 'hadir':
                                            $badgeClass = 'bg-success';
                                            $displayText = 'Hadir';
                                            break;
                                        case 'alpha':
                                            $badgeClass = 'bg-danger';
                                            $displayText = 'Alpha';
                                            break;
                                        case 'ijin':
                                            $badgeClass = 'bg-info';
                                            $displayText = 'Ijin';
                                            break;
                                        default:
                                            $badgeClass = 'bg-secondary';
                                            $displayText = ucfirst($keterangan);
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= $displayText ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= $p['lokasi'] ?? 'Tidak ada data' ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="<?= base_url('admin/laporan') ?>" class="btn btn-outline-primary">
                    Lihat Semua Presensi <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-lift {
        transition: all 0.3s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    }
</style>

<?= $this->endSection() ?>