<?php
require_once '../config/database.php';

if (isset($_GET['uid'])) {
    $uid = $_GET['uid'];
    
    $stmt = $pdo->prepare("SELECT * FROM karyawan WHERE barcode_uid = :uid LIMIT 1");
    $stmt->execute(['uid' => $uid]);
    $karyawan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($karyawan) {
        if ($karyawan['status_aktif'] == false) {
            echo json_encode(['status' => 'error', 'message' => 'Karyawan tidak aktif']);
            exit;
        }
        echo json_encode(['status' => 'success', 'data' => $karyawan]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
    }
}
?>