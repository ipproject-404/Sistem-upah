<?php
session_start();

require_once '../config/database.php';

// Cek akses
if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'produksi') {
    header("Location: ../index.php");
    exit;
}

// Ambil daftar tarif aktif dari database
try {
    $stmtTarif = $pdo->query("
        SELECT
            id,
            nama_pekerjaan,
            harga_per_satuan
        FROM master_tarif
        WHERE status_aktif = true
        ORDER BY nama_pekerjaan ASC
    ");

    $daftarTarif = $stmtTarif->fetchAll();

} catch (PDOException $e) {
    $daftarTarif = [];
    $errorTarif = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Timbangan Produksi - Pabrik Udang</title>

    <!-- Bootstrap -->
    <link
        href="../assets/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- QR Scanner -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>

        body {
            background-color: #f8f9fa;
        }

        .scanner-card {
            border-radius: 12px;
            border: none;
        }

        .scanner-header {
            background-color: #198754;
            color: white;
            border-radius: 12px 12px 0 0;
        }

        #reader {
            width: 100%;
            max-width: 450px;
            margin: auto;
        }

        .employee-card {
            border-left: 5px solid #198754;
        }

        .employee-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
        }

        .label-info {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .value-info {
            font-weight: 600;
            color: #212529;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
        }

        .btn-hijau {
            background-color: #198754;
            color: white;
            border: none;
        }

        .btn-hijau:hover {
            background-color: #146c43;
            color: white;
        }

        .status-scan {
            font-size: 14px;
        }

        .scan-id {
            font-size: 12px;
            color: #6c757d;
        }

        .weight-input {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .disabled-panel {
            opacity: 0.6;
            pointer-events: none;
        }

    </style>

</head>

<body>

<?php
// Jika project menggunakan header/navbar
if (file_exists('../includes/header.php')) {
    include '../includes/header.php';
}

if (file_exists('../includes/navbar.php')) {
    include '../includes/navbar.php';
}
?>


<div class="container-fluid py-4">

    <!-- Judul -->
    <div class="mb-4">

        <h3 class="fw-bold text-success">
            <i class="bi bi-qr-code-scan"></i>
            Timbangan Produksi
        </h3>

        <p class="text-muted mb-0">
            Scan QR karyawan kemudian masukkan berat hasil produksi.
        </p>

    </div>


    <div class="row g-4">

        <!-- ================================= -->
        <!-- BAGIAN SCANNER -->
        <!-- ================================= -->

        <div class="col-lg-5">

            <div class="card shadow-sm scanner-card">

                <div class="card-header scanner-header">

                    <h5 class="mb-0">
                        <i class="bi bi-camera"></i>
                        Scan QR Karyawan
                    </h5>

                </div>

                <div class="card-body text-center">

                    <div
                        id="reader"
                        class="mb-3"
                    ></div>

                    <div
                        id="statusScan"
                        class="status-scan alert alert-info mb-0"
                    >

                        <i class="bi bi-info-circle"></i>

                        Silakan arahkan kamera ke QR Code karyawan.

                    </div>

                </div>

            </div>


            <!-- Informasi Scan -->

            <div class="card shadow-sm mt-3">

                <div class="card-body">

                    <h6 class="fw-bold">
                        <i class="bi bi-clock-history text-success"></i>
                        Informasi Scan
                    </h6>

                    <hr>

                    <div class="row">

                        <div class="col-6">

                            <div class="label-info">
                                ID Scan
                            </div>

                            <div
                                id="scanId"
                                class="scan-id"
                            >
                                -
                            </div>

                        </div>

                        <div class="col-6">

                            <div class="label-info">
                                Waktu Scan
                            </div>

                            <div
                                id="waktuScan"
                                class="scan-id"
                            >
                                -
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================= -->
        <!-- BAGIAN DATA KARYAWAN & TIMBANGAN -->
        <!-- ================================= -->

        <div class="col-lg-7">

            <div
                id="panelTimbangan"
                class="card shadow-sm scanner-card disabled-panel"
            >

                <div class="card-header bg-white">

                    <h5 class="mb-0 fw-bold text-success">

                        <i class="bi bi-person-badge"></i>

                        Data Karyawan & Timbangan

                    </h5>

                </div>


                <div class="card-body">

                    <!-- ========================= -->
                    <!-- DATA KARYAWAN -->
                    <!-- ========================= -->

                    <div class="employee-card mb-4">

                        <h6 class="fw-bold mb-3">
                            Informasi Karyawan
                        </h6>


                        <div class="row g-3">

                            <!-- NIK -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        NIK
                                    </div>

                                    <div
                                        id="nik"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- Nama -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        Nama Lengkap
                                    </div>

                                    <div
                                        id="namaLengkap"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- Tempat Lahir -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        Tempat Lahir
                                    </div>

                                    <div
                                        id="tempatLahir"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- Tanggal Lahir -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        Tanggal Lahir
                                    </div>

                                    <div
                                        id="tanggalLahir"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- Jenis Kelamin -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        Jenis Kelamin
                                    </div>

                                    <div
                                        id="jenisKelamin"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- No HP -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        No. HP
                                    </div>

                                    <div
                                        id="noHp"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- Grup Shift -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        Grup Shift
                                    </div>

                                    <div
                                        id="grupShift"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>


                            <!-- Alamat -->

                            <div class="col-md-6">

                                <div class="employee-info">

                                    <div class="label-info">
                                        Alamat
                                    </div>

                                    <div
                                        id="alamat"
                                        class="value-info"
                                    >
                                        -
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ========================= -->
                    <!-- FORM TIMBANGAN -->
                    <!-- ========================= -->

                    <form
                        id="formTimbangan"
                        onsubmit="simpanTimbangan(event)"
                    >

                        <!-- Hidden ID Karyawan -->

                        <input
                            type="hidden"
                            id="karyawanId"
                            name="karyawan_id"
                        >


                        <!-- Hidden waktu scan -->

                        <input
                            type="hidden"
                            id="waktuScanValid"
                            name="waktu_scan"
                        >


                        <div class="row g-3">

                            <!-- Tarif -->

                            <div class="col-md-6">

                                <label
                                    for="tarifId"
                                    class="form-label fw-bold"
                                >

                                    <i class="bi bi-cash-stack text-success"></i>

                                    Jenis Pekerjaan / Tarif

                                </label>


                                <select
                                    class="form-select"
                                    id="tarifId"
                                    name="tarif_id"
                                    required
                                >

                                    <option
                                        value=""
                                        selected
                                        disabled
                                    >
                                        -- Pilih Tarif --
                                    </option>


                                    <?php if (!empty($daftarTarif)): ?>

                                        <?php foreach ($daftarTarif as $tarif): ?>

                                            <option
                                                value="<?= htmlspecialchars($tarif['id']) ?>"
                                            >

                                                <?= htmlspecialchars($tarif['nama_pekerjaan']) ?>

                                                -
                                                Rp<?= number_format((float)$tarif['harga_per_satuan'], 0, ',', '.') ?>/kg

                                            </option>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <option
                                            value=""
                                            disabled
                                        >
                                            Belum ada tarif aktif
                                        </option>

                                    <?php endif; ?>

                                </select>

                            </div>


                            <!-- Berat -->

                            <div class="col-md-6">

                                <label
                                    for="beratKg"
                                    class="form-label fw-bold"
                                >

                                    <i class="bi bi-speedometer2 text-success"></i>

                                    Berat Timbangan

                                </label>


                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control weight-input"
                                        id="beratKg"
                                        name="berat_kg"
                                        placeholder="0"
                                        min="0.01"
                                        step="0.01"
                                        required
                                    >

                                    <span class="input-group-text fw-bold">
                                        Kg
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- Tombol -->

                        <div class="d-grid mt-4">

                            <button
                                type="submit"
                                id="btnSimpan"
                                class="btn btn-hijau btn-lg fw-bold"
                            >

                                <i class="bi bi-save"></i>

                                Simpan Timbangan

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script src="../assets/js/bootstrap.bundle.min.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| VARIABEL GLOBAL
|--------------------------------------------------------------------------
*/

let html5QrcodeScanner;

let scannerAktif = true;


/*
|--------------------------------------------------------------------------
| SUARA "TIT" KETIKA QR BERHASIL DI-SCAN
|--------------------------------------------------------------------------
*/

function suaraScanBerhasil() {

    try {

        const AudioContext =
            window.AudioContext ||
            window.webkitAudioContext;

        if (!AudioContext) {
            return;
        }

        const audioContext = new AudioContext();

        const oscillator =
            audioContext.createOscillator();

        const gainNode =
            audioContext.createGain();


        // Jenis suara
        oscillator.type = "sine";


        // Frekuensi suara
        oscillator.frequency.setValueAtTime(
            1000,
            audioContext.currentTime
        );


        // Volume suara
        gainNode.gain.setValueAtTime(
            0.2,
            audioContext.currentTime
        );


        // Hubungkan audio
        oscillator.connect(gainNode);

        gainNode.connect(
            audioContext.destination
        );


        // Mulai suara
        oscillator.start();


        // Berhenti setelah 0.15 detik
        oscillator.stop(
            audioContext.currentTime + 0.15
        );

    } catch (error) {

        console.log(
            "Audio tidak dapat diputar:",
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| UPDATE STATUS SCAN
|--------------------------------------------------------------------------
*/

function updateStatusScan(
    message,
    type = "info"
) {

    const status =
        document.getElementById("statusScan");


    status.className =
        "status-scan alert alert-" + type + " mb-0";


    status.innerHTML =
        message;

}


/*
|--------------------------------------------------------------------------
| RESET DATA KARYAWAN
|--------------------------------------------------------------------------
*/

function resetDataKaryawan() {

    document.getElementById("karyawanId").value = "";

    document.getElementById("waktuScanValid").value = "";

    document.getElementById("nik").textContent = "-";

    document.getElementById("namaLengkap").textContent = "-";

    document.getElementById("tempatLahir").textContent = "-";

    document.getElementById("tanggalLahir").textContent = "-";

    document.getElementById("jenisKelamin").textContent = "-";

    document.getElementById("noHp").textContent = "-";

    document.getElementById("grupShift").textContent = "-";

    document.getElementById("alamat").textContent = "-";

    document.getElementById("scanId").textContent = "-";

    document.getElementById("waktuScan").textContent = "-";

    document.getElementById("tarifId").value = "";

    document.getElementById("beratKg").value = "";

}


/*
|--------------------------------------------------------------------------
| FORMAT TANGGAL
|--------------------------------------------------------------------------
*/

function formatTanggal(
    tanggal
) {

    if (!tanggal) {
        return "-";
    }

    const date =
        new Date(tanggal);


    if (isNaN(date.getTime())) {
        return tanggal;
    }


    return date.toLocaleDateString(
        "id-ID",
        {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }
    );

}


/*
|--------------------------------------------------------------------------
| FORMAT WAKTU
|--------------------------------------------------------------------------
*/

function formatWaktu(
    tanggal
) {

    if (!tanggal) {
        return "-";
    }

    const date =
        new Date(tanggal);


    if (isNaN(date.getTime())) {
        return tanggal;
    }


    return date.toLocaleString(
        "id-ID"
    );

}


/*
|--------------------------------------------------------------------------
| QR BERHASIL DI-SCAN
|--------------------------------------------------------------------------
*/

function onScanSuccess(
    decodedText,
    decodedResult
) {

    // Cegah scan berulang
    if (!scannerAktif) {
        return;
    }

    scannerAktif = false;


    /*
    |--------------------------------------------------------------------------
    | SUARA "TIT"
    |--------------------------------------------------------------------------
    */

    suaraScanBerhasil();


    /*
    |--------------------------------------------------------------------------
    | PAUSE SCANNER
    |--------------------------------------------------------------------------
    */

    try {

        html5QrcodeScanner.pause(
            true
        );

    } catch (error) {

        console.log(
            "Scanner tidak dapat di-pause:",
            error
        );

    }


    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN STATUS
    |--------------------------------------------------------------------------
    */

    updateStatusScan(
        '<i class="bi bi-hourglass-split"></i> Membaca data karyawan...',
        "warning"
    );


    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN ID QR
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        "scanId"
    ).textContent = decodedText;


    /*
    |--------------------------------------------------------------------------
    | WAKTU SCAN
    |--------------------------------------------------------------------------
    */

    const waktuSekarang =
        new Date();


    document.getElementById(
        "waktuScan"
    ).textContent =
        formatWaktu(
            waktuSekarang
        );


    document.getElementById(
        "waktuScanValid"
    ).value =
        waktuSekarang.toISOString();


    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA KARYAWAN
    |--------------------------------------------------------------------------
    */

    fetch(
        "../actions/get_karyawan.php?uid=" +
        encodeURIComponent(decodedText)
    )

    .then(
        response => {

            if (!response.ok) {

                throw new Error(
                    "Server mengembalikan error."
                );

            }

            return response.json();

        }
    )

    .then(
        data => {

            /*
            |--------------------------------------------------------------------------
            | JIKA KARYAWAN DITEMUKAN
            |--------------------------------------------------------------------------
            */

            if (
                data.status === "success"
            ) {

                const karyawan =
                    data.data;


                /*
                |--------------------------------------------------------------------------
                | ISI DATA KARYAWAN
                |--------------------------------------------------------------------------
                */

                document.getElementById(
                    "karyawanId"
                ).value =
                    karyawan.id;


                document.getElementById(
                    "nik"
                ).textContent =
                    karyawan.nik || "-";


                document.getElementById(
                    "namaLengkap"
                ).textContent =
                    karyawan.nama_lengkap || "-";


                document.getElementById(
                    "tempatLahir"
                ).textContent =
                    karyawan.tempat_lahir || "-";


                document.getElementById(
                    "tanggalLahir"
                ).textContent =
                    formatTanggal(
                        karyawan.tanggal_lahir
                    );


                document.getElementById(
                    "jenisKelamin"
                ).textContent =
                    karyawan.jenis_kelamin || "-";


                document.getElementById(
                    "noHp"
                ).textContent =
                    karyawan.no_hp || "-";


                document.getElementById(
                    "grupShift"
                ).textContent =
                    karyawan.grup_shift || "-";


                document.getElementById(
                    "alamat"
                ).textContent =
                    karyawan.alamat || "-";


                /*
                |--------------------------------------------------------------------------
                | AKTIFKAN PANEL TIMBANGAN
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById(
                        "panelTimbangan"
                    )
                    .classList
                    .remove(
                        "disabled-panel"
                    );


                /*
                |--------------------------------------------------------------------------
                | STATUS BERHASIL
                |--------------------------------------------------------------------------
                */

                updateStatusScan(
                    '<i class="bi bi-check-circle-fill"></i> ' +
                    'QR berhasil dibaca. Data karyawan ditemukan.',
                    "success"
                );


                /*
                |--------------------------------------------------------------------------
                | FOKUS BERAT
                |--------------------------------------------------------------------------
                */

                setTimeout(
                    function () {

                        document
                            .getElementById(
                                "beratKg"
                            )
                            .focus();

                    },
                    300
                );

            }

            /*
            |--------------------------------------------------------------------------
            | KARYAWAN TIDAK DITEMUKAN
            |--------------------------------------------------------------------------
            */

            else {

                resetDataKaryawan();


                updateStatusScan(
                    '<i class="bi bi-exclamation-triangle-fill"></i> ' +
                    (data.message ||
                        "Data karyawan tidak ditemukan."),
                    "danger"
                );


                /*
                |--------------------------------------------------------------------------
                | AKTIFKAN KEMBALI SCANNER
                |--------------------------------------------------------------------------
                */

                setTimeout(
                    function () {

                        scannerAktif = true;

                        try {

                            html5QrcodeScanner.resume();

                        } catch (error) {

                            console.log(
                                error
                            );

                        }

                    },
                    1500
                );

            }

        }
    )

    .catch(
        error => {

            console.error(
                error
            );


            updateStatusScan(
                '<i class="bi bi-x-circle-fill"></i> ' +
                'Gagal mengambil data karyawan.',
                "danger"
            );


            /*
            |--------------------------------------------------------------------------
            | AKTIFKAN KEMBALI SCANNER
            |--------------------------------------------------------------------------
            */

            setTimeout(
                function () {

                    scannerAktif = true;

                    try {

                        html5QrcodeScanner.resume();

                    } catch (error) {

                        console.log(
                            error
                        );

                    }

                },
                1500
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| ERROR SCAN
|--------------------------------------------------------------------------
*/

function onScanFailure(
    error
) {

    // Tidak perlu menampilkan error
    // karena akan muncul terus ketika
    // kamera belum menemukan QR.

}


/*
|--------------------------------------------------------------------------
| SIMPAN TIMBANGAN
|--------------------------------------------------------------------------
*/

function simpanTimbangan(
    event
) {

    event.preventDefault();


    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA FORM
    |--------------------------------------------------------------------------
    */

    const karyawanId =
        document.getElementById(
            "karyawanId"
        ).value;


    const tarifId =
        document.getElementById(
            "tarifId"
        ).value;


    const beratKg =
        document.getElementById(
            "beratKg"
        ).value;


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (!karyawanId) {

        alert(
            "Silakan scan QR karyawan terlebih dahulu."
        );

        return;

    }


    if (!tarifId) {

        alert(
            "Silakan pilih jenis pekerjaan / tarif."
        );

        return;

    }


    if (
        !beratKg ||
        parseFloat(beratKg) <= 0
    ) {

        alert(
            "Berat timbangan harus lebih dari 0 Kg."
        );

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | KONFIRMASI
    |--------------------------------------------------------------------------
    */

    const namaKaryawan =
        document.getElementById(
            "namaLengkap"
        ).textContent;


    const konfirmasi =
        confirm(
            "Simpan hasil timbangan?\n\n" +
            "Karyawan: " +
            namaKaryawan +
            "\n" +
            "Berat: " +
            beratKg +
            " Kg"
        );


    if (!konfirmasi) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | DISABLE TOMBOL
    |--------------------------------------------------------------------------
    */

    const btnSimpan =
        document.getElementById(
            "btnSimpan"
        );


    const teksAwal =
        btnSimpan.innerHTML;


    btnSimpan.disabled = true;


    btnSimpan.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>' +
        'Menyimpan...';


    /*
    |--------------------------------------------------------------------------
    | FORM DATA
    |--------------------------------------------------------------------------
    */

    const formData =
        new FormData();


    formData.append(
        "karyawan_id",
        karyawanId
    );


    formData.append(
        "tarif_id",
        tarifId
    );


    formData.append(
        "berat_kg",
        beratKg
    );


    /*
    |--------------------------------------------------------------------------
    | KIRIM KE SERVER
    |--------------------------------------------------------------------------
    */

    fetch(
        "../actions/simpan_timbangan.php",
        {
            method: "POST",
            body: formData
        }
    )

    .then(
        response => {

            if (!response.ok) {

                throw new Error(
                    "Server mengembalikan error."
                );

            }

            return response.json();

        }
    )

    .then(
        data => {

            if (
                data.status === "success"
            ) {

                /*
                |--------------------------------------------------------------------------
                | TRANSAKSI BERHASIL
                |--------------------------------------------------------------------------
                */

                alert(
                    "Transaksi berhasil disimpan!\n\n" +

                    "Karyawan: " +
                    data.data.karyawan +
                    "\n" +

                    "Pekerjaan: " +
                    data.data.pekerjaan +
                    "\n" +

                    "Berat: " +
                    data.data.berat_kg +
                    " Kg\n" +

                    "Tarif: Rp" +
                    Number(
                        data.data.harga_per_kg
                    ).toLocaleString(
                        "id-ID"
                    ) +
                    "/Kg\n" +

                    "Total Upah: Rp" +
                    Number(
                        data.data.total_upah
                    ).toLocaleString(
                        "id-ID"
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | RESET FORM
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById(
                        "formTimbangan"
                    )
                    .reset();


                resetDataKaryawan();


                /*
                |--------------------------------------------------------------------------
                | KUNCI PANEL
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById(
                        "panelTimbangan"
                    )
                    .classList
                    .add(
                        "disabled-panel"
                    );


                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                updateStatusScan(
                    '<i class="bi bi-check-circle-fill"></i> ' +
                    'Transaksi berhasil disimpan. Silakan scan QR berikutnya.',
                    "success"
                );


                /*
                |--------------------------------------------------------------------------
                | AKTIFKAN SCANNER
                |--------------------------------------------------------------------------
                */

                setTimeout(
                    function () {

                        scannerAktif = true;

                        try {

                            html5QrcodeScanner.resume();

                        } catch (error) {

                            console.log(
                                error
                            );

                        }

                    },
                    1000
                );

            }

            else {

                alert(
                    data.message ||
                    "Transaksi gagal disimpan."
                );

            }

        }
    )

    .catch(
        error => {

            console.error(
                error
            );


            alert(
                "Terjadi kesalahan saat menyimpan transaksi."
            );

        }
    )

    .finally(
        function () {

            /*
            |--------------------------------------------------------------------------
            | KEMBALIKAN TOMBOL
            |--------------------------------------------------------------------------
            */

            btnSimpan.disabled = false;

            btnSimpan.innerHTML =
                teksAwal;

        }
    );

}


/*
|--------------------------------------------------------------------------
| INISIALISASI QR SCANNER
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        html5QrcodeScanner =
            new Html5QrcodeScanner(
                "reader",
                {
                    fps: 10,

                    qrbox: {
                        width: 250,
                        height: 250
                    },

                    rememberLastUsedCamera: true,

                    supportedScanTypes: [
                        Html5QrcodeScanType.SCAN_TYPE_CAMERA
                    ]
                },
                false
            );


        html5QrcodeScanner.render(
            onScanSuccess,
            onScanFailure
        );

    }
);

</script>

</body>

</html>