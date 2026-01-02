<?php

namespace App\Models;

use CodeIgniter\Model;

class QrCodeModel extends Model
{
    protected $table = 'qr_code';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tanggal', 'waktu_mulai', 'waktu_selesai', 'token', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false;

    /**
     * ========== GENERATE TOKEN STATIS (5 KARAKTER) ==========
     * Generate token statis untuk identifier QR Code
     * Format: 5 karakter huruf besar + angka (contoh: A3F9K, 7BH2X)
     */
    public function generateToken()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';
        
        // Generate 5 karakter random
        for ($i = 0; $i < 5; $i++) {
            $token .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Cek apakah token sudah ada di database (untuk memastikan unique)
        $exists = $this->where('token', $token)->first();
        
        // Jika sudah ada, generate ulang (rekursif)
        if ($exists) {
            return $this->generateToken();
        }
        
        return $token;
    }

    /**
     * ========== GENERATE DYNAMIC TOKEN (5 KARAKTER) ==========
     * Generate token dinamis berdasarkan waktu (berganti setiap 30 detik)
     * Format: 5 karakter dari hash SHA256
     */
    public function generateDynamicToken($qrId, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // Bulatkan timestamp ke interval 30 detik
        $interval = 30; // detik
        $roundedTime = floor($timestamp / $interval) * $interval;
        
        // Ambil data QR untuk mendapatkan tanggal (sebagai salt tambahan)
        $qr = $this->find($qrId);
        $dateSalt = $qr ? $qr['tanggal'] : date('Y-m-d');
        
        // Secret key - GANTI INI dengan string random yang aman
        // Simpan di .env untuk keamanan lebih baik
        $secretKey = env('QR_SECRET_KEY', 'PresensiQRCode2024SecretKey!@#$%');
        
        // Generate token dengan kombinasi ID + rounded time + date + secret key
        $rawToken = $qrId . '_' . $roundedTime . '_' . $dateSalt . '_' . $secretKey;
        
        // Hash menggunakan SHA256 dan ambil 5 karakter pertama (uppercase)
        $hash = hash('sha256', $rawToken);
        return strtoupper(substr($hash, 0, 5));
    }

    /**
     * ========== VALIDASI DYNAMIC TOKEN (5 KARAKTER) ==========
     * Validasi token dinamis (cek apakah token masih valid dalam window time)
     * Window time default 60 detik = 2 interval, untuk toleransi pergantian token
     */
    public function validateDynamicToken($qrId, $token, $windowSeconds = 60)
    {
        $currentTime = time();
        $interval = 30; // interval pergantian token dalam detik
        
        // Pastikan token yang diterima adalah 5 karakter uppercase
        $token = strtoupper(substr($token, 0, 5));
        
        // Cek token dalam window time (default 60 detik = 2 interval)
        // Ini untuk toleransi jika user scan tepat saat pergantian token
        $maxIntervals = ceil($windowSeconds / $interval);
        
        for ($i = 0; $i <= $maxIntervals; $i++) {
            $checkTime = $currentTime - ($i * $interval);
            $validToken = $this->generateDynamicToken($qrId, $checkTime);
            
            if (hash_equals($validToken, $token)) {
                log_message('info', "Dynamic token validated for QR ID {$qrId} (interval offset: {$i})");
                return true;
            }
        }
        
        log_message('warning', "Dynamic token validation failed for QR ID {$qrId}");
        return false;
    }

    /**
     * ========== HITUNG SISA WAKTU SAMPAI TOKEN BERGANTI ==========
     */
    public function getRemainingSeconds($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $interval = 30;
        $nextChange = (floor($timestamp / $interval) + 1) * $interval;
        $remaining = $nextChange - $timestamp;
        
        return [
            'remaining' => $remaining,
            'next_change' => $nextChange,
            'current_time' => $timestamp
        ];
    }

