<?php
// config/database.php

// 1. Fungsi sederhana untuk membaca file .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: File .env tidak ditemukan di sistem.");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Abaikan baris komentar
        if (strpos(trim($line), '#') === 0) continue;
        
        // Pisahkan nama variabel dan nilainya
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// 2. Load .env dari root folder (satu tingkat di atas folder config)
loadEnv(__DIR__ . '/../.env');

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];

// 3. Lakukan koneksi menggunakan PDO PostgreSQL
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Format hasil query menjadi array asosiatif
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Uncomment baris di bawah ini HANYA untuk mengetes koneksi (hapus/komen lagi jika sudah berhasil)
     echo "Koneksi ke Supabase berhasil!";
    
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>