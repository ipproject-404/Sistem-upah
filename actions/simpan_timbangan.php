<?php

session_start();

header('Content-Type: application/json');

require_once '../config/database.php';


/*
|--------------------------------------------------------------------------
| CEK SESSION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['divisi']) ||
    $_SESSION['divisi'] !== 'produksi'
) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Akses tidak diizinkan.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'status' => 'error',
        'message' => 'Metode request tidak valid.'
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA
    |--------------------------------------------------------------------------
    */

    $karyawan_id = $_POST['karyawan_id'] ?? '';

    $tarif_id = $_POST['tarif_id'] ?? '';

    $berat_kg = $_POST['berat_kg'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDASI KARYAWAN
    |--------------------------------------------------------------------------
    */

    if (empty($karyawan_id)) {

        throw new Exception(
            'Karyawan belum dipilih.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI TARIF
    |--------------------------------------------------------------------------
    */

    if (empty($tarif_id)) {

        throw new Exception(
            'Tarif belum dipilih.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI BERAT
    |--------------------------------------------------------------------------
    */

    if (
        $berat_kg === '' ||
        !is_numeric($berat_kg)
    ) {

        throw new Exception(
            'Berat timbangan tidak valid.'
        );

    }


    $berat_kg = (float) $berat_kg;


    if ($berat_kg <= 0) {

        throw new Exception(
            'Berat timbangan harus lebih dari 0 Kg.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CEK KARYAWAN DI DATABASE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            barcode_uid,
            nama_lengkap,
            nik
        FROM karyawan
        WHERE id = :id
          AND is_active = true
          AND status_aktif = true
        LIMIT 1
    ");


    $stmt->execute([
        ':id' => $karyawan_id
    ]);


    $karyawan = $stmt->fetch();


    if (!$karyawan) {

        throw new Exception(
            'Karyawan tidak ditemukan atau sudah tidak aktif.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CEK TARIF
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            nama_pekerjaan,
            harga_per_satuan,
            tipe_tarif
        FROM master_tarif
        WHERE id = :id
          AND status_aktif = true
        LIMIT 1
    ");


    $stmt->execute([
        ':id' => $tarif_id
    ]);


    $tarif = $stmt->fetch();


    if (!$tarif) {

        throw new Exception(
            'Tarif tidak ditemukan atau sudah tidak aktif.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | HARGA TARIF
    |--------------------------------------------------------------------------
    */

    $harga_per_satuan =
        (float) $tarif['harga_per_satuan'];


    /*
    |--------------------------------------------------------------------------
    | HITUNG TOTAL UPAH
    |--------------------------------------------------------------------------
    */

    $total_upah =
        $berat_kg * $harga_per_satuan;


    /*
    |--------------------------------------------------------------------------
    | ADMIN ID
    |--------------------------------------------------------------------------
    |
    | Login sistem saat ini masih menggunakan Demo Mode:
    | $_SESSION['divisi']
    |
    | Belum ada admin_id di session.
    |
    | Karena admin_id di tabel transaksi_timbang
    | tidak menggunakan NOT NULL, sementara kita isi NULL.
    |
    */

    $admin_id = null;


    /*
    |--------------------------------------------------------------------------
    | INSERT TRANSAKSI
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO transaksi_timbang
        (
            karyawan_id,
            admin_id,
            tarif_id,
            berat_kg,
            total_upah
        )
        VALUES
        (
            :karyawan_id,
            :admin_id,
            :tarif_id,
            :berat_kg,
            :total_upah
        )
        RETURNING id
    ");


    $stmt->execute([

        ':karyawan_id' =>
            $karyawan_id,

        ':admin_id' =>
            $admin_id,

        ':tarif_id' =>
            $tarif_id,

        ':berat_kg' =>
            $berat_kg,

        ':total_upah' =>
            $total_upah

    ]);


    /*
    |--------------------------------------------------------------------------
    | ID TRANSAKSI
    |--------------------------------------------------------------------------
    */

    $transaksi_id =
        $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | RESPONSE BERHASIL
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'status' =>
            'success',

        'message' =>
            'Transaksi berhasil disimpan.',

        'data' => [

            'id' =>
                $transaksi_id,

            'karyawan' =>
                $karyawan['nama_lengkap'],

            'pekerjaan' =>
                $tarif['nama_pekerjaan'],

            'berat_kg' =>
                $berat_kg,

            'harga_per_kg' =>
                $harga_per_satuan,

            'total_upah' =>
                $total_upah

        ]

    ]);

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | ERROR DATABASE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'status' =>
            'error',

        'message' =>
            'Database error: ' . $e->getMessage()

    ]);

} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | ERROR UMUM
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'status' =>
            'error',

        'message' =>
            $e->getMessage()

    ]);

}