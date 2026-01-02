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
                <div class="alert alert-warning mt-3">
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrCode;
    let latitude = null;
    let longitude = null;
    let lokasi = 'Lokasi tidak tersedia';
    let isSubmitting = false;
    const hasPresensi = <?= json_encode($has_presensi) ?>;
    const baseUrl = '<?= base_url() ?>';
    const submitUrl = baseUrl + 'pegawai/presensi/submit';

    console.log('Submit URL:', submitUrl);
    console.log('Has Presensi:', hasPresensi);

    // Get GPS Location
    if (navigator.geolocation && !hasPresensi) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;
                
                document.getElementById('location-status').innerHTML = 
                    '<i class="bi bi-check-circle"></i> Lokasi GPS: ' + 
                    latitude.toFixed(6) + ', ' + longitude.toFixed(6);
                
                // Reverse Geocoding
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.display_name) {
                            lokasi = data.display_name;
                            document.getElementById('location-status').innerHTML = 
                                '<i class="bi bi-check-circle"></i> ' + lokasi;
                        }
                    })
                    .catch(err => {
                        lokasi = `${latitude}, ${longitude}`;
                    });
            },
            (error) => {
                document.getElementById('location-status').innerHTML = 
                    '<i class="bi bi-exclamation-triangle"></i> GPS tidak aktif. Presensi tetap dapat dilakukan.';
            }
        );
    }
    
    // Initialize QR Scanner
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
                console.log('QR Code detected:', decodedText);
                showStatus('success', '<i class="bi bi-check-circle"></i> QR Code berhasil di-scan!');
                
                html5QrCode.stop().then(() => {
                    console.log('Scanner stopped');
                    setTimeout(() => {
                        submitPresensi(decodedText);
                    }, 500);
                }).catch(err => {
                    console.log('Error stopping scanner:', err);
                    submitPresensi(decodedText);
                });
            }
        ).catch((err) => {
            console.error('Camera error:', err);
            showStatus('danger', 'Gagal mengakses kamera. Gunakan input manual.');
        });
    }
    
    // ========== FUNCTION: Show Error Modal & Refresh ==========
    function showErrorModal(message) {
        console.log('Showing error modal:', message);
        
        if (html5QrCode) {
            try {
                html5QrCode.stop();
            } catch(e) {}
        }
        
        // Update pesan error
        document.getElementById('error-message').textContent = 
            message || 'Token tidak ditemukan atau sudah kadaluarsa';
        
        // Tampilkan modal
        const errorModal = new bootstrap.Modal(
            document.getElementById('errorModal'), 
            { backdrop: 'static', keyboard: false }
        );
        errorModal.show();
        
        // Countdown & Refresh
        let countdown = 3;
        const countdownInterval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                console.log('Refreshing page...');
                location.reload();
            }
        }, 1000);
    }
    
    // Submit Presensi Function
    function submitPresensi(token, keterangan = 'hadir') {
        if (isSubmitting) {
            console.log('Already submitting...');
            return;
        }
        
        isSubmitting = true;
        console.log('Starting submit with token:', token.substring(0, 20) + '...');
        
        // Show loading modal
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), { backdrop: 'static', keyboard: false });
        loadingModal.show();
        
        showStatus('info', '<i class="bi bi-clock"></i> Mengirim data presensi...');
        
        // Prepare FormData
        const formData = new FormData();
        formData.append('token', token);
        formData.append('keterangan', keterangan);
        formData.append('latitude', latitude);
        formData.append('longitude', longitude);
        formData.append('lokasi', lokasi);
        
        console.log('Form data prepared, sending to:', submitUrl);
        
        // Send POST request
        fetch(submitUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response received, status:', response.status);
            console.log('Response type:', response.type);
            return response.json();
        })
        .then(data => {
            console.log('Response JSON:', data);
            loadingModal.hide();
            
            if (data.success) {
                console.log('✓ Presensi BERHASIL');
                
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('successModal'), { backdrop: 'static', keyboard: false });
                const status = data.data?.keterangan || 'Hadir';
                document.getElementById('success-status').textContent = status;
                successModal.show();
                
                // PENTING: Disable scanner dan form sebelum redirect
                if (html5QrCode) {
                    try {
                        html5QrCode.stop();
                    } catch(e) {}
                }
                
                // Redirect setelah 2 detik
                setTimeout(() => {
                    window.location.href = baseUrl + 'pegawai/presensi/riwayat';
                }, 2000);
            } else {
                console.log('✗ Presensi GAGAL:', data.message);
                
                // ========== PERBAIKAN: Deteksi Error Token Invalid ==========
                if (data.message && (
                    data.message.toLowerCase().includes('token') || 
                    data.message.toLowerCase().includes('valid') || 
                    data.message.toLowerCase().includes('qr') ||
                    data.message.toLowerCase().includes('tidak ditemukan') ||
                    data.message.toLowerCase().includes('kadaluarsa')
                )) {
                    // ERROR TOKEN INVALID → Show error modal & refresh
                    console.log('Detected token invalid error');
                    showErrorModal(data.message);
                } else {
                    // ERROR LAIN (sudah presensi, user invalid, dll) → Show alert & restart scanner
                    console.log('Detected other error');
                    showStatus('danger', '<i class="bi bi-x-circle"></i> ' + data.message);
                    isSubmitting = false;
                    restartScanner();
                }
            }
        })
        .catch(err => {
            console.error('✗ FETCH ERROR:', err);
            loadingModal.hide();
            showStatus('danger', '<i class="bi bi-x-circle"></i> ' + err.message);
            isSubmitting = false;
            
            // Restart scanner
            restartScanner();
        });
    }
    
    // Restart Scanner
    function restartScanner() {
        console.log('Restarting scanner...');
        if (html5QrCode) {
            try {
                html5QrCode.stop();
            } catch(e) {}
            
            setTimeout(() => {
                const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                html5QrCode.start(
                    { facingMode: "environment" }, 
                    config, 
                    (decodedText) => {
                        console.log('QR re-detected:', decodedText);
                        html5QrCode.stop().then(() => {
                            submitPresensi(decodedText);
                        });
                    }
                ).catch(e => {
                    console.error('Error restarting scanner:', e);
                });
            }, 1500);
        }
    }
    
    // Manual Form Submit
    document.getElementById('manual-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const token = document.getElementById('manual-token').value.trim();
        const keterangan = document.getElementById('manual-keterangan').value;
        
        if (!token) {
            showStatus('warning', '<i class="bi bi-exclamation-triangle"></i> Token harus diisi!');
            return;
        }
        
        console.log('Manual submit with token:', token.substring(0, 20) + '...');
        
        if (html5QrCode) {
            try {
                html5QrCode.stop();
            } catch(e) {}
        }
        
        submitPresensi(token, keterangan);
    });
    
    // Show Status Helper
    function showStatus(type, message) {
        const statusDiv = document.getElementById('scan-status');
        if (statusDiv) {
            statusDiv.className = `alert alert-${type}`;
            statusDiv.innerHTML = message;
        }
    }
    
    // Cleanup
    window.addEventListener('beforeunload', () => {
        if (html5QrCode) {
            try {
                html5QrCode.stop();
            } catch(e) {}
        }
    });

    console.log('Script loaded and ready');
</script>
<?= $this->endSection() ?>