<?php
namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        // Jika sudah login, redirect ke dashboard sesuai role
        if (session()->get('logged_in')) {
            $role = session()->get('role');
            return redirect()->to(base_url($role . '/dashboard'));
        }

        $data = [
            'title' => 'Login - Sistem Absensi'
        ];
        
        return view('auth/login', $data);
    }

    public function loginProcess()
    {
        // Validasi input
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[3]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Username dan password harus diisi');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel->where('username', $username)->first();

        // Cek user dan password
        if (!$user || !password_verify($password, $user['password'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Username atau password salah');
        }

        // Cek status akun untuk pegawai
        if ($user['role'] == 'pegawai' && $user['status'] == 'nonaktif') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Akun Anda sudah dinonaktifkan. Silakan hubungi administrator.');
        }

        // Set session
        $sessionData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'nama' => $user['nama'],
            'nip' => $user['nip'],
            'bagian'   => $user['bagian'],
            'role' => $user['role'],
            'foto' => $user['foto'],
            'status' => $user['status'],
            'logged_in' => true
        ];

        session()->set($sessionData);

        // Redirect sesuai role
        return redirect()->to(base_url($user['role'] . '/dashboard'))
            ->with('success', 'Selamat datang, ' . $user['nama']);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login'))
            ->with('success', 'Anda berhasil logout');
    }
}