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

        // Hitung statistik
        $totalPegawai = $userModel->where('role', 'pegawai')->countAllResults();
        $presensiHariIni = $presensiModel->where('DATE(waktu)', date('Y-m-d'))->countAllResults();
        $qrAktif = $qrModel
            ->where('status', 'aktif')
            ->where('tanggal', date('Y-m-d'))
            ->countAllResults();

        // Presensi terbaru (limit 10)
        $recentPresensi = $presensiModel->getPresensiWithUser([]);
        $recentPresensi = array_slice($recentPresensi, 0, 10);

        // Statistik keterangan hari ini
        $hadirHariIni = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'))
            ->where('keterangan', 'hadir')
            ->countAllResults();
            
        $terlambatHariIni = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'))
            ->where('keterangan', 'terlambat')
            ->countAllResults();
            
        $ijinHariIni = $presensiModel
            ->where('DATE(waktu)', date('Y-m-d'))
            ->where('keterangan', 'ijin')
            ->countAllResults();

        $data = [
            'title' => 'Dashboard Admin',
            'total_pegawai' => $totalPegawai,
            'presensi_hari_ini' => $presensiHariIni,
            'qr_aktif' => $qrAktif,
            'hadir_hari_ini' => $hadirHariIni,
            'terlambat_hari_ini' => $terlambatHariIni,
            'ijin_hari_ini' => $ijinHariIni,
            'recent_presensi' => $recentPresensi
        ];

        return view('admin/dashboard', $data);
    }
}