<?php
declare(strict_types=1);

require __DIR__ . '/../includes/app.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

function export_date(string $key, string $default): string
{
    $value = (string) ($_GET[$key] ?? $default);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Rentang tanggal export tidak valid.');
    }

    return $value;
}

try {
    $from = export_date('from', date('Y-m-01'));
    $to = export_date('to', date('Y-m-d'));

    if ($from > $to) {
        throw new InvalidArgumentException('Tanggal awal tidak boleh melewati tanggal akhir.');
    }

    $rows = query_all(
        'SELECT s.invoice_no, s.sold_at,
                GROUP_CONCAT(CONCAT(si.product_name, " x", si.qty) SEPARATOR ", ") AS items,
                s.total, s.total_hpp, s.payment_method, s.status
         FROM sales s
         LEFT JOIN sale_items si ON si.sale_id = s.id
         WHERE DATE(s.sold_at) BETWEEN ? AND ?
         GROUP BY s.id
         ORDER BY s.sold_at DESC',
        'ss',
        [$from, $to]
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Data export penjualan tidak tersedia.',
    ], JSON_UNESCAPED_UNICODE);
}
