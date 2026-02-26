<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BagianModel;

class Bagian extends BaseController
{
    protected $bagianModel;

    public function __construct()
    {
        $this->bagianModel = new BagianModel();
    }

    /** POST /admin/bagian/store — Tambah bagian baru */
    public function store()
    {
        $nama = trim($this->request->getPost('nama'));

        if (empty($nama)) {
            return redirect()->back()->with('error_bagian', 'Nama bagian tidak boleh kosong.');
        }

        if (strlen($nama) > 100) {
            return redirect()->back()->with('error_bagian', 'Nama bagian maksimal 100 karakter.');
        }

        // Buat kode otomatis: lowercase, spasi → underscore, strip non-alfanumerik
        $kode = strtolower(preg_replace('/[^a-z0-9]+/i', '', str_replace(' ', '_', $nama)));

        // Cek duplikat kode
        if ($this->bagianModel->where('kode', $kode)->first()) {
            return redirect()->back()->with('error_bagian', 'Bagian dengan nama tersebut sudah ada.');
        }

        $this->bagianModel->insert(['kode' => $kode, 'nama' => $nama]);

        return redirect()->back()->with('success_bagian', 'Bagian "' . $nama . '" berhasil ditambahkan.');
    }

    /** POST /admin/bagian/delete/:id — Hapus bagian */
    public function delete($id)
    {
        $bagian = $this->bagianModel->find($id);

        if (!$bagian) {
            return redirect()->back()->with('error_bagian', 'Bagian tidak ditemukan.');
        }

        // Cek apakah bagian sedang dipakai pegawai
        $userModel = new \App\Models\UserModel();
        $pakai = $userModel->where('bagian', $bagian['kode'])->countAllResults();

        if ($pakai > 0) {
            return redirect()->back()->with(
                'error_bagian',
                'Tidak bisa hapus! Bagian "' . $bagian['nama'] . '" masih digunakan oleh ' . $pakai . ' pegawai.'
            );
        }

        $this->bagianModel->delete($id);
        return redirect()->back()->with('success_bagian', 'Bagian "' . $bagian['nama'] . '" berhasil dihapus.');
    }
}