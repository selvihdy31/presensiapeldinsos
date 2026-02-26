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
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('admin/pegawai/store') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">NIP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nip" required value="<?= old('nip') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" required value="<?= old('nama') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required value="<?= old('username') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bidang <span class="text-danger">*</span></label>
                        <select class="form-select" name="bagian" required>
                            <option value="">-- Pilih Bidang --</option>
                            <?php foreach($bagianOptions as $kode => $nama): ?>
                                <option value="<?= esc($kode) ?>"
                                    <?= old('bagian') == $kode ? 'selected' : '' ?>>
                                    <?= esc($nama) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($bagianOptions)): ?>
                            <small class="text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                Belum ada bagian. <a href="#" data-bs-toggle="modal" data-bs-target="#modalBagian">Tambah bagian dulu</a>.
                            </small>
                        <?php endif; ?>
                    </div>
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