<?php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$total_karyawan = 0; 
$transaksi_hari_ini = 0; 
$lembur_hari_ini = 0; 

try {
    $stmtKaryawan = $pdo->query("SELECT COUNT(*) as total FROM karyawan");
    $total_karyawan = $stmtKaryawan->fetch()['total'];

    $stmtTimbang = $pdo->query("SELECT COUNT(*) as total FROM transaksi_timbang WHERE DATE(created_at) = CURRENT_DATE");
    $transaksi_hari_ini = $stmtTimbang->fetch()['total'];

    $stmtLembur = $pdo->query("SELECT COUNT(*) as total FROM absensi_lembur WHERE DATE(created_at) = CURRENT_DATE");
    $lembur_hari_ini = $stmtLembur->fetch()['total'];

} catch (PDOException $e) {
    echo '<div class="container mt-4"><div class="alert alert-warning">Gagal mengambil data statistik: ' . $e->getMessage() . '</div></div>';
}

?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-left: 5px solid #198754 !important;">
                <div class="card-body py-4">
                    <h3 class="text-hijau fw-bold">Selamat Datang di Dashboard Admin</h3>
                    <p class="text-muted mb-0">Kelola operasional penimbangan udang dan absensi lembur karyawan dengan cepat dan transparan.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h1 class="display-4 fw-bold text-hijau"><?= $total_karyawan ?></h1>
                    <p class="text-muted mb-0">Total Karyawan Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h1 class="display-4 fw-bold text-hijau"><?= $transaksi_hari_ini ?></h1>
                    <p class="text-muted mb-0">Transaksi Timbang Hari Ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h1 class="display-4 fw-bold text-hijau"><?= $lembur_hari_ini ?></h1>
                    <p class="text-muted mb-0">Karyawan Lembur Hari Ini</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <h5 class="fw-bold text-hijau mb-3">Operasional Borongan</h5>
                    <p class="text-muted mb-4">Buka halaman kasir penimbangan untuk memproses upah harian kupas udang karyawan.</p>
                    <a href="timbangan.php" class="btn btn-hijau px-4 py-2 w-75 fw-bold">Mulai Penimbangan 🦐</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <h5 class="fw-bold text-hijau mb-3">Operasional Lembur</h5>
                    <p class="text-muted mb-4">Buka halaman absensi untuk melakukan scan masuk (Clock-In) dan pulang (Clock-Out) lembur.</p>
                    <a href="lembur.php" class="btn btn-outline-success px-4 py-2 w-75 fw-bold">Catat Absensi Lembur ⏱️</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>