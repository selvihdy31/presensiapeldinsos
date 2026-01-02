<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-file-earmark-check"></i> Riwayat Pengajuan Ijin/Sakit
            </h4>
            <a href="<?= base_url('pegawai/presensi/ijin') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Ajukan Ijin Baru
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if(session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= session('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= session('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Total Pengajuan</h6>
                        <h2 class="mb-0 mt-2"><?= $stats['total'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Menunggu Persetujuan</h6>
                        <h2 class="mb-0 mt-2"><?= $stats['menunggu'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Disetujui</h6>
                        <h2 class="mb-0 mt-2"><?= $stats['disetujui'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Ditolak</h6>
                        <h2 class="mb-0 mt-2"><?= $stats['ditolak'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Daftar Pengajuan Ijin</h6>
            </div>
            <div class="card-body p-0">
                <?php 
                    $ijin = $ijin ?? [];
                    if(!empty($ijin) && is_array($ijin)): 
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Tanggal</th>
                                    <th style="width: 30%;">Keterangan</th>
                                    <th style="width: 15%;">Status</th>
                                    <th style="width: 25%;">Tanggal Pengajuan</th>
                                    <th style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ijin as $item): ?>
                                    <?php 
                                        $statusBadge = match($item['status']) {
                                            'menunggu' => 'warning',
                                            'disetujui' => 'success',
                                            'ditolak' => 'danger',
                                            default => 'secondary'
                                        };
                                        
                                        $statusLabel = match($item['status']) {
                                            'menunggu' => 'Menunggu',
                                            'disetujui' => 'Disetujui',
                                            'ditolak' => 'Ditolak',
                                            default => 'Unknown'
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('d/m/Y', strtotime($item['tanggal'])) ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-block" title="<?= esc($item['keterangan']) ?>">
                                                <?= substr(esc($item['keterangan']), 0, 50) ?>...
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statusBadge ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                            <?php if(!empty($item['keterangan_admin'])): ?>
                                                <br><small class="text-muted">
                                                    <i class="bi bi-chat-square-text-fill"></i> Ada keterangan
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?= $item['id'] ?>">
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="detailModal<?= $item['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-light">
                                                    <h6 class="modal-title">Detail Pengajuan Ijin</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <small class="text-muted">Tanggal Ijin</small>
                                                        <p class="mb-0">
                                                            <strong><?= date('d F Y', strtotime($item['tanggal'])) ?></strong>
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <small class="text-muted">Status</small>
                                                        <p class="mb-0">
                                                            <span class="badge bg-<?= $statusBadge ?> px-3 py-2">
                                                                <?= strtoupper($statusLabel) ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <small class="text-muted">Keterangan Anda</small>
                                                        <p class="mb-0 text-wrap">
                                                            <?= nl2br(esc($item['keterangan'])) ?>
                                                        </p>
                                                    </div>
                                                    
                                                    <!-- Keterangan dari Admin -->
                                                    <?php if(!empty($item['keterangan_admin'])): ?>
                                                        <div class="mb-3 border-top pt-3">
                                                            <small class="text-muted d-flex align-items-center">
                                                                <i class="bi bi-person-badge me-2"></i>
                                                                Keterangan dari Admin
                                                            </small>
                                                            <div class="alert alert-<?= $statusBadge ?> mb-0 mt-2">
                                                                <strong><?= $statusLabel === 'Disetujui' ? 'Persetujuan:' : 'Alasan Penolakan:' ?></strong>
                                                                <p class="mb-0 mt-2"><?= nl2br(esc($item['keterangan_admin'])) ?></p>
                                                                
                                                                <?php if(!empty($item['validator_nama'])): ?>
                                                                    <hr class="my-2">
                                                                    <small>
                                                                        <i class="bi bi-person-check-fill me-1"></i>
                                                                        Oleh: <strong><?= $item['validator_nama'] ?></strong>
                                                                    </small>
                                                                <?php endif; ?>
                                                                
                                                                <?php if(!empty($item['validated_at'])): ?>
                                                                    <br>
                                                                    <small>
                                                                        <i class="bi bi-clock-fill me-1"></i>
                                                                        <?= date('d/m/Y H:i', strtotime($item['validated_at'])) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="mb-3 border-top pt-3">
                                                        <small class="text-muted">Tanggal Pengajuan</small>
                                                        <p class="mb-0">
                                                            <?= date('d/m/Y H:i:s', strtotime($item['created_at'])) ?>
                                                        </p>
                                                    </div>

                                                    <!-- Info Status -->
                                                    <div class="alert alert-<?= $statusBadge === 'warning' ? 'info' : $statusBadge ?> small mt-3 mb-0">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <?php if($item['status'] === 'menunggu'): ?>
                                                            Pengajuan Anda sedang menunggu persetujuan dari admin/pimpinan
                                                        <?php elseif($item['status'] === 'disetujui'): ?>
                                                            <strong>Pengajuan Anda telah disetujui!</strong>
                                                            <?php if(!empty($item['keterangan_admin'])): ?>
                                                                <br>Silakan baca keterangan persetujuan di atas.
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <strong>Pengajuan Anda telah ditolak</strong>
                                                            <?php if(!empty($item['keterangan_admin'])): ?>
                                                                <br>Silakan baca alasan penolakan di atas.
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        Tutup
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3 mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Belum ada pengajuan ijin. <a href="<?= base_url('pegawai/presensi/ijin') ?>">Ajukan sekarang</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>