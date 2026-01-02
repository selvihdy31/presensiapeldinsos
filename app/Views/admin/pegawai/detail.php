<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Detail Presensi
                </h5>
                <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <!-- Status Badge -->
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="bi bi-<?= $presensi['keterangan'] == 'hadir' ? 'check-circle-fill text-success' : ($presensi['keterangan'] == 'terlambat' ? 'exclamation-circle-fill text-warning' : ($presensi['keterangan'] == 'ijin' ? 'file-earmark-text-fill text-info' : 'x-circle-fill text-danger')) ?>" style="font-size: 80px;"></i>
                    </div>
                    <h3>
                        <span class="badge bg-<?= $presensi['keterangan'] == 'hadir' ? 'success' : ($presensi['keterangan'] == 'terlambat' ? 'warning' : ($presensi['keterangan'] == 'ijin' ? 'info' : 'danger')) ?> px-4 py-2">
                            <?= strtoupper($presensi['keterangan']) ?>
                        </span>
                    </h3>
                </div>

                <!-- Detail Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-check text-primary" style="font-size: 30px;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Tanggal</small>
                                        <strong><?= date('d F Y', strtotime($presensi['waktu'])) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-clock text-success" style="font-size: 30px;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Waktu</small>
                                        <strong><?= date('H:i:s', strtotime($presensi['waktu'])) ?> WIB</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if($presensi['latitude'] && $presensi['longitude']): ?>
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-geo-alt text-danger" style="font-size: 30px;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Koordinat GPS</small>
                                        <strong><?= $presensi['latitude'] ?>, <?= $presensi['longitude'] ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-pin-map text-info" style="font-size: 30px;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block">Lokasi</small>
                                        <strong><?= $presensi['lokasi'] ?? 'Lokasi tidak tersedia' ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Keterangan Admin untuk Presensi Ijin -->
                <?php if($presensi['keterangan'] === 'ijin'): ?>
                    <?php
                    // Ambil data ijin terkait dari database
                    $db = \Config\Database::connect();
                    $ijinData = $db->table('ijin')
                        ->select('ijin.*, validator.nama as validator_nama')
                        ->join('users as validator', 'validator.id = ijin.validated_by', 'left')
                        ->where('ijin.user_id', $presensi['user_id'])
                        ->where('DATE(ijin.tanggal)', date('Y-m-d', strtotime($presensi['waktu'])))
                        ->where('ijin.status', 'disetujui')
                        ->get()
                        ->getRowArray();
                    ?>
                    
                    <?php if($ijinData && !empty($ijinData['keterangan_admin'])): ?>
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-person-badge"></i> Informasi Validasi Ijin
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Keterangan Persetujuan</small>
                                    <div class="alert alert-info mb-0">
                                        <?= nl2br(esc($ijinData['keterangan_admin'])) ?>
                                    </div>
                                </div>
                                
                                <?php if(!empty($ijinData['validator_nama'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Disetujui oleh:</small>
                                    <p class="mb-0"><strong><?= $ijinData['validator_nama'] ?></strong></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($ijinData['validated_at'])): ?>
                                <div>
                                    <small class="text-muted">Tanggal Validasi:</small>
                                    <p class="mb-0"><?= date('d/m/Y H:i:s', strtotime($ijinData['validated_at'])) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <div>
                                    <small class="text-muted">Alasan Ijin:</small>
                                    <p class="mb-0"><?= nl2br(esc($ijinData['keterangan'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Map (if coordinates available) -->
                <?php if($presensi['latitude'] && $presensi['longitude']): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="bi bi-map"></i> Peta Lokasi Presensi
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="map" style="height: 400px; border-radius: 0 0 8px 8px;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-grid gap-2 mt-4">
                    <!-- <a href="<?= base_url('pegawai/presensi/cetak-bukti/' . $presensi['id']) ?>" 
                       class="btn btn-primary" 
                       target="_blank">
                        <i class="bi bi-printer"></i> Cetak Bukti Presensi
                    </a> -->
                    <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if($presensi['latitude'] && $presensi['longitude']): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize map
    const map = L.map('map').setView([<?= $presensi['latitude'] ?>, <?= $presensi['longitude'] ?>], 15);
    
    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add marker
    const marker = L.marker([<?= $presensi['latitude'] ?>, <?= $presensi['longitude'] ?>]).addTo(map);
    marker.bindPopup('<b>Lokasi Presensi</b><br><?= date('d/m/Y H:i', strtotime($presensi['waktu'])) ?>').openPopup();
</script>
<?php endif; ?>
<?= $this->endSection() ?>