<?php
namespace App\Controllers\Pegawai;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\QrCodeModel;
use App\Models\IjinModel;

class Presensi extends BaseController
{
    protected $presensiModel;
    protected $qrModel;
    protected $ijinModel;

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->qrModel = new QrCodeModel();
        $this->ijinModel = new IjinModel();
    }

    /**
     * ========== QR CODE SCAN PAGE ==========
     * Halaman Scan QR Code
     */
    public function scan()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Silakan login terlebih dahulu');
        }
        
        log_message('info', '=== SCAN PAGE ACCESS ===');
        log_message('info', 'User ID: ' . $userId);
        
        $hasPresensi = $this->presensiModel->hasPresensiToday($userId);
        log_message('info', 'Has presensi today: ' . ($hasPresensi ? 'YES' : 'NO'));
        
        $data = [
            'title' => 'Scan QR Code',
            'has_presensi' => $hasPresensi,
            'qr_aktif' => $this->qrModel->where('status', 'aktif')
                                        ->where('tanggal', date('Y-m-d'))
                                        ->first()
        ];
        
        return view('admin/pegawai/scan', $data);
    }

    /**
     * ========== QR CODE SUBMIT (AJAX) - SUPPORT TOKEN 5 KARAKTER ==========
     * Submit Presensi dari QR Code Scan
     */
    public function submit()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        log_message('info', '=== PRESENSI SUBMIT START ===');
        log_message('info', 'Method: ' . $this->request->getMethod());
        
        try {
            $method = strtoupper($this->request->getMethod());
            
            if ($method === 'GET') {
                log_message('warning', 'GET request detected - should be POST');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Method GET tidak diizinkan. Gunakan POST.'
                ]);
                exit;
            }
            
            // Get POST data
            $postData = $this->request->getPost();
            log_message('info', 'POST data keys: ' . implode(', ', array_keys($postData)));
            
            // Get user ID dari session
            $userId = session()->get('user_id');
            log_message('info', 'User ID from session: ' . ($userId ?? 'NULL'));
            
            if (!$userId) {
                log_message('error', 'User ID tidak ditemukan di session');
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesi berakhir. Silakan login kembali.'
                ]);
                exit;
            }

            // ========== NORMALISASI TOKEN 5 KARAKTER ==========
            // Get token dan normalisasi (uppercase, max 5 karakter)
            $token = $this->request->getPost('token');
            $tokenOriginal = $token; // Simpan untuk logging
            $token = strtoupper(substr(trim($token), 0, 5));
            
            log_message('info', 'Token (original): ' . ($tokenOriginal ?? 'NULL'));
            log_message('info', 'Token (normalized): ' . ($token ?? 'NULL'));
            
            if (empty($token)) {
                log_message('error', 'Token kosong');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Token tidak boleh kosong'
                ]);
                exit;
            }
            
            // ========== VALIDASI FORMAT TOKEN 5 KARAKTER ==========
            // Token harus 5 karakter alphanumeric uppercase
            if (!preg_match('/^[0-9A-Z]{5}$/', $token)) {
                log_message('error', 'Token format invalid: ' . $token . ' (length: ' . strlen($token) . ')');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Format token tidak valid. Token harus 5 karakter huruf dan angka.',
                    'type' => 'token_format_invalid',
                    'debug' => [
                        'token_received' => $token,
                        'token_length' => strlen($token),
                        'expected_format' => '5 karakter A-Z dan 0-9'
                    ]
                ]);
                exit;
            }
            
            log_message('info', 'Token format valid: ' . $token . ' ✓');

            // Cek sudah presensi hari ini
            $hasPresensi = $this->presensiModel->hasPresensiToday($userId);
            log_message('info', 'Has presensi today: ' . ($hasPresensi ? 'YES' : 'NO'));
            
            if ($hasPresensi) {
                log_message('warning', 'User sudah presensi hari ini');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Anda sudah melakukan presensi hari ini'
                ]);
                exit;
            }

            // ========== VALIDASI QR CODE (SUPPORT TOKEN 5 KARAKTER) ==========
            log_message('info', 'Validating QR Code with token: ' . $token);
            $validation = $this->qrModel->isValidToken($token);
            log_message('info', 'Validation result: ' . json_encode($validation));
            
            if (!$validation['valid']) {
                log_message('error', 'QR Code tidak valid: ' . $validation['message']);
                http_response_code(400);
                
                $errorMessage = $validation['message'] ?? 'QR Code tidak valid atau sudah kadaluarsa';
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage,
                    'type' => 'token_invalid',
                    'debug' => [
                        'token' => $token,
                        'validation_message' => $validation['message']
                    ]
                ]);
                exit;
            }

            // Tentukan keterangan berdasarkan waktu
            $qrData = $validation['data'];
            
            date_default_timezone_set('Asia/Jakarta');
            $waktuSekarang = date('H:i:s');
            $waktuMulai = $qrData['waktu_mulai'];
            $batasWaktu = date('H:i:s', strtotime($waktuMulai . ' +15 minutes'));
            
            $keterangan = ($waktuSekarang > $batasWaktu) ? 'terlambat' : 'hadir';
            
            log_message('info', 'Waktu Sekarang (WIB): ' . $waktuSekarang);
            log_message('info', 'Waktu Mulai: ' . $waktuMulai);
            log_message('info', 'Batas Waktu: ' . $batasWaktu);
            log_message('info', 'Keterangan: ' . $keterangan);
            
            // Override manual
            $manualKeterangan = $this->request->getPost('keterangan');
            if ($manualKeterangan && in_array($manualKeterangan, ['hadir', 'alpha', 'ijin'])) {
                $keterangan = $manualKeterangan;
                log_message('info', 'Keterangan manual override: ' . $keterangan);
            }

            // Prepare data
            $data = [
                'user_id' => (int)$userId,
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
                'latitude' => $this->request->getPost('latitude'),
                'longitude' => $this->request->getPost('longitude'),
                'lokasi' => $this->request->getPost('lokasi') ?? 'Lokasi tidak tersedia'
            ];

            log_message('info', 'Data to insert: ' . json_encode($data));

            // Insert ke database
            $db = \Config\Database::connect();
            $builder = $db->table('presensi');
            
            $inserted = $builder->insert($data);
            $insertId = $db->insertID();
            
            log_message('info', 'Insert result: ' . ($inserted ? 'SUCCESS - ID: ' . $insertId : 'FAILED'));
            
            if ($inserted && $insertId > 0) {
                log_message('info', '✓ PRESENSI BERHASIL - ID: ' . $insertId . ' - Token: ' . $token);
                
                $waktuDisplay = date('d-m-Y H:i:s');
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Presensi berhasil dicatat!',
                    'type' => 'success',
                    'data' => [
                        'id' => $insertId,
                        'keterangan' => ucfirst($keterangan),
                        'waktu' => $waktuDisplay,
                        'status' => $keterangan === 'terlambat' ? 'warning' : 'success',
                        'token' => $token // Token 5 karakter yang digunakan
                    ]
                ]);
            } else {
                $error = $db->error();
                log_message('error', '✗ INSERT FAILED: ' . json_encode($error));
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan presensi',
                    'type' => 'database_error',
                    'debug' => [
                        'error_code' => $error['code'],
                        'error_message' => $error['message']
                    ]
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', '✗ EXCEPTION: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'type' => 'exception'
            ]);
        }
        
        exit;
    }

    /**
     * ========== RIWAYAT PRESENSI ==========
     * Halaman Riwayat Presensi User
     */
    public function riwayat()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        
        $presensi = $this->presensiModel->getPresensiWithUser(['user_id' => $userId]);
        
        $stats = [
            'total' => count($presensi),
            'hadir' => 0,
            'alpha' => 0,
            'ijin' => 0
        ];
        
        foreach ($presensi as $p) {
            if (isset($stats[$p['keterangan']])) {
                $stats[$p['keterangan']]++;
            }
        }
        
        return view('admin/pegawai/riwayat', [
            'title' => 'Riwayat Presensi',
            'presensi' => $presensi,
            'stats' => $stats
        ]);
    }

    /**
     * ========== DETAIL PRESENSI ==========
     * Halaman Detail Presensi dengan Map
     */
    public function detail($id)
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        
        // Ambil data presensi
        $presensi = $this->presensiModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$presensi) {
            return redirect()->to(base_url('pegawai/presensi/riwayat'))
                ->with('error', 'Data presensi tidak ditemukan');
        }
        
        return view('admin/pegawai/detail', [
            'title' => 'Detail Presensi',
            'presensi' => $presensi
        ]);
    }

    /**
     * ========== CETAK BUKTI PRESENSI ==========
     * Generate PDF Bukti Presensi
     */
    public function cetakBukti($id)
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        
        // Ambil data presensi
        $presensi = $this->presensiModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$presensi) {
            return redirect()->to(base_url('pegawai/presensi/riwayat'))
                ->with('error', 'Data presensi tidak ditemukan');
        }
        
        // Get user data
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);
        
        // Simple HTML view sebagai PDF content
        $html = view('admin/pegawai/bukti-presensi', [
            'presensi' => $presensi,
            'user' => $user,
            'tanggal_cetak' => date('d-m-Y H:i:s')
        ]);
        
        return response()
            ->setContentType('application/pdf')
            ->download('bukti-presensi-' . $id . '.pdf')
            ->setBody($html);
    }

    /**
     * ========== HALAMAN PENGAJUAN IJIN ==========
     * Form untuk mengajukan ijin/sakit
     */
    public function ijin()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        
        return view('admin/pegawai/ijin', [
            'title' => 'Pengajuan Ijin/Sakit'
        ]);
    }

    /**
     * ========== SUBMIT PENGAJUAN IJIN ==========
     * Proses Pengajuan Ijin/Sakit
     * Status: menunggu, disetujui, ditolak (sesuai database)
     */
    /**
 * ========== SUBMIT PENGAJUAN IJIN ==========
 * Proses Pengajuan Ijin/Sakit
 * Status: menunggu, disetujui, ditolak (sesuai database)
 */
    public function submitIjin()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        
        // Validasi input menggunakan validation service
        $validation = \Config\Services::validation();
        
        // Set validation rules dengan setRules method (benar untuk CodeIgniter 4)
        $validation->setRules([
            'tanggal' => 'required|valid_date[Y-m-d]',
            'keterangan' => 'required|min_length[10]'
        ]);
        
        // Run validation dengan request
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        // Get input data
        $tanggal = $this->request->getPost('tanggal');
        $keteranganIjin = $this->request->getPost('keterangan');
        
        // Validasi tanggal tidak boleh di masa depan
        if ($tanggal > date('Y-m-d')) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Tidak dapat mengajukan ijin untuk tanggal di masa depan');
        }
        
        // Cek apakah sudah ada presensi di tanggal tersebut
        $presensiExists = $this->presensiModel
            ->where('user_id', $userId)
            ->where('DATE(waktu)', $tanggal)
            ->first();
        
        if ($presensiExists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Tidak dapat mengajukan ijin untuk tanggal yang sudah ada presensi');
        }
        
        // Cek apakah sudah ada pengajuan ijin di tanggal tersebut
        if ($this->ijinModel->hasIjinOnDate($userId, $tanggal)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sudah ada pengajuan ijin untuk tanggal tersebut');
        }
        
        // FIXED: Hapus created_at karena sudah otomatis di-handle oleh model (useTimestamps = true)
        $dataIjin = [
            'user_id' => (int)$userId,  // Cast to integer
            'tanggal' => $tanggal,
            'keterangan' => $keteranganIjin,
            'status' => 'menunggu'
            // created_at akan otomatis diisi oleh model karena useTimestamps = true
        ];
        
        try {
            $inserted = $this->ijinModel->insert($dataIjin);
            
            if ($inserted) {
                log_message('info', 'Ijin berhasil diajukan untuk user ' . $userId . ' - ID: ' . $inserted);
                
                return redirect()->to(base_url('pegawai/presensi/ijin-riwayat'))
                    ->with('success', 'Pengajuan ijin berhasil! Status: Menunggu persetujuan');
            } else {
                // Get validation errors dari model
                $errors = $this->ijinModel->errors();
                log_message('error', 'Gagal insert ijin: ' . json_encode($errors));
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal mengajukan ijin: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception insert ijin: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * ========== RIWAYAT PENGAJUAN IJIN ==========
     * Halaman Riwayat Pengajuan Ijin/Sakit
     */
    public function ijinRiwayat()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return redirect()->to(base_url('login'));
        }
        
        // Get riwayat ijin dengan status sesuai database: menunggu, disetujui, ditolak
        $ijin = $this->ijinModel
            ->where('user_id', $userId)
            ->orderBy('tanggal', 'DESC')
            ->get()
            ->getResultArray();
        
        // Stats sesuai status di database
        $stats = [
            'total' => count($ijin),
            'menunggu' => 0,
            'disetujui' => 0,
            'ditolak' => 0
        ];
        
        foreach ($ijin as $item) {
            if (isset($stats[$item['status']])) {
                $stats[$item['status']]++;
            }
        }
        
        return view('admin/pegawai/ijin-riwayat', [
            'title' => 'Riwayat Pengajuan Ijin',
            'ijin' => $ijin,
            'stats' => $stats
        ]);
    }
}