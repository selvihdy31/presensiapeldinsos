<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PresensiModel;
use App\Models\QrCodeModel;

class Dashboard extends BaseController
{
    public function index()
    {
        // Cek role admin
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('pegawai/dashboard'));
        }

        $userModel = new UserModel();
        $presensiModel = new PresensiModel();
        $qrModel = new QrCodeModel();

        // Ambil filter bagian dari query string
        $bagian = $this->request->getGet('bagian');

        // Hitung total pegawai berdasarkan bagian
        $totalPegawaiQuery = $userModel->where('role', 'pegawai')->where('status', 'aktif');
        if (!empty($bagian)) {
            $totalPegawaiQuery->where('bagian', $bagian);
        }
        $totalPegawai = $totalPegawaiQuery->countAllResults();

        // Ambil semua ID pegawai aktif berdasarkan bagian
        $pegawaiQuery = $userModel->where('role', 'pegawai')->where('status', 'aktif');
        if (!empty($bagian)) {
            $pegawaiQuery->where('bagian', $bagian);
        }
        $pegawaiList = $pegawaiQuery->findAll();
        $pegawaiIds = array_column($pegawaiList, 'id');

        // Hitung pegawai yang sudah presensi hari ini
        $presensiHariIniQuery = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'));
        if (!empty($bagian) && !empty($pegawaiIds)) {
            $presensiHariIniQuery->whereIn('user_id', $pegawaiIds);
        }
        $presensiHariIni = $presensiHariIniQuery->countAllResults();

        // Hitung pegawai yang belum presensi
        $belumPresensi = $totalPegawai - $presensiHariIni;

        // QR Code aktif (tidak perlu filter bagian)
        $qrAktif = $qrModel
            ->where('status', 'aktif')
            ->where('tanggal', date('Y-m-d'))
            ->countAllResults();

        // Presensi terbaru dengan filter bagian
        $filters = [];
        if (!empty($bagian)) {
            $filters['bagian'] = $bagian;
        }
        $recentPresensi = $presensiModel->getPresensiWithUser($filters);
        $recentPresensi = array_slice($recentPresensi, 0, 10);

        // Statistik keterangan hari ini berdasarkan bagian
        $hadirQuery = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'))
            ->where('keterangan', 'hadir');
        if (!empty($bagian) && !empty($pegawaiIds)) {
            $hadirQuery->whereIn('user_id', $pegawaiIds);
        }
        $hadirHariIni = $hadirQuery->countAllResults();
        
        // PERUBAHAN: Ganti dari 'terlambat' menjadi 'alpha'
        $alphaQuery = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'))
            ->where('keterangan', 'alpha');
        if (!empty($bagian) && !empty($pegawaiIds)) {
            $alphaQuery->whereIn('user_id', $pegawaiIds);
        }
        $alphaHariIni = $alphaQuery->countAllResults();
        
        $ijinQuery = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'))
            ->where('keterangan', 'ijin');
        if (!empty($bagian) && !empty($pegawaiIds)) {
            $ijinQuery->whereIn('user_id', $pegawaiIds);
        }
        $ijinHariIni = $ijinQuery->countAllResults();

        $data = [
            'title' => 'Dashboard Admin',
            'total_pegawai' => $totalPegawai,
            'presensi_hari_ini' => $presensiHariIni,
            'belum_presensi' => $belumPresensi,
            'qr_aktif' => $qrAktif,
            'hadir_hari_ini' => $hadirHariIni,
            'alpha_hari_ini' => $alphaHariIni,  // PERUBAHAN: Ganti nama variabel
            'ijin_hari_ini' => $ijinHariIni,
            'recent_presensi' => $recentPresensi,
            'selected_bagian' => $bagian
        ];

        return view('admin/dashboard', $data);
    }
}