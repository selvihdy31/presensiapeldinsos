<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Pegawai extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Manajemen Pegawai',
            'pegawai' => $this->userModel->where('role', 'pegawai')->findAll()
        ];
        return view('admin/pegawai/index', $data);
    }

    public function create()
    {
        return view('admin/pegawai/create', ['title' => 'Tambah Pegawai']);
    }

    public function store()
    {
        $rules = [
            'nip' => 'required|min_length[3]|is_unique[users.nip]',
            'nama' => 'required|min_length[3]',
            'username' => 'required|min_length[3]|is_unique[users.username]',
            'password' => 'required|min_length[6]',
            'bagian' => 'required|in_list[sekretariat,rehlinjamsos,dayasos]',
            'foto' => 'max_size[foto,2048]|is_image[foto]|mime_in[foto,image/jpg,image/jpeg,image/png]'
        ];

        $errors = [
            'nip' => [
                'is_unique' => 'NIP sudah terdaftar'
            ],
            'username' => [
                'is_unique' => 'Username sudah digunakan'
            ],
            'bagian' => [
                'required' => 'Bagian harus dipilih',
                'in_list' => 'Bagian tidak valid'
            ],
            'foto' => [
                'max_size' => 'Ukuran foto maksimal 2MB',
                'is_image' => 'File harus berupa gambar',
                'mime_in' => 'Format foto harus JPG/PNG'
            ]
        ];

        if (!$this->validate($rules, $errors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nip' => $this->request->getPost('nip'),
            'nama' => $this->request->getPost('nama'),
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'bagian' => $this->request->getPost('bagian'),
            'role' => 'pegawai',
            'status' => 'aktif'
        ];

        // Upload foto ke folder public/uploads/pegawai
        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = 'pegawai_' . time() . '_' . $foto->getRandomName();
            
            // Pastikan folder ada
            $uploadPath = FCPATH . 'uploads/pegawai';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $foto->move($uploadPath, $newName);
            $data['foto'] = $newName;
        }

        $this->userModel->insert($data);
        
        return redirect()->to(base_url('admin/pegawai'))
            ->with('success', 'Pegawai berhasil ditambahkan');
    }

    public function edit($id)
    {
        $pegawai = $this->userModel->find($id);
        
        if (!$pegawai) {
            return redirect()->to(base_url('admin/pegawai'))
                ->with('error', 'Pegawai tidak ditemukan');
        }

        $data = [
            'title' => 'Edit Pegawai',
            'pegawai' => $pegawai
        ];
        
        return view('admin/pegawai/edit', $data);
    }

    public function update($id)
    {
        $pegawai = $this->userModel->find($id);
        
        if (!$pegawai) {
            return redirect()->to(base_url('admin/pegawai'))
                ->with('error', 'Pegawai tidak ditemukan');
        }

        $rules = [
            'nip' => "required|min_length[3]|is_unique[users.nip,id,{$id}]",
            'nama' => 'required|min_length[3]',
            'username' => "required|min_length[3]|is_unique[users.username,id,{$id}]",
            'bagian' => 'required|in_list[sekretariat,rehlinjamsos,dayasos]',
            'foto' => 'max_size[foto,2048]|is_image[foto]|mime_in[foto,image/jpg,image/jpeg,image/png]'
        ];

        // Jika password diisi, tambahkan validasi
        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nip' => $this->request->getPost('nip'),
            'nama' => $this->request->getPost('nama'),
            'username' => $this->request->getPost('username'),
            'bagian' => $this->request->getPost('bagian')
        ];

        // Update password jika diisi
        if ($this->request->getPost('password')) {
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        // Upload foto baru jika ada
        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            // Hapus foto lama
            if ($pegawai['foto']) {
                $oldPath = FCPATH . 'uploads/pegawai/' . $pegawai['foto'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $newName = 'pegawai_' . time() . '_' . $foto->getRandomName();
            $uploadPath = FCPATH . 'uploads/pegawai';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $foto->move($uploadPath, $newName);
            $data['foto'] = $newName;
        }

        $this->userModel->update($id, $data);
        
        return redirect()->to(base_url('admin/pegawai'))
            ->with('success', 'Data pegawai berhasil diupdate');
    }

    public function toggleStatus($id)
    {
        $pegawai = $this->userModel->find($id);
        
        if (!$pegawai) {
            return redirect()->to(base_url('admin/pegawai'))
                ->with('error', 'Pegawai tidak ditemukan');
        }

        // Toggle status
        $newStatus = ($pegawai['status'] == 'aktif') ? 'nonaktif' : 'aktif';
        
        $this->userModel->update($id, ['status' => $newStatus]);
        
        $message = ($newStatus == 'aktif') ? 'Pegawai berhasil diaktifkan' : 'Pegawai berhasil dinonaktifkan';
        
        return redirect()->to(base_url('admin/pegawai'))
            ->with('success', $message);
    }

    public function delete($id)
    {
        $pegawai = $this->userModel->find($id);
        
        if (!$pegawai) {
            return redirect()->to(base_url('admin/pegawai'))
                ->with('error', 'Pegawai tidak ditemukan');
        }

        // Hapus foto jika ada
        if ($pegawai['foto']) {
            $fotoPath = FCPATH . 'uploads/pegawai/' . $pegawai['foto'];
            if (file_exists($fotoPath)) {
                unlink($fotoPath);
            }
        }

        $this->userModel->delete($id);
        
        return redirect()->to(base_url('admin/pegawai'))
            ->with('success', 'Pegawai berhasil dihapus');
    }
}