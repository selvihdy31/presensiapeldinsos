<?php

namespace App\Models;

use CodeIgniter\Model;

class IjinModel extends Model
{
    protected $table = 'ijin';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'user_id',
        'tanggal',
        'keterangan',
        'status',
        'keterangan_admin',
        'validated_by',
        'validated_at',
        'created_at'
    ];
    
    protected $useTimestamps = false;

    // Validation rules
    protected $validationRules = [
        'user_id' => 'required|integer',
        'tanggal' => 'required|valid_date[Y-m-d]',
        'keterangan' => 'required|min_length[10]',
        'status' => 'required|in_list[menunggu,disetujui,ditolak]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID harus diisi',
            'integer' => 'User ID harus berupa angka'
        ],
        'tanggal' => [
            'required' => 'Tanggal harus diisi',
            'valid_date' => 'Format tanggal tidak valid'
        ],
        'keterangan' => [
            'required' => 'Keterangan harus diisi',
            'min_length' => 'Keterangan minimal 10 karakter'
        ],
        'status' => [
            'required' => 'Status harus diisi',
            'in_list' => 'Status tidak valid (menunggu/disetujui/ditolak)'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Get ijin dengan data user dan validator
     */
    public function getIjinWithUser($filters = [])
    {
        $builder = $this->db->table($this->table)
            ->select('ijin.*, users.nip, users.nama, validator.nama as validator_nama')
            ->join('users', 'users.id = ijin.user_id', 'left')
            ->join('users as validator', 'validator.id = ijin.validated_by', 'left');

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $builder->where('ijin.user_id', (int)$filters['user_id']);
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $builder->where('ijin.status', $filters['status']);
        }
        
        if (isset($filters['tanggal_mulai']) && isset($filters['tanggal_selesai'])) {
            if (!empty($filters['tanggal_mulai']) && !empty($filters['tanggal_selesai'])) {
                $builder->where('ijin.tanggal >=', $filters['tanggal_mulai']);
                $builder->where('ijin.tanggal <=', $filters['tanggal_selesai']);
            }
        }

        return $builder->orderBy('ijin.tanggal', 'DESC')->get()->getResultArray();
    }

    /**
     * Get ijin pending dengan user info
     */
    public function getIjinPendingWithUser()
    {
        return $this->db->table($this->table)
            ->select('ijin.*, users.nip, users.nama, users.email')
            ->join('users', 'users.id = ijin.user_id', 'left')
            ->where('ijin.status', 'menunggu')
            ->orderBy('ijin.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Approve ijin dengan keterangan
     */
    public function approveIjin($id, $keteranganAdmin, $validatedBy)
    {
        return $this->update($id, [
            'status' => 'disetujui',
            'keterangan_admin' => $keteranganAdmin,
            'validated_by' => $validatedBy,
            'validated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Reject ijin dengan keterangan
     */
    public function rejectIjin($id, $keteranganAdmin, $validatedBy)
    {
        return $this->update($id, [
            'status' => 'ditolak',
            'keterangan_admin' => $keteranganAdmin,
            'validated_by' => $validatedBy,
            'validated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check apakah user sudah punya ijin di tanggal tertentu
     */
    public function hasIjinOnDate($userId, $tanggal)
    {
        $count = $this->where('user_id', $userId)
            ->where('tanggal', $tanggal)
            ->countAllResults();
        
        return $count > 0;
    }

    /**
     * Check apakah user sudah punya ijin pending di tanggal tertentu
     */
    public function hasIjinPendingOnDate($userId, $tanggal)
    {
        $count = $this->where('user_id', $userId)
            ->where('tanggal', $tanggal)
            ->where('status', 'menunggu')
            ->countAllResults();
        
        return $count > 0;
    }

    /**
     * Get statistik ijin bulan ini
     */
    public function getStatsThisMonth($userId = null)
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');

        $builder = $this->db->table($this->table)
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate);
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        $ijin = $builder->get()->getResultArray();

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

        return $stats;
    }

    /**
     * Get statistik ijin per status
     */
    public function getStatsByStatus()
    {
        $stats = [];
        
        $stats['menunggu'] = $this->where('status', 'menunggu')->countAllResults();
        $stats['disetujui'] = $this->where('status', 'disetujui')->countAllResults();
        $stats['ditolak'] = $this->where('status', 'ditolak')->countAllResults();
        $stats['total'] = $this->countAllResults();

        return $stats;
    }

    /**
     * Insert ijin - Override untuk logging
     */
    public function insert($data = null, bool $returnID = true)
    {
        log_message('info', 'IjinModel::insert called');
        log_message('info', 'Data: ' . json_encode($data));
        
        try {
            if (empty($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            
            if (empty($data['status'])) {
                $data['status'] = 'menunggu';
            }
            
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

    /**
     * Get ijin user tertentu
     */
    public function getByUserId($userId)
    {
        return $this->select('ijin.*, users.nama, users.nip, validator.nama as validator_nama')
            ->join('users', 'users.id = ijin.user_id', 'left')
            ->join('users as validator', 'validator.id = ijin.validated_by', 'left')
            ->where('ijin.user_id', $userId)
            ->orderBy('ijin.tanggal', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get ijin user tertentu bulan ini
     */
    public function getByUserIdThisMonth($userId)
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');

        return $this->select('ijin.*, users.nama, users.nip, validator.nama as validator_nama')
            ->join('users', 'users.id = ijin.user_id', 'left')
            ->join('users as validator', 'validator.id = ijin.validated_by', 'left')
            ->where('ijin.user_id', $userId)
            ->where('ijin.tanggal >=', $startDate)
            ->where('ijin.tanggal <=', $endDate)
            ->orderBy('ijin.tanggal', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get detail ijin
     */
    public function getDetail($id, $userId = null)
    {
        $builder = $this->select('ijin.*, users.nama, users.nip, users.email, validator.nama as validator_nama')
            ->join('users', 'users.id = ijin.user_id', 'left')
            ->join('users as validator', 'validator.id = ijin.validated_by', 'left')
            ->where('ijin.id', $id);
        
        if ($userId) {
            $builder->where('ijin.user_id', $userId);
        }
        
        return $builder->get()->getRowArray();
    }
}