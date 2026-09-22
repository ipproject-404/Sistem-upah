<?php
session_start();

if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'akunting') {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| FILTER PERIODE
|--------------------------------------------------------------------------
*/

$periode = $_GET['periode'] ?? 'bulan';

switch ($periode) {

    case 'hari':
        $label_periode = 'Hari Ini';

        $where_periode = "
            DATE(t.created_at) = CURRENT_DATE
        ";

        break;

    case 'minggu':
        $label_periode = 'Minggu Ini';

        $where_periode = "
            t.created_at >= DATE_TRUNC('week', CURRENT_DATE)
            AND t.created_at < DATE_TRUNC('week', CURRENT_DATE) + INTERVAL '1 week'
        ";

        break;

    case 'bulan':
        $label_periode = 'Bulan Ini';

        $where_periode = "
            t.created_at >= DATE_TRUNC('month', CURRENT_DATE)
            AND t.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ";

        break;

    case 'tahun':
        $label_periode = 'Tahun Ini';

        $where_periode = "
            t.created_at >= DATE_TRUNC('year', CURRENT_DATE)
            AND t.created_at < DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year'
        ";

        break;

    default:
        $label_periode = 'Bulan Ini';

        $where_periode = "
            t.created_at >= DATE_TRUNC('month', CURRENT_DATE)
            AND t.created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ";

        $periode = 'bulan';
}


/*
|--------------------------------------------------------------------------
| TOTAL KARYAWAN AKTIF
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM karyawan
    WHERE is_active = true
    AND status_aktif = true
");

$total_karyawan_aktif = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| TOTAL PRODUKSI
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COALESCE(SUM(t.berat_kg), 0) AS total_kg
    FROM transaksi_timbang t
    WHERE $where_periode
");

$total_produksi = $stmt->fetch()['total_kg'];


/*
|--------------------------------------------------------------------------
| TOTAL UPAH
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COALESCE(SUM(t.total_upah), 0) AS total_upah
    FROM transaksi_timbang t
    WHERE $where_periode
");

$total_upah = $stmt->fetch()['total_upah'];


/*
|--------------------------------------------------------------------------
| TOTAL TRANSAKSI
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM transaksi_timbang t
    WHERE $where_periode
");

$total_transaksi = $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| TOTAL LEMBUR
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS jumlah_lembur,
        COALESCE(SUM(total_upah_lembur), 0) AS total_upah_lembur
    FROM absensi_lembur
    WHERE
        created_at >= DATE_TRUNC('month', CURRENT_DATE)
        AND created_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
");

$data_lembur = $stmt->fetch();

$jumlah_lembur = $data_lembur['jumlah_lembur'];
$total_upah_lembur = $data_lembur['total_upah_lembur'];


/*
|--------------------------------------------------------------------------
| TOTAL KESELURUHAN UPAH
|--------------------------------------------------------------------------
*/

$total_pengeluaran_upah =
    (float)$total_upah +
    (float)$total_upah_lembur;


/*
|--------------------------------------------------------------------------
| REKAP UPAH PER KARYAWAN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        k.id,
        k.nama_lengkap,
        k.barcode_uid,
        k.grup_shift,

        COALESCE(
            SUM(t.berat_kg),
            0
        ) AS total_kg,

        COALESCE(
            SUM(t.total_upah),
            0
        ) AS total_upah

    FROM karyawan k

    INNER JOIN transaksi_timbang t
        ON t.karyawan_id = k.id

    WHERE $where_periode

    GROUP BY
        k.id,
        k.nama_lengkap,
        k.barcode_uid,
        k.grup_shift

    ORDER BY
        total_upah DESC,
        k.nama_lengkap ASC

    LIMIT 10
");

$rekap_karyawan = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| TRANSAKSI TERBARU
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        t.id,
        t.berat_kg,
        t.total_upah,
        t.created_at,

        k.nama_lengkap,
        k.barcode_uid,

        mt.nama_pekerjaan,
        mt.harga_per_satuan

    FROM transaksi_timbang t

    LEFT JOIN karyawan k
        ON t.karyawan_id = k.id

    LEFT JOIN master_tarif mt
        ON t.tarif_id = mt.id

    ORDER BY t.created_at DESC

    LIMIT 10
");

$transaksi_terbaru = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| FORMAT
|--------------------------------------------------------------------------
*/

function formatKg($angka)
{
    return number_format(
        (float)$angka,
        2,
        ',',
        '.'
    ) . ' Kg';
}


function formatRupiah($angka)
{
    return 'Rp ' . number_format(
        (float)$angka,
        0,
        ',',
        '.'
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard Akunting - Pabrik Udang
    </title>

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
            color: #ffffff;
        }

        .topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 25px;
        }

        .content {
            min-height: 100vh;
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table th {
            font-size: 13px;
            color: #6c757d;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .quick-btn {
            text-decoration: none;
            color: #198754;
            border: 1px solid #d1e7dd;
            border-radius: 10px;
            padding: 15px;
            display: block;
            transition: 0.2s;
            background: #ffffff;
        }

        .quick-btn:hover {
            background-color: #e9f7ef;
            color: #146c43;
        }

        .summary-label {
            font-size: 13px;
            color: #6c757d;
        }

        .period-btn.active {
            background-color: #198754;
            color: white;
        }

        .badge-aktif {
            background-color: #d1e7dd;
            color: #0f5132;
        }

    </style>

</head>


<body>

<div class="container-fluid">

    <div class="row">


        <!-- =====================================================
             SIDEBAR
        ====================================================== -->

        <div class="col-md-3 col-lg-2 px-0 sidebar">

            <div class="sidebar-brand">

                <h5>
                    🍤 Pabrik Udang
                </h5>

                <small class="text-muted">
                    Sistem Manajemen Upah
                </small>

            </div>


            <div class="p-2">

                <small class="text-muted px-3">
                    MENU AKUNTING
                </small>


                <nav class="nav flex-column mt-2">


                    <!-- DASHBOARD -->

                    <a
                        href="dashboard_akunting.php"
                        class="nav-link active"
                    >
                        📊 Dashboard
                    </a>


                    <!-- REKAP PRODUKSI -->

                    <a
                        href="rekap_produksi.php"
                        class="nav-link"
                    >
                        ⚖️ Rekap Produksi
                    </a>


                    <!-- REKAP UPAH -->

                    <a
                        href="rekap_upah.php"
                        class="nav-link"
                    >
                        💰 Rekap Upah
                    </a>


                    <!-- HISTORI -->

                    <a
                        href="histori_produksi_akunting.php"
                        class="nav-link"
                    >
                        📋 Histori Transaksi
                    </a>


                    <!-- TARIF -->

                    <a
                        href="master_tarif.php"
                        class="nav-link"
                    >
                        💵 Master Tarif
                    </a>


                    <!-- LEMBUR -->

                    <a
                        href="rekap_lembur.php"
                        class="nav-link"
                    >
                        ⏰ Rekap Lembur
                    </a>


                    <hr>


                    <!-- PROFIL -->

                    <a
                        href="profil_akunting.php"
                        class="nav-link"
                    >
                        👤 Profil Akunting
                    </a>


                    <!-- LOGOUT -->

                    <a
                        href="../logout.php"
                        class="nav-link text-danger"
                    >
                        🚪 Logout
                    </a>

                </nav>

            </div>

        </div>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <div class="col-md-9 col-lg-10 px-0 content">


            <!-- TOPBAR -->

            <div class="topbar">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-1 fw-bold">
                            Dashboard Akunting
                        </h5>

                        <small class="text-muted">
                            Rekap produksi dan perhitungan upah
                        </small>

                    </div>


                    <div>

                        <span class="badge bg-success">
                            AKUNTING
                        </span>

                    </div>

                </div>

            </div>


            <!-- MAIN CONTENT -->

            <div class="p-4">


                <!-- =================================================
                     HEADER + FILTER
                ================================================== -->

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <div>

                        <h4 class="fw-bold mb-1">
                            Ringkasan Keuangan Produksi
                        </h4>

                        <small class="text-muted">
                            Periode: <?= htmlspecialchars($label_periode) ?>
                        </small>

                    </div>


                    <div>

                        <form method="GET">

                            <select
                                name="periode"
                                class="form-select"
                                onchange="this.form.submit()"
                            >

                                <option
                                    value="hari"
                                    <?= $periode === 'hari' ? 'selected' : '' ?>
                                >
                                    Hari Ini
                                </option>

                                <option
                                    value="minggu"
                                    <?= $periode === 'minggu' ? 'selected' : '' ?>
                                >
                                    Minggu Ini
                                </option>

                                <option
                                    value="bulan"
                                    <?= $periode === 'bulan' ? 'selected' : '' ?>
                                >
                                    Bulan Ini
                                </option>

                                <option
                                    value="tahun"
                                    <?= $periode === 'tahun' ? 'selected' : '' ?>
                                >
                                    Tahun Ini
                                </option>

                            </select>

                        </form>

                    </div>

                </div>


                <!-- =================================================
                     STATISTICS
                ================================================== -->

                <div class="row g-3 mb-4">


                    <!-- KARYAWAN -->

                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="summary-label">
                                            Karyawan Aktif
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0">
                                            <?= number_format($total_karyawan_aktif) ?>
                                        </h3>

                                    </div>


                                    <div class="stat-icon">
                                        👥
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PRODUKSI -->

                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="summary-label">
                                            Total Produksi
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0">
                                            <?= formatKg($total_produksi) ?>
                                        </h3>

                                    </div>


                                    <div class="stat-icon">
                                        ⚖️
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- TRANSAKSI -->

                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="summary-label">
                                            Jumlah Transaksi
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0">
                                            <?= number_format($total_transaksi) ?>
                                        </h3>

                                    </div>


                                    <div class="stat-icon">
                                        🧾
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- UPAH -->

                    <div class="col-md-6 col-xl-3">

                        <div class="card stat-card h-100">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <small class="summary-label">
                                            Total Upah
                                        </small>

                                        <h3 class="fw-bold mt-2 mb-0 text-success">
                                            <?= formatRupiah($total_upah) ?>
                                        </h3>

                                    </div>


                                    <div class="stat-icon">
                                        💰
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     LEMBUR
                ================================================== -->

                <div class="row g-3 mb-4">


                    <div class="col-md-6">

                        <div class="card stat-card">

                            <div class="card-body">

                                <small class="summary-label">
                                    Jumlah Transaksi Lembur Bulan Ini
                                </small>

                                <h3 class="fw-bold mt-2 mb-0">
                                    <?= number_format($jumlah_lembur) ?>
                                </h3>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="card stat-card">

                            <div class="card-body">

                                <small class="summary-label">
                                    Total Upah Lembur Bulan Ini
                                </small>

                                <h3 class="fw-bold mt-2 mb-0 text-success">
                                    <?= formatRupiah($total_upah_lembur) ?>
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     TOTAL PENGELUARAN
                ================================================== -->

                <div class="card card-custom mb-4">

                    <div class="card-body">

                        <div class="row align-items-center">

                            <div class="col-md-8">

                                <small class="text-muted">
                                    Estimasi Total Pengeluaran Upah
                                    Bulan Ini
                                </small>

                                <h2 class="fw-bold text-success mt-2 mb-0">
                                    <?= formatRupiah($total_pengeluaran_upah) ?>
                                </h2>

                                <small class="text-muted">
                                    Upah produksi + upah lembur
                                </small>

                            </div>


                            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                                <a
                                    href="rekap_upah.php"
                                    class="btn btn-success"
                                >
                                    Lihat Rekap Upah
                                </a>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     QUICK ACTION
                ================================================== -->

                <div class="card card-custom mb-4">

                    <div class="card-body">

                        <h5 class="fw-bold mb-3">
                            Akses Cepat
                        </h5>


                        <div class="row g-3">


                            <div class="col-md-3">

                                <a
                                    href="rekap_produksi.php"
                                    class="quick-btn"
                                >

                                    <div style="font-size:25px;">
                                        ⚖️
                                    </div>

                                    <strong class="d-block mt-2">
                                        Rekap Produksi
                                    </strong>

                                    <small class="text-muted">
                                        Lihat total produksi
                                    </small>

                                </a>

                            </div>


                            <div class="col-md-3">

                                <a
                                    href="rekap_upah.php"
                                    class="quick-btn"
                                >

                                    <div style="font-size:25px;">
                                        💰
                                    </div>

                                    <strong class="d-block mt-2">
                                        Rekap Upah
                                    </strong>

                                    <small class="text-muted">
                                        Perhitungan upah karyawan
                                    </small>

                                </a>

                            </div>


                            <div class="col-md-3">

                                <a
                                    href="master_tarif.php"
                                    class="quick-btn"
                                >

                                    <div style="font-size:25px;">
                                        💵
                                    </div>

                                    <strong class="d-block mt-2">
                                        Master Tarif
                                    </strong>

                                    <small class="text-muted">
                                        Kelola tarif pekerjaan
                                    </small>

                                </a>

                            </div>


                            <div class="col-md-3">

                                <a
                                    href="rekap_lembur.php"
                                    class="quick-btn"
                                >

                                    <div style="font-size:25px;">
                                        ⏰
                                    </div>

                                    <strong class="d-block mt-2">
                                        Rekap Lembur
                                    </strong>

                                    <small class="text-muted">
                                        Rekap upah lembur
                                    </small>

                                </a>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     REKAP KARYAWAN
                ================================================== -->

                <div class="card card-custom mb-4">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h5 class="fw-bold mb-1">
                                    Rekap Upah Per Karyawan
                                </h5>

                                <small class="text-muted">
                                    <?= htmlspecialchars($label_periode) ?>
                                </small>

                            </div>


                            <a
                                href="rekap_upah.php"
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
                                            No
                                        </th>

                                        <th>
                                            Karyawan
                                        </th>

                                        <th>
                                            Shift
                                        </th>

                                        <th>
                                            Total Produksi
                                        </th>

                                        <th>
                                            Total Upah
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                <?php if (count($rekap_karyawan) > 0): ?>

                                    <?php foreach ($rekap_karyawan as $i => $row): ?>

                                        <tr>

                                            <td>
                                                <?= $i + 1 ?>
                                            </td>


                                            <td>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $row['nama_lengkap']
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small class="text-muted">
                                                    <?= htmlspecialchars(
                                                        $row['barcode_uid']
                                                    ) ?>
                                                </small>

                                            </td>


                                            <td>
                                                <?= htmlspecialchars(
                                                    $row['grup_shift'] ?? '-'
                                                ) ?>
                                            </td>


                                            <td>
                                                <?= formatKg(
                                                    $row['total_kg']
                                                ) ?>
                                            </td>


                                            <td class="fw-bold text-success">
                                                <?= formatRupiah(
                                                    $row['total_upah']
                                                ) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="text-center text-muted py-5"
                                        >
                                            Belum ada transaksi pada
                                            periode ini.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     TRANSAKSI TERBARU
                ================================================== -->

                <div class="card card-custom">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h5 class="fw-bold mb-1">
                                    Transaksi Penimbangan Terbaru
                                </h5>

                                <small class="text-muted">
                                    Data produksi terbaru
                                </small>

                            </div>


                            <a
                                href="histori_produksi_akunting.php"
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
                                            Waktu
                                        </th>

                                        <th>
                                            Karyawan
                                        </th>

                                        <th>
                                            Pekerjaan
                                        </th>

                                        <th>
                                            Berat
                                        </th>

                                        <th>
                                            Total Upah
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                <?php if (count($transaksi_terbaru) > 0): ?>

                                    <?php foreach ($transaksi_terbaru as $row): ?>

                                        <tr>

                                            <td>

                                                <?= date(
                                                    'd/m/Y H:i',
                                                    strtotime(
                                                        $row['created_at']
                                                    )
                                                ) ?>

                                            </td>


                                            <td>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $row['nama_lengkap']
                                                        ?? 'Tidak diketahui'
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small class="text-muted">
                                                    <?= htmlspecialchars(
                                                        $row['barcode_uid']
                                                        ?? '-'
                                                    ) ?>
                                                </small>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $row['nama_pekerjaan']
                                                    ?? '-'
                                                ) ?>

                                            </td>


                                            <td class="fw-bold">

                                                <?= formatKg(
                                                    $row['berat_kg']
                                                ) ?>

                                            </td>


                                            <td class="fw-bold text-success">

                                                <?= formatRupiah(
                                                    $row['total_upah']
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="text-center text-muted py-5"
                                        >
                                            Belum ada transaksi.

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