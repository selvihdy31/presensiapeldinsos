<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\QrCodeModel;

class QrCode extends BaseController
{
    protected $qrModel;

    public function __construct()
    {
        $this->qrModel = new QrCodeModel();
    }

    /**
     * ========== HALAMAN DAFTAR QR CODE ==========
     * Menampilkan daftar QR Code dengan auto disable & alpha
     */
    public function index()
    {
        // TEMPORARY: Disable auto alpha on page load to prevent errors
        // Auto alpha hanya jalan saat admin klik button "Process Alpha"
        
        // Uncomment line ini jika ingin auto alpha berjalan otomatis:
        // $expiredCount = $this->qrModel->autoDisableExpired();
        // if ($expiredCount > 0) {
        //     log_message('info', 'Auto disabled & alpha: ' . $expiredCount . ' QR codes');
        // }
        
        $data = [
            'title' => 'Manajemen QR Code',
            'qr_codes' => $this->qrModel->orderBy('created_at', 'DESC')->findAll()
        ];
        
        return view('admin/qrcode/index', $data);
    }

    /**
     * ========== HALAMAN FORM BUAT QR CODE ==========
     */
    public function create()
    {
        return view('admin/qrcode/create', ['title' => 'Buat QR Code Baru']);
    }

    /**
     * ========== PROSES SIMPAN QR CODE BARU ==========
     */
    public function store()
    {
        // Validasi input
        $rules = [
            'tanggal' => 'required|valid_date',
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Semua field harus diisi dengan benar');
        }

        $tanggal = $this->request->getPost('tanggal');
        $waktuMulai = $this->request->getPost('waktu_mulai');
        $waktuSelesai = $this->request->getPost('waktu_selesai');

        // Validasi batasan waktu
        list($jamMulai, $menitMulai) = explode(':', $waktuMulai);
        list($jamSelesai, $menitSelesai) = explode(':', $waktuSelesai);
        
        $jamMulai = (int)$jamMulai;
        $jamSelesai = (int)$jamSelesai;
        
        // Validasi waktu mulai harus antara 07:00 - 09:59
        if ($jamMulai < 7 || $jamMulai > 9) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Waktu mulai harus antara jam 07:00 - 09:59 WIB');
        }
        
