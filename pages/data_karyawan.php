<?php
session_start();

if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'hr') {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| AKSI
|--------------------------------------------------------------------------
*/

$pesan = "";
$error = "";

/*
|--------------------------------------------------------------------------
| TAMBAH KARYAWAN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {

    $barcode_uid      = trim($_POST['barcode_uid']);
    $nama_lengkap     = trim($_POST['nama_lengkap']);
    $nik              = trim($_POST['nik']);
    $jenis_kelamin    = $_POST['jenis_kelamin'] ?? null;
    $alamat           = trim($_POST['alamat']);
    $no_hp            = trim($_POST['no_hp']);
    $tanggal_bergabung = !empty($_POST['tanggal_bergabung'])
        ? $_POST['tanggal_bergabung']
        : null;
    $tempat_lahir     = trim($_POST['tempat_lahir']);
    $tanggal_lahir    = !empty($_POST['tanggal_lahir'])
        ? $_POST['tanggal_lahir']
        : null;
    $grup_shift       = trim($_POST['grup_shift']);

    try {

        $sql = "
            INSERT INTO karyawan (
                barcode_uid,
                nama_lengkap,
                grup_shift,
                is_active,
                nik,
                jenis_kelamin,
                alamat,
                no_hp,
                tanggal_bergabung,
                status_aktif,
                tempat_lahir,
                tanggal_lahir
            )
            VALUES (
                :barcode_uid,
                :nama_lengkap,
                :grup_shift,
                true,
                :nik,
                :jenis_kelamin,
                :alamat,
                :no_hp,
                :tanggal_bergabung,
                true,
                :tempat_lahir,
                :tanggal_lahir
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':barcode_uid'       => $barcode_uid,
            ':nama_lengkap'      => $nama_lengkap,
            ':grup_shift'        => $grup_shift ?: null,
            ':nik'               => $nik ?: null,
            ':jenis_kelamin'     => $jenis_kelamin ?: null,
            ':alamat'            => $alamat ?: null,
            ':no_hp'             => $no_hp ?: null,
            ':tanggal_bergabung' => $tanggal_bergabung,
            ':tempat_lahir'      => $tempat_lahir ?: null,
            ':tanggal_lahir'     => $tanggal_lahir
        ]);

        header("Location: data_karyawan.php?status=tambah");
        exit;

    } catch (PDOException $e) {

        $error = "Gagal menambahkan karyawan: " . $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| EDIT KARYAWAN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {

    $id                = $_POST['id'];
    $barcode_uid       = trim($_POST['barcode_uid']);
    $nama_lengkap      = trim($_POST['nama_lengkap']);
    $nik               = trim($_POST['nik']);
    $jenis_kelamin     = $_POST['jenis_kelamin'] ?? null;
    $alamat            = trim($_POST['alamat']);
    $no_hp             = trim($_POST['no_hp']);
    $tanggal_bergabung = !empty($_POST['tanggal_bergabung'])
        ? $_POST['tanggal_bergabung']
        : null;
    $tempat_lahir      = trim($_POST['tempat_lahir']);
    $tanggal_lahir     = !empty($_POST['tanggal_lahir'])
        ? $_POST['tanggal_lahir']
        : null;
    $grup_shift        = trim($_POST['grup_shift']);

    try {

        $sql = "
            UPDATE karyawan
            SET
                barcode_uid = :barcode_uid,
                nama_lengkap = :nama_lengkap,
                grup_shift = :grup_shift,
                nik = :nik,
                jenis_kelamin = :jenis_kelamin,
                alamat = :alamat,
                no_hp = :no_hp,
                tanggal_bergabung = :tanggal_bergabung,
                tempat_lahir = :tempat_lahir,
                tanggal_lahir = :tanggal_lahir
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':id'                => $id,
            ':barcode_uid'       => $barcode_uid,
            ':nama_lengkap'      => $nama_lengkap,
            ':grup_shift'        => $grup_shift ?: null,
            ':nik'               => $nik ?: null,
            ':jenis_kelamin'     => $jenis_kelamin ?: null,
            ':alamat'            => $alamat ?: null,
            ':no_hp'             => $no_hp ?: null,
            ':tanggal_bergabung' => $tanggal_bergabung,
            ':tempat_lahir'      => $tempat_lahir ?: null,
            ':tanggal_lahir'     => $tanggal_lahir
        ]);

        header("Location: data_karyawan.php?status=edit");
        exit;

    } catch (PDOException $e) {

        $error = "Gagal mengubah data: " . $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| AKTIF / NONAKTIF
|--------------------------------------------------------------------------
*/

if (isset($_GET['toggle'])) {

    $id = $_GET['toggle'];

    try {

        $stmt = $pdo->prepare("
            UPDATE karyawan
            SET
                is_active = NOT is_active,
                status_aktif = NOT status_aktif
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        header("Location: data_karyawan.php?status=toggle");
        exit;

    } catch (PDOException $e) {

        $error = "Gagal mengubah status karyawan.";
    }
}


/*
|--------------------------------------------------------------------------
| PESAN
|--------------------------------------------------------------------------
*/

if (isset($_GET['status'])) {

    if ($_GET['status'] === 'tambah') {
        $pesan = "Karyawan berhasil ditambahkan.";
    }

    if ($_GET['status'] === 'edit') {
        $pesan = "Data karyawan berhasil diperbarui.";
    }

    if ($_GET['status'] === 'toggle') {
        $pesan = "Status karyawan berhasil diperbarui.";
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM karyawan
        WHERE
            nama_lengkap ILIKE :search
            OR nik ILIKE :search
            OR barcode_uid ILIKE :search
        ORDER BY nama_lengkap ASC
    ");

    $stmt->execute([
        ':search' => '%' . $search . '%'
    ]);

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM karyawan
        ORDER BY nama_lengkap ASC
    ");
}

$karyawan = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Karyawan - Pabrik Udang</title>

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

        <a class="nav-link active" href="data_karyawan.php">
            👥 Data Karyawan
        </a>

        <a class="nav-link" href="qr_karyawan.php">
            📱 QR Code Karyawan
        </a>

        <a class="nav-link" href="monitoring_produksi.php">
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

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h4 class="fw-bold mb-1">
                    Data Karyawan
                </h4>

                <small class="text-muted">
                    Kelola data karyawan pabrik
                </small>

            </div>

            <button
                class="btn btn-success"
                data-bs-toggle="modal"
                data-bs-target="#modalTambah"
            >
                + Tambah Karyawan
            </button>

        </div>


        <?php if ($pesan): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($pesan) ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


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
                            Cari
                        </button>

                    </div>

                </form>


                <div class="table-responsive">

                    <table class="table table-hover">

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>Karyawan</th>

                                <th>NIK</th>

                                <th>Shift</th>

                                <th>Jenis Kelamin</th>

                                <th>Status</th>

                                <th>Aksi</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (count($karyawan) > 0): ?>

                            <?php foreach ($karyawan as $i => $k): ?>

                                <tr>

                                    <td>
                                        <?= $i + 1 ?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars($k['nama_lengkap']) ?>
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            QR: <?= htmlspecialchars($k['barcode_uid']) ?>
                                        </small>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($k['nik'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($k['grup_shift'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($k['jenis_kelamin'] ?? '-') ?>
                                    </td>

                                    <td>

                                        <?php if ($k['is_active'] && $k['status_aktif']): ?>

                                            <span class="badge badge-aktif">
                                                Aktif
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-nonaktif">
                                                Nonaktif
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <a
                                            href="qr_karyawan.php?id=<?= urlencode($k['id']) ?>"
                                            class="btn btn-sm btn-outline-success"
                                        >
                                            QR
                                        </a>

                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEdit<?= htmlspecialchars($k['id']) ?>"
                                        >
                                            Edit
                                        </button>

                                        <a
                                            href="data_karyawan.php?toggle=<?= urlencode($k['id']) ?>"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="return confirm('Ubah status karyawan ini?')"
                                        >
                                            <?= ($k['is_active'] && $k['status_aktif'])
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan' ?>
                                        </a>

                                    </td>

                                </tr>


                                <!-- MODAL EDIT -->

                                <div
                                    class="modal fade"
                                    id="modalEdit<?= htmlspecialchars($k['id']) ?>"
                                    tabindex="-1"
                                >

                                    <div class="modal-dialog modal-lg">

                                        <div class="modal-content">

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="aksi"
                                                    value="edit"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars($k['id']) ?>"
                                                >

                                                <div class="modal-header">

                                                    <h5 class="modal-title">
                                                        Edit Data Karyawan
                                                    </h5>

                                                    <button
                                                        type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"
                                                    ></button>

                                                </div>

                                                <div class="modal-body">

                                                    <div class="row g-3">

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Barcode UID
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="barcode_uid"
                                                                class="form-control"
                                                                required
                                                                value="<?= htmlspecialchars($k['barcode_uid']) ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Nama Lengkap
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="nama_lengkap"
                                                                class="form-control"
                                                                required
                                                                value="<?= htmlspecialchars($k['nama_lengkap']) ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                NIK
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="nik"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($k['nik'] ?? '') ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Grup Shift
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="grup_shift"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($k['grup_shift'] ?? '') ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Jenis Kelamin
                                                            </label>

                                                            <select
                                                                name="jenis_kelamin"
                                                                class="form-select"
                                                            >

                                                                <option value="">
                                                                    -- Pilih --
                                                                </option>

                                                                <option
                                                                    value="Laki-laki"
                                                                    <?= ($k['jenis_kelamin'] === 'Laki-laki') ? 'selected' : '' ?>
                                                                >
                                                                    Laki-laki
                                                                </option>

                                                                <option
                                                                    value="Perempuan"
                                                                    <?= ($k['jenis_kelamin'] === 'Perempuan') ? 'selected' : '' ?>
                                                                >
                                                                    Perempuan
                                                                </option>

                                                            </select>

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                No. HP
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="no_hp"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($k['no_hp'] ?? '') ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tempat Lahir
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="tempat_lahir"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($k['tempat_lahir'] ?? '') ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tanggal Lahir
                                                            </label>

                                                            <input
                                                                type="date"
                                                                name="tanggal_lahir"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($k['tanggal_lahir'] ?? '') ?>"
                                                            >

                                                        </div>

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tanggal Bergabung
                                                            </label>

                                                            <input
                                                                type="date"
                                                                name="tanggal_bergabung"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($k['tanggal_bergabung'] ?? '') ?>"
                                                            >

                                                        </div>

                                                        <div class="col-12">

                                                            <label class="form-label">
                                                                Alamat
                                                            </label>

                                                            <textarea
                                                                name="alamat"
                                                                class="form-control"
                                                                rows="3"
                                                            ><?= htmlspecialchars($k['alamat'] ?? '') ?></textarea>

                                                        </div>

                                                    </div>

                                                </div>

                                                <div class="modal-footer">

                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary"
                                                        data-bs-dismiss="modal"
                                                    >
                                                        Batal
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-success"
                                                    >
                                                        Simpan Perubahan
                                                    </button>

                                                </div>

                                            </form>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center py-5 text-muted"
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

    </div>

</div>

</div>

</div>


<!-- MODAL TAMBAH -->

<div class="modal fade" id="modalTambah" tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form method="POST">

                <input
                    type="hidden"
                    name="aksi"
                    value="tambah"
                >

                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Karyawan
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Barcode UID
                            </label>

                            <input
                                type="text"
                                name="barcode_uid"
                                class="form-control"
                                placeholder="Contoh: KR001"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Nama Lengkap
                            </label>

                            <input
                                type="text"
                                name="nama_lengkap"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                NIK
                            </label>

                            <input
                                type="text"
                                name="nik"
                                class="form-control"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Grup Shift
                            </label>

                            <input
                                type="text"
                                name="grup_shift"
                                class="form-control"
                                placeholder="Contoh: Shift A"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Jenis Kelamin
                            </label>

                            <select
                                name="jenis_kelamin"
                                class="form-select"
                            >

                                <option value="">
                                    -- Pilih --
                                </option>

                                <option value="Laki-laki">
                                    Laki-laki
                                </option>

                                <option value="Perempuan">
                                    Perempuan
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                No. HP
                            </label>

                            <input
                                type="text"
                                name="no_hp"
                                class="form-control"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Tempat Lahir
                            </label>

                            <input
                                type="text"
                                name="tempat_lahir"
                                class="form-control"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Tanggal Lahir
                            </label>

                            <input
                                type="date"
                                name="tanggal_lahir"
                                class="form-control"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Tanggal Bergabung
                            </label>

                            <input
                                type="date"
                                name="tanggal_bergabung"
                                class="form-control"
                            >

                        </div>

                        <div class="col-12">

                            <label class="form-label">
                                Alamat
                            </label>

                            <textarea
                                name="alamat"
                                class="form-control"
                                rows="3"
                            ></textarea>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        Simpan Karyawan
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script src="../assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>