<?php
session_start();

if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'hr') {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$periode = $_GET['periode'] ?? 'bulan';


/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        k.id,
        k.nama_lengkap,
        k.barcode_uid,
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
            ),
            0
        ) AS produksi_hari_ini,

        COALESCE(
            SUM(
                CASE
                    WHEN DATE_TRUNC('month', t.created_at)
                         = DATE_TRUNC('month', CURRENT_DATE)
                    THEN t.berat_kg
                    ELSE 0
                END
            ),
            0
        ) AS produksi_bulan_ini,

        COALESCE(
            SUM(t.berat_kg),
            0
        ) AS produksi_total,

        COALESCE(
            SUM(
                CASE
                    WHEN DATE(t.created_at) = CURRENT_DATE
                    THEN t.total_upah
                    ELSE 0
                END
            ),
            0
        ) AS upah_hari_ini,

        COALESCE(
            SUM(
                CASE
                    WHEN DATE_TRUNC('month', t.created_at)
                         = DATE_TRUNC('month', CURRENT_DATE)
                    THEN t.total_upah
                    ELSE 0
                END
            ),
            0
        ) AS upah_bulan_ini

    FROM karyawan k

    LEFT JOIN transaksi_timbang t
        ON t.karyawan_id = k.id
";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        WHERE
            k.nama_lengkap ILIKE :search
            OR k.barcode_uid ILIKE :search
            OR k.nik ILIKE :search
    ";

    $params[':search'] = '%' . $search . '%';
}


$sql .= "

    GROUP BY
        k.id,
        k.nama_lengkap,
        k.barcode_uid,
        k.grup_shift,
        k.is_active,
        k.status_aktif

    ORDER BY
        produksi_hari_ini DESC,
        k.nama_lengkap ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$data = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| FORMAT
|--------------------------------------------------------------------------
*/

function kg($value)
{
    return number_format((float)$value, 2, ',', '.') . " Kg";
}

function rupiah($value)
{
    return "Rp " . number_format((float)$value, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Monitoring Produksi - Pabrik Udang</title>

<link href="../assets/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    background: #f8f9fa;
}

.sidebar {
    min-height: 100vh;
    background: white;
    border-right: 1px solid #dee2e6;
}

.brand {
    padding: 22px;
    border-bottom: 1px solid #dee2e6;
}

.brand h5 {
    color: #198754;
    font-weight: bold;
}

.nav-link {
    color: #495057;
    margin: 4px 10px;
    border-radius: 8px;
}

.nav-link:hover {
    background: #e9f7ef;
    color: #198754;
}

.nav-link.active {
    background: #198754;
    color: white;
}

.card-custom {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}

.table td {
    vertical-align: middle;
}

.badge-aktif {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-nonaktif {
    background: #f8d7da;
    color: #842029;
}

</style>

</head>

<body>

<div class="container-fluid">

<div class="row">


<!-- SIDEBAR -->

<div class="col-md-3 col-lg-2 px-0 sidebar">

    <div class="brand">

        <h5>🍤 Pabrik Udang</h5>

        <small class="text-muted">
            Sistem Manajemen Upah
        </small>

    </div>

    <nav class="nav flex-column p-2">

        <a class="nav-link" href="dashboard_hr.php">
            📊 Dashboard
        </a>

        <a class="nav-link" href="data_karyawan.php">
            👥 Data Karyawan
        </a>

        <a class="nav-link" href="qr_karyawan.php">
            📱 QR Code Karyawan
        </a>

        <a class="nav-link active" href="monitoring_produksi.php">
            ⚖️ Monitoring Produksi
        </a>

        <a class="nav-link" href="histori_produksi.php">
            📋 Histori Produksi
        </a>

        <a class="nav-link" href="profil_hr.php">
            👤 Profil HR
        </a>

        <hr>

        <a class="nav-link text-danger" href="../logout.php">
            🚪 Logout
        </a>

    </nav>

</div>


<!-- CONTENT -->

<div class="col-md-9 col-lg-10">

<div class="p-4">

    <div class="mb-4">

        <h4 class="fw-bold mb-1">
            Monitoring Produksi
        </h4>

        <small class="text-muted">
            Monitoring produksi setiap karyawan
        </small>

    </div>


    <div class="card card-custom">

        <div class="card-body">

            <form method="GET" class="row g-2 mb-4">

                <div class="col-md-10">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nama, NIK, atau barcode..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>

                <div class="col-md-2">

                    <button class="btn btn-success w-100">
                        🔍 Cari
                    </button>

                </div>

            </form>


            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Karyawan</th>

                            <th>Shift</th>

                            <th>Produksi Hari Ini</th>

                            <th>Produksi Bulan Ini</th>

                            <th>Total Produksi</th>

                            <th>Upah Bulan Ini</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($data) > 0): ?>

                        <?php foreach ($data as $i => $row): ?>

                            <tr>

                                <td>
                                    <?= $i + 1 ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars($row['nama_lengkap']) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars($row['barcode_uid']) ?>
                                    </small>

                                </td>

                                <td>
                                    <?= htmlspecialchars($row['grup_shift'] ?? '-') ?>
                                </td>

                                <td class="fw-bold">
                                    <?= kg($row['produksi_hari_ini']) ?>
                                </td>

                                <td>
                                    <?= kg($row['produksi_bulan_ini']) ?>
                                </td>

                                <td>
                                    <?= kg($row['produksi_total']) ?>
                                </td>

                                <td class="text-success fw-bold">
                                    <?= rupiah($row['upah_bulan_ini']) ?>
                                </td>

                                <td>

                                    <?php if ($row['is_active'] && $row['status_aktif']): ?>

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
                                colspan="8"
                                class="text-center text-muted py-5"
                            >
                                Data produksi belum tersedia.

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