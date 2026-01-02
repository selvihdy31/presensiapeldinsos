<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-check"></i> Manajemen Pengajuan Ijin</h5>
    <div class="btn-group" role="group">
        <a href="<?= base_url('admin/ijin?filter=all') ?>" 
           class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?> btn-sm">
            Semua (<?= $stats['total'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('admin/ijin?filter=menunggu') ?>" 
           class="btn btn-<?= $filter === 'menunggu' ? 'warning' : 'outline-warning' ?> btn-sm">
            Menunggu (<?= $stats['menunggu'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('admin/ijin?filter=disetujui') ?>" 
           class="btn btn-<?= $filter === 'disetujui' ? 'success' : 'outline-success' ?> btn-sm">
            Disetujui (<?= $stats['disetujui'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('admin/ijin?filter=ditolak') ?>" 
           class="btn btn-<?= $filter === 'ditolak' ? 'danger' : 'outline-danger' ?> btn-sm">
            Ditolak (<?= $stats['ditolak'] ?? 0 ?>)
        </a>
    </div>
</div>

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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Tanggal Ijin</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($ijin)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 40px; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Tidak ada data pengajuan ijin</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1; 
                        $ijinData = is_array($ijin) ? $ijin : [];
                        foreach($ijinData as $item): 
                        ?>
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
                                <td><?= $no++ ?></td>
                                <td><small><?= $item['nip'] ?? '-' ?></small></td>
                                <td><strong><?= $item['nama'] ?? '-' ?></strong></td>
                                <td>
                                    <small><?= date('d/m/Y', strtotime($item['tanggal'])) ?></small>
                                </td>
                                <td>
                                    <small class="text-truncate d-block" title="<?= esc($item['keterangan']) ?>">
                                        <?= substr(esc($item['keterangan']), 0, 40) ?>...
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusBadge ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if($item['status'] === 'menunggu'): ?>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="approveIjin(<?= $item['id'] ?>, '<?= esc($item['nama']) ?>')">
                                            <i class="bi bi-check-circle"></i> Setujui
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="rejectIjin(<?= $item['id'] ?>, '<?= esc($item['nama']) ?>')">
                                            <i class="bi bi-x-circle"></i> Tolak
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal<?= $item['id'] ?>">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                    <?php endif; ?>
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
                                                <small class="text-muted">Nama Pegawai</small>
                                                <p class="mb-0"><strong><?= $item['nama'] ?? '-' ?></strong></p>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted">NIP</small>
                                                <p class="mb-0"><?= $item['nip'] ?? '-' ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted">Tanggal Ijin</small>
                                                <p class="mb-0"><?= date('d F Y', strtotime($item['tanggal'])) ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted">Status</small>
                                                <p class="mb-0">
                                                    <span class="badge bg-<?= $statusBadge ?>">
                                                        <?= ucfirst($statusLabel) ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted">Keterangan Pegawai</small>
                                                <p class="mb-0"><?= nl2br(esc($item['keterangan'])) ?></p>
                                            </div>
                                            
                                            <?php if(!empty($item['keterangan_admin'])): ?>
                                                <div class="mb-3 border-top pt-3">
                                                    <small class="text-muted">Keterangan Admin</small>
                                                    <div class="alert alert-<?= $statusBadge ?> mb-0 mt-2">
                                                        <?= nl2br(esc($item['keterangan_admin'])) ?>
                                                    </div>
                                                </div>
                                                <?php if(!empty($item['validator_nama'])): ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted">Divalidasi oleh</small>
                                                        <p class="mb-0"><strong><?= $item['validator_nama'] ?></strong></p>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if(!empty($item['validated_at'])): ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted">Tanggal Validasi</small>
                                                        <p class="mb-0"><?= date('d/m/Y H:i:s', strtotime($item['validated_at'])) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3 border-top pt-3">
                                                <small class="text-muted">Tanggal Pengajuan</small>
                                                <p class="mb-0"><?= date('d/m/Y H:i:s', strtotime($item['created_at'])) ?></p>
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Approve dengan Keterangan -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title">Setujui Pengajuan Ijin</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menyetujui pengajuan ijin?</p>
                <div id="approve-details"></div>
                
                <div class="mt-3">
                    <label for="approve-keterangan" class="form-label">Keterangan Persetujuan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="approve-keterangan" rows="3" 
                              placeholder="Masukkan keterangan persetujuan (wajib diisi)"></textarea>
                    <small class="text-muted">Keterangan ini akan dilihat oleh pegawai</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" onclick="submitApprove()">
                    <i class="bi bi-check-circle"></i> Setujui
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reject dengan Keterangan -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title">Tolak Pengajuan Ijin</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menolak pengajuan ijin?</p>
                <div id="reject-details"></div>
                
                <div class="mt-3">
                    <label for="reject-keterangan" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reject-keterangan" rows="3" 
                              placeholder="Masukkan alasan penolakan (wajib diisi)"></textarea>
                    <small class="text-muted">Alasan ini akan dilihat oleh pegawai</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitReject()">
                    <i class="bi bi-x-circle"></i> Tolak
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let currentIjinId = null;
    const baseUrl = '<?= base_url() ?>';

    function approveIjin(id, nama) {
        currentIjinId = id;
        document.getElementById('approve-details').innerHTML = `
            <div class="alert alert-info">
                <p class="mb-0"><strong>${nama}</strong></p>
            </div>
        `;
        document.getElementById('approve-keterangan').value = '';
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    }

    function rejectIjin(id, nama) {
        currentIjinId = id;
        document.getElementById('reject-details').innerHTML = `
            <div class="alert alert-danger">
                <p class="mb-0"><strong>${nama}</strong></p>
            </div>
        `;
        document.getElementById('reject-keterangan').value = '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    function submitApprove() {
        if (!currentIjinId) return;
        
        const keterangan = document.getElementById('approve-keterangan').value.trim();
        
        if (!keterangan) {
            alert('Keterangan persetujuan harus diisi');
            return;
        }
        
        fetch(`<?= base_url('admin/ijin/approve/') ?>${currentIjinId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                keterangan_admin: keterangan
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
                alert(data.message);
                location.reload();
            } else {
                alert('Gagal menyetujui ijin: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }

    function submitReject() {
        if (!currentIjinId) return;
        
        const keterangan = document.getElementById('reject-keterangan').value.trim();
        
        if (!keterangan) {
            alert('Alasan penolakan harus diisi');
            return;
        }
        
        fetch(`<?= base_url('admin/ijin/reject/') ?>${currentIjinId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                keterangan_admin: keterangan
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                alert(data.message);
                location.reload();
            } else {
                alert('Gagal menolak ijin: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }
</script>
<?= $this->endSection() ?>