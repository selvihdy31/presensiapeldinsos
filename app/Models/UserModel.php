<?php
namespace App\Models;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nip', 'nama', 'username', 'password', 'role', 'foto', 'status', 'bagian'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nip' => 'required|exact_length[18]|numeric',
        'nama' => 'required|min_length[3]|max_length[100]',
        'username' => 'required|min_length[3]|max_length[50]',
        'role' => 'required|in_list[admin,pegawai]',
        'status' => 'in_list[aktif,nonaktif]',
        'bagian' => 'permit_empty|in_list[sekretariat,rehlinjamsos,dayasos]'
    ];

    public function getAllPegawai()
    {
        return $this->where('role', 'pegawai')->findAll();
    }

    public function getAllPegawaiAktif()
    {
        return $this->where('role', 'pegawai')
                    ->where('status', 'aktif')
                    ->findAll();
    }
    
    public function getPegawaiByBagian($bagian)
    {
        return $this->where('role', 'pegawai')
                    ->where('bagian', $bagian)
                    ->where('status', 'aktif')
                    ->findAll();
    }
}