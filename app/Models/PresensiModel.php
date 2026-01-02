<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiModel extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'user_id',
        'keterangan',
        'waktu',
        'latitude',
        'longitude',
        'lokasi'
    ];
    
    protected $useTimestamps = false;

    // Validation rules - UPDATED: Tambah 'ijin' ke in_list
    protected $validationRules = [
        'user_id' => 'required|integer',
        'keterangan' => 'required|in_list[hadir,terlambat,ijin,alpha]',
        'waktu' => 'required'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID harus diisi',
            'integer' => 'User ID harus berupa angka'
        ],
        'keterangan' => [
            'required' => 'Keterangan harus diisi',
            'in_list' => 'Keterangan tidak valid (hadir/terlambat/ijin/alpha)'
        ],
        'waktu' => [
            'required' => 'Waktu harus diisi'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Get presensi dengan data user
     * UPDATED: Tambah filter tahun
     */
    public function getPresensiWithUser($filters = [])
    {
        $builder = $this->db->table($this->table)
            ->select('presensi.*, users.nip, users.nama')
            ->join('users', 'users.id = presensi.user_id', 'left');

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $builder->where('presensi.user_id', (int)$filters['user_id']);
        }
        
        if (isset($filters['tanggal']) && !empty($filters['tanggal'])) {
            $builder->where("DATE(presensi.waktu)", $filters['tanggal']);
        }
        
        // TAMBAHAN: Filter berdasarkan tahun
        if (isset($filters['tahun']) && !empty($filters['tahun'])) {
            $builder->where("YEAR(presensi.waktu)", (int)$filters['tahun']);
        }
        
        if (isset($filters['tanggal_mulai']) && isset($filters['tanggal_selesai'])) {
            if (!empty($filters['tanggal_mulai']) && !empty($filters['tanggal_selesai'])) {
                $builder->where("DATE(presensi.waktu) >=", $filters['tanggal_mulai']);
                $builder->where("DATE(presensi.waktu) <=", $filters['tanggal_selesai']);
            }
        }
        
        if (isset($filters['keterangan']) && !empty($filters['keterangan'])) {
            $builder->where('presensi.keterangan', $filters['keterangan']);
        }

        return $builder->orderBy('presensi.waktu', 'DESC')->get()->getResultArray();
    }

    /**
     * Cek apakah user sudah presensi hari ini
     * CRITICAL: Method ini harus bekerja dengan benar!
     */
    public function hasPresensiToday($userId)
    {
        if (empty($userId)) {
            log_message('error', 'hasPresensiToday: User ID kosong');
            return false;
        }

        // Set timezone WIB
        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');
        
        log_message('debug', 'Checking presensi for user ' . $userId . ' on ' . $today);
        
        $builder = $this->db->table($this->table);
        $builder->where('user_id', (int)$userId);
        $builder->where("DATE(waktu)", $today);
        
        $count = $builder->countAllResults();
        
        log_message('debug', 'Presensi count: ' . $count);
        
        return $count > 0;
    }

    /**
     * Get presensi hari ini untuk user tertentu
     */
    public function getPresensiToday($userId)
    {
        if (empty($userId)) {
            return null;
        }

        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');
        
        return $this->db->table($this->table)
            ->where('user_id', (int)$userId)
            ->where("DATE(waktu)", $today)
            ->get()
            ->getRowArray();
    }

    /**
     * Get statistik presensi bulan ini
     * UPDATED: Tambah 'ijin' ke stats
     */
    public function getStatsThisMonth($userId)
    {
        if (empty($userId)) {
            return [
                'total' => 0,
                'hadir' => 0,
                'terlambat' => 0,
                'ijin' => 0,
                'alpha' => 0
            ];
        }

        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');

        $presensi = $this->db->table($this->table)
            ->where('user_id', (int)$userId)
            ->where("DATE(waktu) >=", $startDate)
            ->where("DATE(waktu) <=", $endDate)
            ->get()
            ->getResultArray();

        $stats = [
            'total' => count($presensi),
            'hadir' => 0,
            'terlambat' => 0,
            'ijin' => 0,
            'alpha' => 0
        ];

        foreach ($presensi as $p) {
            if (isset($stats[$p['keterangan']])) {
                $stats[$p['keterangan']]++;
            }
        }

        return $stats;
    }

    /**
     * Insert presensi - Override untuk logging
     */
    public function insert($data = null, bool $returnID = true)
    {
        log_message('info', 'PresensiModel::insert called');
        log_message('info', 'Data: ' . json_encode($data));
        
        try {
            $result = parent::insert($data, $returnID);
            log_message('info', 'Insert result: ' . ($result ? 'Success - ID: ' . $result : 'Failed'));
            
            if (!$result) {
                $errors = $this->errors();
                log_message('error', 'Validation errors: ' . json_encode($errors));
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Insert exception: ' . $e->getMessage());
            return false;
        }
    }
}