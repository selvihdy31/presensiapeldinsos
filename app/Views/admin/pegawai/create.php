<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Tambah Pegawai Baru</h5>
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

                <form action="<?= base_url('admin/pegawai/store') ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">NIP *</label>
                        <input type="text" class="form-control" name="nip" required value="<?= old('nip') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama" required value="<?= old('nama') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required value="<?= old('username') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bidang *</label>
                        <select class="form-select" name="bagian" required>
                            <option value="">-- Pilih Bidang --</option>
                            <option value="sekretariat" <?= old('bagian') == 'sekretariat' ? 'selected' : '' ?>>
                                Sekretariat
                            </option>
                            <option value="rehlinjamsos" <?= old('bagian') == 'rehlinjamsos' ? 'selected' : '' ?>>
                                Rehlinjamsos
                            </option>
                            <option value="dayasos" <?= old('bagian') == 'dayasos' ? 'selected' : '' ?>>
                                Dayasos
                            </option>
                        </select>
                    </div>
                    <!-- <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                    </div> -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan
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