<?php
declare(strict_types=1);
require __DIR__ . '/../includes/app.php';
require_auth();
header('Content-Type: application/json; charset=utf-8');
try {
    $todayDate = date('Y-m-d');
    $stats = one('SELECT COALESCE(SUM(total),0) total,COUNT(*) transactions,COALESCE(SUM(total_hpp),0) hpp FROM sales WHERE status="COMPLETED" AND DATE(sold_at)=?', 's', [$todayDate]);
    echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Dashboard tidak tersedia.']);
}
