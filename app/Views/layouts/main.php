<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dinsos.png?v=2') ?>">
    <title><?= $title ?? 'Dashboard' ?> - Sistem Absensi</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand .brand-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 30px;
        }
        
        .menu-item i {
            width: 25px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0 !important;
        }
        
        /* Responsive */
        .sidebar-toggle {
            display: none;
            background: var(--primary);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                left: -260px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .content-area {
                padding: 15px;
            }
        }
        
        /* Alert Enhancements */
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        /* Button Enhancements */
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
        }
        
        /* Table Enhancements */
        .table {
            background: white;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= base_url('assets/dinsos.png') ?>" alt="Logo Dinsos" class="brand-logo">
            <h5 class="mb-0">Presensi Apel Pagi Dinas Sosial</h5>
            <small><?= ucfirst(session()->get('role')) ?></small>
        </div>
        
        <div class="sidebar-menu">
            <?php if(session()->get('role') == 'admin'): ?>
                <a href="<?= base_url('admin/dashboard') ?>" class="menu-item <?= uri_string() == 'admin/dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?= base_url('admin/pegawai') ?>" class="menu-item <?= strpos(uri_string(), 'admin/pegawai') !== false ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> Data Pegawai
                </a>
                <a href="<?= base_url('admin/qrcode') ?>" class="menu-item <?= strpos(uri_string(), 'admin/qrcode') !== false ? 'active' : '' ?>">
                    <i class="bi bi-qr-code"></i> Kelola QR Code
                </a>
                <a href="<?= base_url('admin/laporan') ?>" class="menu-item <?= strpos(uri_string(), 'admin/laporan') !== false ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> Laporan Presensi
                </a>
            <?php else: ?>
                <a href="<?= base_url('pegawai/dashboard') ?>" class="menu-item <?= uri_string() == 'pegawai/dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?= base_url('pegawai/presensi/scan') ?>" class="menu-item <?= strpos(uri_string(), 'pegawai/presensi/scan') !== false ? 'active' : '' ?>">
                    <i class="bi bi-qr-code-scan"></i> Scan Presensi
                </a>
                <a href="<?= base_url('pegawai/presensi/riwayat') ?>" class="menu-item <?= strpos(uri_string(), 'pegawai/presensi/riwayat') !== false ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Riwayat Presensi
                </a>
            <?php endif; ?>
            
            <hr style="border-color: rgba(255,255,255,0.2); margin: 20px 25px;">
            
            <a href="<?= base_url('logout') ?>" class="menu-item" onclick="return confirm('Yakin ingin logout?')">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            
            <div>
                <h5 class="mb-0 d-none d-md-block"><?= $title ?? 'Dashboard' ?></h5>
            </div>
            
            <div class="user-info">
                <div class="d-none d-md-block text-end">
                    <div class="fw-bold"><?= session()->get('nama') ?></div>
                    <small class="text-muted"><?= session()->get('nip') ?></small>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr(session()->get('nama'), 0, 1)) ?>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-area">
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
            
            <?= $this->renderSection('content') ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
    
    <?= $this->renderSection('scripts') ?>
</body>
</html>