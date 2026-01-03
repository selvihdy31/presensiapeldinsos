<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\UserModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;

class Laporan extends BaseController
{
    protected $presensiModel;
    protected $userModel;

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $filters = [];
        
        if ($this->request->getGet('user_id')) {
            $filters['user_id'] = $this->request->getGet('user_id');
        }
        if ($this->request->getGet('bagian')) {
            $filters['bagian'] = $this->request->getGet('bagian');
        }
        if ($this->request->getGet('tanggal')) {
            $filters['tanggal'] = $this->request->getGet('tanggal');
        }
        if ($this->request->getGet('tahun')) {
            $filters['tahun'] = $this->request->getGet('tahun');
        }
        if ($this->request->getGet('tanggal_mulai') && $this->request->getGet('tanggal_selesai')) {
            $filters['tanggal_mulai'] = $this->request->getGet('tanggal_mulai');
            $filters['tanggal_selesai'] = $this->request->getGet('tanggal_selesai');
        }
        if ($this->request->getGet('keterangan')) {
            $filters['keterangan'] = $this->request->getGet('keterangan');
        }

        $data = [
            'title' => 'Laporan Presensi',
            'presensi' => $this->presensiModel->getPresensiWithUser($filters),
            'pegawai' => $this->userModel->where('role', 'pegawai')->findAll(),
            'filters' => $filters
        ];

