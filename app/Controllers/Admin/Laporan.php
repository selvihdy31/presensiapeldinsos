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
    protected $bagianOptions;

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
            'bagianOptions' => $this->bagianOptions,
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
            $filters  = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $groupedData = $this->_groupByDate($presensi);
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $row         = 1;

            foreach ($groupedData as $date => $attendances) {
                // Pecah per 18 baris
                $chunks     = array_chunk($attendances, 18);
                $totalChunk = count($chunks);

                foreach ($chunks as $chunkIndex => $chunk) {
                    [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances); // total per hari

                    $sheet->setCellValue('A'.$row, 'DAFTAR HADIR APEL PAGI');
                    $sheet->mergeCells('A'.$row.':E'.$row);
                    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
                    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $row++;

                    $sheet->setCellValue('A'.$row, 'DINAS SOSIAL KAB. BATANG');
                    $sheet->mergeCells('A'.$row.':E'.$row);
                    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
                    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $row++;

                    $pageLabel = $totalChunk > 1 ? ' (Hal. '.($chunkIndex+1).'/'.$totalChunk.')' : '';
                    $sheet->setCellValue('A'.$row, 'HARI/TANGGAL : '.$this->_getDayName($date).', '.date('d-m-Y', strtotime($date)).$pageLabel);
                    $sheet->mergeCells('A'.$row.':E'.$row);
                    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $row += 2;

                    $headerRow = $row;
                    foreach (['A'=>'NO','B'=>'NAMA','C'=>'NIP','D'=>'BIDANG','E'=>'KETERANGAN'] as $col => $label) {
                        $sheet->setCellValue($col.$headerRow, $label);
                    }
                    $sheet->getStyle('A'.$headerRow.':E'.$headerRow)->getFont()->setBold(true);
                    $sheet->getStyle('A'.$headerRow.':E'.$headerRow)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('CCCCCC');
                    $sheet->getStyle('A'.$headerRow.':E'.$headerRow)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $row++;

                    $globalOffset = $chunkIndex * 18; // nomor urut global
                    for ($i = 0; $i < 18; $i++) {
                        $nomorUrut = $globalOffset + $i + 1;
                        if (isset($chunk[$i])) {
                            $p = $chunk[$i];
                            $sheet->setCellValue('A'.$row, $nomorUrut);
                            $sheet->setCellValue('B'.$row, $p['nama']);
                            $sheet->setCellValue('C'.$row, $p['nip']);
                            $sheet->setCellValue('D'.$row, $this->_getBagianLabel($p['bagian']));
                            $sheet->setCellValue('E'.$row, strtoupper($p['keterangan']));
                        } else {
                            $sheet->setCellValue('A'.$row, $nomorUrut);
                            foreach (['B','C','D','E'] as $col) $sheet->setCellValue($col.$row, '');
                        }
                        $row++;
                    }

                    $borderStyle = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
                    $sheet->getStyle('A'.$headerRow.':E'.($headerRow+18))->applyFromArray($borderStyle);

                    foreach (['A'=>5,'B'=>30,'C'=>20,'D'=>20,'E'=>20] as $col => $w) {
                        $sheet->getColumnDimension($col)->setWidth($w);
                    }

                    $row++;
                    $keteranganRow = $row;

                    // Keterangan kiri (hanya di chunk terakhir per hari)
                    if ($chunkIndex === $totalChunk - 1) {
                        $sheet->setCellValue('A'.$row, 'KETERANGAN :'); $sheet->getStyle('A'.$row)->getFont()->setBold(true); $row++;
                        $sheet->setCellValue('A'.$row, 'HADIR = '.$jumlahHadir); $row++;
                        $sheet->setCellValue('A'.$row, 'TIDAK HADIR = '.$jumlahTidakHadir);
                    }

                    $ttdRow = $keteranganRow;
                    $sheet->setCellValue('D'.$ttdRow, 'Kepala Dinas Sosial');
                    $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                    $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $ttdRow++;
                    $sheet->setCellValue('D'.$ttdRow, 'Kabupaten Batang');
                    $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                    $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $ttdRow += 4;
                    $sheet->setCellValue('D'.$ttdRow, 'WILLOPO, AP., M.M.');
                    $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                    $sheet->getStyle('D'.$ttdRow)->getFont()->setBold(true)->setUnderline(true);
                    $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $ttdRow++;
                    $sheet->setCellValue('D'.$ttdRow, 'Pembina Utama Muda');
                    $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                    $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $ttdRow++;
                    $sheet->setCellValue('D'.$ttdRow, 'NIP.19740502 199311 1 001');
                    $sheet->mergeCells('D'.$ttdRow.':E'.$ttdRow);
                    $sheet->getStyle('D'.$ttdRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $row = max($row, $ttdRow) + 3;
                }
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="daftar-hadir-apel-'.date('Y-m-d-His').'.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Excel: '.$e->getMessage());
        }
    }

    /** Export Word */
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

            foreach ($groupedData as $date => $attendances) {
                [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);
                $chunks     = array_chunk($attendances, 18);
                $totalChunk = count($chunks);

                foreach ($chunks as $chunkIndex => $chunk) {
                    $section = $phpWord->addSection(['marginLeft'=>600,'marginRight'=>600,'marginTop'=>600,'marginBottom'=>600]);
                    $section->addText('DAFTAR HADIR APEL PAGI', ['bold'=>true,'size'=>14], ['alignment'=>Jc::CENTER]);
                    $section->addText('DINAS SOSIAL KAB. BATANG', ['bold'=>true,'size'=>14], ['alignment'=>Jc::CENTER]);

                    $pageLabel = $totalChunk > 1 ? ' (Hal. '.($chunkIndex+1).'/'.$totalChunk.')' : '';
                    $section->addText('HARI/TANGGAL : '.$this->_getDayName($date).', '.date('d-m-Y', strtotime($date)).$pageLabel, ['size'=>11], ['alignment'=>Jc::CENTER]);
                    $section->addTextBreak(1);

                    $cellStyle = ['borderSize'=>6,'borderColor'=>'000000'];
                    $table     = $section->addTable(['borderSize'=>6,'borderColor'=>'000000','width'=>100*50,'unit'=>'pct']);
                    $table->addRow(400);
                    $table->addCell(1000, $cellStyle)->addText('NO',         ['bold'=>true], ['alignment'=>Jc::CENTER]);
                    $table->addCell(3500, $cellStyle)->addText('NAMA',       ['bold'=>true], ['alignment'=>Jc::CENTER]);
                    $table->addCell(2500, $cellStyle)->addText('NIP',        ['bold'=>true], ['alignment'=>Jc::CENTER]);
                    $table->addCell(1500, $cellStyle)->addText('BIDANG',     ['bold'=>true], ['alignment'=>Jc::CENTER]);
                    $table->addCell(1500, $cellStyle)->addText('KETERANGAN', ['bold'=>true], ['alignment'=>Jc::CENTER]);

                    $globalOffset = $chunkIndex * 18;
                    for ($i = 0; $i < 18; $i++) {
                        $nomorUrut = $globalOffset + $i + 1;
                        $table->addRow();
                        if (isset($chunk[$i])) {
                            $p = $chunk[$i];
                            $table->addCell(1000, $cellStyle)->addText($nomorUrut, null, ['alignment'=>Jc::CENTER]);
                            $table->addCell(3500, $cellStyle)->addText($p['nama']);
                            $table->addCell(2500, $cellStyle)->addText($p['nip']);
                            $table->addCell(1500, $cellStyle)->addText($this->_getBagianLabel($p['bagian']), null, ['alignment'=>Jc::CENTER]);
                            $table->addCell(1500, $cellStyle)->addText(strtoupper($p['keterangan']), null, ['alignment'=>Jc::CENTER]);
                        } else {
                            $table->addCell(1000, $cellStyle)->addText($nomorUrut, null, ['alignment'=>Jc::CENTER]);
                            foreach ([3500,2500,1500,1500] as $w) $table->addCell($w, $cellStyle)->addText('');
                        }
                    }

                    $section->addTextBreak(1);
                    $layoutTable = $section->addTable(['borderSize'=>0,'borderColor'=>'FFFFFF','width'=>100*50,'unit'=>'pct']);
                    $layoutTable->addRow();
                    $leftCell = $layoutTable->addCell(5000, ['borderSize'=>0]);

                    // Keterangan jumlah hanya di halaman terakhir per hari
                    if ($chunkIndex === $totalChunk - 1) {
                        $leftCell->addText('KETERANGAN :', ['bold'=>true]);
                        $leftCell->addText('HADIR = '.$jumlahHadir);
                        $leftCell->addText('TIDAK HADIR = '.$jumlahTidakHadir);
                    } else {
                        $leftCell->addText('');
                    }

                    $rightCell = $layoutTable->addCell(5000, ['borderSize'=>0,'valign'=>'top']);
                    $rightCell->addText('Kepala Dinas Sosial', null, ['alignment'=>Jc::CENTER]);
                    $rightCell->addText('Kabupaten Batang', null, ['alignment'=>Jc::CENTER]);
                    $rightCell->addTextBreak(3);
                    $rightCell->addText('WILLOPO, AP., M.M.',       ['bold'=>true,'underline'=>'single'], ['alignment'=>Jc::CENTER]);
                    $rightCell->addText('Pembina Utama Muda',        null, ['alignment'=>Jc::CENTER]);
                    $rightCell->addText('NIP.19740502 199311 1 001', null, ['alignment'=>Jc::CENTER]);
                }
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="daftar-hadir-apel-'.date('Y-m-d-His').'.docx"');
            header('Cache-Control: max-age=0');
            IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Word Error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Word: '.$e->getMessage());
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
            table{width:100%;border-collapse:collapse;margin:10px 0;}
            th,td{border:1px solid #000;padding:6px;text-align:left;}
            th{background-color:#ddd;font-weight:bold;text-align:center;}
            td.center{text-align:center;}
            .footer-table{width:100%;margin-top:15px;border-collapse:collapse;}
            .footer-table td{border:none;padding:4px;}
            .footer-left{width:50%;vertical-align:top;}
            .footer-right{width:50%;text-align:center;vertical-align:top;}
            .signature-space{margin-top:80px;}
        </style></head><body>';

        foreach ($groupedData as $date => $attendances) {
            [$jumlahHadir, $jumlahTidakHadir] = $this->_hitungKehadiran($attendances);

            // Pecah per 18 baris
            $chunks     = array_chunk($attendances, 18);
            $totalChunk = count($chunks);

            foreach ($chunks as $chunkIndex => $chunk) {
                $pageLabel = $totalChunk > 1 ? ' (Hal. '.($chunkIndex+1).'/'.$totalChunk.')' : '';
                $globalOffset = $chunkIndex * 18;

                $html .= '<div class="page">';
                $html .= '<div class="title">DAFTAR HADIR APEL PAGI</div>';
                $html .= '<div class="subtitle">DINAS SOSIAL KAB. BATANG</div>';
                $html .= '<div class="header">HARI/TANGGAL : '.$this->_getDayName($date).', '.date('d-m-Y', strtotime($date)).$pageLabel.'</div>';

                $html .= '<table><thead><tr>';
                $headers = [
                    ['5%',  'NO'],
                    ['30%', 'NAMA'],
                    ['25%', 'NIP'],
                    ['20%', 'BIDANG'],
                    ['20%', 'KETERANGAN'],
                ];
                foreach ($headers as [$w, $h]) {
                    $html .= '<th width="'.$w.'">'.$h.'</th>';
                }
                $html .= '</tr></thead><tbody>';

                for ($i = 0; $i < 18; $i++) {
                    $nomorUrut = $globalOffset + $i + 1;
                    $html .= '<tr>';
                    if (isset($chunk[$i])) {
                        $p     = $chunk[$i];
                        $html .= '<td class="center">'.$nomorUrut.'</td>';
                        $html .= '<td>'.htmlspecialchars($p['nama']).'</td>';
                        $html .= '<td>'.htmlspecialchars($p['nip']).'</td>';
                        $html .= '<td class="center">'.htmlspecialchars($this->_getBagianLabel($p['bagian'])).'</td>';
                        $html .= '<td class="center">'.strtoupper($p['keterangan']).'</td>';
                    } else {
                        $html .= '<td class="center">'.$nomorUrut.'</td><td></td><td></td><td></td><td></td>';
                    }
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';

                $html .= '<table class="footer-table"><tr>';
                $html .= '<td class="footer-left">';
                // Keterangan jumlah hanya di halaman terakhir per hari
                if ($chunkIndex === $totalChunk - 1) {
                    $html .= '<strong>KETERANGAN :</strong><br>HADIR = '.$jumlahHadir.'<br>TIDAK HADIR = '.$jumlahTidakHadir;
                }
                $html .= '</td>';
                $html .= '<td class="footer-right">Kepala Dinas Sosial<br>Kabupaten Batang<div class="signature-space">&nbsp;</div><strong><u>WILLOPO, AP., M.M.</u></strong><br>Pembina Utama Muda<br>NIP.19740502 199311 1 001</td>';
                $html .= '</tr></table>';

                $html .= '</div>'; // .page
            }
        }

        return $html.'</body></html>';
    }

    private function _getDayName(string $date): string
    {
        return ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date))];
    }

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