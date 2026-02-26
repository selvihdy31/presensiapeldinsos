<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\UserModel;
use App\Models\BagianModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class Laporan extends BaseController
{
    protected $presensiModel;
    protected $userModel;
    protected $bagianModel;
    protected $bagianOptions; // ['kode' => 'Nama', ...]

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->userModel     = new UserModel();
        $this->bagianModel   = new BagianModel();
        $this->bagianOptions = $this->bagianModel->getAsOptions();
    }

    public function index()
    {
        $filters = $this->_getFilters();

        $data = [
            'title'         => 'Laporan Presensi',
            'presensi'      => $this->presensiModel->getPresensiWithUser($filters),
            'pegawai'       => $this->userModel->where('role', 'pegawai')->findAll(),
            'filters'       => $filters,
            'bagianOptions' => $this->bagianOptions, // << inject ke view
        ];

        return view('admin/laporan/index', $data);
    }

    /** Export PDF */
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
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('F4', 'portrait');
            $dompdf->render();
            $dompdf->stream('daftar-hadir-apel-' . date('Y-m-d-His') . '.pdf', ['Attachment' => true]);

        } catch (\Exception $e) {
            log_message('error', 'Export PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    /** Export Excel */
    public function exportExcel()
    {
        try {
            $filters     = $this->_getFilters();
            $presensi    = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $groupedData = $this->_groupByDate($presensi);
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $row         = 1;
            $dateIndex   = 0;
            $totalDates  = count($groupedData);

            foreach ($groupedData as $date => $attendances) {
                $dateIndex++;
                [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);

                $sheet->setCellValue('A' . $row, 'DAFTAR HADIR APEL PAGI');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->setCellValue('A' . $row, 'DINAS SOSIAL KAB. BATANG');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->setCellValue('A' . $row, 'HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)));
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 2;

                $headerRow = $row;
                foreach (['A'=>'NO','B'=>'NAMA','C'=>'NIP','D'=>'BIDANG','E'=>'KETERANGAN'] as $col => $label) {
                    $sheet->setCellValue($col . $headerRow, $label);
                }
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('CCCCCC');
                $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                for ($i = 0; $i < 18; $i++) {
                    if (isset($attendances[$i])) {
                        $p = $attendances[$i];
                        $sheet->setCellValue('A'.$row, $i+1);
                        $sheet->setCellValue('B'.$row, $p['nama']);
                        $sheet->setCellValue('C'.$row, $p['nip']);
                        $sheet->setCellValue('D'.$row, $this->_getBagianLabel($p['bagian']));
                        $sheet->setCellValue('E'.$row, strtoupper($p['keterangan']));
                    } else {
                        $sheet->setCellValue('A'.$row, $i+1);
                        foreach (['B','C','D','E'] as $col) $sheet->setCellValue($col.$row, '');
                    }
                    $row++;
                }

                $row++;
                $keteranganRow = $row;
                $sheet->setCellValue('A'.$row, 'KETERANGAN :'); $sheet->getStyle('A'.$row)->getFont()->setBold(true); $row++;
                $sheet->setCellValue('A'.$row, 'HADIR = ' . $jumlahHadir); $row++;
                $sheet->setCellValue('A'.$row, 'TIDAK HADIR = ' . $jumlahTidakHadir);

                $ttdRow = $keteranganRow;
                foreach (['D'=>'Kepala Dinas Sosial','E'=>''] as $col => $val) {
                    $sheet->setCellValue('D'.$ttdRow, 'Kepala Dinas Sosial');
                    $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                    $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    break;
                }
                $ttdRow++;
                $sheet->setCellValue('D'.$ttdRow, 'Kabupaten Batang'); $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow += 4;
                $sheet->setCellValue('D'.$ttdRow, 'WILLOPO, AP., M.M.'); $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                $sheet->getStyle('D'.$ttdRow)->getFont()->setBold(true)->setUnderline(true);
                $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;
                $sheet->setCellValue('D'.$ttdRow, 'Pembina Utama Muda'); $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $ttdRow++;
                $sheet->setCellValue('D'.$ttdRow, 'NIP.19740502 199311 1 001'); $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $borderStyle = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
                $sheet->getStyle('A'.$headerRow.':E'.($headerRow+18))->applyFromArray($borderStyle);

                foreach (['A'=>5,'B'=>30,'C'=>20,'D'=>20,'E'=>20] as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                if ($dateIndex < $totalDates) $row = max($row, $ttdRow) + 3;
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

    /** Export Word */
    public function exportWord()
    {
        try {
            $filters     = $this->_getFilters();
            $presensi    = $this->presensiModel->getPresensiWithUser($filters);

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

                $section = $phpWord->addSection(['marginLeft'=>600,'marginRight'=>600,'marginTop'=>600,'marginBottom'=>600]);
                $section->addText('DAFTAR HADIR APEL PAGI', ['bold'=>true,'size'=>14], ['alignment'=>Jc::CENTER]);
                $section->addText('DINAS SOSIAL KAB. BATANG', ['bold'=>true,'size'=>14], ['alignment'=>Jc::CENTER]);
                $section->addText('HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)), ['size'=>11], ['alignment'=>Jc::CENTER]);
                $section->addTextBreak(2);

                $cellStyle = ['borderSize'=>6,'borderColor'=>'000000'];
                $table     = $section->addTable(['borderSize'=>6,'borderColor'=>'000000','width'=>100*50,'unit'=>'pct']);
                $table->addRow(400);
                $table->addCell(1000, $cellStyle)->addText('NO',          ['bold'=>true], ['alignment'=>Jc::CENTER]);
                $table->addCell(3500, $cellStyle)->addText('NAMA',        ['bold'=>true], ['alignment'=>Jc::CENTER]);
                $table->addCell(2500, $cellStyle)->addText('NIP',         ['bold'=>true], ['alignment'=>Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText('BIDANG',      ['bold'=>true], ['alignment'=>Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText('KETERANGAN',  ['bold'=>true], ['alignment'=>Jc::CENTER]);

                for ($i = 0; $i < 18; $i++) {
                    $table->addRow();
                    if (isset($attendances[$i])) {
                        $p = $attendances[$i];
                        $table->addCell(1000, $cellStyle)->addText($i+1, null, ['alignment'=>Jc::CENTER]);
                        $table->addCell(3500, $cellStyle)->addText($p['nama']);
                        $table->addCell(2500, $cellStyle)->addText($p['nip']);
                        $table->addCell(1500, $cellStyle)->addText($this->_getBagianLabel($p['bagian']), null, ['alignment'=>Jc::CENTER]);
                        $table->addCell(1500, $cellStyle)->addText(strtoupper($p['keterangan']), null, ['alignment'=>Jc::CENTER]);
                    } else {
                        $table->addCell(1000, $cellStyle)->addText($i+1, null, ['alignment'=>Jc::CENTER]);
                        foreach ([3500,2500,1500,1500] as $w) $table->addCell($w, $cellStyle)->addText('');
                    }
                }

                $section->addTextBreak(1);
                $layoutTable = $section->addTable(['borderSize'=>0,'borderColor'=>'FFFFFF','width'=>100*50,'unit'=>'pct']);
                $layoutTable->addRow();
                $leftCell  = $layoutTable->addCell(5000, ['borderSize'=>0]);
                $leftCell->addText('KETERANGAN :', ['bold'=>true]);
                $leftCell->addText('HADIR = ' . $jumlahHadir);
                $leftCell->addText('TIDAK HADIR = ' . $jumlahTidakHadir);
                $rightCell = $layoutTable->addCell(5000, ['borderSize'=>0,'valign'=>'top']);
                $rightCell->addText('Kepala Dinas Sosial', null, ['alignment'=>Jc::CENTER]);
                $rightCell->addText('Kabupaten Batang',    null, ['alignment'=>Jc::CENTER]);
                $rightCell->addTextBreak(3);
                $rightCell->addText('WILLOPO, AP., M.M.',            ['bold'=>true,'underline'=>'single'], ['alignment'=>Jc::CENTER]);
                $rightCell->addText('Pembina Utama Muda',             null, ['alignment'=>Jc::CENTER]);
                $rightCell->addText('NIP.19740502 199311 1 001',      null, ['alignment'=>Jc::CENTER]);

                if ($dateIndex < $totalDates) $section->addPageBreak();
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

    // ===== PRIVATE HELPERS =====

    private function _groupByDate(array $presensi): array
    {
        $grouped = [];
        foreach ($presensi as $p) {
            $grouped[date('Y-m-d', strtotime($p['waktu']))][] = $p;
        }
        return $grouped;
    }

    private function _hitungKehadiran(array $attendances): array
    {
        $hadir = 0;
        foreach ($attendances as $a) {
            if (in_array(strtolower($a['keterangan']), ['hadir', 'terlambat'])) $hadir++;
        }
        return [$hadir, count($attendances) - $hadir];
    }

    private function _generatePdfTemplate(array $groupedData, array $filters): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body{font-family:Arial,sans-serif;font-size:11px;}
            .page{page-break-after:always;padding:20px;}.page:last-child{page-break-after:auto;}
            .title{text-align:center;font-size:14px;font-weight:bold;margin-bottom:3px;}
            .subtitle{text-align:center;font-size:14px;font-weight:bold;margin-bottom:5px;}
            .header{text-align:center;margin-bottom:10px;}
            table{width:100%;border-collapse:collapse;margin:20px 0;}
            th,td{border:1px solid #000;padding:8px;text-align:left;}
            th{background-color:#ddd;font-weight:bold;text-align:center;}
            td.center{text-align:center;}
            .footer-section{display:table;width:100%;margin-top:20px;}
            .footer-left{display:table-cell;width:50%;vertical-align:top;}
            .footer-right{display:table-cell;width:50%;text-align:center;vertical-align:top;}
            .signature-space{margin-top:120px;}
        </style></head><body>';

        $dateIndex  = 0;
        $totalDates = count($groupedData);

        foreach ($groupedData as $date => $attendances) {
            $dateIndex++;
            [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);

            $html .= '<div class="page">';
            $html .= '<div class="title">DAFTAR HADIR APEL PAGI</div>';
            $html .= '<div class="subtitle">DINAS SOSIAL KAB. BATANG</div>';
            $html .= '<div class="header">HARI/TANGGAL : ' . $this->_getDayName($date) . ', ' . date('d-m-Y', strtotime($date)) . '</div>';
            $html .= '<table><thead><tr>';
            foreach (['5%'=>'NO','30%'=>'NAMA','25%'=>'NIP','20%'=>'BIDANG','20%'=>'KETERANGAN'] as $w => $h) {
                $html .= '<th width="'.$w.'">'.$h.'</th>';
            }
            $html .= '</tr></thead><tbody>';

            for ($i = 0; $i < 18; $i++) {
                $html .= '<tr>';
                if (isset($attendances[$i])) {
                    $p     = $attendances[$i];
                    $html .= '<td class="center">'.($i+1).'</td>';
                    $html .= '<td>'.htmlspecialchars($p['nama']).'</td>';
                    $html .= '<td>'.htmlspecialchars($p['nip']).'</td>';
                    $html .= '<td class="center">'.htmlspecialchars($this->_getBagianLabel($p['bagian'])).'</td>';
                    $html .= '<td class="center">'.strtoupper($p['keterangan']).'</td>';
                } else {
                    $html .= '<td class="center">'.($i+1).'</td><td></td><td></td><td></td><td></td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            $html .= '<div class="footer-section">';
            $html .= '<div class="footer-left"><strong>KETERANGAN :</strong><br>HADIR = '.$jumlahHadir.'<br>TIDAK HADIR = '.$jumlahTidakHadir.'</div>';
            $html .= '<div class="footer-right">Kepala Dinas Sosial<br>Kabupaten Batang<br><div class="signature-space">&nbsp;</div><strong><u>WILLOPO, AP., M.M.</u></strong><br>Pembina Utama Muda<br>NIP.19740502 199311 1 001</div>';
            $html .= '</div></div>';
        }

        return $html . '</body></html>';
    }

    private function _getDayName(string $date): string
    {
        return ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date))];
    }

    /** Ambil nama bagian dari $bagianOptions dinamis */
    private function _getBagianLabel(?string $kode): string
    {
        return $this->bagianOptions[$kode] ?? ($kode ? ucfirst($kode) : '-');
    }

    private function _getFilters(): array
    {
        $filters = [];
        $keys    = ['user_id','bagian','tanggal','tahun','tanggal_mulai','tanggal_selesai','keterangan'];
        foreach ($keys as $key) {
            $val = $this->request->getGet($key);
            if (!empty($val)) $filters[$key] = $val;
        }
        return $filters;
    }
}