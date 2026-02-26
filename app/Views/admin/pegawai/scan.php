<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-qr-code-scan"></i> Scan QR Code Presensi</h5>
            </div>
            <div class="card-body">
                <!-- Alert jika sudah presensi hari ini -->
                <?php if ($has_presensi): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> 
                        <strong>Anda sudah melakukan presensi hari ini!</strong>
                        <br>Presensi hanya dapat dilakukan 1x per hari.
                        <br>
                        <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-clock-history"></i> Lihat Riwayat
                        </a>
                    </div>
                <?php else: ?>
                    
                    <!-- QR Scanner -->
                    <div id="qr-reader" class="mb-3" style="border-radius: 10px; overflow: hidden; max-width: 500px; margin: 0 auto;"></div>
                    
                    <!-- Status -->
                    <div id="scan-status" class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Arahkan kamera ke QR Code untuk mulai scan
                    </div>
                    
                    <!-- Manual Input (Fallback) -->
                    <div class="card bg-light mt-3">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bi bi-keyboard"></i> Atau Input Manual Token
                            </h6>
                            <form id="manual-form">
                                <div class="mb-3">
                                    <label class="form-label">Token QR Code</label>
                                    <input type="text" class="form-control" id="manual-token" 
                                           placeholder="Paste token dari QR Code" required>
                                    <small class="text-muted">Token berupa kode alfanumerik panjang</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <select class="form-select" id="manual-keterangan">
                                        <option value="hadir">Hadir</option>
                                        <option value="terlambat">Terlambat</option>
                                        <option value="ijin">Ijin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btn-manual-submit">
                                    <i class="bi bi-send"></i> Submit Manual
                                </button>
                            </form>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
                <!-- Location Info -->
                <div id="location-alert" class="alert alert-warning mt-3">
                    <i class="bi bi-geo-alt"></i> 
                    <span id="location-status">Mendapatkan lokasi GPS...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Loading -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">Memproses presensi...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Success -->
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-success">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3">Presensi Berhasil!</h5>
                <p id="success-message" class="mb-0 text-muted">Status: <strong id="success-status">Hadir</strong></p>
                <p class="small text-muted mt-2">Mengalihkan ke halaman riwayat...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Error - TOKEN INVALID -->
