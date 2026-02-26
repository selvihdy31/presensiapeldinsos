<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people"></i> Daftar Pegawai</h5>
    <div class="d-flex gap-2">
        <!-- Tombol Kelola Bagian -->
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalBagian">
            <i class="bi bi-diagram-3"></i> Kelola Bagian
        </button>
        <a href="<?= base_url('admin/pegawai/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Pegawai
        </a>
    </div>
</div>

<?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(session()->getFlashdata('success_bagian')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success_bagian') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(session()->getFlashdata('error_bagian')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error_bagian') ?>
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
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Bidang</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pegawai)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada data pegawai</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach($pegawai as $p): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($p['nip']) ?></td>
                                <td><?= esc($p['nama']) ?></td>
                                <td><?= esc($p['username']) ?></td>
                                <td>
                                    <?php if(!empty($p['bagian'])): ?>
                                        <span class="badge" style="background-color: <?= getBagianColor($p['bagian']) ?>">
                                            <?= esc($bagianOptions[$p['bagian']] ?? ucfirst($p['bagian'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($p['status'] == 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Non-Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/pegawai/edit/' . $p['id']) ?>"
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if($p['status'] == 'aktif'): ?>
                                        <a href="<?= base_url('admin/pegawai/toggleStatus/' . $p['id']) ?>"
                                           class="btn btn-sm btn-secondary"
                                           onclick="return confirm('Yakin ingin menonaktifkan pegawai ini?')"
                                           title="Nonaktifkan">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/pegawai/toggleStatus/' . $p['id']) ?>"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Yakin ingin mengaktifkan pegawai ini?')"
                                           title="Aktifkan">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== MODAL KELOLA BAGIAN ===== -->
<div class="modal fade" id="modalBagian" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-diagram-3"></i> Kelola Bidang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Form tambah bagian baru -->
                <form action="<?= base_url('admin/bagian/store') ?>" method="POST" class="mb-4">
                    <?= csrf_field() ?>
                    <label class="form-label fw-bold">Tambah Bidang Baru</label>
                    <div class="input-group">
                        <input type="text"
                               name="nama"
                               class="form-control"
                               placeholder="Contoh: Perlindungan Sosial"
                               maxlength="100"
                               required>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    </div>
                    <small class="text-muted">Kode bidang akan dibuat otomatis dari nama.</small>
                </form>

                <hr>

                <!-- Daftar bagian yang sudah ada -->
                <label class="form-label fw-bold">Daftar Bidang</label>
                <?php if(empty($daftarBagian)): ?>
                    <p class="text-muted small">Belum ada bidang.</p>
                <?php else: ?>
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Bidang</th>
                                <th>Kode</th>
                                <th class="text-center" width="80">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($daftarBagian as $b): ?>
                                <tr>
                                    <td><?= esc($b['nama']) ?></td>
                                    <td><code><?= esc($b['kode']) ?></code></td>
                                    <td class="text-center">
                                        <form action="<?= base_url('admin/bagian/delete/' . $b['id']) ?>" method="POST"
                                              onsubmit="return confirm('Hapus bagian \'<?= esc($b['nama']) ?>\'?')">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
// Helper: warna badge berdasarkan urutan index kode bagian
function getBagianColor($kode): string {
    $colors = ['#0d6efd','#0dcaf0','#ffc107','#198754','#6f42c1','#fd7e14','#dc3545','#20c997'];
    return $colors[abs(crc32($kode)) % count($colors)];
}
?>

<?= $this->endSection() ?>