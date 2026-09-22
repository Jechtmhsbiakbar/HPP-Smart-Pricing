<?php
declare(strict_types=1);
require __DIR__ . '/../includes/app.php';
require_auth();
header('Content-Type: application/json; charset=utf-8');
try { echo json_encode(['success'=>true,'data'=>recipes_with_cost()], JSON_UNESCAPED_UNICODE); } catch(Throwable $e) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Tidak dapat memuat produk.']); }
