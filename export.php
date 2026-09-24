<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require_auth();

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$type = (string) ($_GET['type'] ?? 'ingredients');

$exports = [
    'ingredients' => [
        'filename' => 'laporan-bahan-baku.xlsx',
        'title' => 'Laporan Bahan Baku',
        'description' => 'Ringkasan harga dasar dan ketersediaan stok bahan baku.',
        'sheet' => 'Bahan Baku',
        'headers' => [
            'Nama Bahan Baku',
            'Satuan Dasar',
            'Harga Dasar / Satuan',
            'Stok Tersedia',
        ],
        'sql' => '
            SELECT
                name,
                base_unit,
                unit_price_base,
                stock_qty_base
            FROM ingredients
            ORDER BY name
        ',
        'currencyColumns' => [2],
        'numberColumns' => [3],
    ],

    'products' => [
        'filename' => 'laporan-produk.xlsx',
        'title' => 'Laporan Produk & Harga Jual',
        'description' => 'Daftar produk aktif beserta estimasi modal dan harga jual.',
        'sheet' => 'Produk',
        'headers' => [
            'Nama Produk',
            'Estimasi HPP / Modal',
            'Harga Jual',
        ],
        'sql' => '
            SELECT
                name,
                hpp,
                selling_price
            FROM recipes
            WHERE is_active = 1
            ORDER BY name
        ',
        'currencyColumns' => [1, 2],
        'numberColumns' => [],
    ],

    'movements' => [
        'filename' => 'laporan-pergerakan-stok.xlsx',
        'title' => 'Laporan Pergerakan Stok',
        'description' => 'Riwayat perubahan stok bahan baku dan saldo setelah transaksi.',
        'sheet' => 'Pergerakan Stok',
        'headers' => [
            'Waktu Perubahan',
            'Nama Bahan Baku',
            'Jenis Pergerakan',
            'Jumlah Perubahan',
            'Saldo Stok Setelahnya',
        ],
        'sql' => '
            SELECT
                m.created_at,
                i.name,
                m.movement_type,
                m.quantity_base,
                m.balance_after
            FROM stock_movements m
            JOIN ingredients i ON i.id = m.ingredient_id
            ORDER BY m.created_at DESC
        ',
        'currencyColumns' => [],
        'numberColumns' => [3, 4],
    ],
];

if (!isset($exports[$type])) {
    http_response_code(404);
    exit('Ekspor tidak ditemukan.');
}

$config = $exports[$type];

$data = query_all($config['sql']);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle($config['sheet']);

/*
|--------------------------------------------------------------------------
| Informasi Laporan
|--------------------------------------------------------------------------
*/

$lastColumn = count($config['headers']);

function excelColumnName(int $number): string
{
    $column = '';

    while ($number > 0) {
        $number--;
        $column = chr(65 + ($number % 26)) . $column;
        $number = intdiv($number, 26);
    }

    return $column;
}

$lastColumnLetter = excelColumnName($lastColumn);

/*
|--------------------------------------------------------------------------
| Judul
|--------------------------------------------------------------------------
*/

$sheet->mergeCells("A1:{$lastColumnLetter}1");

$sheet->setCellValue('A1', $config['title']);

$sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 18,
        'color' => [
            'rgb' => '1D4ED8',
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

$sheet->getRowDimension(1)->setRowHeight(30);

/*
|--------------------------------------------------------------------------
| Subjudul
|--------------------------------------------------------------------------
*/

$sheet->mergeCells("A2:{$lastColumnLetter}2");

$sheet->setCellValue(
    'A2',
    $config['description'] . ' • Dibuat pada ' . date('d/m/Y H:i')
);

$sheet->getStyle("A2:{$lastColumnLetter}2")->applyFromArray([
    'font' => [
        'italic' => true,
        'size' => 10,
        'color' => [
            'rgb' => '64748B',
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

$sheet->getRowDimension(2)->setRowHeight(24);

/*
|--------------------------------------------------------------------------
| Header Tabel
|--------------------------------------------------------------------------
*/

$headerRow = 4;

foreach ($config['headers'] as $index => $header) {
    $column = excelColumnName($index + 1);

    $sheet->setCellValue(
        "{$column}{$headerRow}",
        $header
    );
}

$headerRange = "A{$headerRow}:{$lastColumnLetter}{$headerRow}";

$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF',
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '2563EB',
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => 'D1D5DB',
            ],
        ],
    ],
]);

$sheet->getRowDimension($headerRow)->setRowHeight(25);

/*
|--------------------------------------------------------------------------
| Isi Data
|--------------------------------------------------------------------------
*/

$dataStartRow = 5;

foreach ($data as $rowIndex => $row) {

    $excelRow = $dataStartRow + $rowIndex;
    $values = array_values($row);

    foreach ($values as $columnIndex => $value) {

        $column = excelColumnName($columnIndex + 1);

        /*
         * Jangan paksa angka menjadi string.
         * Excel perlu mengetahui bahwa nilai tersebut memang angka.
         */
        if (
            in_array($columnIndex, $config['currencyColumns'], true)
            || in_array($columnIndex, $config['numberColumns'], true)
        ) {
            $sheet->setCellValue(
                "{$column}{$excelRow}",
                is_numeric($value) ? (float) $value : $value
            );
        } else {
            $sheet->setCellValue(
                "{$column}{$excelRow}",
                $value
            );
        }
    }
}

