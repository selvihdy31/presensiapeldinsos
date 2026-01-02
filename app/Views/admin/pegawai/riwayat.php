<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-clock-history"></i> Riwayat Presensi Saya
        </h5>
        <div class="d-flex gap-2">
            <a href="<?= base_url('pegawai/presensi/scan') ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-qr-code-scan"></i> Scan Presensi
            </a>
            <a href="<?= base_url('pegawai/presensi/ijin') ?>" class="btn btn-sm btn-info">
                <i class="bi bi-file-earmark-text"></i> Ajukan Ijin
            </a>
        </div>
    </div>
    
    <!-- FILTER SECTION -->
    <div class="card-body border-bottom bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-funnel-fill text-primary"></i> Filter & Pencarian
            </h6>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFilterBtn">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
            </button>
        </div>
        
        <form id="filterForm" method="GET">
            <div class="row g-3">
                <!-- Filter Tahun -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-calendar3"></i> Tahun
                    </label>
                    <select class="form-select form-select-sm" name="tahun" id="filterTahun">
                        <option value="">Semua Tahun</option>
                        <?php
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= $currentYear - 5; $i--):
                        ?>
                            <option value="<?= $i ?>" <?= (isset($_GET['tahun']) && $_GET['tahun'] == $i) ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- Filter Dari Tanggal -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-calendar-date"></i> Dari Tanggal
                    </label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="dari_tanggal" 
                           id="filterDariTanggal"
                           value="<?= $_GET['dari_tanggal'] ?? '' ?>">
                </div>
                
                <!-- Filter Sampai Tanggal -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-calendar-check"></i> Sampai Tanggal
                    </label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="sampai_tanggal" 
                           id="filterSampaiTanggal"
                           value="<?= $_GET['sampai_tanggal'] ?? '' ?>">
                </div>
                
                <!-- Filter Keterangan -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-tag-fill"></i> Keterangan
                    </label>
                    <select class="form-select form-select-sm" name="keterangan" id="filterKeterangan">
                        <option value="">Semua Status</option>
                        <option value="hadir" <?= (isset($_GET['keterangan']) && $_GET['keterangan'] == 'hadir') ? 'selected' : '' ?>>
                            Hadir
                        </option>
                        <option value="terlambat" <?= (isset($_GET['keterangan']) && $_GET['keterangan'] == 'terlambat') ? 'selected' : '' ?>>
                            Terlambat
                        </option>
                        <option value="ijin" <?= (isset($_GET['keterangan']) && $_GET['keterangan'] == 'ijin') ? 'selected' : '' ?>>
                            Ijin
                        </option>
                        <option value="alpha" <?= (isset($_GET['keterangan']) && $_GET['keterangan'] == 'alpha') ? 'selected' : '' ?>>
                            Alpha
                        </option>
                    </select>
                </div>
                
                <!-- Tombol Filter -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-white">.</label>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i> Terapkan
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Info Filter Aktif -->
        <?php 
        $activeFilters = [];
        if(!empty($_GET['tahun'])) $activeFilters[] = 'Tahun: ' . $_GET['tahun'];
        if(!empty($_GET['dari_tanggal'])) $activeFilters[] = 'Dari: ' . date('d/m/Y', strtotime($_GET['dari_tanggal']));
        if(!empty($_GET['sampai_tanggal'])) $activeFilters[] = 'Sampai: ' . date('d/m/Y', strtotime($_GET['sampai_tanggal']));
        if(!empty($_GET['keterangan'])) $activeFilters[] = 'Status: ' . ucfirst($_GET['keterangan']);
        
        if(!empty($activeFilters)):
        ?>
            <div class="mt-3 p-2 bg-white rounded border border-primary">
                <small class="text-muted">
                    <i class="bi bi-filter-circle-fill text-primary"></i> 
                    <strong>Filter Aktif:</strong>
                    <?= implode(' | ', $activeFilters) ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <?php
        // FILTER DATA PRESENSI
        $filteredPresensi = $presensi;
        
        // Filter berdasarkan tahun
        if(!empty($_GET['tahun'])) {
            $filteredPresensi = array_filter($filteredPresensi, function($p) {
                return date('Y', strtotime($p['waktu'])) == $_GET['tahun'];
            });
        }
        
        // Filter berdasarkan dari tanggal
        if(!empty($_GET['dari_tanggal'])) {
            $filteredPresensi = array_filter($filteredPresensi, function($p) {
                return date('Y-m-d', strtotime($p['waktu'])) >= $_GET['dari_tanggal'];
            });
        }
        
        // Filter berdasarkan sampai tanggal
        if(!empty($_GET['sampai_tanggal'])) {
            $filteredPresensi = array_filter($filteredPresensi, function($p) {
                return date('Y-m-d', strtotime($p['waktu'])) <= $_GET['sampai_tanggal'];
            });
        }
        
        // Filter berdasarkan keterangan
        if(!empty($_GET['keterangan'])) {
            $filteredPresensi = array_filter($filteredPresensi, function($p) {
                return strtolower($p['keterangan']) == strtolower($_GET['keterangan']);
            });
        }
        
        // Reindex array setelah filter
        $filteredPresensi = array_values($filteredPresensi);
        ?>
        
        <?php if(empty($presensi)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size: 80px; opacity: 0.2;"></i>
                <h5 class="mt-4">Belum Ada Riwayat Presensi</h5>
                <p>Mulai presensi dengan scan QR Code</p>
                <a href="<?= base_url('pegawai/presensi/scan') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-qr-code-scan"></i> Scan Sekarang
                </a>
            </div>
        <?php elseif(empty($filteredPresensi)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-search" style="font-size: 80px; opacity: 0.2;"></i>
                <h5 class="mt-4">Tidak Ada Data yang Sesuai Filter</h5>
                <p>Coba ubah kriteria pencarian Anda</p>
                <button type="button" class="btn btn-secondary mt-3" id="resetFilterBtn2">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </button>
            </div>
        <?php else: ?>
            <!-- Info Hasil Filter -->
            <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-3">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>
                    Menampilkan <strong><?= count($filteredPresensi) ?></strong> dari <strong><?= count($presensi) ?></strong> total presensi
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 12%;">Tanggal</th>
                            <th style="width: 10%;">Waktu</th>
                            <th style="width: 15%;">Keterangan</th>
                            <th style="width: 15%;">Koordinat</th>
                            <th style="width: 30%;">Ket. Lanjutan</th>
                            <th style="width: 13%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        // Ambil semua data ijin yang disetujui untuk user ini
                        $db = \Config\Database::connect();
                        $userId = session()->get('user_id');
                        $ijinList = $db->table('ijin')
                            ->select('DATE(tanggal) as tanggal_ijin, keterangan_admin, validator.nama as validator_nama, validated_at')
                            ->join('users as validator', 'validator.id = ijin.validated_by', 'left')
                            ->where('ijin.user_id', $userId)
                            ->where('ijin.status', 'disetujui')
                            ->where('ijin.keterangan_admin IS NOT NULL')
                            ->get()
                            ->getResultArray();
                        
                        // Buat lookup array untuk cepat cek keterangan admin
                        $ijinLookup = [];
                        foreach($ijinList as $ijinItem) {
                            $ijinLookup[$ijinItem['tanggal_ijin']] = $ijinItem;
                        }
                        
                        foreach($filteredPresensi as $p): 
                            // Cek apakah ada keterangan admin untuk tanggal ini
                            $tanggalPresensi = date('Y-m-d', strtotime($p['waktu']));
                            $hasAdminNote = isset($ijinLookup[$tanggalPresensi]) && $p['keterangan'] === 'ijin';
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($p['waktu'])) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= strftime('%A', strtotime($p['waktu'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark">
                                        <i class="bi bi-clock"></i> <?= date('H:i', strtotime($p['waktu'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $keterangan = strtolower($p['keterangan']);
                                    switch ($keterangan) {
                                        case 'hadir':
                                            $badgeClass = 'bg-success';
                                            $icon = 'check-circle-fill';
                                            $displayText = 'Hadir';
                                            break;
                                        case 'terlambat':
                                            $badgeClass = 'bg-warning';
                                            $icon = 'clock-fill';
                                            $displayText = 'Terlambat';
                                            break;
                                        case 'alpha':
                                            $badgeClass = 'bg-danger';
                                            $icon = 'x-circle-fill';
                                            $displayText = 'Alpha';
                                            break;
                                        case 'ijin':
                                            $badgeClass = 'bg-info';
                                            $icon = 'file-earmark-text-fill';
                                            $displayText = 'Ijin';
                                            break;
                                        default:
                                            $badgeClass = 'bg-secondary';
                                            $icon = 'question-circle-fill';
                                            $displayText = ucfirst($keterangan);
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-3 py-2">
                                        <i class="bi bi-<?= $icon ?>"></i>
                                        <?= $displayText ?>
                                    </span>
                                    
                                    <?php if($hasAdminNote): ?>
                                        <br>
                                        <small class="text-info mt-1 d-inline-block">
                                            <i class="bi bi-chat-square-text-fill"></i> Ada keterangan admin
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($p['latitude'] && $p['longitude']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt-fill text-danger"></i>
                                            <?= $p['latitude'] ?>, <?= $p['longitude'] ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-pin-map-fill"></i>
                                        <?php 
                                        $lokasi = $p['lokasi'] ?? 'Tidak ada data';
                                        echo strlen($lokasi) > 50 ? substr($lokasi, 0, 50) . '...' : $lokasi;
                                        ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('pegawai/presensi/detail/' . $p['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="Lihat Detail">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Statistics (berdasarkan data yang difilter) -->
            <!-- <div class="mt-4 p-4 bg-light rounded">
                <h6 class="mb-3 fw-bold">
                    <i class="bi bi-bar-chart-fill text-primary"></i> Ringkasan Presensi
                    <?php if(!empty($activeFilters)): ?>
                        <small class="text-muted">(Hasil Filter)</small>
                    <?php endif; ?>
                </h6>
                <div class="row text-center g-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-clipboard-check text-primary" style="font-size: 30px;"></i>
                            <h4 class="text-primary mt-2 mb-1"><?= count($filteredPresensi) ?></h4>
                            <small class="text-muted">Total Presensi</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 30px;"></i>
                            <h4 class="text-success mt-2 mb-1">
                                <?= count(array_filter($filteredPresensi, fn($p) => strtolower($p['keterangan']) == 'hadir')) ?>
                            </h4>
                            <small class="text-muted">Hadir</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-file-earmark-text-fill text-info" style="font-size: 30px;"></i>
                            <h4 class="text-info mt-2 mb-1">
                                <?= count(array_filter($filteredPresensi, fn($p) => strtolower($p['keterangan']) == 'ijin')) ?>
                            </h4>
                            <small class="text-muted">Ijin</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 30px;"></i>
                            <h4 class="text-danger mt-2 mb-1">
                                <?= count(array_filter($filteredPresensi, fn($p) => strtolower($p['keterangan']) == 'alpha')) ?>
                            </h4>
                            <small class="text-muted">Alpha</small>
                        </div>
                    </div>
                </div> -->
                
                <!-- Tingkat Kehadiran -->
                <!-- <?php 
                $totalPresensi = count($filteredPresensi);
                $totalHadir = count(array_filter($filteredPresensi, fn($p) => strtolower($p['keterangan']) == 'hadir'));
                $totalTerlambat = count(array_filter($filteredPresensi, fn($p) => strtolower($p['keterangan']) == 'terlambat'));
                $totalKehadiran = $totalHadir + $totalTerlambat;
                $persenKehadiran = $totalPresensi > 0 ? round(($totalKehadiran / $totalPresensi) * 100, 1) : 0;
                ?>
                
                <div class="mt-4 pt-4 border-top">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-2">Tingkat Kehadiran</h6>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar <?= $persenKehadiran >= 90 ? 'bg-success' : ($persenKehadiran >= 75 ? 'bg-warning' : 'bg-danger') ?>" 
                                     role="progressbar" 
                                     style="width: <?= $persenKehadiran ?>%;"
                                     aria-valuenow="<?= $persenKehadiran ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <span class="fw-bold fs-6"><?= $persenKehadiran ?>%</span>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <?= $totalKehadiran ?> dari <?= $totalPresensi ?> presensi (Hadir + Terlambat)
                            </small>
                        </div>
                        <div class="col-md-4 text-center">
                            <?php if($persenKehadiran >= 90): ?>
                                <i class="bi bi-trophy-fill text-success" style="font-size: 50px;"></i>
                                <p class="mb-0 mt-2">
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-star-fill"></i> Sangat Baik
                                    </span>
                                </p>
                            <?php elseif($persenKehadiran >= 75): ?>
                                <i class="bi bi-star-fill text-warning" style="font-size: 50px;"></i>
                                <p class="mb-0 mt-2">
                                    <span class="badge bg-warning px-3 py-2">
                                        <i class="bi bi-hand-thumbs-up"></i> Baik
                                    </span>
                                </p>
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 50px;"></i>
                                <p class="mb-0 mt-2">
                                    <span class="badge bg-danger px-3 py-2">
                                        <i class="bi bi-arrow-up-circle"></i> Perlu Ditingkatkan
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div> -->
            
            <!-- Info Box untuk Ijin dengan Keterangan Admin -->
            <!-- <?php 
            $ijinWithNotes = count(array_filter($filteredPresensi, function($p) use ($ijinLookup) {
                $tanggal = date('Y-m-d', strtotime($p['waktu']));
                return $p['keterangan'] === 'ijin' && isset($ijinLookup[$tanggal]);
            }));
            ?>
            
            <?php if($ijinWithNotes > 0): ?>
                <div class="alert alert-info mt-4 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-3" style="font-size: 24px;"></i>
                    <div>
                        <strong>Informasi:</strong> Anda memiliki <strong><?= $ijinWithNotes ?></strong> presensi ijin dengan keterangan dari admin. 
                        Klik tombol "Detail" untuk melihat keterangan lengkap.
                    </div>
                </div>
            <?php endif; ?> -->
        <?php endif; ?>
    </div>
</div>

<style>
    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .badge {
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
        transform: scale(1.01);
    }
    
    .form-label.small {
        margin-bottom: 0.25rem;
    }
    
    .form-select-sm, .form-control-sm {
        font-size: 0.875rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reset Filter Button
    const resetBtn = document.getElementById('resetFilterBtn');
    const resetBtn2 = document.getElementById('resetFilterBtn2');
    
    if(resetBtn) {
        resetBtn.addEventListener('click', function() {
            window.location.href = '<?= base_url('pegawai/presensi/riwayat') ?>';
        });
    }
    
    if(resetBtn2) {
        resetBtn2.addEventListener('click', function() {
            window.location.href = '<?= base_url('pegawai/presensi/riwayat') ?>';
        });
    }
    
    // Validasi tanggal (dari tidak boleh lebih besar dari sampai)
    const dariTanggal = document.getElementById('filterDariTanggal');
    const sampaiTanggal = document.getElementById('filterSampaiTanggal');
    
    if(dariTanggal && sampaiTanggal) {
        dariTanggal.addEventListener('change', function() {
            if(sampaiTanggal.value && this.value > sampaiTanggal.value) {
                alert('Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai"');
                this.value = '';
            }
        });
        
        sampaiTanggal.addEventListener('change', function() {
            if(dariTanggal.value && this.value < dariTanggal.value) {
                alert('Tanggal "Sampai" tidak boleh lebih kecil dari tanggal "Dari"');
                this.value = '';
            }
        });
    }
    
    // Auto submit saat filter berubah (opsional)
    const autoSubmitFilters = document.querySelectorAll('#filterTahun, #filterKeterangan');
    autoSubmitFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            // Uncomment baris berikut jika ingin auto-submit
            // document.getElementById('filterForm').submit();
        });
    });
});
</script>

<?= $this->endSection() ?>