<?php
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ============================================================
// PUBLIC ROUTES
// ============================================================
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::loginProcess');
$routes->get('logout', 'Auth::logout');

// ============================================================
// DEBUG ROUTES (Temporary - Remove in Production)
// ============================================================
$routes->get('debug/session', 'DebugTest::testSession');
$routes->get('debug/qrcode', 'DebugTest::testQrCode');
$routes->get('debug/validateqr', 'DebugTest::testValidateQr');
$routes->get('debug/presensi-submit', 'DebugTest::testPresensiSubmit');

// Debug Presensi Routes - NO FILTER
$routes->get('debug-presensi/form', 'DebugPresensi::form');
$routes->post('debug-presensi/test-submit', 'DebugPresensi::testSubmit');
$routes->post('debug-presensi/test-submit-with-session', 'DebugPresensi::testSubmitWithSession');

// ============================================================
// PEGAWAI ROUTES - TANPA FILTER UNTUK DEBUG
// ============================================================
$routes->group('pegawai', function($routes) {
    $routes->get('dashboard', 'Pegawai\Dashboard::index');

    // ========== PRESENSI QR CODE ==========
    $routes->get('presensi/scan', 'Pegawai\Presensi::scan');
    $routes->post('presensi/submit', 'Pegawai\Presensi::submit');
    
    // ========== RIWAYAT PRESENSI ==========
    $routes->get('presensi/riwayat', 'Pegawai\Presensi::riwayat');
    $routes->get('presensi/detail/(:num)', 'Pegawai\Presensi::detail/$1');
    $routes->get('presensi/cetak-bukti/(:num)', 'Pegawai\Presensi::cetakBukti/$1');
    
    // ========== PENGAJUAN IJIN/SAKIT ==========
    $routes->get('presensi/ijin', 'Pegawai\Presensi::ijin');
    $routes->post('presensi/submit-ijin', 'Pegawai\Presensi::submitIjin');
    $routes->get('presensi/ijin-riwayat', 'Pegawai\Presensi::ijinRiwayat');
});

// ============================================================
// ADMIN ROUTES (Protected)
// ============================================================
$routes->group('admin', ['filter' => 'auth:admin'], function($routes) {
    // ========== DASHBOARD ==========
    $routes->get('dashboard', 'Admin\Dashboard::index');
    
    // ========== PEGAWAI MANAGEMENT ==========
    $routes->get('pegawai', 'Admin\Pegawai::index');
    $routes->get('pegawai/create', 'Admin\Pegawai::create');
    $routes->post('pegawai/store', 'Admin\Pegawai::store');
    $routes->get('pegawai/edit/(:num)', 'Admin\Pegawai::edit/$1');
    $routes->post('pegawai/update/(:num)', 'Admin\Pegawai::update/$1');
    $routes->get('pegawai/delete/(:num)', 'Admin\Pegawai::delete/$1');
    $routes->get('pegawai/toggleStatus/(:num)', 'Admin\Pegawai::toggleStatus/$1');
    
    // ========== QR CODE MANAGEMENT ==========
    $routes->get('qrcode', 'Admin\QrCode::index');
    $routes->get('qrcode/create', 'Admin\QrCode::create');
    $routes->post('qrcode/store', 'Admin\QrCode::store');
    $routes->get('qrcode/generate/(:num)', 'Admin\QrCode::generate/$1');
    $routes->get('qrcode/toggle/(:num)', 'Admin\QrCode::toggle/$1');
    $routes->get('qrcode/delete/(:num)', 'Admin\QrCode::delete/$1');
    $routes->get('qrcode/download/(:num)', 'Admin\QrCode::download/$1');
    
    // ========== QR CODE LAPORAN & AUTO ALPHA ==========
    $routes->get('qrcode/download-laporan/(:num)', 'Admin\QrCode::downloadLaporan/$1');
    $routes->get('qrcode/process-alpha/(:num)', 'Admin\QrCode::processAlpha/$1');
    
    // ========== 🆕 DYNAMIC TOKEN API ==========
    $routes->get('qrcode/get-dynamic-token/(:num)', 'Admin\QrCode::getDynamicToken/$1');
    
    // ========== IJIN MANAGEMENT ==========
    $routes->get('ijin', 'Admin\Ijin::index');
    $routes->get('ijin/pending-ajax', 'Admin\Ijin::pendingAjax');
    $routes->post('ijin/approve/(:num)', 'Admin\Ijin::approve/$1');
    $routes->post('ijin/reject/(:num)', 'Admin\Ijin::reject/$1');
    $routes->get('ijin/export-pdf', 'Admin\Ijin::exportPdf');
    $routes->get('ijin/statistics', 'Admin\Ijin::statistics');
    
    // ========== LAPORAN ==========
    $routes->get('laporan', 'Admin\Laporan::index');
    $routes->get('laporan/export-pdf', 'Admin\Laporan::exportPdf');
    $routes->get('laporan/export-word', 'Admin\Laporan::exportWord');
    $routes->get('laporan/export-excel', 'Admin\Laporan::exportExcel');
});