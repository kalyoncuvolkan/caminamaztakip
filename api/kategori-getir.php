<?php
require_once '../config/auth.php';
checkAuth();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = $_GET['id'] ?? 0;

if(!$id) {
    echo json_encode(['error' => 'ID gerekli']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ders_kategorileri WHERE id = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if($kategori) {
    echo json_encode($kategori);
} else {
    echo json_encode(['error' => 'Kategori bulunamadı']);
}
?>