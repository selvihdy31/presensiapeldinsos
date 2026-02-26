<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-qr-code-scan"></i> Buat QR Code Baru</h5>
            </div>
            <div class="card-body">
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('admin/qrcode/store') ?>" method="POST" id="formQrCode">
                    <?= csrf_field() ?>
                    
                    <!-- Tanggal -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-calendar"></i> Tanggal Presensi
                            <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               class="form-control" 
                               name="tanggal" 
                               id="tanggal"
                               value="<?= old('tanggal') ?? date('Y-m-d') ?>" 
                               required>
                        <small class="form-text text-muted">Pilih tanggal untuk QR Code presensi</small>
                    </div>

                    <!-- Waktu Mulai -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-clock"></i> Waktu Mulai (WIB)
                            <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Jam</label>
                                <select class="form-select" name="waktu_mulai_jam" id="waktu_mulai_jam" required>
                                    <option value="">Pilih Jam</option>
                                    <?php for($h = 7; $h <= 9; $h++): ?>
                                        <option value="<?= sprintf('%02d', $h) ?>" <?= $h == 7 ? 'selected' : '' ?>>
                                            <?= sprintf('%02d', $h) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Menit</label>
                                <select class="form-select" name="waktu_mulai_menit" id="waktu_mulai_menit" required>
                                    <option value="">Pilih Menit</option>
                                    <?php for($m = 0; $m <= 59; $m += 5): ?>
                                        <option value="<?= sprintf('%02d', $m) ?>" <?= $m == 0 ? 'selected' : '' ?>>
                                            <?= sprintf('%02d', $m) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="waktu_mulai" id="waktu_mulai">
                        <small class="form-text text-muted">
                            <strong>Hanya jam 07:00 - 09:59 WIB yang diperbolehkan</strong>
                        </small>
                    </div>

                    <!-- Waktu Selesai -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-clock-fill"></i> Waktu Selesai (WIB)
                            <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Jam</label>
                                <select class="form-select" name="waktu_selesai_jam" id="waktu_selesai_jam" required>
                                    <option value="">Pilih Jam</option>
                                    <?php for($h = 7; $h <= 12; $h++): ?>
                                        <option value="<?= sprintf('%02d', $h) ?>" <?= $h == 12 ? 'selected' : '' ?>>
                                            <?= sprintf('%02d', $h) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Menit</label>
                                <select class="form-select" name="waktu_selesai_menit" id="waktu_selesai_menit" required>
                                    <option value="">Pilih Menit</option>
                                    <?php for($m = 0; $m <= 59; $m += 5): ?>
                                        <option value="<?= sprintf('%02d', $m) ?>" <?= $m == 0 ? 'selected' : '' ?>>
                                            <?= sprintf('%02d', $m) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="waktu_selesai" id="waktu_selesai">
                        <small class="form-text text-muted">
                            <strong>Hanya jam 07:00 - 12:59 WIB yang diperbolehkan</strong>
                        </small>
                    </div>

                    <!-- Info Box -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle"></i> Informasi Penting
                        </h6>
                        <ul class="mb-0 small">
                            <li>QR Code akan aktif sesuai rentang waktu yang ditentukan</li>
                            <li><strong>Waktu Mulai: Jam 07:00 - 09:59 WIB</strong></li>
                            <li><strong>Waktu Selesai: Jam 07:00 - 12:59 WIB</strong></li>
                            <li>Pegawai hanya bisa presensi dalam rentang waktu tersebut</li>
                            <li>Sistem otomatis menonaktifkan QR setelah waktu selesai</li>
                            <!-- <li>Pegawai yang terlambat lebih dari 15 menit akan tercatat "Terlambat"</li> -->
                        </ul>
                    </div>

                    <!-- Preview Waktu WIB -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-eye"></i> Preview Waktu
                            </h6>
                            <div id="preview-waktu">
                                <p class="mb-1">
                                    <strong>Mulai:</strong> 
                                    <span id="preview-mulai" class="text-primary">07:00 WIB</span>
                                </p>
                                <p class="mb-1">
                                    <strong>Selesai:</strong> 
                                    <span id="preview-selesai" class="text-danger">12:00 WIB</span>
                                </p>
                                <p class="mb-0">
                                    <strong>Durasi:</strong> 
                                    <span id="preview-durasi" class="text-success">5 jam</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?= base_url('admin/qrcode') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i class="bi bi-save"></i> Simpan QR Code
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formQrCode = document.getElementById('formQrCode');
    const tanggal = document.getElementById('tanggal');
    
    // Elements untuk waktu mulai
    const waktuMulaiJam = document.getElementById('waktu_mulai_jam');
    const waktuMulaiMenit = document.getElementById('waktu_mulai_menit');
    const waktuMulaiHidden = document.getElementById('waktu_mulai');
    
    // Elements untuk waktu selesai
    const waktuSelesaiJam = document.getElementById('waktu_selesai_jam');
    const waktuSelesaiMenit = document.getElementById('waktu_selesai_menit');
    const waktuSelesaiHidden = document.getElementById('waktu_selesai');
    
    // Update hidden input saat dropdown berubah
    function updateWaktuMulai() {
        const jam = waktuMulaiJam.value;
        const menit = waktuMulaiMenit.value;
        if (jam && menit) {
            waktuMulaiHidden.value = jam + ':' + menit;
            updatePreview();
        }
    }
    
    function updateWaktuSelesai() {
        const jam = waktuSelesaiJam.value;
        const menit = waktuSelesaiMenit.value;
        if (jam && menit) {
            waktuSelesaiHidden.value = jam + ':' + menit;
            updatePreview();
        }
    }
    
    // Event listeners
    waktuMulaiJam.addEventListener('change', updateWaktuMulai);
    waktuMulaiMenit.addEventListener('change', updateWaktuMulai);
    waktuSelesaiJam.addEventListener('change', updateWaktuSelesai);
    waktuSelesaiMenit.addEventListener('change', updateWaktuSelesai);
    
    // Initial update
    updateWaktuMulai();
    updateWaktuSelesai();
    
    // Preview waktu real-time
    function updatePreview() {
        const mulai = waktuMulaiHidden.value;
        const selesai = waktuSelesaiHidden.value;
        
        if (!mulai || !selesai) return;
        
        document.getElementById('preview-mulai').textContent = mulai + ' WIB';
        document.getElementById('preview-selesai').textContent = selesai + ' WIB';
        
        // Hitung durasi
        const [jamMulai, menitMulai] = mulai.split(':').map(Number);
        const [jamSelesai, menitSelesai] = selesai.split(':').map(Number);
        
        let totalMenit = (jamSelesai * 60 + menitSelesai) - (jamMulai * 60 + menitMulai);
        
        if (totalMenit < 0) totalMenit += 24 * 60; // Handle overnight
        
        const jam = Math.floor(totalMenit / 60);
        const menit = totalMenit % 60;
        
        let durasiText = '';
        if (jam > 0) durasiText += jam + ' jam ';
        if (menit > 0) durasiText += menit + ' menit';
        
        document.getElementById('preview-durasi').textContent = durasiText || '0 menit';
    }
    
    // Validasi form
    formQrCode.addEventListener('submit', function(e) {
        const mulai = waktuMulaiHidden.value;
        const selesai = waktuSelesaiHidden.value;
        const tgl = tanggal.value;
        
        // Validasi waktu
        if (!mulai || !selesai) {
            e.preventDefault();
            alert('Waktu mulai dan selesai harus diisi!');
            return false;
        }
        
        // Validasi batasan jam
        const [jamMulai, menitMulai] = mulai.split(':').map(Number);
        const [jamSelesai, menitSelesai] = selesai.split(':').map(Number);
        
        // Cek waktu mulai (hanya 07:00 - 09:59)
        if (jamMulai < 7 || jamMulai > 9) {
            e.preventDefault();
            alert('Waktu mulai harus antara jam 07:00 - 09:59 WIB!');
            waktuMulaiJam.focus();
            return false;
        }
        
        // Cek waktu selesai (hanya 07:00 - 12:59)
        if (jamSelesai < 7 || jamSelesai > 12) {
            e.preventDefault();
            alert('Waktu selesai harus antara jam 07:00 - 12:59 WIB!');
            waktuSelesaiJam.focus();
            return false;
        }
        
        // Validasi waktu selesai > waktu mulai
        const totalMenitMulai = jamMulai * 60 + menitMulai;
        const totalMenitSelesai = jamSelesai * 60 + menitSelesai;
        
        if (totalMenitSelesai <= totalMenitMulai) {
            e.preventDefault();
            alert('Waktu selesai harus lebih besar dari waktu mulai!');
            waktuSelesaiJam.focus();
            return false;
        }
        
        // Validasi tanggal tidak boleh kemarin
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(tgl);
        
        if (selectedDate < today) {
            e.preventDefault();
            alert('Tidak dapat membuat QR Code untuk tanggal yang sudah lewat!');
            tanggal.focus();
            return false;
        }
        
        // Confirm
        const confirm = window.confirm(
            `Buat QR Code untuk:\n\n` +
            `Tanggal: ${tgl}\n` +
            `Waktu: ${mulai} - ${selesai} WIB\n\n` +
            `Lanjutkan?`
        );
        
        if (!confirm) {
            e.preventDefault();
            return false;
        }
        
        // Disable button
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('btnSubmit').innerHTML = '<i class="spinner-border spinner-border-sm"></i> Menyimpan...';
    });
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    tanggal.setAttribute('min', today);
});
</script>

<?= $this->endSection() ?>