    /**
     * ========== VALIDASI TOKEN (SUPPORT STATIS & DINAMIS 5 KARAKTER) ==========
     */
    public function isValidToken($token)
    {
        // Set timezone ke WIB
        date_default_timezone_set('Asia/Jakarta');
        
        // Normalisasi token (uppercase, max 5 karakter)
        $token = strtoupper(substr(trim($token), 0, 5));
        
        // Coba cek token statis dulu (5 karakter)
        $qr = $this->where('token', $token)->where('status', 'aktif')->first();
        
        // Jika tidak ditemukan dengan token statis, coba validasi dynamic token
        if (!$qr) {
            $today = date('Y-m-d');
            $activeQRs = $this->where('tanggal', $today)
                             ->where('status', 'aktif')
                             ->findAll();
            
            foreach ($activeQRs as $activeQR) {
                if ($this->validateDynamicToken($activeQR['id'], $token)) {
                    $qr = $activeQR;
                    log_message('info', 'Dynamic token matched for QR ID: ' . $activeQR['id']);
                    break;
                }
            }
        }
        
        if (!$qr) {
            return ['valid' => false, 'message' => 'QR tidak valid atau sudah kadaluarsa'];
        }

        $now = date('Y-m-d H:i:s');
        $mulai = $qr['tanggal'] . ' ' . $qr['waktu_mulai'];
        $selesai = $qr['tanggal'] . ' ' . $qr['waktu_selesai'];

        if ($now < $mulai) {
            return ['valid' => false, 'message' => 'QR belum aktif'];
        }
        
        if ($now > $selesai) {
            $this->builder()->where('id', $qr['id'])->update(['status' => 'nonaktif']);
            return ['valid' => false, 'message' => 'QR kadaluarsa'];
        }

        return ['valid' => true, 'message' => 'Valid', 'data' => $qr];
    }

    /**
     * ========== AUTO DISABLE & AUTO ALPHA ==========
     * Disable QR yang expired DAN tandai pegawai yang belum presensi sebagai alpha
     */
    public function autoDisableExpired()
    {
        // Set timezone ke WIB
        date_default_timezone_set('Asia/Jakarta');
        
        $now = date('Y-m-d H:i:s');
        
        // Cari QR yang aktif tapi sudah lewat waktu selesai
        $expiredQrs = $this->where('status', 'aktif')
            ->where("CONCAT(tanggal, ' ', waktu_selesai) <", $now)
            ->findAll();
        
        if (!empty($expiredQrs)) {
            $db = \Config\Database::connect();
            
            foreach ($expiredQrs as $qr) {
                // Disable QR - Gunakan Query Builder langsung
                $qrId = (int)$qr['id'];
                
                $db->table('qr_code')
                   ->where('id', $qrId)
                   ->update(['status' => 'nonaktif']);
                
                // Proses auto alpha untuk QR ini
                $this->processAutoAlpha($qr);
                
                log_message('info', 'QR ID ' . $qrId . ' expired - status updated to nonaktif');
            }
        }
        
        return count($expiredQrs);
    }

