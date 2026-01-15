<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Filter Bagian -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= base_url('admin/dashboard') ?>" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 fw-bold">
                    <i class="bi bi-funnel"></i> Filter Berdasarkan Bidang:
                </label>
            </div>
            <div class="col-auto">
                <select name="bagian" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Bidang</option>
                    <option value="sekretariat" <?= isset($selected_bagian) && $selected_bagian == 'sekretariat' ? 'selected' : '' ?>>
                        Sekretariat
                    </option>
                    <option value="rehlinjamsos" <?= isset($selected_bagian) && $selected_bagian == 'rehlinjamsos' ? 'selected' : '' ?>>
                        Rehabilitasi dan Jaminan Sosial
                    </option>
                    <option value="dayasos" <?= isset($selected_bagian) && $selected_bagian == 'dayasos' ? 'selected' : '' ?>>
                        Pemberdayaan Sosial
                    </option>
                </select>
            </div>
            <?php if(isset($selected_bagian) && !empty($selected_bagian)): ?>
            <div class="col-auto">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Reset Filter
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Total Pegawai -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2 opacity-75">Total Pegawai</h6>
                        <h2 class="mb-0 fw-bold"><?= $total_pegawai ?></h2>
                        <?php if(isset($selected_bagian) && !empty($selected_bagian)): ?>
                            <small class="opacity-75">
                                <?= ucfirst($selected_bagian) ?>
                            </small>
                        <?php else: ?>
                            <small class="opacity-75">Semua Bidang</small>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 50px; opacity: 0.3;">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sudah Presensi Hari Ini -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2 opacity-75">Sudah Presensi</h6>
                        <h2 class="mb-0 fw-bold"><?= $presensi_hari_ini ?></h2>
                        <small class="opacity-75">
                            <?= $total_pegawai > 0 ? round(($presensi_hari_ini/$total_pegawai)*100, 1) : 0 ?>% dari total
                        </small>
                    </div>
                    <div style="font-size: 50px; opacity: 0.3;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Belum Presensi Hari Ini -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2 opacity-75">Belum Presensi</h6>
                        <h2 class="mb-0 fw-bold"><?= $belum_presensi ?></h2>
                        <small class="opacity-75">
                            <?= $total_pegawai > 0 ? round(($belum_presensi/$total_pegawai)*100, 1) : 0 ?>% dari total
                        </small>
                    </div>
                    <div style="font-size: 50px; opacity: 0.3;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Detail -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 text-muted">Hadir</h6>
                        <h4 class="mb-0 fw-bold"><?= $hadir_hari_ini ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-x-circle-fill text-danger fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 text-muted">Alpha</h6>
                        <h4 class="mb-0 fw-bold"><?= $alpha_hari_ini ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-file-earmark-check-fill text-info fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 text-muted">Ijin</h6>
                        <h4 class="mb-0 fw-bold"><?= $ijin_hari_ini ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-qr-code text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 text-muted">QR Aktif</h6>
                        <h4 class="mb-0 fw-bold"><?= $qr_aktif ?></h4>
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
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-clock-history"></i> Presensi Terbaru
        </h5>
        <?php if(isset($selected_bagian) && !empty($selected_bagian)): ?>
            <span class="badge bg-primary">
                Filter: <?= ucfirst($selected_bagian) ?>
            </span>
        <?php endif; ?>
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
                            <th>Bidang</th>
                            <th>Waktu</th>
                            <th>Keterangan</th>
                            <th>Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($recent_presensi, 0, 10) as $p): ?>
                            <tr>
                                <td><?= $p['nip'] ?></td>
                                <td><?= $p['nama'] ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst($p['bagian']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($p['waktu'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $keterangan = strtolower($p['keterangan']);
                                    switch ($keterangan) {
                                        case 'hadir':
                                            $badgeClass = 'bg-success';
                                            $displayText = 'Hadir';
                                            break;
                                        case 'terlambat':
                                            $badgeClass = 'bg-warning';
                                            $displayText = 'Terlambat';
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
                                        <?= $p['lokasi'] ?? '-' ?>
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
    <div class="watermark">
        <small>Sistem by Selvi Hidayah</small>
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
    .watermark {
        text-align: center;
        padding: 15px 30px;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
    }
    .watermark small {
        color: #6c757d;
        font-weight: 500;
    }
</style>

<?= $this->endSection() ?>