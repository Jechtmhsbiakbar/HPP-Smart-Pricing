<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
require_auth();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$rows = query_all('SELECT s.*,GROUP_CONCAT(CONCAT(si.product_name," x",si.qty) SEPARATOR ", ") items FROM sales s LEFT JOIN sale_items si ON si.sale_id=s.id WHERE DATE(s.sold_at) BETWEEN ? AND ? GROUP BY s.id ORDER BY s.sold_at DESC', 'ss', [$from, $to]);
$summary = one('SELECT COALESCE(SUM(total),0) total,COALESCE(SUM(total_hpp),0) hpp,COUNT(*) count FROM sales WHERE status="COMPLETED" AND DATE(sold_at) BETWEEN ? AND ?', 'ss', [$from, $to]) ?: ['total' => 0, 'hpp' => 0, 'count' => 0];
if (isset($_GET['export'])) {
    header('Content-Type:text/csv; charset=utf-8');
    header('Content-Disposition:attachment; filename=laporan-penjualan.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Invoice', 'Tanggal', 'Item', 'Total', 'HPP', 'Metode', 'Status']);
    foreach ($rows as $r)
        fputcsv($out, [$r['invoice_no'], $r['sold_at'], $r['items'], $r['total'], $r['total_hpp'], $r['payment_method'], $r['status']]);
    exit;
}
layout_start('Laporan', 'reports.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">REPORTING</p>
        <h1>Laporan penjualan</h1>
        <p class="muted">Gross profit adalah estimasi omzet dikurangi HPP, bukan laba bersih.</p>
    </div><a class="button secondary" href="?from=<?= e($from) ?>&to=<?= e($to) ?>&export=1">Export CSV</a>
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