$dataEndRow = max(
    $dataStartRow,
    $dataStartRow + count($data) - 1
);

/*
|--------------------------------------------------------------------------
| Styling Isi Tabel
|--------------------------------------------------------------------------
*/

$tableRange = "A{$headerRow}:{$lastColumnLetter}{$dataEndRow}";

$sheet->getStyle($tableRange)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => 'D1D5DB',
            ],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
]);

/*
|--------------------------------------------------------------------------
| Baris Alternatif
|--------------------------------------------------------------------------
*/

for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {

    $sheet->getRowDimension($row)->setRowHeight(22);

    if (($row - $dataStartRow) % 2 === 1) {

        $sheet->getStyle(
            "A{$row}:{$lastColumnLetter}{$row}"
        )->getFill()->setFillType(Fill::FILL_SOLID);

        $sheet->getStyle(
            "A{$row}:{$lastColumnLetter}{$row}"
        )->getFill()->getStartColor()->setRGB('F8FAFC');
    }
}

/*
|--------------------------------------------------------------------------
| Format Mata Uang
|--------------------------------------------------------------------------
*/

foreach ($config['currencyColumns'] as $columnIndex) {

    $column = excelColumnName($columnIndex + 1);

    $sheet
        ->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
        ->getNumberFormat()
        ->setFormatCode('"Rp" #,##0');
}

/*
|--------------------------------------------------------------------------
| Format Angka
|--------------------------------------------------------------------------
*/

foreach ($config['numberColumns'] as $columnIndex) {

    $column = excelColumnName($columnIndex + 1);

    $sheet
        ->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
        ->getNumberFormat()
        ->setFormatCode('#,##0.##');
}

/*
|--------------------------------------------------------------------------
| Alignment
|--------------------------------------------------------------------------
*/

foreach ($config['currencyColumns'] as $columnIndex) {

    $column = excelColumnName($columnIndex + 1);

    $sheet
        ->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

foreach ($config['numberColumns'] as $columnIndex) {

    $column = excelColumnName($columnIndex + 1);

    $sheet
        ->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

/*
|--------------------------------------------------------------------------
| Auto Width
|--------------------------------------------------------------------------
*/

for ($i = 1; $i <= $lastColumn; $i++) {

    $column = excelColumnName($i);

    $sheet
        ->getColumnDimension($column)
        ->setAutoSize(true);
}

/*
|--------------------------------------------------------------------------
| Batas Lebar Kolom
|--------------------------------------------------------------------------
*/

for ($i = 1; $i <= $lastColumn; $i++) {

    $column = excelColumnName($i);

    $width = $sheet
        ->getColumnDimension($column)
        ->getWidth();

    if ($width < 12) {
        $sheet
            ->getColumnDimension($column)
            ->setWidth(12);
    }

    if ($width > 45) {
        $sheet
            ->getColumnDimension($column)
            ->setWidth(45);
    }
}

// Lebar minimum per kolom menjaga judul dan isi tetap terbaca serta sejajar.
$minimumWidths = [
    'A' => 22,
    'B' => 18,
    'C' => 22,
    'D' => 18,
    'E' => 18,
];

for ($i = 1; $i <= $lastColumn; $i++) {
    $column = excelColumnName($i);
    $width = $sheet->getColumnDimension($column)->getWidth();

    if (isset($minimumWidths[$column]) && $width < $minimumWidths[$column]) {
        $sheet->getColumnDimension($column)->setWidth($minimumWidths[$column]);
    }
}

/*
|--------------------------------------------------------------------------
| Freeze Header
|--------------------------------------------------------------------------
*/

$sheet->freezePane('A5');

/*
|--------------------------------------------------------------------------
| Auto Filter
|--------------------------------------------------------------------------
*/

$sheet->setAutoFilter($tableRange);

/*
|--------------------------------------------------------------------------
| Footer / Ringkasan
|--------------------------------------------------------------------------
*/

$summaryRow = $dataEndRow + 2;

$sheet->setCellValue(
    "A{$summaryRow}",
    'Total Baris Data'
);

$sheet->setCellValue(
    "B{$summaryRow}",
    count($data)
);

$sheet->getStyle("A{$summaryRow}:B{$summaryRow}")->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'EFF6FF',
        ],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => '93C5FD',
            ],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

$sheet->getRowDimension($summaryRow)->setRowHeight(22);

/*
|--------------------------------------------------------------------------
| Page Setup
|--------------------------------------------------------------------------
*/

$sheet->getPageSetup()
    ->setOrientation('landscape');

$sheet->getPageSetup()
    ->setPaperSize(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
    );

$sheet->getPageMargins()->setTop(0.5);
$sheet->getPageMargins()->setBottom(0.5);
$sheet->getPageMargins()->setLeft(0.5);
$sheet->getPageMargins()->setRight(0.5);

/*
|--------------------------------------------------------------------------
| Output XLSX
|--------------------------------------------------------------------------
*/

$filename = $config['filename'];

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;