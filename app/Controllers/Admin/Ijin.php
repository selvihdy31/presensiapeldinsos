<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\IjinModel;
use App\Models\PresensiModel;

class Ijin extends BaseController
{
    protected $ijinModel;
    protected $presensiModel;

    public function __construct()
    {
        $this->ijinModel = new IjinModel();
        $this->presensiModel = new PresensiModel();
    }

    public function index()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('pegawai/dashboard'));
        }

        $filter = $this->request->getGet('filter') ?? 'all';
        
        $filters = [];
        if ($filter !== 'all') {
            $filters['status'] = $filter;
        }
        
        $ijin = $this->ijinModel->getIjinWithUser($filters);
        $stats = $this->ijinModel->getStatsByStatus();
        
        return view('admin/ijin/index', [
            'title' => 'Manajemen Pengajuan Ijin',
            'ijin' => $ijin,
            'stats' => $stats,
            'filter' => $filter
        ]);
    }

    public function pendingAjax()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $ijin = $this->ijinModel->getIjinWithUser(['status' => 'menunggu']);
            
            echo json_encode([
                'success' => true,
                'data' => $ijin,
                'count' => count($ijin)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Approve ijin dengan keterangan admin
     * UPDATED: Menggunakan waktu realtime saat approval, bukan waktu QR code
     */
    public function approve($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $id = (int)$id;
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID ijin tidak valid'
                ]);
                exit;
            }
            
            // Get keterangan dari POST
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $keteranganAdmin = $data['keterangan_admin'] ?? '';
            
            if (empty($keteranganAdmin)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Keterangan admin harus diisi'
                ]);
                exit;
            }
            
            // Get ijin data
            $ijin = $this->ijinModel->find($id);
            
            if (!$ijin) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Data ijin tidak ditemukan'
                ]);
                exit;
            }
            
            // Cek apakah sudah ada presensi
            $existingPresensi = $this->presensiModel
                ->where('user_id', $ijin['user_id'])
                ->where('DATE(waktu)', $ijin['tanggal'])
                ->first();
            
            if ($existingPresensi) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pegawai sudah presensi di tanggal tersebut. Tidak bisa approve ijin.'
                ]);
                exit;
            }
            
            // Start transaction
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Update status ijin dengan keterangan
            $updated = $this->ijinModel->approveIjin(
                $id, 
                $keteranganAdmin,
                session()->get('user_id')
            );
            
            if (!$updated) {
                $db->transRollback();
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal update status ijin'
                ]);
                exit;
            }
            
            // ========== PERUBAHAN UTAMA: GUNAKAN WAKTU REALTIME ==========
            // Set timezone
            date_default_timezone_set('Asia/Jakarta');
            
            // Ambil waktu realtime saat ini (waktu approval)
            $waktuApproval = date('Y-m-d H:i:s');
            
            // Jika tanggal ijin adalah hari ini, gunakan waktu approval realtime
            // Jika tanggal ijin adalah hari lalu, gunakan tanggal ijin + waktu approval hari ini
            if ($ijin['tanggal'] === date('Y-m-d')) {
                // Ijin untuk hari ini - gunakan waktu approval sekarang
                $waktuPresensi = $waktuApproval;
                log_message('info', 'Ijin hari ini - waktu presensi: ' . $waktuPresensi);
            } else {
                // Ijin untuk tanggal lain - gunakan tanggal ijin + jam approval sekarang
                $jamApproval = date('H:i:s');
                $waktuPresensi = $ijin['tanggal'] . ' ' . $jamApproval;
                log_message('info', 'Ijin tanggal lain - waktu presensi: ' . $waktuPresensi);
            }
            
            // Insert presensi dengan waktu realtime
            $presensiData = [
                'user_id' => (int)$ijin['user_id'],
                'keterangan' => 'ijin',
                'waktu' => $waktuPresensi, // Waktu realtime saat approval
                'latitude' => null,
                'longitude' => null,
                'lokasi' => 'Ijin Disetujui - ' . substr($ijin['keterangan'], 0, 50)
            ];
            
            log_message('info', 'Insert presensi ijin dengan data: ' . json_encode($presensiData));
            
            $presensiInserted = $db->table('presensi')->insert($presensiData);
            
            if (!$presensiInserted) {
                $db->transRollback();
                $error = $db->error();
                log_message('error', 'Gagal insert presensi: ' . json_encode($error));
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal insert presensi'
                ]);
                exit;
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Transaction failed'
                ]);
                exit;
            }
            
            log_message('info', 'Ijin ID ' . $id . ' disetujui oleh admin ' . session()->get('user_id') . ' dengan waktu presensi: ' . $waktuPresensi);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Ijin berhasil disetujui dan presensi telah dicatat',
                'data' => [
                    'id' => $id,
                    'status' => 'disetujui',
                    'presensi_created' => true,
                    'waktu_presensi' => $waktuPresensi
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Exception approve ijin: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Reject ijin dengan keterangan admin
     */
    public function reject($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $id = (int)$id;
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID ijin tidak valid'
                ]);
                exit;
            }
            
            // Get keterangan dari POST
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $keteranganAdmin = $data['keterangan_admin'] ?? '';
            
            if (empty($keteranganAdmin)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Keterangan penolakan harus diisi'
                ]);
                exit;
            }
            
            // Get ijin data
            $ijin = $this->ijinModel->find($id);
            
            if (!$ijin) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Data ijin tidak ditemukan'
                ]);
                exit;
            }
            
            // Update status dengan keterangan
            $updated = $this->ijinModel->rejectIjin(
                $id, 
                $keteranganAdmin,
                session()->get('user_id')
            );
            
            if ($updated) {
                log_message('info', 'Ijin ID ' . $id . ' ditolak oleh admin ' . session()->get('user_id'));
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Ijin berhasil ditolak',
                    'data' => [
                        'id' => $id,
                        'status' => 'ditolak'
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal update status ijin'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception reject ijin: ' . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function exportPdf()
    {
        $filter = $this->request->getGet('filter') ?? 'all';
        
        $filters = [];
        if ($filter !== 'all') {
            $filters['status'] = $filter;
        }
        
        $ijin = $this->ijinModel->getIjinWithUser($filters);
        
        $html = view('admin/ijin/report-pdf', [
            'ijin' => $ijin,
            'tanggal_cetak' => date('d-m-Y H:i:s'),
            'filter' => $filter
        ]);
        
        return response()
            ->setContentType('application/pdf')
            ->download('laporan-ijin-' . date('Y-m-d') . '.pdf')
            ->setBody($html);
    }

    public function statistics()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $stats = $this->ijinModel->getStatsByStatus();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }
}