<div class="modal fade" id="errorModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-danger">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3">QR Code Tidak Valid!</h5>
                <p id="error-message" class="mb-3 text-muted">
                    Token tidak ditemukan atau sudah kadaluarsa
                </p>
                <p class="small text-muted">
                    Halaman akan refresh dalam <span id="countdown">3</span> detik...
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lokasi Di Luar Radius -->
<div class="modal fade" id="locationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-warning">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-geo-alt-fill text-warning" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-2">Di Luar Jangkauan Kantor</h5>
                <p id="location-modal-message" class="mb-3 text-muted small">
                    Anda berada di luar radius kantor.
                </p>
                <button class="btn btn-warning btn-sm" onclick="tutupLocationModal()">
                    <i class="bi bi-arrow-clockwise"></i> Coba Lagi
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    // ===== KONFIGURASI LOKASI KANTOR =====
    const OFFICE_LAT   = <?= $office_lat ?? -6.9002095 ?>;
    const OFFICE_LNG   = <?= $office_lng ?? 109.7166471 ?>;
    const MAX_RADIUS_M = <?= $max_radius ?? 10 ?>;

    let html5QrCode;
    let latitude     = null;
    let longitude    = null;
    let lokasi       = 'Lokasi tidak tersedia';
    let lokasiValid  = false;
    let isSubmitting = false;
    const hasPresensi = <?= json_encode($has_presensi) ?>;
    const baseUrl     = '<?= base_url() ?>';
    const submitUrl   = baseUrl + 'pegawai/presensi/submit';

    // ===== FORMULA HAVERSINE =====
    function hitungJarak(lat1, lng1, lat2, lng2) {
        const R    = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a    = Math.sin(dLat/2) * Math.sin(dLat/2)
                   + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                   * Math.sin(dLng/2) * Math.sin(dLng/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    // ===== GET GPS LOCATION =====
    if (navigator.geolocation && !hasPresensi) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitude  = position.coords.latitude;
                longitude = position.coords.longitude;

                const jarak      = hitungJarak(latitude, longitude, OFFICE_LAT, OFFICE_LNG);
                const jarakBulat = Math.round(jarak);

                if (jarak <= MAX_RADIUS_M) {
                    // Dalam radius — tampilkan hijau
                    lokasiValid = true;
                    document.getElementById('location-alert').className = 'alert alert-success mt-3';
                    document.getElementById('location-status').innerHTML =
                        '<i class="bi bi-check-circle-fill"></i> Lokasi valid &mdash; Anda berada <strong>' +
                        jarakBulat + ' meter</strong> dari kantor. Silakan scan QR Code.';
                } else {
                    // Di luar radius — tampilkan merah
                    lokasiValid = false;
                    document.getElementById('location-alert').className = 'alert alert-danger mt-3';
                    document.getElementById('location-status').innerHTML =
                        '<i class="bi bi-x-circle-fill"></i> Lokasi tidak valid &mdash; Anda berada <strong>' +
                        jarakBulat + ' meter</strong> dari kantor. ' +
                        'Presensi hanya diizinkan dalam radius <strong>' + MAX_RADIUS_M + ' meter</strong>.';
                }

                // Reverse Geocoding untuk nama lokasi
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.display_name) lokasi = data.display_name;
                    })
                    .catch(() => {
                        lokasi = `${latitude}, ${longitude}`;
                    });
            },
            (error) => {
                lokasiValid = false;
                const pesan = {
                    1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser.',
                    2: 'Posisi tidak tersedia. Pastikan GPS aktif.',
                    3: 'Waktu habis mendapatkan lokasi. Coba refresh halaman.'
                };
                document.getElementById('location-alert').className = 'alert alert-danger mt-3';
                document.getElementById('location-status').innerHTML =
                    '<i class="bi bi-exclamation-triangle-fill"></i> ' +
                    (pesan[error.code] || 'GPS tidak aktif. Aktifkan GPS untuk melakukan presensi.');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
    
    // ===== INITIALIZE QR SCANNER =====
    if (!hasPresensi) {
        html5QrCode = new Html5Qrcode("qr-reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                showStatus('success', '<i class="bi bi-check-circle"></i> QR Code berhasil di-scan!');
                
                html5QrCode.stop().then(() => {
                    setTimeout(() => { submitPresensi(decodedText); }, 500);
                }).catch(() => {
                    submitPresensi(decodedText);
                });
            }
        ).catch(() => {
            showStatus('danger', 'Gagal mengakses kamera. Gunakan input manual.');
        });
    }
    
    // ===== MODAL ERROR TOKEN =====
    function showErrorModal(message) {
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e) {} }
        
        document.getElementById('error-message').textContent =
            message || 'Token tidak ditemukan atau sudah kadaluarsa';
        
        const errorModal = new bootstrap.Modal(
            document.getElementById('errorModal'),
            { backdrop: 'static', keyboard: false }
        );
        errorModal.show();
        
        let countdown = 3;
        const iv = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            if (countdown <= 0) { clearInterval(iv); location.reload(); }
        }, 1000);
    }

    // ===== MODAL LOKASI DILUAR RADIUS =====
    let locationModalInstance = null;

    function showLocationModal(message) {
        document.getElementById('location-modal-message').textContent = message;
        locationModalInstance = new bootstrap.Modal(
            document.getElementById('locationModal'),
            { backdrop: 'static', keyboard: false }
        );
        locationModalInstance.show();
    }

    function tutupLocationModal() {
        if (locationModalInstance) {
            locationModalInstance.hide();
            locationModalInstance = null;
        }
        isSubmitting = false;
        restartScanner();
    }
    
    // ===== SUBMIT PRESENSI =====
    function submitPresensi(token, keterangan = 'hadir') {
        if (isSubmitting) return;

        // ===== CEK LOKASI VALID SEBELUM SUBMIT =====
        if (!lokasiValid) {
            const msg = latitude !== null
                ? `Anda berada di luar radius ${MAX_RADIUS_M} meter dari kantor. Presensi tidak dapat dilakukan.`
                : 'Lokasi GPS belum tersedia. Pastikan GPS aktif dan izin lokasi sudah diberikan.';
            showLocationModal(msg);
            return;
        }

        isSubmitting = true;
        
        const loadingModal = new bootstrap.Modal(
            document.getElementById('loadingModal'),
            { backdrop: 'static', keyboard: false }
        );
        loadingModal.show();
        showStatus('info', '<i class="bi bi-clock"></i> Mengirim data presensi...');
        
        const formData = new FormData();
        formData.append('token',      token);
        formData.append('keterangan', keterangan);
        formData.append('latitude',   latitude);
        formData.append('longitude',  longitude);
        formData.append('lokasi',     lokasi);
        
        fetch(submitUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            loadingModal.hide();
            
            if (data.success) {
                const successModal = new bootstrap.Modal(
                    document.getElementById('successModal'),
                    { backdrop: 'static', keyboard: false }
                );
                document.getElementById('success-status').textContent = data.data?.keterangan || 'Hadir';
                successModal.show();
                
                if (html5QrCode) { try { html5QrCode.stop(); } catch(e) {} }
                setTimeout(() => { window.location.href = baseUrl + 'pegawai/presensi/riwayat'; }, 2000);

            } else {
                // ===== DETEKSI JENIS ERROR =====
                if (data.type === 'location_out_of_range') {
                    // Penolakan radius dari server (double-check keamanan)
                    showLocationModal(data.message);

                } else if (data.message && (
                    data.message.toLowerCase().includes('token') ||
                    data.message.toLowerCase().includes('valid') ||
                    data.message.toLowerCase().includes('qr') ||
                    data.message.toLowerCase().includes('tidak ditemukan') ||
                    data.message.toLowerCase().includes('kadaluarsa')
                )) {
                    // Token invalid → modal + auto refresh
                    showErrorModal(data.message);

                } else {
                    // Error lain (sudah presensi, dsb) → alert + restart scanner
                    showStatus('danger', '<i class="bi bi-x-circle"></i> ' + data.message);
                    isSubmitting = false;
                    restartScanner();
                }
            }
        })
        .catch(() => {
            loadingModal.hide();
            showStatus('danger', '<i class="bi bi-x-circle"></i> Gagal menghubungi server. Periksa koneksi internet.');
            isSubmitting = false;
            restartScanner();
        });
    }
    
    // ===== RESTART SCANNER =====
    function restartScanner() {
        if (html5QrCode) {
            try { html5QrCode.stop(); } catch(e) {}
            setTimeout(() => {
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        html5QrCode.stop().then(() => { submitPresensi(decodedText); });
                    }
                ).catch(e => console.error('Error restarting scanner:', e));
            }, 1500);
        }
    }
    
    // ===== MANUAL FORM SUBMIT =====
    document.getElementById('manual-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const token      = document.getElementById('manual-token').value.trim();
        const keterangan = document.getElementById('manual-keterangan').value;
        if (!token) {
            showStatus('warning', '<i class="bi bi-exclamation-triangle"></i> Token harus diisi!');
            return;
        }
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e) {} }
        submitPresensi(token, keterangan);
    });
    
    // ===== SHOW STATUS HELPER =====
    function showStatus(type, message) {
        const statusDiv = document.getElementById('scan-status');
        if (statusDiv) {
            statusDiv.className = `alert alert-${type}`;
            statusDiv.innerHTML = message;
        }
    }
    
    window.addEventListener('beforeunload', () => {
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e) {} }
    });
</script>
<?= $this->endSection() ?>