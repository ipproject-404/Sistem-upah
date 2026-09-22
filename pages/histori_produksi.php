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

$tanggal_mulai  = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir  = $_GET['tanggal_akhir'] ?? '';
$karyawan_id    = $_GET['karyawan_id'] ?? '';
$search         = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| DATA KARYAWAN UNTUK FILTER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, nama_lengkap, barcode_uid
    FROM karyawan
    ORDER BY nama_lengkap ASC
");

$daftar_karyawan = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| QUERY HISTORI
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        t.id,
        t.berat_kg,
        t.total_upah,
        t.created_at,

        k.nama_lengkap,
        k.barcode_uid,
        k.grup_shift,

        mt.nama_pekerjaan,
        mt.harga_per_satuan,
        mt.tipe_tarif

    FROM transaksi_timbang t

    LEFT JOIN karyawan k
        ON t.karyawan_id = k.id

    LEFT JOIN master_tarif mt
        ON t.tarif_id = mt.id

    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| FILTER TANGGAL MULAI
|--------------------------------------------------------------------------
*/

if ($tanggal_mulai !== '') {

    $sql .= "
        AND t.created_at >= :tanggal_mulai
    ";

    $params[':tanggal_mulai'] = $tanggal_mulai . ' 00:00:00';
}


/*
|--------------------------------------------------------------------------
| FILTER TANGGAL AKHIR
|--------------------------------------------------------------------------
*/

if ($tanggal_akhir !== '') {

    $sql .= "
        AND t.created_at <= :tanggal_akhir
    ";

    $params[':tanggal_akhir'] = $tanggal_akhir . ' 23:59:59';
}


/*
|--------------------------------------------------------------------------
| FILTER KARYAWAN
|--------------------------------------------------------------------------
*/

if ($karyawan_id !== '') {

    $sql .= "
        AND t.karyawan_id = :karyawan_id
    ";

    $params[':karyawan_id'] = $karyawan_id;
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            k.nama_lengkap ILIKE :search
            OR k.barcode_uid ILIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}


$sql .= "
    ORDER BY t.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$histori = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$total_kg = 0;
$total_upah = 0;

foreach ($histori as $row) {

    $total_kg += (float)$row['berat_kg'];

    $total_upah += (float)$row['total_upah'];
}


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

<title>Histori Produksi - Pabrik Udang</title>

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

.summary-card {
    border: none;
    border-radius: 12px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
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

        <a class="nav-link" href="monitoring_produksi.php">
            ⚖️ Monitoring Produksi
        </a>

        <a class="nav-link active" href="histori_produksi.php">
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
            Histori Produksi
        </h4>

        <small class="text-muted">
            Riwayat seluruh transaksi penimbangan
        </small>

    </div>


    <!-- FILTER -->

    <div class="card card-custom mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label">
                            Tanggal Mulai
                        </label>

                        <input
                            type="date"
                            name="tanggal_mulai"
                            class="form-control"
                            value="<?= htmlspecialchars($tanggal_mulai) ?>"
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Tanggal Akhir
                        </label>

                        <input
                            type="date"
                            name="tanggal_akhir"
                            class="form-control"
                            value="<?= htmlspecialchars($tanggal_akhir) ?>"
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Karyawan
                        </label>

                        <select
                            name="karyawan_id"
                            class="form-select"
                        >

                            <option value="">
                                Semua Karyawan
                            </option>

                            <?php foreach ($daftar_karyawan as $k): ?>

                                <option
                                    value="<?= htmlspecialchars($k['id']) ?>"
                                    <?= $karyawan_id === $k['id'] ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($k['nama_lengkap']) ?>
                                    -
                                    <?= htmlspecialchars($k['barcode_uid']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Cari
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Nama / Barcode"
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>


                    <div class="col-12">

                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            🔍 Terapkan Filter
                        </button>

                        <a
                            href="histori_produksi.php"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- SUMMARY -->

    <div class="row g-3 mb-4">

        <div class="col-md-6">

            <div class="card summary-card">

                <div class="card-body">

                    <small class="text-muted">
                        Total Berat
                    </small>

                    <h3 class="fw-bold text-success mb-0">
                        <?= kg($total_kg) ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card summary-card">

                <div class="card-body">

                    <small class="text-muted">
                        Total Upah
                    </small>

                    <h3 class="fw-bold text-success mb-0">
                        <?= rupiah($total_upah) ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>


    <!-- TABLE -->

    <div class="card card-custom">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Waktu</th>

                            <th>Karyawan</th>

                            <th>Shift</th>

                            <th>Pekerjaan</th>

                            <th>Berat</th>

                            <th>Tarif</th>

                            <th>Total Upah</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($histori) > 0): ?>

                        <?php foreach ($histori as $i => $row): ?>

                            <tr>

                                <td>
                                    <?= $i + 1 ?>
                                </td>

                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($row['created_at'])
                                    ) ?>

                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $row['nama_lengkap'] ?? 'Tidak diketahui'
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            $row['barcode_uid'] ?? '-'
                                        ) ?>
                                    </small>

                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['grup_shift'] ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['nama_pekerjaan'] ?? '-'
                                    ) ?>
                                </td>

                                <td class="fw-bold">
                                    <?= kg($row['berat_kg']) ?>
                                </td>

                                <td>

                                    <?= rupiah(
                                        $row['harga_per_satuan'] ?? 0
                                    ) ?>

                                </td>

                                <td class="fw-bold text-success">

                                    <?= rupiah(
                                        $row['total_upah']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-5"
                            >
                                Tidak ada histori produksi.

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