        // Validasi waktu selesai harus antara 07:00 - 12:59
        if ($jamSelesai < 7 || $jamSelesai > 12) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Waktu selesai harus antara jam 07:00 - 12:59 WIB');
        }

        // Validasi waktu selesai > waktu mulai
        if (strtotime($waktuSelesai) <= strtotime($waktuMulai)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Waktu selesai harus lebih besar dari waktu mulai');
        }

        try {
            // Generate token statis (untuk identifier)
            $token = $this->qrModel->generateToken();
            
            // Data yang akan disimpan
            $data = [
                'tanggal' => $tanggal,
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'token' => $token,
                'status' => 'aktif'
            ];

            // Debug: Log data yang akan disimpan
            log_message('info', 'Attempting to insert QR Code: ' . json_encode($data));

            // Insert menggunakan Query Builder langsung
            $builder = $this->qrModel->builder();
            $inserted = $builder->insert($data);
            
            if (!$inserted) {
                $error = $this->qrModel->db->error();
                log_message('error', 'QR Code insert failed: ' . json_encode($error));
                throw new \Exception('Gagal menyimpan: ' . ($error['message'] ?? 'Unknown error'));
            }
            
            // Get inserted ID
            $insertedId = $this->qrModel->db->insertID();
            log_message('info', 'QR Code inserted successfully with ID: ' . $insertedId);
            
            return redirect()->to(base_url('admin/qrcode'))
                ->with('success', 'QR Code berhasil dibuat untuk tanggal ' . date('d/m/Y', strtotime($tanggal)));
                
        } catch (\Exception $e) {
            log_message('error', 'QR Code store exception: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat QR Code: ' . $e->getMessage());
        }
    }

    /**
     * ========== HALAMAN GENERATE/TAMPILKAN QR CODE ==========
     * UPDATED: Tidak generate QR image di sini, biar JavaScript yang handle
     */
    public function generate($id)
    {
        $qr = $this->qrModel->find($id);
        
        if (!$qr) {
            return redirect()->to(base_url('admin/qrcode'))
                ->with('error', 'QR Code tidak ditemukan');
        }

        // Cek apakah masih dalam masa aktif
        $isInActiveTime = $this->qrModel->isInActiveTime($qr);
        
        // Auto disable jika sudah expired
        if (!$isInActiveTime && $qr['status'] === 'aktif') {
            $now = date('Y-m-d H:i:s');
            $selesai = $qr['tanggal'] . ' ' . $qr['waktu_selesai'];
            
            if ($now > $selesai) {
                $this->qrModel->builder()
                    ->where('id', $id)
                    ->update(['status' => 'nonaktif']);
                $qr['status'] = 'nonaktif';
            }
        }

        // TIDAK GENERATE QR IMAGE DI SINI
        // Biarkan JavaScript di view yang handle dengan dynamic token
        
        $data = [
            'title' => 'QR Code Presensi',
            'qr' => $qr,
            'is_in_active_time' => $isInActiveTime
        ];

        return view('admin/qrcode/generate', $data);
    }

    /**
     * ========== API: GET DYNAMIC TOKEN ==========
     * Endpoint untuk mengambil token dinamis yang berganti setiap 30 detik
     */
    public function getDynamicToken($id)
    {
        // Set timezone ke WIB
        date_default_timezone_set('Asia/Jakarta');
        
        $qr = $this->qrModel->find($id);
        
        if (!$qr) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'QR Code tidak ditemukan'
            ]);
        }
        
        // Cek apakah QR masih aktif
        if ($qr['status'] !== 'aktif') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'QR Code sudah nonaktif'
            ]);
        }
        
        // Generate token dinamis
        $dynamicToken = $this->qrModel->generateDynamicToken($id);
        
        // Hitung sisa waktu sampai token berganti
        $timeInfo = $this->qrModel->getRemainingSeconds();
        
        // Cek apakah dalam waktu aktif
        $isInActiveTime = $this->qrModel->isInActiveTime($qr);
        
        return $this->response->setJSON([
            'success' => true,
            'token' => $dynamicToken,
            'qr_id' => (int)$id,
            'timestamp' => $timeInfo['current_time'],
            'remaining_seconds' => $timeInfo['remaining'],
            'next_change' => $timeInfo['next_change'],
            'is_active' => $isInActiveTime,
            'waktu_mulai' => $qr['waktu_mulai'],
            'waktu_selesai' => $qr['waktu_selesai'],
            'tanggal' => $qr['tanggal'],
            'current_time_wib' => date('H:i:s')
        ]);
    }

    /**
     * ========== TOGGLE STATUS QR CODE ==========
     */
    public function toggle($id)
    {
        $qr = $this->qrModel->find($id);
        
        if (!$qr) {
            return redirect()->back()
                ->with('error', 'QR Code tidak ditemukan');
        }

        $newStatus = $qr['status'] == 'aktif' ? 'nonaktif' : 'aktif';
        
        try {
            $this->qrModel->builder()
                ->where('id', $id)
                ->update(['status' => $newStatus]);
            
            $message = $newStatus == 'aktif' 
                ? 'QR Code berhasil diaktifkan' 
                : 'QR Code berhasil dinonaktifkan';
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            log_message('error', 'Toggle status error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal mengubah status QR Code');
        }
    }

    /**
     * ========== HAPUS QR CODE ==========
     */
    public function delete($id)
    {
        try {
            $this->qrModel->builder()
                ->where('id', $id)
                ->delete();
                
            return redirect()->to(base_url('admin/qrcode'))
                ->with('success', 'QR Code berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus QR Code');
        }
    }

    /**
     * ========== DOWNLOAD LAPORAN QR CODE ==========
     * Generate PDF Laporan Presensi untuk QR Code tertentu
     */
    public function downloadLaporan($id)
    {
        $laporan = $this->qrModel->getLaporanByQrId($id);
        
        if (!$laporan) {
            return redirect()->back()->with('error', 'QR Code tidak ditemukan');
        }
        
        $qr = $laporan['qr'];
        $presensi = $laporan['presensi'];
        $summary = $laporan['summary'];
        
        // Load Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Generate HTML untuk PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Presensi QR Code</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 11pt; }
        h2 { text-align: center; margin-bottom: 5px; color: #1e40af; }
        h4 { text-align: center; margin-top: 0; color: #666; }
        .info-box { 
            background: #f5f5f5; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px;
            border-left: 4px solid #1e40af;
        }
        .info-row { margin: 5px 0; line-height: 1.6; }
        .summary { 
            display: table;
            width: 100%;
            margin: 20px 0; 
            background: #e0f2fe;
            border-radius: 5px;
        }
        .summary-item { 
            display: table-cell;
            text-align: center; 
            padding: 15px;
            width: 20%;
        }
        .summary-item .number { font-size: 28px; font-weight: bold; }
        .summary-item .label { font-size: 11px; color: #666; margin-top: 5px; }
        .hadir-color { color: #22c55e; }
        .terlambat-color { color: #f59e0b; }
        .ijin-color { color: #3b82f6; }
        .alpha-color { color: #ef4444; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th { 
            background: #1e40af; 
            color: white; 
            padding: 10px 8px; 
            text-align: left;
            font-size: 10pt;
        }
        td { 
            padding: 8px; 
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        tr:nth-child(even) { background: #f9fafb; }
        .badge { 
            padding: 4px 8px; 
            border-radius: 3px; 
            font-size: 9px; 
            font-weight: bold;
            display: inline-block;
        }
        .badge-hadir { background: #22c55e; color: white; }
        .badge-terlambat { background: #f59e0b; color: white; }
        .badge-ijin { background: #3b82f6; color: white; }
        .badge-alpha { background: #ef4444; color: white; }
        .footer { 
            margin-top: 30px; 
            padding-top: 15px; 
            border-top: 2px solid #ddd; 
            text-align: right; 
            font-size: 9px; 
            color: #666; 
        }
        .no-data { 
            text-align: center; 
            color: #999; 
            padding: 30px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h2>LAPORAN PRESENSI PEGAWAI</h2>
    <h4>Sistem Absensi QR Code</h4>
    
    <div class="info-box">
        <div class="info-row"><strong>Tanggal:</strong> ' . date('d F Y', strtotime($qr['tanggal'])) . '</div>
        <div class="info-row"><strong>Jam Presensi:</strong> ' . $qr['waktu_mulai'] . ' - ' . $qr['waktu_selesai'] . ' WIB</div>
        <div class="info-row"><strong>Status QR:</strong> ' . ucfirst($qr['status']) . '</div>
        <div class="info-row"><strong>Tanggal Cetak:</strong> ' . date('d F Y H:i:s') . '</div>
    </div>
    
    <div class="summary">
        <div class="summary-item">
            <div class="number">' . $summary['total'] . '</div>
            <div class="label">Total</div>
        </div>
        <div class="summary-item">
            <div class="number hadir-color">' . $summary['hadir'] . '</div>
            <div class="label">Hadir</div>
        </div>
        <div class="summary-item">
            <div class="number terlambat-color">' . $summary['terlambat'] . '</div>
            <div class="label">Terlambat</div>
        </div>
        <div class="summary-item">
            <div class="number ijin-color">' . $summary['ijin'] . '</div>
            <div class="label">Ijin</div>
        </div>
        <div class="summary-item">
            <div class="number alpha-color">' . $summary['alpha'] . '</div>
            <div class="label">Alpha</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">NIP</th>
                <th width="22%">Nama</th>
                <th width="12%">Waktu</th>
                <th width="13%">Keterangan</th>
                <th width="36%">Lokasi</th>
            </tr>
        </thead>
        <tbody>';
        
        if (empty($presensi)) {
            $html .= '<tr><td colspan="6" class="no-data">Tidak ada data presensi</td></tr>';
        } else {
            $no = 1;
            foreach ($presensi as $p) {
                $badgeClass = 'badge-' . $p['keterangan'];
                $lokasi = $p['lokasi'] ?? '-';
                
                $html .= '<tr>
                    <td>' . $no++ . '</td>
                    <td>' . $p['nip'] . '</td>
                    <td>' . $p['nama'] . '</td>
                    <td>' . date('H:i:s', strtotime($p['waktu'])) . '</td>
                    <td><span class="badge ' . $badgeClass . '">' . strtoupper($p['keterangan']) . '</span></td>
                    <td style="font-size: 8pt;">' . (strlen($lokasi) > 60 ? substr($lokasi, 0, 60) . '...' : $lokasi) . '</td>
                </tr>';
            }
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="footer">
        Generated by Sistem Absensi QR Code<br>
        ' . date('d F Y H:i:s') . '
    </div>
</body>
</html>';
        
        // Load HTML ke Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Output PDF
        $filename = 'laporan-qr-' . date('Ymd', strtotime($qr['tanggal'])) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * ========== PROSES AUTO ALPHA MANUAL ==========
     * Admin bisa trigger auto alpha secara manual untuk QR tertentu
     */
    public function processAlpha($id)
    {
        $qr = $this->qrModel->find($id);
        
        if (!$qr) {
            return redirect()->back()->with('error', 'QR Code tidak ditemukan');
        }
        
        // Cek apakah QR sudah expired
        $now = date('Y-m-d H:i:s');
        $selesai = $qr['tanggal'] . ' ' . $qr['waktu_selesai'];
        
        if ($now <= $selesai) {
            return redirect()->back()->with('warning', 'QR Code masih aktif. Auto alpha hanya bisa diproses setelah QR expired.');
        }
        
        // Proses auto alpha
        $totalAlpha = $this->qrModel->processAutoAlpha($qr);
        
        if ($totalAlpha > 0) {
            return redirect()->back()->with('success', 
                'Proses auto alpha selesai! Total pegawai yang di-alpha: ' . $totalAlpha
            );
        } else {
            return redirect()->back()->with('info', 
                'Tidak ada pegawai yang perlu di-alpha. Semua sudah presensi atau ijin.'
            );
        }
    }
}