<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['divisi']) || $_SESSION['divisi'] !== 'produksi') {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-top: 5px solid #198754;">
                <div class="card-body text-center">
                    <h5 class="text-hijau fw-bold mb-3">Scan QR Karyawan</h5>
                    <div id="reader" width="100%"></div>
                    <div id="scanResult" class="mt-3 fw-bold text-success fs-5"></div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm" id="panelTimbangan" style="opacity: 0.4; pointer-events: none;">
                <div class="card-body p-4">
                    <h5 class="text-hijau fw-bold mb-3">Profil Karyawan & Input Timbangan</h5>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small mb-0">ID / Waktu Scan</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-bold text-primary" id="displayIdWaktu" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-muted small mb-0">NIK - Nama Lengkap</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-bold" id="displayNama" readonly>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small mb-0">Tempat, Tgl Lahir</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="displayTtl" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small mb-0">Jenis Kelamin</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="displayJk" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small mb-0">No. HP</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="displayHp" readonly>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small mb-0">Grup Shift</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="displayShift" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-muted small mb-0">Alamat Lengkap</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="displayAlamat" readonly>
                        </div>
                    </div>

                    <hr class="mb-3">

                    <form id="formTimbangan" onsubmit="return simpanTimbangan(event)">
                        <input type="hidden" id="karyawanId" required>
                        <input type="hidden" id="waktuScanValid" required>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pilih Tarif Udang</label>
                                <select class="form-select form-select-lg" id="tarifId" required>
                                    <option value="" selected disabled>-- Pilih Jenis --</option>
                                    <option value="1">Kupas Besar (Rp 1.500/kg)</option>
                                    <option value="2">Kupas Kecil (Rp 2.000/kg)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold">Berat Timbangan (Kg)</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" step="0.01" class="form-control" id="beratInput" required>
                                    <span class="input-group-text bg-hijau text-white border-0">Kg</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-hijau btn-lg w-100 fw-bold">Simpan Transaksi (Enter)</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);

    function onScanSuccess(decodedText, decodedResult) {
        html5QrcodeScanner.pause(true); 
        document.getElementById('scanResult').innerText = "Mencari data: " + decodedText + "...";

        fetch('../actions/get_karyawan.php?uid=' + decodedText)
            .then(response => response.json())
            .then(res => {
                if(res.status === 'success') {
                    let now = new Date();
                    let waktuScan = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    let waktuDb = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' + waktuScan;

                    document.getElementById('karyawanId').value = res.data.id; 
                    document.getElementById('waktuScanValid').value = waktuDb;
                    
                    document.getElementById('displayIdWaktu').value = decodedText + ' | ' + waktuScan;
                    document.getElementById('displayNama').value = (res.data.nik || '-') + ' - ' + res.data.nama_lengkap;
                    document.getElementById('displayTtl').value = (res.data.tempat_lahir || '-') + ', ' + (res.data.tanggal_lahir || '-');
                    document.getElementById('displayJk').value = res.data.jenis_kelamin || '-';
                    document.getElementById('displayHp').value = res.data.no_hp || '-';
                    document.getElementById('displayShift').value = res.data.grup_shift || '-';
                    document.getElementById('displayAlamat').value = res.data.alamat || '-';
                    
                    document.getElementById('scanResult').innerText = "Terkunci: " + decodedText;
                    document.getElementById('scanResult').className = "mt-3 fw-bold text-success fs-5";
                    
                    let panel = document.getElementById('panelTimbangan');
                    panel.style.opacity = "1";
                    panel.style.pointerEvents = "auto";
                    
                    document.getElementById('beratInput').focus();
                } else {
                    document.getElementById('scanResult').innerText = res.message;
                    document.getElementById('scanResult').className = "mt-3 fw-bold text-danger fs-5";
                    setTimeout(() => { html5QrcodeScanner.resume(); document.getElementById('scanResult').innerText = ""; }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                html5QrcodeScanner.resume();
            });
    }

    html5QrcodeScanner.render(onScanSuccess);

    function simpanTimbangan(event) {
        event.preventDefault();
        alert("Data timbangan berhasil disimpan!");
        
        document.getElementById('formTimbangan').reset();
        document.getElementById('displayIdWaktu').value = "";
        document.getElementById('displayNama').value = "";
        document.getElementById('displayTtl').value = "";
        document.getElementById('displayJk').value = "";
        document.getElementById('displayHp').value = "";
        document.getElementById('displayShift').value = "";
        document.getElementById('displayAlamat').value = "";
        document.getElementById('scanResult').innerText = "";
        
        let panel = document.getElementById('panelTimbangan');
        panel.style.opacity = "0.4";
        panel.style.pointerEvents = "none";
        
        html5QrcodeScanner.resume();
    }
</script>

<?php require_once '../includes/footer.php'; ?>