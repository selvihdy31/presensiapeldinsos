<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-filter"></i> Filter Laporan</h5>
    </div>
    <div class="card-body">
        <form method="get" action="<?= base_url('admin/laporan') ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Pegawai</label>
                    <select class="form-select" name="user_id">
                        <option value="">Semua Pegawai</option>
                        <?php foreach ($pegawai as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= (isset($filters['user_id']) && $filters['user_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= esc($p['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bidang</label>
                    <select class="form-select" name="bagian">
                        <option value="">Semua Bidang</option>
                        <?php foreach($bagianOptions as $kode => $nama): ?>
                            <option value="<?= esc($kode) ?>"
                                <?= (isset($filters['bagian']) && $filters['bagian'] == $kode) ? 'selected' : '' ?>>
                                <?= esc($nama) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tanggal_mulai"
                           value="<?= $filters['tanggal_mulai'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tanggal_selesai"
                           value="<?= $filters['tanggal_selesai'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Keterangan</label>
                    <select class="form-select" name="keterangan">
                        <option value="">Semua</option>
                        <option value="hadir" <?= (isset($filters['keterangan']) && $filters['keterangan'] == 'hadir') ? 'selected' : '' ?>>Hadir</option>
                        <option value="alpha" <?= (isset($filters['keterangan']) && $filters['keterangan'] == 'alpha') ? 'selected' : '' ?>>Alpha</option>
                        <option value="ijin"  <?= (isset($filters['keterangan']) && $filters['keterangan'] == 'ijin')  ? 'selected' : '' ?>>Ijin</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Data Presensi</h5>
        <div>
            <a href="<?= base_url('admin/laporan/export-excel?' . http_build_query($filters)) ?>" class="btn btn-success btn-sm">
                <i class="bi bi-file-excel"></i> Export Excel
            </a>
            <a href="<?= base_url('admin/laporan/export-pdf?' . http_build_query($filters)) ?>" class="btn btn-danger btn-sm">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
            <a href="<?= base_url('admin/laporan/export-word?' . http_build_query($filters)) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-file-word"></i> Export Word
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Bidang</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Keterangan</th>
                        <th>Koordinat</th>
                        <th>Ket-Lanjutan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($presensi)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">Tidak ada data presensi</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($presensi as $p): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($p['nip']) ?></td>
                                <td><?= esc($p['nama']) ?></td>
                                <td>
                                    <?php if(!empty($p['bagian'])): ?>
                                        <span class="badge bg-secondary">
                                            <?= esc($bagianOptions[$p['bagian']] ?? ucfirst($p['bagian'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($p['waktu'])) ?></td>
                                <td><?= date('H:i', strtotime($p['waktu'])) ?></td>
                                <td>
                                    <?php
                                    $k   = strtolower($p['keterangan']);
                                    $map = ['hadir'=>'bg-success','alpha'=>'bg-danger','ijin'=>'bg-info','terlambat'=>'bg-warning text-dark'];
                                    $cls = $map[$k] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= ucfirst($k) ?></span>
                                </td>
                                <td><?= esc($p['latitude']) ?>, <?= esc($p['longitude']) ?></td>
                                <td><?= esc($p['lokasi'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>