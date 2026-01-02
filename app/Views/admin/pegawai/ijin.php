<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-file-text"></i> Pengajuan Ijin/Sakit
                </h5>
            </div>
            <div class="card-body">
                <!-- Info Alert -->
                <div class="alert alert-info d-flex align-items-start">
                    <i class="bi bi-info-circle-fill me-2" style="font-size: 24px;"></i>
                    <div>
                        <strong>Perhatian!</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Hanya 1 pengajuan ijin per hari diperbolehkan</strong></li>
                            <li>Pengajuan ijin hanya bisa untuk tanggal hari ini atau sebelumnya</li>
                            <li>Tidak dapat mengajukan ijin untuk tanggal yang sudah ada presensi</li>
                            <li>Keterangan wajib diisi minimal 10 karakter</li>
                        </ul>
                    </div>
                </div>

                <!-- Success Messages -->
                <?php if(session()->has('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= session('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if(session()->has('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Terjadi Kesalahan:</strong>
                        <ul class="mb-0 mt-2">
                            <?php 
                                $errors = session('errors');
                                if (is_array($errors)): 
                                    foreach($errors as $error): 
                            ?>
                                <li><?= esc($error) ?></li>
                            <?php 
                                    endforeach; 
                                endif; 
                            ?>
                        </ul>
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

                <!-- Form Pengajuan Ijin -->
                <form action="<?= base_url('pegawai/presensi/submit-ijin') ?>" method="POST" id="ijin-form">
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-calendar3"></i> Tanggal Ijin
                            <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               class="form-control form-control-lg" 
                               name="tanggal" 
                               id="tanggal"
                               max="<?= date('Y-m-d') ?>"
                               value="<?= old('tanggal') ?>"
                               required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Pilih tanggal ijin (maksimal hari ini)
                        </small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-chat-left-text"></i> Keterangan Ijin
                            <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                  name="keterangan" 
                                  id="keterangan_ijin"
                                  rows="6" 
                                  placeholder="Tuliskan alasan ijin Anda (minimal 10 karakter)..."
                                  required
                                  minlength="10"><?= old('keterangan') ?></textarea>
                        <div class="form-text">
                            <span id="char-count">0</span> / 10 karakter minimum
                        </div>
                    </div>

                    <!-- Preview Card -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-eye"></i> Preview Pengajuan
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Tanggal:</small>
                                    <p class="mb-1" id="preview-tanggal">-</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Status:</small>
                                    <p class="mb-1">
                                        <span class="badge bg-warning">MENUNGGU</span>
                                    </p>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted">Keterangan:</small>
                                    <p class="mb-0" id="preview-keterangan">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send"></i> Ajukan Ijin
                        </button>
                        <a href="<?= base_url('pegawai/presensi/ijin-riwayat') ?>" class="btn btn-outline-info">
                            <i class="bi bi-clock-history"></i> Lihat Riwayat Pengajuan
                        </a>
                        <a href="<?= base_url('pegawai/dashboard') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card mt-3 border-warning">
            <div class="card-body">
                <h6 class="card-title text-warning">
                    <i class="bi bi-lightbulb"></i> Tips Pengajuan Ijin
                </h6>
                <ul class="mb-0 small">
                    <li><strong>Ajukan ijin segera</strong> - Maksimal 1 pengajuan per hari</li>
                    <li>Berikan keterangan yang jelas dan lengkap</li>
                    <li>Simpan bukti pengajuan jika diperlukan</li>
                    <li>Untuk ijin lebih dari 1 hari, ajukan di hari berikutnya</li>
                    <li>Periksa riwayat pengajuan untuk melihat status approval</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Character counter
    const keteranganInput = document.getElementById('keterangan_ijin');
    const charCount = document.getElementById('char-count');
    
    keteranganInput.addEventListener('input', function() {
        charCount.textContent = this.value.length;
        
        if (this.value.length >= 10) {
            charCount.classList.remove('text-danger');
            charCount.classList.add('text-success');
        } else {
            charCount.classList.remove('text-success');
            charCount.classList.add('text-danger');
        }
        
        // Update preview
        document.getElementById('preview-keterangan').textContent = this.value || '-';
    });
    
    // Date preview
    const tanggalInput = document.getElementById('tanggal');
    tanggalInput.addEventListener('change', function() {
        if (this.value) {
            const date = new Date(this.value);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('preview-tanggal').textContent = date.toLocaleDateString('id-ID', options);
        } else {
            document.getElementById('preview-tanggal').textContent = '-';
        }
    });
    
    // Form validation
    document.getElementById('ijin-form').addEventListener('submit', function(e) {
        const keterangan = keteranganInput.value.trim();
        const tanggal = tanggalInput.value;
        
        if (!tanggal) {
            e.preventDefault();
            alert('Tanggal ijin harus diisi!');
            tanggalInput.focus();
            return false;
        }
        
        if (keterangan.length < 10) {
            e.preventDefault();
            alert('Keterangan ijin minimal 10 karakter!');
            keteranganInput.focus();
            return false;
        }
        
        // Konfirmasi sebelum submit
        const confirmSubmit = confirm(
            'Anda hanya bisa mengajukan 1 ijin per hari.\n\n' +
            'Apakah Anda yakin ingin mengajukan ijin untuk tanggal ' + 
            new Date(tanggal).toLocaleDateString('id-ID') + '?'
        );
        
        if (!confirmSubmit) {
            e.preventDefault();
            return false;
        }
    });
</script>
<?= $this->endSection() ?>