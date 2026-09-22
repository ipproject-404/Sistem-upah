<?php
session_start();

// Cek login/divisi
if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] != 'hr') {
    header("Location: ../index.php");
    exit;
}

// Koneksi database
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| STATISTIK KARYAWAN
|--------------------------------------------------------------------------
*/

// Total seluruh karyawan
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM karyawan
");
$total_karyawan = $stmt->fetch()['total'];

// Karyawan aktif
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM karyawan
    WHERE is_active = true
    AND status_aktif = true
");
$karyawan_aktif = $stmt->fetch()['total'];

// Karyawan nonaktif
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM karyawan
    WHERE is_active = false
    OR status_aktif = false
");
$karyawan_nonaktif = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| PRODUKSI HARI INI
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(berat_kg), 0) AS total
    FROM transaksi_timbang
    WHERE DATE(created_at) = CURRENT_DATE
");
$produksi_hari_ini = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| PRODUKSI BULAN INI
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(berat_kg), 0) AS total
    FROM transaksi_timbang
    WHERE DATE_TRUNC('month', created_at)
          = DATE_TRUNC('month', CURRENT_DATE)
");
$produksi_bulan_ini = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| TOTAL UPAH PRODUKSI BULAN INI
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_upah), 0) AS total
    FROM transaksi_timbang
    WHERE DATE_TRUNC('month', created_at)
          = DATE_TRUNC('month', CURRENT_DATE)
");
$total_upah_bulan_ini = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| MONITORING PRODUKSI PER KARYAWAN
|--------------------------------------------------------------------------
|
| Menampilkan:
| - Nama karyawan
| - Shift
| - Produksi hari ini
| - Produksi bulan ini
| - Status
|
*/

