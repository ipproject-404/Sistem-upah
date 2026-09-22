<?php
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT barcode_uid, nama_lengkap, grup_shift FROM karyawan ORDER BY barcode_uid ASC");
    $karyawanList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Karyawan (Testing)</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #000 !important; box-shadow: none !important; }
            body { background-color: #fff !important; }
        }
    </style>
</head>
<body class="bg-light pb-5">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3 class="text-success fw-bold">Daftar QR Code Karyawan (Test Scanner)</h3>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak / Save PDF</button>
    </div>

    <div class="row">
        <?php foreach($karyawanList as $k) : ?>
        <div class="col-md-2 col-sm-3 col-6 mb-4 text-center">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body p-3">
                   <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($k['barcode_uid']) ?>" 
                         alt="QR <?= $k['barcode_uid'] ?>" 
                         class="img-fluid mb-2">
                    
                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($k['barcode_uid']) ?></h6>
                    <small class="d-block text-truncate"><?= htmlspecialchars($k['nama_lengkap']) ?></small>
                    <span class="badge bg-success mt-1"><?= htmlspecialchars($k['grup_shift']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>