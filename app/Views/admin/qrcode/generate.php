<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Format waktu ke 24 jam
$waktuMulai = date('H:i', strtotime($qr['waktu_mulai']));
$waktuSelesai = date('H:i', strtotime($qr['waktu_selesai']));
$tanggalFormatted = date('d F Y', strtotime($qr['tanggal']));

// Hari dalam bahasa Indonesia
$days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$hari = $days[date('w', strtotime($qr['tanggal']))];

// Bulan dalam bahasa Indonesia
$months = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$bulan = $months[(int)date('n', strtotime($qr['tanggal']))];
$tanggalIndo = date('d', strtotime($qr['tanggal'])) . ' ' . $bulan . ' ' . date('Y', strtotime($qr['tanggal']));
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-qr-code"></i> QR Code Presensi
                    <!-- <span class="badge bg-warning float-end" id="refresh-indicator">
                        <i class="bi bi-arrow-clockwise"></i> Auto Refresh
                    </span> -->
                </h5>
            </div>
            <div class="card-body text-center">
                <!-- QR Code Image - akan di-update setiap 30 detik -->
                <div class="mb-4 p-3 bg-light rounded position-relative" style="min-height: 450px;">
                    <img id="qr-image" src="" alt="QR Code" class="img-fluid" style="max-width: 400px; display: none;">
                    
                    <!-- Countdown Timer -->
                    <div class="position-absolute top-0 end-0 m-3" style="z-index: 10;">
                        <span class="badge bg-danger fs-6" id="countdown-badge">
                            <i class="bi bi-clock-fill"></i> <span id="countdown">30</span>s
                        </span>
                    </div>
                    
                    <!-- Loading Indicator -->
                    <div id="qr-loading" class="position-absolute top-50 start-50 translate-middle">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Memuat QR Code...</p>
                    </div>
                    
                    <!-- Error Message -->
                    <div id="qr-error" class="alert alert-danger position-absolute top-50 start-50 translate-middle w-75" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span id="error-message">Gagal memuat QR Code</span>
                    </div>
                </div>

                <!-- Token Info (untuk debug - bisa dihapus di production) -->
                <div class="alert alert-secondary text-start small">
                    <strong><i class="bi bi-key"></i> Current Token:</strong><br>
                    <code id="current-token" style="font-size: 9px; word-break: break-all; display: block; margin-top: 5px;">Loading...</code>
                    <div class="mt-2" id="token-info" style="display: none;">
                        <small class="text-muted">
                            Token berganti setiap 30 detik • 
                            Next refresh: <span id="next-refresh-time">--:--:--</span>
                        </small>
                    </div>
                </div>

                <!-- Info QR Code -->
                <div class="alert alert-info text-start">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle"></i> Informasi QR Code
                    </h6>
                    <hr>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="40%"><strong>Hari, Tanggal:</strong></td>
                            <td><?= $hari ?>, <?= $tanggalIndo ?></td>
                        </tr>
                        <tr>
                            <td><strong>Jam Aktif:</strong></td>
                            <td>
                                <span class="badge bg-primary"><?= $waktuMulai ?> WIB</span>
                                <span class="mx-1">sampai</span>
                                <span class="badge bg-danger"><?= $waktuSelesai ?> WIB</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Durasi:</strong></td>
                            <td>
                                <?php
                                $mulaiTime = strtotime($qr['waktu_mulai']);
                                $selesaiTime = strtotime($qr['waktu_selesai']);
                                $durasi = ($selesaiTime - $mulaiTime) / 3600;
                                echo number_format($durasi, 1) . ' jam';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <?php if ($qr['status'] === 'aktif'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-x-circle"></i> Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Kondisi Saat Ini:</strong></td>
                            <td>
                                <span id="live-status-badge">
                                    <?php if ($is_in_active_time && $qr['status'] === 'aktif'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle-fill"></i> Sedang Bisa Digunakan
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-exclamation-triangle"></i> Tidak Bisa Digunakan Saat Ini
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <br>
                                <small class="text-muted">
                                    Waktu sekarang: <span id="current-time-display"><?= date('H:i:s') ?></span> WIB
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>ID QR:</strong></td>
                            <td><code>#<?= str_pad($qr['id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                        </tr>
                    </table>
                </div>

                <!-- Ketentuan Presensi -->
                <div class="alert alert-warning text-start">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-triangle"></i> Ketentuan Presensi
                    </h6>
                    <ul class="mb-0 small">
                        <li>Presensi <strong>HADIR</strong>: Scan sebelum atau tepat <?= date('H:i', strtotime($waktuMulai . ' +15 minutes')) ?> WIB</li>
                        <!-- <li>Presensi <strong>TERLAMBAT</strong>: Scan setelah <?= date('H:i', strtotime($waktuMulai . ' +15 minutes')) ?> WIB</li> -->
                        <li>Batas waktu scan: <?= $waktuSelesai ?> WIB</li>
                        <li>Pegawai hanya bisa presensi <strong>1 kali per hari</strong></li>
                        <li><strong class="text-danger">QR Code berganti otomatis setiap 30 detik</strong></li>
                        <li>QR Code otomatis nonaktif setelah jam <?= $waktuSelesai ?> WIB</li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <button onclick="window.print()" class="btn btn-success">
                        <i class="bi bi-printer"></i> Print QR Code
                    </button>
                    <a href="<?= base_url('admin/qrcode/toggle/' . $qr['id']) ?>" class="btn btn-warning">
                        <i class="bi bi-toggle-<?= $qr['status'] === 'aktif' ? 'off' : 'on' ?>"></i>
                        <?= $qr['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </a>
                    <a href="<?= base_url('admin/qrcode/download-laporan/' . $qr['id']) ?>" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> Laporan
                    </a>
                    <a href="<?= base_url('admin/qrcode') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Clock WIB -->
<div class="row justify-content-center mt-3">
    <div class="col-md-6">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h6><i class="bi bi-clock"></i> Waktu Saat Ini (WIB)</h6>
                <h2 id="current-time" class="mb-0 fw-bold"><?= date('H:i:s') ?></h2>
                <small><?= $hari ?>, <?= $tanggalIndo ?></small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .btn, .alert-heading, hr, #current-time, .alert-warning, 
    #countdown-badge, #token-info, #refresh-indicator { 
        display: none !important; 
    }
    .card { 
        box-shadow: none; 
        border: none; 
    }
    .card-header { 
        background: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #qr-image {
        display: block !important;
    }
}

/* Animation untuk countdown badge */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

#countdown-badge {
    animation: pulse 1s ease-in-out infinite;
}