    /**
     * ========== PROCESS AUTO ALPHA ==========
     * Tandai pegawai yang belum presensi dan tidak ijin sebagai alpha
     */
    public function processAutoAlpha($qr)
    {
        $db = \Config\Database::connect();
        $tanggal = $qr['tanggal'];
        
        log_message('info', '=== AUTO ALPHA PROCESS START ===');
        log_message('info', 'QR ID: ' . $qr['id'] . ', Tanggal: ' . $tanggal);
        
        // Query: Pegawai yang belum presensi DAN tidak ada ijin di tanggal tersebut
        $query = "
            SELECT u.id, u.nip, u.nama
            FROM users u
            WHERE u.role = 'pegawai'
            AND u.id NOT IN (
                SELECT p.user_id 
                FROM presensi p 
                WHERE DATE(p.waktu) = ?
            )
            AND u.id NOT IN (
                SELECT i.user_id 
                FROM ijin i 
                WHERE i.tanggal = ?
                AND i.status IN ('disetujui', 'menunggu')
            )
        ";
        
        $pegawaiAlpha = $db->query($query, [$tanggal, $tanggal])->getResultArray();
        
        log_message('info', 'Pegawai yang akan di-alpha: ' . count($pegawaiAlpha));
        
        if (!empty($pegawaiAlpha)) {
            $presensiBuilder = $db->table('presensi');
            $totalInserted = 0;
            
            foreach ($pegawaiAlpha as $pegawai) {
                // Cek apakah sudah ada record alpha untuk user ini di tanggal tersebut
                $existing = $presensiBuilder
                    ->where('user_id', $pegawai['id'])
                    ->where('DATE(waktu)', $tanggal)
                    ->where('keterangan', 'alpha')
                    ->get()
                    ->getRow();
                
                // Skip jika sudah ada
                if ($existing) {
                    log_message('info', 'Alpha already exists for: ' . $pegawai['nama']);
                    continue;
                }
                
                // Insert presensi alpha
                $data = [
                    'user_id' => (int)$pegawai['id'],
                    'keterangan' => 'alpha',
                    'waktu' => $tanggal . ' ' . $qr['waktu_selesai'],
                    'latitude' => null,
                    'longitude' => null,
                    'lokasi' => 'Auto Alpha - Tidak Presensi'
                ];
                
                $inserted = $presensiBuilder->insert($data);
                
                if ($inserted) {
                    $totalInserted++;
                    log_message('info', 'Alpha created for: ' . $pegawai['nama'] . ' (NIP: ' . $pegawai['nip'] . ')');
                }
            }
            
            log_message('info', 'Total alpha inserted: ' . $totalInserted . ' dari ' . count($pegawaiAlpha));
        } else {
            log_message('info', 'Tidak ada pegawai yang perlu di-alpha');
        }
        
        log_message('info', '=== AUTO ALPHA PROCESS END ===');
        
        return count($pegawaiAlpha);
    }

    /**
     * ========== GET LAPORAN PER QR CODE ==========
     * Ambil data presensi untuk QR Code tertentu
     */
    public function getLaporanByQrId($qrId)
    {
        $qr = $this->find($qrId);
        if (!$qr) return null;
        
        $db = \Config\Database::connect();
        $tanggal = $qr['tanggal'];
        
        // Ambil semua presensi di tanggal QR tersebut
        $query = "
            SELECT 
                p.id,
                p.user_id,
                u.nip,
                u.nama,
                p.keterangan,
                p.waktu,
                p.latitude,
                p.longitude,
                p.lokasi
            FROM presensi p
            INNER JOIN users u ON u.id = p.user_id
            WHERE DATE(p.waktu) = ?
            ORDER BY p.waktu ASC
        ";
        
        $presensi = $db->query($query, [$tanggal])->getResultArray();
        
        return [
            'qr' => $qr,
            'presensi' => $presensi,
            'summary' => [
                'total' => count($presensi),
                'hadir' => count(array_filter($presensi, fn($p) => $p['keterangan'] === 'hadir')),
                'terlambat' => count(array_filter($presensi, fn($p) => $p['keterangan'] === 'terlambat')),
                'ijin' => count(array_filter($presensi, fn($p) => $p['keterangan'] === 'ijin')),
                'alpha' => count(array_filter($presensi, fn($p) => $p['keterangan'] === 'alpha'))
            ]
        ];
    }

    /**
     * ========== CEK APAKAH QR MASIH DALAM WAKTU AKTIF ==========
     */
    public function isInActiveTime($qr)
    {
        // Set timezone ke WIB
        date_default_timezone_set('Asia/Jakarta');
        
        $now = date('Y-m-d H:i:s');
        $mulai = $qr['tanggal'] . ' ' . $qr['waktu_mulai'];
        $selesai = $qr['tanggal'] . ' ' . $qr['waktu_selesai'];
        return ($now >= $mulai && $now <= $selesai);
    }
}