$stmt = $pdo->query("
    SELECT
        k.id,
        k.barcode_uid,
        k.nama_lengkap,
        k.grup_shift,
        k.is_active,
        k.status_aktif,

        COALESCE(
            SUM(
                CASE
                    WHEN DATE(t.created_at) = CURRENT_DATE
                    THEN t.berat_kg
                    ELSE 0
                END
            ), 0
        ) AS produksi_hari_ini,

        COALESCE(
            SUM(
                CASE
                    WHEN DATE_TRUNC('month', t.created_at)
                         = DATE_TRUNC('month', CURRENT_DATE)
                    THEN t.berat_kg
                    ELSE 0
                END
            ), 0
        ) AS produksi_bulan_ini

    FROM karyawan k

    LEFT JOIN transaksi_timbang t
        ON t.karyawan_id = k.id

    GROUP BY
        k.id,
        k.barcode_uid,
        k.nama_lengkap,
        k.grup_shift,
        k.is_active,
        k.status_aktif

    ORDER BY
        produksi_hari_ini DESC,
        k.nama_lengkap ASC

    LIMIT 10
");

$data_karyawan = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PRODUKSI TERBARU
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        t.id,
        t.berat_kg,
        t.total_upah,
        t.created_at,
        k.nama_lengkap,
        k.barcode_uid
    FROM transaksi_timbang t
    LEFT JOIN karyawan k
        ON t.karyawan_id = k.id
    ORDER BY t.created_at DESC
    LIMIT 5
");

$produksi_terbaru = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| FORMAT ANGKA
|--------------------------------------------------------------------------
*/

function formatKg($angka)
{
    return number_format((float)$angka, 2, ',', '.') . " Kg";
}

function formatRupiah($angka)
{
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard HR - Pabrik Udang</title>

    <link
        href="../assets/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
        }

        .sidebar-brand {
            padding: 22px;
            border-bottom: 1px solid #dee2e6;
        }

        .sidebar-brand h5 {
            color: #198754;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .sidebar-brand small {
            color: #6c757d;
        }

        .sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            margin: 4px 10px;
            border-radius: 8px;
        }

        .sidebar .nav-link:hover {
            background-color: #e9f7ef;
            color: #198754;
        }

        .sidebar .nav-link.active {
            background-color: #198754;
            color: white;
        }

        .topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 25px;
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background-color: #e9f7ef;
            color: #198754;
        }

        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table th {
            font-size: 13px;
            color: #6c757d;
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .badge-aktif {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-nonaktif {
            background-color: #f8d7da;
            color: #842029;
        }

        .quick-btn {
            text-decoration: none;
            color: #198754;
            border: 1px solid #d1e7dd;
            border-radius: 10px;
            padding: 15px;
            display: block;
            transition: 0.2s;
        }

        .quick-btn:hover {
            background-color: #e9f7ef;
            color: #146c43;
        }

        .content {
            min-height: 100vh;
        }

    </style>

</head>

<body>

<div class="container-fluid">

    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 px-0 sidebar">

            <div class="sidebar-brand">

                <h5>🍤 Pabrik Udang</h5>

                <small>
                    Sistem Manajemen Upah
                </small>

            </div>

            <div class="p-2">

                <small class="text-muted px-3">
                    MENU HR
                </small>

                <nav class="nav flex-column mt-2">

                    <a
                        href="dashboard_hr.php"
                        class="nav-link active"
                    >
                        📊 Dashboard
                    </a>

                    <a
                        href="data_karyawan.php"
                        class="nav-link"
                    >
                        👥 Data Karyawan
                    </a>

                    <a
                        href="qr_karyawan.php"
                        class="nav-link"
                    >
                        📱 QR Code Karyawan
                    </a>

                    <a
                        href="monitoring_produksi.php"
                        class="nav-link"
                    >
                        ⚖️ Monitoring Produksi
                    </a>

                    <a
                        href="histori_produksi.php"
                        class="nav-link"
                    >
                        📋 Histori Produksi
                    </a>

                    <a
                        href="profil_hr.php"
                        class="nav-link"
                    >
                        👤 Profil HR
                    </a>

                    <hr>

                    <a
                        href="../logout.php"
                        class="nav-link text-danger"
                    >
                        🚪 Logout
                    </a>

                </nav>

            </div>

        </div>


        <!-- CONTENT -->
        <div class="col-md-9 col-lg-10 px-0 content">

            <!-- TOPBAR -->
            <div class="topbar">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-1 fw-bold">
                            Dashboard HR
                        </h5>

                        <small class="text-muted">
                            Monitoring data karyawan dan produksi
                        </small>

                    </div>

                    <div class="text-end">

                        <span class="badge bg-success">
                            HR
                        </span>

                    </div>

                </div>

            </div>


            <!-- MAIN -->
            <div class="p-4">

                <!-- STATISTIK -->
                <div class="row g-3 mb-4">

                    <!-- TOTAL KARYAWAN -->
                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted">
                                            Total Karyawan
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0">
                                            <?= number_format($total_karyawan) ?>
                                        </h3>

                                    </div>

                                    <div class="stat-icon">
                                        👥
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- AKTIF -->
                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted">
                                            Karyawan Aktif
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0 text-success">
                                            <?= number_format($karyawan_aktif) ?>
                                        </h3>

                                    </div>

                                    <div class="stat-icon">
                                        ✅
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- NONAKTIF -->
                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted">
                                            Karyawan Nonaktif
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0 text-danger">
                                            <?= number_format($karyawan_nonaktif) ?>
                                        </h3>

                                    </div>

                                    <div class="stat-icon">
                                        ⛔
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PRODUKSI HARI INI -->
                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="text-muted">
                                            Produksi Hari Ini
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0">
                                            <?= formatKg($produksi_hari_ini) ?>
                                        </h3>

                                    </div>

                                    <div class="stat-icon">
                                        ⚖️
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PRODUKSI BULAN + UPAH -->
                <div class="row g-3 mb-4">

                    <div class="col-md-6">

                        <div class="card stat-card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Total Produksi Bulan Ini
                                </small>

                                <h3 class="fw-bold mt-2 mb-0">
                                    <?= formatKg($produksi_bulan_ini) ?>
                                </h3>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="card stat-card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Total Upah Produksi Bulan Ini
                                </small>

                                <h3 class="fw-bold mt-2 mb-0 text-success">
                                    <?= formatRupiah($total_upah_bulan_ini) ?>
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- QUICK ACTION -->
                <div class="card card-custom mb-4">

                    <div class="card-body">

                        <h5 class="fw-bold mb-3">
                            Akses Cepat
                        </h5>

                        <div class="row g-3">

                            <div class="col-md-3">

                                <a
                                    href="data_karyawan.php"
                                    class="quick-btn"
                                >
                                    👥
                                    <strong class="d-block mt-2">
                                        Data Karyawan
                                    </strong>

                                    <small class="text-muted">
                                        Kelola data karyawan
                                    </small>

                                </a>

                            </div>


                            <div class="col-md-3">

                                <a
                                    href="qr_karyawan.php"
                                    class="quick-btn"
                                >
                                    📱
                                    <strong class="d-block mt-2">
                                        QR Karyawan
                                    </strong>

                                    <small class="text-muted">
                                        Kelola QR Code
                                    </small>

                                </a>

                            </div>


                            <div class="col-md-3">

                                <a
                                    href="monitoring_produksi.php"
                                    class="quick-btn"
                                >
                                    ⚖️
                                    <strong class="d-block mt-2">
                                        Monitoring Produksi
                                    </strong>

                                    <small class="text-muted">
                                        Lihat produksi karyawan
                                    </small>

                                </a>

                            </div>


                            <div class="col-md-3">

                                <a
                                    href="histori_produksi.php"
                                    class="quick-btn"
                                >
                                    📋
                                    <strong class="d-block mt-2">
                                        Histori Produksi
                                    </strong>

                                    <small class="text-muted">
                                        Riwayat penimbangan
                                    </small>

                                </a>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- MONITORING KARYAWAN -->
                <div class="card card-custom mb-4">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h5 class="fw-bold mb-1">
                                    Monitoring Produksi Karyawan
                                </h5>

                                <small class="text-muted">
                                    10 karyawan dengan produksi terbaru
                                </small>

                            </div>

                            <a
                                href="monitoring_produksi.php"
                                class="btn btn-outline-success btn-sm"
                            >
                                Lihat Semua
                            </a>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-hover">

                                <thead>

                                    <tr>

                                        <th>
                                            Karyawan
                                        </th>

                                        <th>
                                            Shift
                                        </th>

                                        <th>
                                            Produksi Hari Ini
                                        </th>

                                        <th>
                                            Produksi Bulan Ini
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php if (count($data_karyawan) > 0): ?>

                                    <?php foreach ($data_karyawan as $karyawan): ?>

                                        <tr>

                                            <td>

                                                <div class="fw-bold">
                                                    <?= htmlspecialchars($karyawan['nama_lengkap']) ?>
                                                </div>

                                                <small class="text-muted">
                                                    <?= htmlspecialchars($karyawan['barcode_uid']) ?>
                                                </small>

                                            </td>


                                            <td>

                                                <?php if (!empty($karyawan['grup_shift'])): ?>

                                                    <?= htmlspecialchars($karyawan['grup_shift']) ?>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        -
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <td class="fw-bold">

                                                <?= formatKg($karyawan['produksi_hari_ini']) ?>

                                            </td>


                                            <td>

                                                <?= formatKg($karyawan['produksi_bulan_ini']) ?>

                                            </td>


                                            <td>

                                                <?php if (
                                                    $karyawan['is_active'] &&
                                                    $karyawan['status_aktif']
                                                ): ?>

                                                    <span class="badge badge-aktif">
                                                        Aktif
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge badge-nonaktif">
                                                        Nonaktif
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="text-center text-muted py-4"
                                        >
                                            Belum ada data karyawan.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- PRODUKSI TERBARU -->
                <div class="card card-custom">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h5 class="fw-bold mb-1">
                                    Transaksi Penimbangan Terbaru
                                </h5>

                                <small class="text-muted">
                                    Data produksi yang baru dicatat
                                </small>

                            </div>

                            <a
                                href="histori_produksi.php"
                                class="btn btn-outline-success btn-sm"
                            >
                                Lihat Histori
                            </a>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-hover">

                                <thead>

                                    <tr>

                                        <th>
                                            Karyawan
                                        </th>

                                        <th>
                                            Berat
                                        </th>

                                        <th>
                                            Total Upah
                                        </th>

                                        <th>
                                            Waktu
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php if (count($produksi_terbaru) > 0): ?>

                                    <?php foreach ($produksi_terbaru as $produksi): ?>

                                        <tr>

                                            <td>

                                                <div class="fw-bold">
                                                    <?= htmlspecialchars(
                                                        $produksi['nama_lengkap'] ?? 'Tidak diketahui'
                                                    ) ?>
                                                </div>

                                                <small class="text-muted">
                                                    <?= htmlspecialchars(
                                                        $produksi['barcode_uid'] ?? '-'
                                                    ) ?>
                                                </small>

                                            </td>


                                            <td class="fw-bold">

                                                <?= formatKg($produksi['berat_kg']) ?>

                                            </td>


                                            <td class="text-success fw-bold">

                                                <?= formatRupiah($produksi['total_upah']) ?>

                                            </td>


                                            <td>

                                                <?= date(
                                                    'd/m/Y H:i',
                                                    strtotime($produksi['created_at'])
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td
                                            colspan="4"
                                            class="text-center text-muted py-4"
                                        >
                                            Belum ada transaksi penimbangan.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script src="../assets/js/bootstrap.bundle.min.js"></script>

</body>

</html>