/* Loading spinner animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// ========== GLOBAL VARIABLES ==========
const QR_CONFIG = {
    qrId: <?= $qr['id'] ?>,
    apiUrl: '<?= base_url('admin/qrcode/get-dynamic-token/') ?>',
    refreshInterval: 30, // detik
    qrApiBase: 'https://api.qrserver.com/v1/create-qr-code/'
};

let countdownInterval = null;
let remainingSeconds = 30;
let isRefreshing = false;

// ========== DOM ELEMENTS ==========
const elements = {
    qrImage: document.getElementById('qr-image'),
    currentToken: document.getElementById('current-token'),
    countdown: document.getElementById('countdown'),
    loadingEl: document.getElementById('qr-loading'),
    errorEl: document.getElementById('qr-error'),
    errorMessage: document.getElementById('error-message'),
    tokenInfo: document.getElementById('token-info'),
    nextRefreshTime: document.getElementById('next-refresh-time'),
    currentTimeDisplay: document.getElementById('current-time-display'),
    liveStatusBadge: document.getElementById('live-status-badge')
};

// ========== FUNGSI UTAMA: REFRESH QR CODE ==========
async function refreshQRCode() {
    if (isRefreshing) {
        console.log('Already refreshing, skipping...');
        return;
    }
    
    isRefreshing = true;
    
    try {
        // Show loading
        elements.qrImage.style.display = 'none';
        elements.loadingEl.style.display = 'block';
        elements.errorEl.style.display = 'none';
        
        // Fetch token dinamis dari server
        const response = await fetch(`${QR_CONFIG.apiUrl}${QR_CONFIG.qrId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Update QR Code image
            const qrApiUrl = `${QR_CONFIG.qrApiBase}?size=400x400&data=${encodeURIComponent(data.token)}`;
            
            // Preload image
            const img = new Image();
            img.onload = function() {
                elements.qrImage.src = qrApiUrl;
                elements.qrImage.style.display = 'block';
                elements.loadingEl.style.display = 'none';
            };
            img.onerror = function() {
                throw new Error('Failed to load QR image');
            };
            img.src = qrApiUrl;
            
            // Update token display
            elements.currentToken.textContent = data.token;
            elements.tokenInfo.style.display = 'block';
            
            // Update countdown
            remainingSeconds = data.remaining_seconds;
            elements.countdown.textContent = remainingSeconds;
            
            // Update next refresh time
            const nextDate = new Date(data.next_change * 1000);
            elements.nextRefreshTime.textContent = nextDate.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            // Update live status badge
            updateLiveStatus(data);
            
            console.log('✅ QR Code refreshed successfully:', {
                token: data.token.substring(0, 20) + '...',
                remaining: data.remaining_seconds,
                isActive: data.is_active
            });
            
        } else {
            throw new Error(data.message || 'Failed to get dynamic token');
        }
        
    } catch (error) {
        console.error('❌ Error refreshing QR code:', error);
        
        elements.qrImage.style.display = 'none';
        elements.loadingEl.style.display = 'none';
        elements.errorEl.style.display = 'block';
        elements.errorMessage.textContent = error.message || 'Gagal memuat QR Code';
        
    } finally {
        isRefreshing = false;
    }
}

// ========== UPDATE LIVE STATUS ==========
function updateLiveStatus(data) {
    const statusHtml = data.is_active 
        ? `<span class="badge bg-success">
               <i class="bi bi-check-circle-fill"></i> Sedang Bisa Digunakan
           </span>`
        : `<span class="badge bg-warning text-dark">
               <i class="bi bi-exclamation-triangle"></i> Tidak Bisa Digunakan Saat Ini
           </span>`;
    
    elements.liveStatusBadge.innerHTML = statusHtml;
}

// ========== UPDATE COUNTDOWN ==========
function updateCountdown() {
    if (remainingSeconds > 0) {
        elements.countdown.textContent = remainingSeconds;
        remainingSeconds--;
    } else {
        // Token akan berganti, refresh QR Code
        console.log('⏰ Token expired, refreshing QR Code...');
        refreshQRCode();
        remainingSeconds = QR_CONFIG.refreshInterval;
    }
}

// ========== REAL-TIME CLOCK ==========
function updateClock() {
    const now = new Date();
    
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const timeString = `${hours}:${minutes}:${seconds}`;
    
    document.getElementById('current-time').textContent = timeString;
    elements.currentTimeDisplay.textContent = timeString;
}

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dynamic QR Code System Initialized');
    console.log('📍 QR ID:', QR_CONFIG.qrId);
    console.log('⏱️  Refresh Interval:', QR_CONFIG.refreshInterval, 'seconds');
    
    // Load QR Code pertama kali
    refreshQRCode();
    
    // Update countdown setiap 1 detik
    countdownInterval = setInterval(updateCountdown, 1000);
    
    // Update clock setiap 1 detik
    setInterval(updateClock, 1000);
    updateClock(); // Initial call
    
    console.log('✅ QR Code auto-refresh system started');
});

// ========== CLEANUP ==========
window.addEventListener('beforeunload', function() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        console.log('🧹 Cleanup: Countdown interval cleared');
    }
});

// ========== VISIBILITY CHANGE HANDLER ==========
// Refresh QR saat user kembali ke tab ini
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('👁️  Tab visible, refreshing QR Code...');
        refreshQRCode();
    }
});
</script>

<?= $this->endSection() ?>