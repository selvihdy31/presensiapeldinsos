<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nip' => '123456',
                'nama' => 'Administrator',
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'nip' => '789012',
                'nama' => 'Pegawai Demo',
                'username' => 'pegawai',
                'password' => password_hash('pegawai123', PASSWORD_DEFAULT),
                'role' => 'pegawai',
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->db->table('users')->insertBatch($data);
    }
}