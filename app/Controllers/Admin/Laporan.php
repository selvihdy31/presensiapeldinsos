<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\UserModel;
use App\Models\BagianModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class LaporanController extends BaseController
{
    protected PresensiModel $presensiModel;
    protected UserModel $userModel;
    protected BagianModel $bagianModel;
    protected array $bagianOptions;

    // Kolom header tabel (bisa disesuaikan)
    protected array $tableHeaders = ['Nama'];

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->userModel     = new UserModel();
        $this->bagianModel   = new BagianModel();
        $this->bagianOptions = $this->bagianModel->getAsOptions();
    }

    // =========================================================================
    // PUBLIC ACTIONS
    // =========================================================================

    public function index()
    {
        $filters = $this->_getFilters();
        $data = [
            'title'        => 'Laporan Presensi',
            'presensi'     => $this->presensiModel->getPresensiWithUser($filters),
            'pegawai'      => $this->userModel->where('role', 'pegawai')->findAll(),
            'filters'      => $filters,
            'bagianOptions' => $this->bagianOptions,
        ];
        return view('admin/laporan/index', $data);
    }

    // -------------------------------------------------------------------------
    // Export PDF
    // -------------------------------------------------------------------------
    public function exportPdf()
    {
        try {
            $filters  = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $groupedData = $this->_groupByDate($presensi);
            $html        = $this->_generatePdfTemplate($groupedData, $filters);

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('F4', 'portrait');
            $dompdf->render();
            $dompdf->stream(
                'daftar-hadir-apel-' . date('Y-m-d-His') . '.pdf',
                ['Attachment' => true]
            );
        } catch (\Exception $e) {
            log_message('error', 'Export PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Export Excel
    // -------------------------------------------------------------------------
    public function exportExcel()
    {
        try {
            $filters  = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $groupedData = $this->_groupByDate($presensi);
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();

            $row        = 1;
            $dateIndex  = 0;
            $totalDates = count($groupedData);

            foreach ($groupedData as $date => $attendances) {
                $dateIndex++;
                [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);

                // ----- Judul -----
                $sheet->setCellValue('A' . $row, 'DAFTAR HADIR APEL PAGI');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->setCellValue('A' . $row, 'DINAS SOSIAL KAB. BATANG');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->setCellValue('A' . $row, 'HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)));
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 2;

                // ----- Header tabel -----
                $headerRow = $row;
                foreach (['A' => 'NO', 'B' => 'NAMA', 'C' => 'NIP', 'D' => 'BIDANG', 'E' => 'KETERANGAN'] as $col => $label) {
                    $sheet->setCellValue($col . $headerRow, $label);
                }
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFill()
                      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('CCCCCC');
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // ----- Baris data (18 baris) -----
                for ($i = 0; $i < 18; $i++) {
                    if (isset($attendances[$i])) {
                        $p = $attendances[$i];
                        $sheet->setCellValue('A' . $row, $i + 1);
                        $sheet->setCellValue('B' . $row, $p['nama']);
                        $sheet->setCellValue('C' . $row, $p['nip']);
                        $sheet->setCellValue('D' . $row, $this->_getBagianLabel($p['bagian']));
                        $sheet->setCellValue('E' . $row, strtoupper($p['keterangan']));
                    } else {
                        $sheet->setCellValue('A' . $row, $i + 1);
                        foreach (['B', 'C', 'D', 'E'] as $col) {
                            $sheet->setCellValue($col . $row, '');
                        }
                    }
                    $row++;
                }

                // Border seluruh tabel
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A' . $headerRow . ':E' . ($headerRow + 18))->applyFromArray($borderStyle);

                // ----- Footer keterangan -----
                $row++;
                $keteranganRow = $row;

                $sheet->setCellValue('A' . $row, 'KETERANGAN :');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                $sheet->setCellValue('A' . $row, 'HADIR = ' . $jumlahHadir);
                $row++;
                $sheet->setCellValue('A' . $row, 'TIDAK HADIR = ' . $jumlahTidakHadir);

                // ----- TTD (kolom D-E, sejajar keterangan) -----
                $ttdRow = $keteranganRow;

                $sheet->setCellValue('D' . $ttdRow, 'Kepala Dinas Sosial');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;

                $sheet->setCellValue('D' . $ttdRow, 'Kabupaten Batang');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow += 4; // jarak TTD

                $sheet->setCellValue('D' . $ttdRow, 'WILLOPO, AP., M.M.');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getFont()->setBold(true)->setUnderline(true);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;

                $sheet->setCellValue('D' . $ttdRow, 'Pembina Utama Muda');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;

                $sheet->setCellValue('D' . $ttdRow, 'NIP.19740502 199311 1 001');
                $sheet->mergeCells('D' . $ttdRow . ':E' . $ttdRow);
                $sheet->getStyle('D' . $ttdRow)->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Lebar kolom
                foreach (['A' => 5, 'B' => 30, 'C' => 20, 'D' => 20, 'E' => 20] as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                if ($dateIndex < $totalDates) {
                    $row = max($row, $ttdRow) + 3;
                }
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="daftar-hadir-apel-' . date('Y-m-d-His') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Export Word
    // -------------------------------------------------------------------------
    public function exportWord()
    {
        try {
            $filters  = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $groupedData = $this->_groupByDate($presensi);
            $phpWord     = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $dateIndex  = 0;
            $totalDates = count($groupedData);

            foreach ($groupedData as $date => $attendances) {
                $dateIndex++;
                [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);

                $section = $phpWord->addSection([
                    'marginLeft'   => 600,
                    'marginRight'  => 600,
                    'marginTop'    => 600,
                    'marginBottom' => 600,
                ]);

                $section->addText('DAFTAR HADIR APEL PAGI', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
                $section->addText('DINAS SOSIAL KAB. BATANG', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
                $section->addText(
                    'HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)),
                    ['size' => 11],
                    ['alignment' => Jc::CENTER]
                );
                $section->addTextBreak(1);

                // ----- Tabel absensi -----
                $cellStyle = ['borderSize' => 6, 'borderColor' => '000000'];
                $table = $section->addTable([
                    'borderSize'  => 6,
                    'borderColor' => '000000',
                    'width'       => 100 * 50,
                    'unit'        => 'pct',
                ]);

                // Header
                $table->addRow(400);
                $table->addCell(800,  $cellStyle)->addText('NO',          ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(3500, $cellStyle)->addText('NAMA',        ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(2500, $cellStyle)->addText('NIP',         ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText('BIDANG',      ['bold' => true], ['alignment' => Jc::CENTER]);
                $table->addCell(1700, $cellStyle)->addText('KETERANGAN',  ['bold' => true], ['alignment' => Jc::CENTER]);

                // Data rows
                for ($i = 0; $i < 18; $i++) {
                    $table->addRow();
                    if (isset($attendances[$i])) {
                        $p = $attendances[$i];
                        $table->addCell(800,  $cellStyle)->addText($i + 1,                                   null, ['alignment' => Jc::CENTER]);
                        $table->addCell(3500, $cellStyle)->addText(htmlspecialchars($p['nama']));
                        $table->addCell(2500, $cellStyle)->addText(htmlspecialchars($p['nip']));
                        $table->addCell(1500, $cellStyle)->addText($this->_getBagianLabel($p['bagian']),      null, ['alignment' => Jc::CENTER]);
                        $table->addCell(1700, $cellStyle)->addText(strtoupper($p['keterangan']),              null, ['alignment' => Jc::CENTER]);
                    } else {
                        $table->addCell(800,  $cellStyle)->addText($i + 1, null, ['alignment' => Jc::CENTER]);
                        foreach ([3500, 2500, 1500, 1700] as $w) {
                            $table->addCell($w, $cellStyle)->addText('');
                        }
                    }
                }

                $section->addTextBreak(1);

                // ----- Footer: keterangan + TTD -----
                $layoutTable = $section->addTable([
                    'borderSize'  => 0,
                    'borderColor' => 'FFFFFF',
                    'width'       => 100 * 50,
                    'unit'        => 'pct',
                ]);
                $layoutTable->addRow();

                $leftCell = $layoutTable->addCell(5000, ['borderSize' => 0]);
                $leftCell->addText('KETERANGAN :', ['bold' => true]);
                $leftCell->addText('HADIR = ' . $jumlahHadir);
                $leftCell->addText('TIDAK HADIR = ' . $jumlahTidakHadir);

                $rightCell = $layoutTable->addCell(5000, ['borderSize' => 0, 'valign' => 'top']);
                $rightCell->addText('Kepala Dinas Sosial',       null,                                ['alignment' => Jc::CENTER]);
                $rightCell->addText('Kabupaten Batang',           null,                                ['alignment' => Jc::CENTER]);
                $rightCell->addTextBreak(3);
                $rightCell->addText('WILLOPO, AP., M.M.',         ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
                $rightCell->addText('Pembina Utama Muda',         null,                                ['alignment' => Jc::CENTER]);
                $rightCell->addText('NIP.19740502 199311 1 001',  null,                                ['alignment' => Jc::CENTER]);

                if ($dateIndex < $totalDates) {
                    $section->addPageBreak();
                }
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="daftar-hadir-apel-' . date('Y-m-d-His') . '.docx"');
            header('Cache-Control: max-age=0');

            IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Word Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Word: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Kelompokkan data presensi berdasarkan tanggal.
     */
    private function _groupByDate(array $presensi): array
    {
        $grouped = [];
        foreach ($presensi as $p) {
            $grouped[date('Y-m-d', strtotime($p['waktu']))][] = $p;
        }
        ksort($grouped); // urutkan dari tanggal terlama
        return $grouped;
    }

    /**
     * Hitung jumlah hadir & tidak hadir.
     * 'hadir' dan 'terlambat' dianggap hadir.
     */
    private function _hitungKehadiran(array $attendances): array
    {
        $hadir = 0;
        foreach ($attendances as $a) {
            if (in_array(strtolower($a['keterangan']), ['hadir', 'terlambat'])) {
                $hadir++;
            }
        }
        return [$hadir, count($attendances) - $hadir];
    }

    /**
     * Generate HTML untuk Dompdf.
     * Struktur valid lengkap dengan <html><head><style><body>.
     */
    private function _generatePdfTemplate(array $groupedData, array $filters): string
    {
        $html  = '<!DOCTYPE html>';
        $html .= '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>
            * { box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                font-size: 11pt;
                margin: 0;
                padding: 0;
                color: #000;
            }
            .page {
                padding: 15px 20px;
                page-break-after: always;
            }
            .page:last-child {
                page-break-after: avoid;
            }
            .title {
                text-align: center;
                font-size: 14pt;
                font-weight: bold;
                margin: 2px 0;
            }
            .subtitle {
                text-align: center;
                font-size: 14pt;
                font-weight: bold;
                margin: 2px 0;
            }
            .tanggal {
                text-align: center;
                font-size: 11pt;
                margin: 6px 0 10px 0;
            }
            table.absensi {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
                font-size: 10pt;
            }
            table.absensi th,
            table.absensi td {
                border: 1px solid #000;
                padding: 4px 6px;
            }
            table.absensi thead th {
                background-color: #cccccc;
                text-align: center;
                font-weight: bold;
            }
            table.absensi tbody td.center {
                text-align: center;
            }
            table.footer-layout {
                width: 100%;
                border: none;
                border-collapse: collapse;
                margin-top: 8px;
                font-size: 10pt;
            }
            table.footer-layout td {
                border: none;
                vertical-align: top;
                padding: 0;
            }
            .ttd-block {
                text-align: center;
            }
            .ttd-name {
                font-weight: bold;
                text-decoration: underline;
            }
        </style>';
        $html .= '</head><body>';

        foreach ($groupedData as $date => $attendances) {
            [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);

            $html .= '<div class="page">';

            // ----- Judul -----
            $html .= '<p class="title">DAFTAR HADIR APEL PAGI</p>';
            $html .= '<p class="subtitle">DINAS SOSIAL KAB. BATANG</p>';
            $html .= '<p class="tanggal">HARI/TANGGAL : '
                   . $this->_getDayName($date) . ', '
                   . date('d-m-Y', strtotime($date))
                   . '</p>';

            // ----- Tabel absensi -----
            $html .= '<table class="absensi">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width:5%;">NO</th>';
            $html .= '<th style="width:30%;">NAMA</th>';
            $html .= '<th style="width:25%;">NIP</th>';
            $html .= '<th style="width:20%;">BIDANG</th>';
            $html .= '<th style="width:20%;">KETERANGAN</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            for ($i = 0; $i < 18; $i++) {
                $html .= '<tr>';
                $html .= '<td class="center">' . ($i + 1) . '</td>';

                if (isset($attendances[$i])) {
                    $p = $attendances[$i];
                    $html .= '<td>' . htmlspecialchars($p['nama'], ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>' . htmlspecialchars($p['nip'],  ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td class="center">' . htmlspecialchars($this->_getBagianLabel($p['bagian']), ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td class="center">' . strtoupper(htmlspecialchars($p['keterangan'], ENT_QUOTES, 'UTF-8')) . '</td>';
                } else {
                    $html .= '<td>&nbsp;</td>';
                    $html .= '<td>&nbsp;</td>';
                    $html .= '<td>&nbsp;</td>';
                    $html .= '<td>&nbsp;</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            // ----- Footer: keterangan kiri | TTD kanan -----
            $html .= '<table class="footer-layout">';
            $html .= '<tr>';

            // Kiri
            $html .= '<td style="width:50%;">';
            $html .= '<strong>KETERANGAN :</strong><br>';
            $html .= 'HADIR = ' . $jumlahHadir . '<br>';
            $html .= 'TIDAK HADIR = ' . $jumlahTidakHadir;
            $html .= '</td>';

            // Kanan
            $html .= '<td style="width:50%;">';
            $html .= '<div class="ttd-block">';
            $html .= 'Kepala Dinas Sosial<br>';
            $html .= 'Kabupaten Batang<br>';
            $html .= '<br><br><br>';
            $html .= '<span class="ttd-name">WILLOPO, AP., M.M.</span><br>';
            $html .= 'Pembina Utama Muda<br>';
            $html .= 'NIP.19740502 199311 1 001';
            $html .= '</div>';
            $html .= '</td>';

            $html .= '</tr>';
            $html .= '</table>';

            $html .= '</div>'; // end .page
        }

        $html .= '</body></html>';
        return $html;
    }

    /**
     * Kembalikan nama hari dalam Bahasa Indonesia.
     */
    private function _getDayName(string $date): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $days[date('w', strtotime($date))];
    }

    /**
     * Ambil label bagian dari $bagianOptions secara dinamis.
     */
    private function _getBagianLabel(?string $kode): string
    {
        return $this->bagianOptions[$kode] ?? ($kode ? ucfirst($kode) : '-');
    }

    /**
     * Ambil filter dari query string GET.
     */
    private function _getFilters(): array
    {
        $filters = [];
        $keys    = ['user_id', 'bagian', 'tanggal', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'keterangan'];
        foreach ($keys as $key) {
            $val = $this->request->getGet($key);
            if (!empty($val)) {
                $filters[$key] = $val;
            }
        }
        return $filters;
    }
}