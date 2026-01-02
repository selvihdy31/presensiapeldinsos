<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people"></i> Daftar Pegawai</h5>
    <a href="<?= base_url('admin/pegawai/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Pegawai
    </a>
</div>

<?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pegawai)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada data pegawai</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach($pegawai as $p): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $p['nip'] ?></td>
                                <td><?= $p['nama'] ?></td>
                                <td><?= $p['username'] ?></td>
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
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
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

<?= $this->endSection() ?>