<?php
namespace App\Models;

use CodeIgniter\Model;

class BagianModel extends Model
{
    protected $table      = 'bagian';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['kode', 'nama'];
    protected $useTimestamps = false;

    protected $validationRules = [
        'kode' => 'required|min_length[2]|max_length[50]|is_unique[bagian.kode,id,{id}]',
        'nama' => 'required|min_length[2]|max_length[100]',
    ];

    protected $validationMessages = [
        'kode' => [
            'required'   => 'Kode bagian harus diisi',
            'is_unique'  => 'Kode bagian sudah digunakan',
            'max_length' => 'Kode bagian maksimal 50 karakter',
        ],
        'nama' => [
            'required'  => 'Nama bagian harus diisi',
            'max_length' => 'Nama bagian maksimal 100 karakter',
        ],
    ];

    /** Ambil semua bagian sebagai array asosiatif kode => nama */
    public function getAsOptions(): array
    {
        $rows = $this->orderBy('nama', 'ASC')->findAll();
        $options = [];
        foreach ($rows as $row) {
            $options[$row['kode']] = $row['nama'];
        }
        return $options;
    }
}