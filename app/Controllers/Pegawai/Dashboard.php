<?php
namespace App\Controllers\Pegawai;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\QrCodeModel;

class Dashboard extends BaseController
{
    protected $presensiModel;
    protected $qrModel;

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->qrModel = new QrCodeModel();
    }

    public function index()
    {
        // Cek role pegawai
        if (session()->get('role') !== 'pegawai') {
            log_message('warning', 'Non-pegawai trying to access pegawai dashboard');
            return redirect()->to(base_url('admin/dashboard'));
        }

        $userId = session()->get('user_id');
        
        if (!$userId) {
            log_message('error', 'User ID not found in session');
            return redirect()->to(base_url('login'))
                ->with('error', 'Silakan login terlebih dahulu');
        }

        log_message('info', '=== PEGAWAI DASHBOARD ACCESS ===');
        log_message('info', 'User ID: ' . $userId);
        log_message('info', 'Date: ' . date('Y-m-d H:i:s'));

        // Set timezone
        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');

        // ========== CEK PRESENSI HARI INI ==========
        $hasPresensiToday = $this->presensiModel->hasPresensiToday($userId);
        log_message('info', 'Has presensi today: ' . ($hasPresensiToday ? 'YES' : 'NO'));

        // ========== AMBIL DATA PRESENSI HARI INI ==========
        $presensiToday = $this->presensiModel->getPresensiToday($userId);
        
        if ($presensiToday) {
            log_message('info', 'Today presensi found - ID: ' . $presensiToday['id']);
            log_message('info', 'Keterangan: ' . $presensiToday['keterangan']);
            log_message('info', 'Waktu: ' . $presensiToday['waktu']);
        } else {
            log_message('info', 'No presensi found for today');
        }

        // ========== RIWAYAT PRESENSI 30 HARI TERAKHIR ==========
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        
        $recentPresensi = $this->presensiModel
            ->where('user_id', $userId)
            ->where('DATE(waktu) >=', $thirtyDaysAgo)
            ->where('DATE(waktu) <=', $today)
            ->orderBy('waktu', 'DESC')
            ->findAll();

        log_message('info', 'Recent presensi (30 days): ' . count($recentPresensi) . ' records');

        // ========== STATISTIK BULAN INI ==========
        $firstDayOfMonth = date('Y-m-01');
        $lastDayOfMonth = date('Y-m-t');
        
        $presensiThisMonth = $this->presensiModel
            ->where('user_id', $userId)
            ->where('DATE(waktu) >=', $firstDayOfMonth)
            ->where('DATE(waktu) <=', $lastDayOfMonth)
            ->findAll();

        // Hitung statistik dengan semua status
        $stats = [
            'total' => count($presensiThisMonth),
            'hadir' => 0,
            'terlambat' => 0,
            'ijin' => 0,
            'alpha' => 0
        ];
        
        foreach ($presensiThisMonth as $p) {
            $keterangan = $p['keterangan'] ?? 'unknown';
            if (isset($stats[$keterangan])) {
                $stats[$keterangan]++;
            } else {
                log_message('warning', 'Unknown keterangan: ' . $keterangan);
            }
        }

        log_message('info', 'Stats this month: ' . json_encode($stats));

        // ========== CEK QR CODE AKTIF HARI INI ==========
        $qrAktif = $this->qrModel
            ->where('status', 'aktif')
            ->where('tanggal', $today)
            ->first();

        if ($qrAktif) {
            log_message('info', 'Active QR Code found for today');
        } else {
            log_message('warning', 'No active QR Code for today');
        }

        // ========== PREPARE DATA UNTUK VIEW ==========
        // PENTING: Nama variabel harus sesuai dengan yang digunakan di view!
        // View menggunakan: $today_presensi (bukan $presensi_today)
        $data = [
            'title' => 'Dashboard Pegawai',
            'has_presensi_today' => $hasPresensiToday,
            'today_presensi' => $presensiToday,  // ✓ BENAR: today_presensi
            'recent_presensi' => $recentPresensi,
            'stats' => $stats,
            'qr_aktif' => $qrAktif
        ];

        // Log data yang dikirim ke view untuk debugging
        log_message('info', 'Data sent to view:');
        log_message('info', '- has_presensi_today: ' . ($hasPresensiToday ? 'true' : 'false'));
        log_message('info', '- today_presensi: ' . ($presensiToday ? 'EXISTS (keterangan: ' . ($presensiToday['keterangan'] ?? 'NULL') . ')' : 'NULL'));
        log_message('info', '- recent_presensi: ' . count($recentPresensi) . ' records');
        log_message('info', '- stats total: ' . $stats['total']);
        
        // Validasi akhir sebelum kirim ke view
        if ($hasPresensiToday && !$presensiToday) {
            log_message('error', 'INCONSISTENT STATE: has_presensi_today is TRUE but today_presensi is NULL!');
        }
        
        log_message('info', '=== END DASHBOARD ACCESS ===');

        return view('admin/pegawai/dashboard', $data);
    }
}