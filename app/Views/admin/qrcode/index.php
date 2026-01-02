<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-qr-code"></i> Daftar QR Code</h5>
    <a href="<?= base_url('admin/qrcode/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Buat QR Code
    </a>
</div>

<?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i> <?= session()->getFlashdata('info') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= session()->getFlashdata('warning') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">Tanggal</th>
                        <th width="15%">Jam Aktif (WIB)</th>
                        <th width="10%">Status</th>
                        <th width="12%">Dibuat</th>
                        <th width="43%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($qr_codes)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada QR Code</p>
                                <small class="text-muted">Klik tombol "Buat QR Code" untuk membuat yang baru</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Set timezone ke WIB
                        date_default_timezone_set('Asia/Jakarta');
                        
                        $no = 1; 
                        foreach($qr_codes as $qr): 
                            $now = date('Y-m-d H:i:s');
                            $selesai = $qr['tanggal'] . ' ' . $qr['waktu_selesai'];
                            $isExpired = ($now > $selesai);
                            
                            // Format waktu ke 24 jam
                            $waktuMulaiFormatted = date('H:i', strtotime($qr['waktu_mulai']));
                            $waktuSelesaiFormatted = date('H:i', strtotime($qr['waktu_selesai']));
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong class="d-block"><?= date('d/m/Y', strtotime($qr['tanggal'])) ?></strong>
                                    <small class="text-muted">
                                        <?php
                                        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                        echo $days[date('w', strtotime($qr['tanggal']))];
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <i class="bi bi-clock text-primary"></i> 
                                    <?= $waktuMulaiFormatted ?> - <?= $waktuSelesaiFormatted ?>
                                    <br>
                                    <small class="badge bg-secondary">WIB</small>
                                </td>
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
                                    
                                    <?php if ($isExpired): ?>
                                        <br>
                                        <small class="badge bg-warning text-dark mt-1">
                                            <i class="bi bi-clock-history"></i> Expired
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($qr['created_at'])) ?>
                                    <br>
                                    <small class="text-muted"><?= date('H:i', strtotime($qr['created_at'])) ?> WIB</small>
                                </td>
                                <td>
                                    <!-- Baris 1: Aksi Utama -->
                                    <div class="btn-group btn-group-sm mb-1" role="group">
                                        <a href="<?= base_url('admin/qrcode/generate/' . $qr['id']) ?>" 
                                           class="btn btn-primary" 
                                           title="Lihat QR Code">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
                                        <a href="<?= base_url('admin/qrcode/toggle/' . $qr['id']) ?>" 
                                           class="btn btn-warning" 
                                           title="Toggle Status">
                                            <i class="bi bi-toggle-<?= $qr['status'] === 'aktif' ? 'on' : 'off' ?>"></i>
                                        </a>
                                        <!-- <a href="<?= base_url('admin/qrcode/delete/' . $qr['id']) ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Yakin hapus QR Code ini? Data presensi tidak akan terhapus.')"
                                           title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a> -->
                                    </div>
                                    
                                    <!-- Baris 2: Laporan & Alpha -->
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= base_url('admin/qrcode/download-laporan/' . $qr['id']) ?>" 
                                           class="btn btn-success" 
                                           title="Download Laporan PDF">
                                            <i class="bi bi-file-pdf"></i> Laporan
                                        </a>
                                        
                                        <?php if ($isExpired): ?>
                                            <a href="<?= base_url('admin/qrcode/process-alpha/' . $qr['id']) ?>" 
                                               class="btn btn-dark"
                                               onclick="return confirm('Proses auto alpha untuk QR ini?\n\nPegawai yang belum presensi dan tidak ijin akan ditandai ALPHA.')"
                                               title="Proses Auto Alpha Manual">
                                                <i class="bi bi-person-x"></i> Alpha
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="alert alert-info mt-3">
    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Informasi</h6>
    <ul class="mb-0 small">
        <li><strong>Format Waktu:</strong> Semua waktu menggunakan format 24 jam (00:00 - 23:59) Waktu Indonesia Barat (WIB)</li>
        <li><strong>Auto Alpha:</strong> Sistem otomatis menandai pegawai yang tidak presensi sebagai "alpha" saat QR expired</li>
        <li><strong>Laporan:</strong> Download laporan presensi lengkap untuk QR Code tertentu dalam format PDF</li>
        <li><strong>Process Alpha:</strong> Tombol untuk trigger auto alpha secara manual jika diperlukan</li>
    </ul>
</div>

<?= $this->endSection() ?>