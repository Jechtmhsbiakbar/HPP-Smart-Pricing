<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
require_auth();
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$rows = query_all('SELECT s.*,GROUP_CONCAT(CONCAT(si.product_name," x",si.qty) SEPARATOR ", ") items FROM sales s LEFT JOIN sale_items si ON si.sale_id=s.id WHERE DATE(s.sold_at) BETWEEN ? AND ? GROUP BY s.id ORDER BY s.sold_at DESC', 'ss', [$from, $to]);
$summary = one('SELECT COALESCE(SUM(total),0) total,COALESCE(SUM(total_hpp),0) hpp,COUNT(*) count FROM sales WHERE status="COMPLETED" AND DATE(sold_at) BETWEEN ? AND ?', 'ss', [$from, $to]) ?: ['total' => 0, 'hpp' => 0, 'count' => 0];
if (isset($_GET['export'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Penjualan');

    $headers = ['Invoice', 'Tanggal', 'Item', 'Total', 'HPP', 'Metode', 'Status'];
    $lastColumn = 'G';
    $columns = range('A', $lastColumn);
    $sheet->mergeCells("A1:{$lastColumn}1");
    $sheet->setCellValue('A1', 'Laporan Penjualan');
    $sheet->mergeCells("A2:{$lastColumn}2");
    $sheet->setCellValue('A2', "Periode: {$from} s/d {$to} • Digenerate pada " . date('d/m/Y H:i'));

    foreach ($headers as $index => $header) {
        $sheet->setCellValue("{$columns[$index]}4", $header);
    }

    $headerRange = "A4:{$lastColumn}4";
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2563EB'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D1D5DB'],
            ],
        ],
    ]);

    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 5;
        $values = [
            $row['invoice_no'],
            $row['sold_at'],
            $row['items'] ?? '-',
            is_numeric($row['total']) ? (float) $row['total'] : $row['total'],
            is_numeric($row['total_hpp']) ? (float) $row['total_hpp'] : $row['total_hpp'],
            $row['payment_method'],
            $row['status'],
        ];

        foreach ($values as $columnIndex => $value) {
            $sheet->setCellValue("{$columns[$columnIndex]}{$excelRow}", $value);
        }
    }

    $dataEndRow = max(5, count($rows) + 4);
    $tableRange = "A4:{$lastColumn}{$dataEndRow}";
    $sheet->getStyle($tableRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D1D5DB'],
            ],
        ],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $sheet->getStyle("D5:E{$dataEndRow}")
        ->getNumberFormat()
        ->setFormatCode('"Rp" #,##0');
    $sheet->getStyle("D5:E{$dataEndRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    foreach (range('A', $lastColumn) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $sheet->getColumnDimension('C')->setWidth(40);
    $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(18);
    $sheet->getStyle("A2:{$lastColumn}2")->getFont()->setItalic(true)->setSize(10);
    $sheet->freezePane('A5');
    $sheet->setAutoFilter($tableRange);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="laporan-penjualan.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
layout_start('Laporan', 'reports.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">REPORTING</p>
        <h1>Laporan penjualan</h1>
        <p class="muted">Gross profit adalah estimasi omzet dikurangi HPP, bukan laba bersih.</p>
    </div><a class="button secondary" href="?from=<?= e($from) ?>&to=<?= e($to) ?>&export=1">Export XLSX (File excel)</a>
</section>
<section class="stats">
    <div class="stat"><span>Omzet</span><strong><?= rupiah($summary['total']) ?></strong></div>
    <div class="stat"><span>HPP</span><strong><?= rupiah($summary['hpp']) ?></strong></div>
    <div class="stat accent"><span>Laba
            kotor</span><strong><?= rupiah((float) $summary['total'] - (float) $summary['hpp']) ?></strong></div>
    <div class="stat"><span>Transaksi</span><strong><?= $summary['count'] ?></strong></div>
</section>
<section class="panel">
    <form class="filter-bar"><label>Dari<input type="date" name="from" value="<?= e($from) ?>"></label><label>Sampai<input
                type="date" name="to" value="<?= e($to) ?>"></label><button class="button primary">Terapkan</button>
    </form>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Waktu</th>
                    <th>Item</th>
                    <th>Total</th>
                    <th>HPP</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['invoice_no']) ?></strong></td>
                        <td><?= e($r['sold_at']) ?></td>
                        <td><?= e($r['items'] ?? '-') ?></td>
                        <td><?= rupiah($r['total']) ?></td>
                        <td><?= rupiah($r['total_hpp']) ?></td>
                        <td><?= e($r['status']) ?></td>
                        <td><?php if ($r['status'] === 'COMPLETED'): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="void_sale"><input
                                        type="hidden" name="sale_id" value="<?= $r['id'] ?>"><button class="button danger small"
                                        onclick="return confirm('Void transaksi dan kembalikan stok?')">Void</button></form>
                            <?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>