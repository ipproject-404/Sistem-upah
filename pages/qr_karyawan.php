<?php
session_start();

if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'hr') {
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";

$id = $_GET['id'] ?? null;

if ($id) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM karyawan
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $karyawan = $stmt->fetch();

    if (!$karyawan) {
        die("Data karyawan tidak ditemukan.");
    }

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM karyawan
        ORDER BY nama_lengkap ASC
    ");

    $karyawan_list = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>QR Karyawan - Pabrik Udang</title>

    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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

        .qr-card {
            width: 320px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
        }

        .qr-box {
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        @media print {

            body * {
                visibility: hidden;
            }

            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }

        }

    </style>

</head>

<body>

<div class="container-fluid">

<div class="row">

<!-- SIDEBAR -->

<div class="col-md-3 col-lg-2 px-0 sidebar no-print">

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

        <a class="nav-link active" href="qr_karyawan.php">
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

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">

        <div>

            <h4 class="fw-bold">
                QR Code Karyawan
            </h4>

            <small class="text-muted">
                QR Code berdasarkan Barcode UID karyawan
            </small>

        </div>

        <?php if ($id): ?>

            <a
                href="qr_karyawan.php"
                class="btn btn-outline-success"
            >
                ← Semua Karyawan
            </a>

        <?php endif; ?>

    </div>


    <?php if ($id): ?>

        <!-- DETAIL QR -->

        <div class="d-flex justify-content-center print-area">

            <div class="card qr-card">

                <div class="card-body text-center">

                    <h5 class="fw-bold text-success">
                        🍤 PABRIK UDANG
                    </h5>

                    <hr>

                    <div
                        id="qrcode"
                        class="qr-box"
                    ></div>

                    <h5 class="fw-bold mb-1">
                        <?= htmlspecialchars($karyawan['nama_lengkap']) ?>
                    </h5>

                    <div class="text-muted mb-2">
                        <?= htmlspecialchars($karyawan['barcode_uid']) ?>
                    </div>

                    <?php if ($karyawan['grup_shift']): ?>

                        <span class="badge bg-success">
                            <?= htmlspecialchars($karyawan['grup_shift']) ?>
                        </span>

                    <?php endif; ?>

                    <div class="mt-3 no-print">

                        <button
                            onclick="window.print()"
                            class="btn btn-success w-100"
                        >
                            🖨️ Cetak QR
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <script>

            new QRCode(
                document.getElementById("qrcode"),
                {
                    text: <?= json_encode($karyawan['barcode_uid']) ?>,
                    width: 200,
                    height: 200
                }
            );

        </script>

    <?php else: ?>

        <!-- LIST QR -->

        <div class="row g-4">

            <?php foreach ($karyawan_list as $k): ?>

                <div class="col-md-6 col-lg-4 col-xl-3">

                    <div class="card qr-card w-100">

                        <div class="card-body text-center">

                            <div
                                id="qr<?= htmlspecialchars($k['id']) ?>"
                                class="qr-box"
                            ></div>

                            <h6 class="fw-bold">
                                <?= htmlspecialchars($k['nama_lengkap']) ?>
                            </h6>

                            <small class="text-muted">
                                <?= htmlspecialchars($k['barcode_uid']) ?>
                            </small>

                            <div class="mt-3">

                                <a
                                    href="qr_karyawan.php?id=<?= urlencode($k['id']) ?>"
                                    class="btn btn-sm btn-success"
                                >
                                    Lihat / Cetak
                                </a>

                            </div>

                        </div>

                    </div>

                    <script>

                        new QRCode(
                            document.getElementById(
                                "qr<?= htmlspecialchars($k['id']) ?>"
                            ),
                            {
                                text: <?= json_encode($k['barcode_uid']) ?>,
                                width: 150,
                                height: 150
                            }
                        );

                    </script>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</div>

</div>

</div>


<script src="../assets/js/bootstrap.bundle.min.js"></script>

</body>

</html>