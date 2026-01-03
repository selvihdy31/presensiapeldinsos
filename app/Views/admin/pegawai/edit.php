<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Pegawai</h5>
            </div>
            <div class="card-body">
                <?php if(session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach(session()->getFlashdata('errors') as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('admin/pegawai/update/' . $pegawai['id']) ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">NIP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nip" required 
                               value="<?= $pegawai['nip'] ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" required 
                               value="<?= $pegawai['nama'] ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required 
                               value="<?= $pegawai['username'] ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" class="form-control" name="password">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bidang <span class="text-danger">*</span></label>
                        <select class="form-select" name="bagian" required>
                            <option value="">-- Pilih Bidang --</option>
                            <option value="sekretariat" <?= (isset($pegawai['bagian']) && $pegawai['bagian'] == 'sekretariat') ? 'selected' : '' ?>>
                                Sekretariat
                            </option>
                            <option value="rehlinjamsos" <?= (isset($pegawai['bagian']) && $pegawai['bagian'] == 'rehlinjamsos') ? 'selected' : '' ?>>
                                Rehlinjamsos
                            </option>
                            <option value="dayasos" <?= (isset($pegawai['bagian']) && $pegawai['bagian'] == 'dayasos') ? 'selected' : '' ?>>
                                Dayasos
                            </option>
                        </select>
                    </div>
                    
                    <!-- <div class="mb-3">
                        <label class="form-label">Foto Baru</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <?php if(isset($pegawai['foto']) && $pegawai['foto']): ?>
                            <div class="mt-2">
                                <small class="text-muted">Foto saat ini: <?= $pegawai['foto'] ?></small><br>
                                <img src="<?= base_url('uploads/pegawai/' . $pegawai['foto']) ?>" 
                                     alt="Foto Pegawai" class="img-thumbnail mt-2" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                    </div> -->
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Update
                        </button>
                        <a href="<?= base_url('admin/pegawai') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>