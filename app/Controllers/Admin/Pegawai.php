<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\BagianModel;

class Pegawai extends BaseController
{
    protected $userModel;
    protected $bagianModel;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->bagianModel = new BagianModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Manajemen Pegawai',
            'pegawai' => $this->userModel->where('role', 'pegawai')->findAll()
        ];
        return view('admin/pegawai/index', $data);
    }

    public function create()
    {
        return view('admin/pegawai/create', [
            'title'         => 'Tambah Pegawai',
            'bagianOptions' => $this->bagianModel->getAsOptions()
        ]);
    }

    public function store()
    {
        $rules = [
            'nip'      => 'required|min_length[3]|is_unique[users.nip]',
            'nama'     => 'required|min_length[3]',
            'username' => 'required|min_length[3]|is_unique[users.username]',
            'password' => 'required|min_length[6]',
        ];

        $errors = [
            'nip'      => ['is_unique' => 'NIP sudah terdaftar'],
            'username' => ['is_unique' => 'Username sudah digunakan']
        ];

        if (!$this->validate($rules, $errors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nip'      => $this->request->getPost('nip'),
            'nama'     => $this->request->getPost('nama'),
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'bagian'   => $this->request->getPost('bagian'),
            'role'     => 'pegawai',
            'status'   => 'aktif'
        ];

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
            'title'         => 'Edit Pegawai',
            'pegawai'       => $pegawai,
            'bagianOptions' => $this->bagianModel->getAsOptions() // <-- fix
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
            'nip'      => "required|min_length[3]|is_unique[users.nip,id,{$id}]",
            'nama'     => 'required|min_length[3]',
            'username' => "required|min_length[3]|is_unique[users.username,id,{$id}]",
        ];

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nip'      => $this->request->getPost('nip'),
            'nama'     => $this->request->getPost('nama'),
            'username' => $this->request->getPost('username'),
            'bagian'   => $this->request->getPost('bagian'),
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
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

        $this->userModel->delete($id);

        return redirect()->to(base_url('admin/pegawai'))
            ->with('success', 'Pegawai berhasil dihapus');
    }
}