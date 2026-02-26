<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\UserModel;
use App\Models\QrCodeModel;
use App\Models\BagianModel;

class Dashboard extends BaseController
{
    public function index()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('pegawai/dashboard'));
        }

        $userModel     = new UserModel();
        $presensiModel = new PresensiModel();
        $qrModel       = new QrCodeModel();
        $bagianModel   = new BagianModel();

        $bagian        = $this->request->getGet('bagian');
        $bagianOptions = $bagianModel->getAsOptions(); // ['sekretariat' => 'Sekretariat', ...]

        // Total pegawai
        $totalPegawaiQuery = $userModel->where('role', 'pegawai')->where('status', 'aktif');
        if (!empty($bagian)) $totalPegawaiQuery->where('bagian', $bagian);
        $totalPegawai = $totalPegawaiQuery->countAllResults();

        // ID pegawai aktif berdasarkan filter bagian
        $pegawaiQuery = $userModel->where('role', 'pegawai')->where('status', 'aktif');
        if (!empty($bagian)) $pegawaiQuery->where('bagian', $bagian);
        $pegawaiList = $pegawaiQuery->findAll();
        $pegawaiIds  = array_column($pegawaiList, 'id');

        // Presensi hari ini
        $presensiHariIniQuery = $presensiModel->where('DATE(waktu)', date('Y-m-d'));
        if (!empty($bagian) && !empty($pegawaiIds)) {
            $presensiHariIniQuery->whereIn('user_id', $pegawaiIds);
        }
        $presensiHariIni = $presensiHariIniQuery->countAllResults();
        $belumPresensi   = $totalPegawai - $presensiHariIni;

        // QR aktif
        $qrAktif = $qrModel->where('status', 'aktif')->where('tanggal', date('Y-m-d'))->countAllResults();

        // Recent presensi
        $filters = [];
        if (!empty($bagian)) $filters['bagian'] = $bagian;
        $recentPresensi = array_slice($presensiModel->getPresensiWithUser($filters), 0, 10);

        // Statistik keterangan
        $buildQuery = function($ket) use ($presensiModel, $bagian, $pegawaiIds) {
            $q = $presensiModel->where('DATE(waktu)', date('Y-m-d'))->where('keterangan', $ket);
            if (!empty($bagian) && !empty($pegawaiIds)) $q->whereIn('user_id', $pegawaiIds);
            return $q->countAllResults();
        };

        $data = [
            'title'            => 'Dashboard Admin',
            'total_pegawai'    => $totalPegawai,
            'presensi_hari_ini' => $presensiHariIni,
            'belum_presensi'   => $belumPresensi,
            'qr_aktif'         => $qrAktif,
            'hadir_hari_ini'   => $buildQuery('hadir'),
            'alpha_hari_ini'   => $buildQuery('alpha'),
            'ijin_hari_ini'    => $buildQuery('ijin'),
            'recent_presensi'  => $recentPresensi,
            'selected_bagian'  => $bagian,
            'bagianOptions'    => $bagianOptions,  // << inject ke view
        ];

        return view('admin/dashboard', $data);
    }
}