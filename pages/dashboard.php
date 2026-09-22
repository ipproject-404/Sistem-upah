<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'produksi') {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';

$total_transaksi = 0; 
$total_kg_hari_ini = 0; 
$karyawan_aktif_hari_ini = 0;

try {
    $stmt1 = $pdo->query("SELECT COUNT(*) as total FROM transaksi_timbang WHERE DATE(created_at) = CURRENT_DATE");
    $total_transaksi = $stmt1->fetch()['total'] ?? 0;

    $stmt2 = $pdo->query("SELECT SUM(berat) as total_kg FROM transaksi_timbang WHERE DATE(created_at) = CURRENT_DATE");
    $total_kg_hari_ini = $stmt2->fetch()['total_kg'] ?? 0;

    $stmt3 = $pdo->query("SELECT COUNT(DISTINCT karyawan_id) as total_orang FROM transaksi_timbang WHERE DATE(created_at) = CURRENT_DATE");
    $karyawan_aktif_hari_ini = $stmt3->fetch()['total_orang'] ?? 0;

} catch (PDOException $e) {
    echo '<div class="container mt-4"><div class="alert alert-warning">Data belum bisa ditarik: Pastikan tabel transaksi_timbang sudah memiliki kolom created_at, berat, dan karyawan_id.</div></div>';
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-left: 5px solid #198754 !important;">
                <div class="card-body py-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="text-hijau fw-bold mb-1">Dashboard Produksi</h3>
                        <p class="text-muted mb-0">Pantau pergerakan hasil kupas udang secara real-time hari ini.</p>
                    </div>
                    <div>
                        <span class="badge bg-light text-hijau border p-2 fs-6">📅 <?= date('d M Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h1 class="display-5 fw-bold text-hijau"><?= number_format($total_kg_hari_ini, 2, ',', '.') ?> Kg</h1>
                    <p class="text-muted mb-0">Total Setoran Udang Hari Ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h1 class="display-5 fw-bold text-hijau"><?= $total_transaksi ?></h1>
                    <p class="text-muted mb-0">Antrean Ditimbang Hari Ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h1 class="display-5 fw-bold text-hijau"><?= $karyawan_aktif_hari_ini ?></h1>
                    <p class="text-muted mb-0">Karyawan Menyetor Hari Ini</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <h4 class="fw-bold text-hijau mb-3">Stasiun Penimbangan Timbangan</h4>
                    <p class="text-muted mb-4 w-75 mx-auto">Pastikan scanner QR sudah terhubung dengan laptop/komputer. Karyawan hanya perlu mengarahkan QR dari HP ke scanner, dan sistem akan mencatat otomatis.</p>
                    <a href="timbangan.php" class="btn btn-hijau btn-lg px-5 py-3 fw-bold shadow-sm">
                        🐟 Buka Aplikasi Kasir Timbangan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>