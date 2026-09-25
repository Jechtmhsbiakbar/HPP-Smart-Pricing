<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
require_auth();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$rows = query_all('SELECT s.*,GROUP_CONCAT(CONCAT(si.product_name," x",si.qty) SEPARATOR ", ") items FROM sales s LEFT JOIN sale_items si ON si.sale_id=s.id WHERE DATE(s.sold_at) BETWEEN ? AND ? GROUP BY s.id ORDER BY s.sold_at DESC', 'ss', [$from, $to]);
$summary = one('SELECT COALESCE(SUM(total),0) total,COALESCE(SUM(total_hpp),0) hpp,COUNT(*) count FROM sales WHERE status="COMPLETED" AND DATE(sold_at) BETWEEN ? AND ?', 'ss', [$from, $to]) ?: ['total' => 0, 'hpp' => 0, 'count' => 0];
layout_start('Laporan', 'reports.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">REPORTING</p>
        <h1>Laporan penjualan</h1>
        <p class="muted">Gross profit adalah estimasi omzet dikurangi HPP, bukan laba bersih.</p>
    </div>
    <button class="button secondary" type="button" id="sales-export" data-export-sales data-api="api/sales-export.php">
        Export XLSX (File excel)
    </button>
</section>
<section class="export-status" id="sales-export-feedback" data-export-feedback hidden role="status" aria-live="polite">
    <div class="export-status-heading">
        <div>
            <strong id="sales-export-title">Siap export</strong>
            <p id="sales-export-detail" class="muted">File akan diproses di browser Anda.</p>
        </div>
        <strong id="sales-export-percent">0%</strong>
    </div>
    <div class="export-progress" aria-hidden="true"><span id="sales-export-progress"></span></div>
</section>
<section class="stats">
    <div class="stat"><span>Omzet</span><strong><?= rupiah($summary['total']) ?></strong></div>
    <div class="stat"><span>HPP</span><strong><?= rupiah($summary['hpp']) ?></strong></div>
    <div class="stat accent"><span>Laba
            kotor</span><strong><?= rupiah((float) $summary['total'] - (float) $summary['hpp']) ?></strong></div>
    <div class="stat"><span>Transaksi</span><strong><?= $summary['count'] ?></strong></div>
</section>
<section class="panel">
    <form class="filter-bar"><label>Dari<input id="sales-from" type="date" name="from" value="<?= e($from) ?>"></label><label>Sampai<input id="sales-to"
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
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<?php layout_end(); ?>