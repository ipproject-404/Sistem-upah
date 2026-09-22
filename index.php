<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $divisi = $_POST['divisi'];
    
    $_SESSION['divisi'] = $divisi;

    if ($divisi == 'produksi') {
        header("Location: pages/dashboard.php"); 
        exit;
    } elseif ($divisi == 'hr') {
        header("Location: pages/dashboard_hr.php"); 
        exit;
    } elseif ($divisi == 'akunting') {
        header("Location: pages/dashboard_akunting.php"); 
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Upah Pabrik Udang</title>
    
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa; 
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            width: 100%;
            max-width: 400px;
            border-top: 5px solid #198754; 
            border-radius: 10px;
        }
        .btn-hijau {
            background-color: #198754;
            color: #ffffff;
        }
        .btn-hijau:hover {
            background-color: #146c43;
            color: #ffffff;
        }
        .text-hijau {
            color: #198754 !important;
        }
    </style>
</head>
<body>

<div class="card shadow card-login">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h1 class="text-hijau mb-3">🍤</h1>
            <h4 class="fw-bold text-hijau">Pabrik Udang</h4>
            <p class="text-muted small">Sistem Manajemen Upah & Lembur</p>
        </div>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label fw-bold">Pilih Divisi (Demo Mode)</label>
                <select class="form-select form-select-lg" name="divisi" required>
                    <option value="" selected disabled>-- Pilih Divisi Anda --</option>
                    <option value="produksi">Admin Produksi (Timbangan)</option>
                    <option value="hr">HR (Absensi & Karyawan)</option>
                    <option value="akunting">Akunting (Rekap Gaji)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-hijau btn-lg w-100 fw-bold">Masuk Dashboard</button>
        </form>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>