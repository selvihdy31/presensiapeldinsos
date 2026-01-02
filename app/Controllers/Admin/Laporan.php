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

    /**
     * Halaman Utama Laporan
     */
    public function index()
    {
        $filters = [];
        
        // Ambil filter dari GET request
        if ($this->request->getGet('user_id')) {
            $filters['user_id'] = $this->request->getGet('user_id');
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
     * Export Laporan ke PDF
     */
    public function exportPdf()
    {
        try {
            $filters = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            // Validasi data
            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            // Generate HTML untuk PDF
            $html = view('admin/laporan/pdf', [
                'presensi' => $presensi, 
                'filters' => $filters
            ]);

            // Setup Dompdf dengan options
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Generate filename dengan tanggal
            $filename = 'laporan-presensi-' . date('Y-m-d-His') . '.pdf';

            // Stream PDF ke browser
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
     * Export Laporan ke Excel
     */
    public function exportExcel()
    {
        try {
            $filters = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            // Validasi data
            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            // Load library PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set judul
            $sheet->setCellValue('A1', 'LAPORAN PRESENSI PEGAWAI');
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Tambahkan info filter jika ada
            $row = 2;
            if (!empty($filters)) {
                if (isset($filters['tahun'])) {
                    $sheet->setCellValue('A' . $row, 'Tahun: ' . $filters['tahun']);
                    $sheet->mergeCells('A' . $row . ':G' . $row);
                    $row++;
                }
                if (isset($filters['tanggal_mulai']) && isset($filters['tanggal_selesai'])) {
                    $sheet->setCellValue('A' . $row, 'Periode: ' . date('d-m-Y', strtotime($filters['tanggal_mulai'])) . ' s/d ' . date('d-m-Y', strtotime($filters['tanggal_selesai'])));
                    $sheet->mergeCells('A' . $row . ':G' . $row);
                    $row++;
                }
                $row++; // Baris kosong
            }

            // Header tabel
            $headerRow = $row;
            $sheet->setCellValue('A' . $headerRow, 'No');
            $sheet->setCellValue('B' . $headerRow, 'NIP');
            $sheet->setCellValue('C' . $headerRow, 'Nama');
            $sheet->setCellValue('D' . $headerRow, 'Tanggal');
            $sheet->setCellValue('E' . $headerRow, 'Waktu');
            $sheet->setCellValue('F' . $headerRow, 'Keterangan');
            $sheet->setCellValue('G' . $headerRow, 'Lokasi');

            // Style header
            $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('CCCCCC');

            // Isi data
            $row = $headerRow + 1;
            $no = 1;
            foreach ($presensi as $p) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $p['nip']);
                $sheet->setCellValue('C' . $row, $p['nama']);
                $sheet->setCellValue('D' . $row, date('d-m-Y', strtotime($p['waktu'])));
                $sheet->setCellValue('E' . $row, date('H:i', strtotime($p['waktu'])));
                $sheet->setCellValue('F' . $row, ucfirst($p['keterangan']));
                $sheet->setCellValue('G' . $row, $p['lokasi'] ?? '-');
                $row++;
            }

            // Set border untuk tabel
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A' . $headerRow . ':G' . ($row - 1))->applyFromArray($styleArray);

            // Auto size columns
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate filename
            $filename = 'laporan-presensi-' . date('Y-m-d-His') . '.xlsx';

            // Set headers untuk download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Write file
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Export Laporan ke Word
     */
    public function exportWord()
    {
        try {
            $filters = $this->_getFilters();
            $presensi = $this->presensiModel->getPresensiWithUser($filters);

            // Validasi data
            if (empty($presensi)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $phpWord = new PhpWord();
            
            // Set default font
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            // Add section
            $section = $phpWord->addSection([
                'orientation' => 'landscape',
                'marginLeft' => 600,
                'marginRight' => 600,
                'marginTop' => 600,
                'marginBottom' => 600,
            ]);
            
            // Judul
            $section->addText(
                'LAPORAN PRESENSI PEGAWAI', 
                ['bold' => true, 'size' => 16], 
                ['alignment' => Jc::CENTER]
            );
            $section->addTextBreak(2);

            // Tambahkan info filter jika ada
            if (!empty($filters)) {
                if (isset($filters['tahun'])) {
                    $section->addText(
                        'Tahun: ' . $filters['tahun'],
                        ['size' => 11]
                    );
                }
                if (isset($filters['tanggal_mulai']) && isset($filters['tanggal_selesai'])) {
                    $section->addText(
                        'Periode: ' . date('d-m-Y', strtotime($filters['tanggal_mulai'])) . 
                        ' s/d ' . date('d-m-Y', strtotime($filters['tanggal_selesai'])),
                        ['size' => 11]
                    );
                }
                if (isset($filters['keterangan'])) {
                    $section->addText(
                        'Keterangan: ' . ucfirst($filters['keterangan']),
                        ['size' => 11]
                    );
                }
                $section->addTextBreak(1);
            }

            // Style untuk header tabel
            $headerStyle = [
                'bold' => true,
                'size' => 11,
                'color' => '000000'
            ];

            // Style untuk cell
            $cellStyle = [
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80
            ];

            // Buat tabel
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct'
            ]);

            // Header row
            $table->addRow(400);
            $table->addCell(800, $cellStyle)->addText('No', $headerStyle, ['alignment' => Jc::CENTER]);
            $table->addCell(1500, $cellStyle)->addText('NIP', $headerStyle, ['alignment' => Jc::CENTER]);
            $table->addCell(2500, $cellStyle)->addText('Nama', $headerStyle, ['alignment' => Jc::CENTER]);
            $table->addCell(1500, $cellStyle)->addText('Tanggal', $headerStyle, ['alignment' => Jc::CENTER]);
            $table->addCell(1200, $cellStyle)->addText('Waktu', $headerStyle, ['alignment' => Jc::CENTER]);
            $table->addCell(1500, $cellStyle)->addText('Keterangan', $headerStyle, ['alignment' => Jc::CENTER]);
            $table->addCell(2000, $cellStyle)->addText('Lokasi', $headerStyle, ['alignment' => Jc::CENTER]);

            // Data rows
            $no = 1;
            foreach ($presensi as $p) {
                $table->addRow();
                $table->addCell(800, $cellStyle)->addText($no++, null, ['alignment' => Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText($p['nip']);
                $table->addCell(2500, $cellStyle)->addText($p['nama']);
                $table->addCell(1500, $cellStyle)->addText(date('d-m-Y', strtotime($p['waktu'])), null, ['alignment' => Jc::CENTER]);
                $table->addCell(1200, $cellStyle)->addText(date('H:i', strtotime($p['waktu'])), null, ['alignment' => Jc::CENTER]);
                $table->addCell(1500, $cellStyle)->addText(ucfirst($p['keterangan']), null, ['alignment' => Jc::CENTER]);
                $table->addCell(2000, $cellStyle)->addText($p['lokasi'] ?? '-');
            }

            // Generate filename
            $filename = 'laporan-presensi-' . date('Y-m-d-His') . '.docx';

            // Set headers untuk download
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');

            // Write file
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Word Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Word: ' . $e->getMessage());
        }
    }

    /**
     * Helper function untuk mengambil filters dari GET request
     */
    private function _getFilters()
    {
        $filters = [];
        
        if ($this->request->getGet('user_id')) {
            $filters['user_id'] = $this->request->getGet('user_id');
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