        return view('admin/laporan/index', $data);
    }

    /**
     * Export Laporan ke PDF - Format Template Daftar Hadir
     */
    public function exportPdf()
    {
        try {
            $filters = $this->_getFilters();
            
            // Ambil data presensi
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            // Group data by date
            $groupedData = [];
            foreach ($presensi as $p) {
                $date = date('Y-m-d', strtotime($p['waktu']));
                if (!isset($groupedData[$date])) {
                    $groupedData[$date] = [];
                }
                $groupedData[$date][] = $p;
            }

            // Generate HTML untuk PDF
            $html = $this->_generatePdfTemplate($groupedData, $filters);

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('F4', 'portrait');
            $dompdf->render();

            $filename = 'daftar-hadir-apel-' . date('Y-m-d-His') . '.pdf';

            $dompdf->stream($filename, [
                'Attachment' => true,
                'compress' => true
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Export PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export Laporan ke Excel - Format Template Daftar Hadir
     */
    public function exportExcel()
    {
        try {
            $filters = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            // Group by date
            $groupedData = [];
            foreach ($presensi as $p) {
                $date = date('Y-m-d', strtotime($p['waktu']));
                if (!isset($groupedData[$date])) {
                    $groupedData[$date] = [];
                }
                $groupedData[$date][] = $p;
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $row = 1;
            $dateIndex = 0;
            $totalDates = count($groupedData);

            foreach ($groupedData as $date => $attendances) {
                $dateIndex++;
                
                // Hitung jumlah hadir dan tidak hadir
                $jumlahHadir = 0;
                $jumlahTidakHadir = 0;
                foreach ($attendances as $att) {
                    if (in_array(strtolower($att['keterangan']), ['hadir', 'terlambat'])) {
                        $jumlahHadir++;
                    } else {
                        $jumlahTidakHadir++;
                    }
                }

                // Title - Baris 1
                $sheet->setCellValue('A' . $row, 'DAFTAR HADIR APEL PAGI');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // Subtitle - Baris 2
                $sheet->setCellValue('A' . $row, 'DINAS SOSIAL KAB. BATANG');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // HARI/TANGGAL - Baris 3
                $sheet->setCellValue('A' . $row, 'HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)));
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 2;

                // Table header
                $headerRow = $row;
                $sheet->setCellValue('A' . $headerRow, 'NO');
                $sheet->setCellValue('B' . $headerRow, 'NAMA');
                $sheet->setCellValue('C' . $headerRow, 'NIP');
                $sheet->setCellValue('D' . $headerRow, 'BIDANG');
                $sheet->setCellValue('E' . $headerRow, 'KETERANGAN');

                // Style header
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('CCCCCC');
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // Data rows (max 18 baris sesuai template)
                $no = 1;
                for ($i = 0; $i < 18; $i++) {
                    if (isset($attendances[$i])) {
                        $p = $attendances[$i];
                        $sheet->setCellValue('A' . $row, $no);
                        $sheet->setCellValue('B' . $row, $p['nama']);
                        $sheet->setCellValue('C' . $row, $p['nip']);
                        $sheet->setCellValue('D' . $row, $this->_getBagianLabel($p['bagian']));
                        $sheet->setCellValue('E' . $row, strtoupper($p['keterangan']));
                    } else {
                        $sheet->setCellValue('A' . $row, $no);
                        $sheet->setCellValue('B' . $row, '');
                        $sheet->setCellValue('C' . $row, '');
                        $sheet->setCellValue('D' . $row, '');
                        $sheet->setCellValue('E' . $row, '');
                    }
                    $row++;
                    $no++;
                }

                // Keterangan section (kiri) dan TTD (kanan) - sejajar
                $row++;
                $keteranganRow = $row;
                
                // Bagian kiri: Keterangan jumlah hadir/tidak hadir
                $sheet->setCellValue('A' . $row, 'KETERANGAN :');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                $sheet->setCellValue('A' . $row, 'HADIR = ' . $jumlahHadir);
                $row++;
                $sheet->setCellValue('A' . $row, 'TIDAK HADIR = ' . $jumlahTidakHadir);

                // Bagian kanan: TTD - mulai dari row yang sama dengan KETERANGAN
                $ttdRow = $keteranganRow;
                $sheet->setCellValue('D' . $ttdRow, 'Kepala Dinas Sosial');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;
                $sheet->setCellValue('D' . $ttdRow, 'Kabupaten Batang');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Tambah jarak kosong (3 baris) antara "Kabupaten Batang" dan nama
                $ttdRow += 3;
                
                $sheet->setCellValue('D' . $ttdRow, 'WILLOPO, AP., M.M.');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getFont()->setBold(true)->setUnderline(true);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;
                $sheet->setCellValue('D' . $ttdRow, 'Pembina Utama Muda');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;
                $sheet->setCellValue('D' . $ttdRow, 'NIP.19740502 199311 1 001');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Border untuk tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A' . $headerRow . ':E' . ($headerRow + 18))->applyFromArray($styleArray);

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(20);

                // Update row untuk halaman berikutnya (hanya jika bukan data terakhir)
                if ($dateIndex < $totalDates) {
                    $row = max($row, $ttdRow) + 3;
                }
            }

            $filename = 'daftar-hadir-apel-' . date('Y-m-d-His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Export Laporan ke Word - Format Template Daftar Hadir
     */
    public function exportWord()
    {
        try {
            $filters = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            // Group by date
            $groupedData = [];
            foreach ($presensi as $p) {
                $date = date('Y-m-d', strtotime($p['waktu']));
                if (!isset($groupedData[$date])) {
                    $groupedData[$date] = [];
                }
                $groupedData[$date][] = $p;
            }

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $dateIndex = 0;
            $totalDates = count($groupedData);

            foreach ($groupedData as $date => $attendances) {
                $dateIndex++;
                
                // Hitung jumlah hadir dan tidak hadir
                $jumlahHadir = 0;
                $jumlahTidakHadir = 0;
                foreach ($attendances as $att) {
                    if (in_array(strtolower($att['keterangan']), ['hadir', 'terlambat'])) {
                        $jumlahHadir++;
                    } else {
                        $jumlahTidakHadir++;
                    }
                }

                $section = $phpWord->addSection([
                    'marginLeft' => 600,
                    'marginRight' => 600,
                    'marginTop' => 600,
                    'marginBottom' => 600,
                ]);

                // Title - Baris 1
                $section->addText(
                    'DAFTAR HADIR APEL PAGI',
                    ['bold' => true, 'size' => 14],
                    ['alignment' => Jc::CENTER]
                );
                
                // Subtitle - Baris 2
                $section->addText(
                    'DINAS SOSIAL KAB. BATANG',
                    ['bold' => true, 'size' => 14],
                    ['alignment' => Jc::CENTER]
                );
                
                // HARI/TANGGAL - Baris 3
                $section->addText(
                    'HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)),
                    ['size' => 11],
                    ['alignment' => Jc::CENTER]
                );
                $section->addTextBreak(2);

                // Table
                $cellStyle = [
                    'borderSize' => 6,
                    'borderColor' => '000000',
                ];

                $table = $section->addTable([
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'width' => 100 * 50,
                    'unit' => 'pct'
                ]);

                // Header row
                $table->addRow(400);
                $table->addCell(1000, $cellStyle)->addText('NO', ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(3500, $cellStyle)->addText('NAMA', ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(2500, $cellStyle)->addText('NIP', ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText('BIDANG', ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText('KETERANGAN', ['bold' => true], ['alignment' => Jc::CENTER]);

                // Data rows (max 18)
                for ($i = 0; $i < 18; $i++) {
                    $table->addRow();
                    if (isset($attendances[$i])) {
                        $p = $attendances[$i];
                        $table->addCell(1000, $cellStyle)->addText(($i + 1), null, ['alignment' => Jc::CENTER]);
                        $table->addCell(3500, $cellStyle)->addText($p['nama']);
                        $table->addCell(2500, $cellStyle)->addText($p['nip']);
                        $table->addCell(1500, $cellStyle)->addText($this->_getBagianLabel($p['bagian']), null, ['alignment' => Jc::CENTER]);
                        $table->addCell(1500, $cellStyle)->addText(strtoupper($p['keterangan']), null, ['alignment' => Jc::CENTER]);
                    } else {
                        $table->addCell(1000, $cellStyle)->addText(($i + 1), null, ['alignment' => Jc::CENTER]);
                        $table->addCell(3500, $cellStyle)->addText('');
                        $table->addCell(2500, $cellStyle)->addText('');
                        $table->addCell(1500, $cellStyle)->addText('');
                        $table->addCell(1500, $cellStyle)->addText('');
                    }
                }

                $section->addTextBreak(1);

                // Buat tabel untuk layout 2 kolom (keterangan di kiri, TTD di kanan)
                $layoutTable = $section->addTable([
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                    'width' => 100 * 50,
                    'unit' => 'pct'
                ]);

                $layoutTable->addRow();
                
                // Kolom kiri: Keterangan
                $leftCell = $layoutTable->addCell(5000, ['borderSize' => 0]);
                $leftCell->addText('KETERANGAN :', ['bold' => true]);
                $leftCell->addText('HADIR = ' . $jumlahHadir);
                $leftCell->addText('TIDAK HADIR = ' . $jumlahTidakHadir);

                // Kolom kanan: TTD
                $rightCell = $layoutTable->addCell(5000, ['borderSize' => 0, 'valign' => 'top']);
                $rightCell->addText('Kepala Dinas Sosial', null, ['alignment' => Jc::CENTER]);
                $rightCell->addText('Kabupaten Batang', null, ['alignment' => Jc::CENTER]);
                
                // Tambah jarak kosong (3 textbreak) antara "Kabupaten Batang" dan nama
                $rightCell->addTextBreak(3);
                
                $rightCell->addText('WILLOPO, AP., M.M.', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
                $rightCell->addText('Pembina Utama Muda', null, ['alignment' => Jc::CENTER]);
                $rightCell->addText('NIP.19740502 199311 1 001', null, ['alignment' => Jc::CENTER]);

                // Page break untuk tanggal berikutnya (hanya jika bukan data terakhir)
                if ($dateIndex < $totalDates) {
                    $section->addPageBreak();
                }
            }

            $filename = 'daftar-hadir-apel-' . date('Y-m-d-His') . '.docx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Word Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Word: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF Template HTML
     */
    private function _generatePdfTemplate($groupedData, $filters)
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; font-size: 11px; }
            .page { page-break-after: always; padding: 20px; }
            .page:last-child { page-break-after: auto; }
            .header { text-align: center; margin-bottom: 10px; }
            .title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 3px; }
            .subtitle { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #ddd; font-weight: bold; text-align: center; }
            td.center { text-align: center; }
            .footer-section { display: table; width: 100%; margin-top: 20px; }
            .footer-left { display: table-cell; width: 50%; vertical-align: top; }
            .footer-right { display: table-cell; width: 50%; text-align: center; vertical-align: top; }
            .keterangan { margin-top: 0; }
            .signature div { margin: 5px 0; }
            .signature-space { margin-top: 140px; margin-bottom: 7px; }
        </style></head><body>';

        $dateIndex = 0;
        $totalDates = count($groupedData);

        foreach ($groupedData as $date => $attendances) {
            $dateIndex++;
            
            // Hitung jumlah hadir dan tidak hadir
            $jumlahHadir = 0;
            $jumlahTidakHadir = 0;
            foreach ($attendances as $att) {
                if (in_array(strtolower($att['keterangan']), ['hadir', 'terlambat'])) {
                    $jumlahHadir++;
                } else {
                    $jumlahTidakHadir++;
                }
            }

            $html .= '<div class="page">';
            
            // Title - Baris 1
            $html .= '<div class="title">DAFTAR HADIR APEL PAGI</div>';
            
            // Subtitle - Baris 2
            $html .= '<div class="subtitle">DINAS SOSIAL KAB. BATANG</div>';
            
            // HARI/TANGGAL - Baris 3
            $html .= '<div class="header">HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)) . '</div>';
            
            $html .= '<table><thead><tr>';
            $html .= '<th width="5%">NO</th>';
            $html .= '<th width="30%">NAMA</th>';
            $html .= '<th width="25%">NIP</th>';
            $html .= '<th width="20%">BIDANG</th>';
            $html .= '<th width="20%">KETERANGAN</th>';
            $html .= '</tr></thead><tbody>';

            for ($i = 0; $i < 18; $i++) {
                $html .= '<tr>';
                if (isset($attendances[$i])) {
                    $p = $attendances[$i];
                    $html .= '<td class="center">' . ($i + 1) . '</td>';
                    $html .= '<td>' . $p['nama'] . '</td>';
                    $html .= '<td>' . $p['nip'] . '</td>';
                    $html .= '<td class="center">' . $this->_getBagianLabel($p['bagian']) . '</td>';
                    $html .= '<td class="center">' . strtoupper($p['keterangan']) . '</td>';
                } else {
                    $html .= '<td class="center">' . ($i + 1) . '</td>';
                    $html .= '<td></td><td></td><td></td><td></td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            
            // Footer section dengan 2 kolom
            $html .= '<div class="footer-section">';
            
            // Kolom kiri: Keterangan
            $html .= '<div class="footer-left">';
            $html .= '<div class="keterangan">';
            $html .= '<div><strong>KETERANGAN :</strong></div>';
            $html .= '<div>HADIR = ' . $jumlahHadir . '</div>';
            $html .= '<div>TIDAK HADIR = ' . $jumlahTidakHadir . '</div>';
            $html .= '</div>';
            $html .= '</div>';

            // Kolom kanan: TTD
            $html .= '<div class="footer-right">';
            $html .= '<div class="signature">';
            $html .= '<div>Kepala Dinas Sosial</div>';
            $html .= '<div>Kabupaten Batang</div>';
            // Jarak kosong untuk tanda tangan (lebih lebar untuk PDF)
            $html .= '<div class="signature-space">&nbsp;</div>';
            $html .= '<div><strong><u>WILLOPO, AP., M.M.</u></strong></div>';
            $html .= '<div>Pembina Utama Muda</div>';
            $html .= '<div>NIP.19740502 199311 1 001</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>'; // end footer-section
            
            $html .= '</div>'; // end page
        }

        $html .= '</body></html>';
        return $html;
    }

    /**
     * Helper function untuk mendapatkan nama hari dalam bahasa Indonesia
     */
    private function _getDayName($date)
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $days[date('w', strtotime($date))];
    }

    /**
     * Helper function untuk mendapatkan label bagian
     */
    private function _getBagianLabel($bagian)
    {
        $labels = [
            'sekretariat' => 'Sekretariat',
            'rehlinjamsos' => 'Rehlinjamsos',
            'dayasos' => 'Dayasos'
        ];
        return isset($labels[$bagian]) ? $labels[$bagian] : '-';
    }
    private function _getFilters()
    {
        $filters = [];
        
        if ($this->request->getGet('user_id')) {
            $filters['user_id'] = $this->request->getGet('user_id');
        }
        if ($this->request->getGet('bagian')) {
            $filters['bagian'] = $this->request->getGet('bagian');
        }
        if ($this->request->getGet('tanggal')) {
            $filters['tanggal'] = $this->request->getGet('tanggal');
        }
        if ($this->request->getGet('tahun')) {
            $filters['tahun'] = $this->request->getGet('tahun');
        }
        if ($this->request->getGet('tanggal_mulai')) {
            $filters['tanggal_mulai'] = $this->request->getGet('tanggal_mulai');
        }
        if ($this->request->getGet('tanggal_selesai')) {
            $filters['tanggal_selesai'] = $this->request->getGet('tanggal_selesai');
        }
        if ($this->request->getGet('keterangan')) {
            $filters['keterangan'] = $this->request->getGet('keterangan');
        }
        
        return $filters;
    }
}