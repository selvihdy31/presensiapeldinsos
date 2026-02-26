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

    // ========== KONFIGURASI LOKASI KANTOR ==========
    const OFFICE_LAT    = -6.9002095;
    const OFFICE_LNG    = 109.7166471;
    const MAX_RADIUS_M  = 10; // Maksimal jarak dalam meter

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->qrModel       = new QrCodeModel();
        $this->ijinModel     = new IjinModel();
    }

    /**
     * Hitung jarak antara dua koordinat menggunakan rumus Haversine (meter)
     */
    private function hitungJarak($lat1, $lng1, $lat2, $lng2): float
    {
        $R    = 6371000; // Radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    /**
     * ========== QR CODE SCAN PAGE ==========
     */
    public function scan()
    {
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Silakan login terlebih dahulu');
        }

        $hasPresensi = $this->presensiModel->hasPresensiToday($userId);

        $data = [
            'title'       => 'Scan QR Code',
            'has_presensi' => $hasPresensi,
            'qr_aktif'    => $this->qrModel->where('status', 'aktif')
                                            ->where('tanggal', date('Y-m-d'))
                                            ->first(),
            // Kirim konfigurasi lokasi ke view
            'office_lat'  => self::OFFICE_LAT,
            'office_lng'  => self::OFFICE_LNG,
            'max_radius'  => self::MAX_RADIUS_M,
        ];

        return view('admin/pegawai/scan', $data);
    }

    /**
     * ========== QR CODE SUBMIT (AJAX) ==========
     */
    public function submit()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $method = strtoupper($this->request->getMethod());

            if ($method === 'GET') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Method GET tidak diizinkan. Gunakan POST.']);
                exit;
            }

            $userId = session()->get('user_id');
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
                exit;
            }

            // ========== VALIDASI TOKEN ==========
            $token = strtoupper(substr(trim($this->request->getPost('token')), 0, 5));

            if (empty($token)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token tidak boleh kosong']);
                exit;
            }

            if (!preg_match('/^[0-9A-Z]{5}$/', $token)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Format token tidak valid. Token harus 5 karakter huruf dan angka.',
                    'type'    => 'token_format_invalid'
                ]);
                exit;
            }

            // ========== CEK SUDAH PRESENSI HARI INI ==========
            if ($this->presensiModel->hasPresensiToday($userId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Anda sudah melakukan presensi hari ini']);
                exit;
            }

            // ========== VALIDASI KOORDINAT & RADIUS ==========
            $latitude  = $this->request->getPost('latitude');
            $longitude = $this->request->getPost('longitude');

            if (empty($latitude) || empty($longitude)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Data lokasi tidak ditemukan. Pastikan GPS aktif dan izin lokasi diberikan.',
                    'type'    => 'location_missing'
                ]);
                exit;
            }

            $latitude  = (float) $latitude;
            $longitude = (float) $longitude;

            // Validasi format koordinat
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Format koordinat tidak valid.',
                    'type'    => 'location_invalid'
                ]);
                exit;
            }

            // Hitung jarak ke kantor
            $jarak = $this->hitungJarak($latitude, $longitude, self::OFFICE_LAT, self::OFFICE_LNG);

            log_message('info', sprintf(
                'Presensi - User: %d | Koordinat: %.7f, %.7f | Jarak ke kantor: %.2f m | Maks: %d m',
                $userId, $latitude, $longitude, $jarak, self::MAX_RADIUS_M
            ));

            if ($jarak > self::MAX_RADIUS_M) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => sprintf(
                        'Anda berada %.0f meter dari lokasi kantor. Presensi hanya diizinkan dalam radius %d meter.',
                        $jarak,
                        self::MAX_RADIUS_M
                    ),
                    'type'    => 'location_out_of_range',
                    'data'    => [
                        'jarak_meter' => round($jarak, 2),
                        'maks_meter'  => self::MAX_RADIUS_M,
                    ]
                ]);
                exit;
            }

            // ========== VALIDASI QR CODE ==========
            $validation = $this->qrModel->isValidToken($token);

            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $validation['message'] ?? 'QR Code tidak valid atau sudah kadaluarsa',
                    'type'    => 'token_invalid'
                ]);
                exit;
            }

            // ========== TENTUKAN KETERANGAN (HADIR / TERLAMBAT) ==========
            $qrData       = $validation['data'];
            date_default_timezone_set('Asia/Jakarta');
            $waktuSekarang = date('H:i:s');
            $waktuMulai    = $qrData['waktu_mulai'];
            $batasWaktu    = date('H:i:s', strtotime($waktuMulai . ' +15 minutes'));

            $keterangan = ($waktuSekarang > $batasWaktu) ? 'terlambat' : 'hadir';

            // Override manual (opsional)
            $manualKeterangan = $this->request->getPost('keterangan');
            if ($manualKeterangan && in_array($manualKeterangan, ['hadir', 'alpha', 'ijin'])) {
                $keterangan = $manualKeterangan;
            }

            // ========== INSERT PRESENSI ==========
            $data = [
                'user_id'    => (int) $userId,
                'keterangan' => $keterangan,
                'waktu'      => date('Y-m-d H:i:s'),
                'latitude'   => $latitude,
                'longitude'  => $longitude,
                'lokasi'     => $this->request->getPost('lokasi') ?? 'Lokasi tidak tersedia'
            ];

            $db       = \Config\Database::connect();
            $builder  = $db->table('presensi');
            $inserted = $builder->insert($data);
            $insertId = $db->insertID();

            if ($inserted && $insertId > 0) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Presensi berhasil dicatat!',
                    'type'    => 'success',
                    'data'    => [
                        'id'          => $insertId,
                        'keterangan'  => ucfirst($keterangan),
                        'waktu'       => date('d-m-Y H:i:s'),
                        'status'      => $keterangan === 'terlambat' ? 'warning' : 'success',
                        'jarak_meter' => round($jarak, 2),
                    ]
                ]);
            } else {
                $error = $db->error();
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan presensi',
                    'type'    => 'database_error'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Presensi submit exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'type'    => 'exception'
            ]);
        }

        exit;
    }

    /**
     * ========== RIWAYAT PRESENSI ==========
     */
    public function riwayat()
    {
        $userId = session()->get('user_id');
        if (!$userId) return redirect()->to(base_url('login'));

        $presensi = $this->presensiModel->getPresensiWithUser(['user_id' => $userId]);

        $stats = ['total' => count($presensi), 'hadir' => 0, 'alpha' => 0, 'ijin' => 0];
        foreach ($presensi as $p) {
            if (isset($stats[$p['keterangan']])) $stats[$p['keterangan']]++;
        }

        return view('admin/pegawai/riwayat', [
            'title'    => 'Riwayat Presensi',
            'presensi' => $presensi,
            'stats'    => $stats
        ]);
    }

    /**
     * ========== DETAIL PRESENSI ==========
     */
    public function detail($id)
    {
        $userId = session()->get('user_id');
        if (!$userId) return redirect()->to(base_url('login'));

        $presensi = $this->presensiModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$presensi) {
            return redirect()->to(base_url('pegawai/presensi/riwayat'))
                ->with('error', 'Data presensi tidak ditemukan');
        }

        return view('admin/pegawai/detail', ['title' => 'Detail Presensi', 'presensi' => $presensi]);
    }

    /**
     * ========== CETAK BUKTI PRESENSI ==========
     */
    public function cetakBukti($id)
    {
        $userId = session()->get('user_id');
        if (!$userId) return redirect()->to(base_url('login'));

        $presensi = $this->presensiModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$presensi) {
            return redirect()->to(base_url('pegawai/presensi/riwayat'))
                ->with('error', 'Data presensi tidak ditemukan');
        }

        $userModel = new \App\Models\UserModel();
        $user      = $userModel->find($userId);

        $html = view('admin/pegawai/bukti-presensi', [
            'presensi'      => $presensi,
            'user'          => $user,
            'tanggal_cetak' => date('d-m-Y H:i:s')
        ]);

        return response()
            ->setContentType('application/pdf')
            ->download('bukti-presensi-' . $id . '.pdf')
            ->setBody($html);
    }

    /**
     * ========== HALAMAN PENGAJUAN IJIN ==========
     */
    public function ijin()
    {
        $userId = session()->get('user_id');
        if (!$userId) return redirect()->to(base_url('login'));

        return view('admin/pegawai/ijin', ['title' => 'Pengajuan Ijin/Sakit']);
    }

    /**
     * ========== SUBMIT PENGAJUAN IJIN ==========
     */
    public function submitIjin()
    {
        $userId = session()->get('user_id');
        if (!$userId) return redirect()->to(base_url('login'));

        $validation = \Config\Services::validation();
        $validation->setRules([
            'tanggal'    => 'required|valid_date[Y-m-d]',
            'keterangan' => 'required|min_length[10]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $tanggal        = $this->request->getPost('tanggal');
        $keteranganIjin = $this->request->getPost('keterangan');

        // Cek presensi sudah ada
        $presensiExists = $this->presensiModel
            ->where('user_id', $userId)
            ->where('DATE(waktu)', $tanggal)
            ->first();

        if ($presensiExists) {
            return redirect()->back()->withInput()
                ->with('error', 'Tidak dapat mengajukan ijin untuk tanggal yang sudah ada presensi');
        }

        // Cek ijin sudah ada
        if ($this->ijinModel->hasIjinOnDate($userId, $tanggal)) {
            return redirect()->back()->withInput()
                ->with('error', 'Sudah ada pengajuan ijin untuk tanggal tersebut');
        }

        $dataIjin = [
            'user_id'    => (int) $userId,
            'tanggal'    => $tanggal,
            'keterangan' => $keteranganIjin,
            'status'     => 'menunggu'
        ];

        try {
            $inserted = $this->ijinModel->insert($dataIjin);

            if ($inserted) {
                return redirect()->to(base_url('pegawai/presensi/ijin-riwayat'))
                    ->with('success', 'Pengajuan ijin berhasil! Status: Menunggu persetujuan');
            } else {
                $errors = $this->ijinModel->errors();
                return redirect()->back()->withInput()
                    ->with('error', 'Gagal mengajukan ijin: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception insert ijin: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * ========== RIWAYAT PENGAJUAN IJIN ==========
     */
    public function ijinRiwayat()
    {
        $userId = session()->get('user_id');
        if (!$userId) return redirect()->to(base_url('login'));

        $ijin = $this->ijinModel
            ->where('user_id', $userId)
            ->orderBy('tanggal', 'DESC')
            ->get()
            ->getResultArray();

        $stats = ['total' => count($ijin), 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0];
        foreach ($ijin as $item) {
            if (isset($stats[$item['status']])) $stats[$item['status']]++;
        }

        return view('admin/pegawai/ijin-riwayat', [
            'title' => 'Riwayat Pengajuan Ijin',
            'ijin'  => $ijin,
            'stats' => $stats
        ]);
    }
}