<?php
session_start();
require 'config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// Ambil data area parkir yang masih tersedia
$query_area = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir WHERE terisi < kapasitas ORDER BY nama_area ASC");

// Ambil data tarif
$query_tarif = mysqli_query($koneksi, "SELECT * FROM tb_tarif ORDER BY id_tarif ASC");

// Ambil data kendaraan untuk autocomplete
$query_kendaraan = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan ORDER BY plat_nomor ASC");

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat_nomor = mysqli_real_escape_string($koneksi, $_POST['plat_nomor']);
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    $warna = mysqli_real_escape_string($koneksi, $_POST['warna']);
    $pemilik = mysqli_real_escape_string($koneksi, $_POST['pemilik']);
    $id_area = (int)$_POST['id_area'];
    $id_tarif = (int)$_POST['id_tarif'];
    $id_user = (int)$_SESSION['id_user'];
    
    mysqli_begin_transaction($koneksi);
    
    try {
        // Cek apakah kendaraan sudah ada
        $cek_kendaraan = mysqli_query($koneksi, "SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = '$plat_nomor'");
        
        if (mysqli_num_rows($cek_kendaraan) > 0) {
            $data_kendaraan = mysqli_fetch_assoc($cek_kendaraan);
            $id_kendaraan = $data_kendaraan['id_kendaraan'];
            
            // Update data kendaraan
            mysqli_query($koneksi, "UPDATE tb_kendaraan SET 
                jenis_kendaraan = '$jenis_kendaraan',
                warna = '$warna',
                pemilik = '$pemilik'
                WHERE id_kendaraan = $id_kendaraan");
        } else {
            // Insert kendaraan baru
            mysqli_query($koneksi, "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) 
                VALUES ('$plat_nomor', '$jenis_kendaraan', '$warna', '$pemilik', $id_user)");
            $id_kendaraan = mysqli_insert_id($koneksi);
        }
        
        // Insert transaksi
        mysqli_query($koneksi, "INSERT INTO tb_transaksi (id_kendaraan, id_tarif, id_area, waktu_masuk, status, id_user) 
            VALUES ($id_kendaraan, $id_tarif, $id_area, NOW(), 'masuk', $id_user)");
        
        // Update terisi area parkir
        mysqli_query($koneksi, "UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = $id_area");
        
        // Log aktivitas
        mysqli_query($koneksi, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) 
            VALUES ($id_user, 'Kendaraan $plat_nomor masuk parkir')");
        
        mysqli_commit($koneksi);
        header("Location: transaksi_masuk.php?pesan=sukses");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $error = "Transaksi gagal: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Masuk | Sistem Parkir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root{
            --bg:#070d1c;
            --card:rgba(255,255,255,.06);
            --border:rgba(255,255,255,.10);
            --text:rgba(255,255,255,.92);
            --muted:rgba(255,255,255,.62);
            --radius:18px;
        }

        body{
            min-height:100vh;
            color:var(--text);
            background:
                radial-gradient(900px 520px at 15% 8%, rgba(99,102,241,.30), transparent 60%),
                radial-gradient(800px 480px at 88% 14%, rgba(34,211,238,.20), transparent 58%),
                radial-gradient(900px 600px at 50% 110%, rgba(168,85,247,.18), transparent 60%),
                var(--bg);
        }

        .subtle{ color: var(--muted); }

        /* TOPBAR */
        .topbar{
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(7,13,28,.55);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .brand-chip{
            width:44px;height:44px;border-radius:16px;
            display:grid;place-items:center;
            background:linear-gradient(135deg,#2563eb,#a855f7);
            box-shadow:0 14px 40px rgba(37,99,235,.30);
        }
        .btn-pill{ border-radius:999px; }
        .btn-soft{
            color:var(--text);
            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.12);
        }
        .btn-soft:hover{ background:rgba(255,255,255,.12); }

        .content-wrap{ padding: 22px; }

        /* GLASS CARD */
        .glass, .card{
            background: var(--card) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            backdrop-filter: blur(14px);
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
        }
        
        .card-header{
            background: rgba(255,255,255,.08) !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
            color: #fff !important;
        }

        /* FORM STYLING */
        .form-label{
            color: var(--text);
            font-weight: 600;
            margin-bottom: .5rem;
        }
        .form-control, .form-select{
            background: rgba(255,255,255,.08) !important;
            border: 1px solid rgba(255,255,255,.14) !important;
            border-radius: 12px !important;
            color: var(--text) !important;
            padding: .75rem 1rem;
        }
        .form-control:focus, .form-select:focus{
            background: rgba(255,255,255,.12) !important;
            border-color: rgba(99,102,241,.5) !important;
            box-shadow: 0 0 0 .25rem rgba(99,102,241,.15) !important;
            color: var(--text) !important;
        }
        .form-control::placeholder{
            color: var(--muted);
        }
        .form-select option{
            background: #1a1f35;
            color: var(--text);
        }

        .alert{
            border-radius: 16px !important;
            border: 1px solid !important;
        }
        .alert-success{
            background: rgba(34,197,94,.15) !important;
            border-color: rgba(34,197,94,.4) !important;
            color: #4ade80 !important;
        }
        .alert-danger{
            background: rgba(239,68,68,.15) !important;
            border-color: rgba(239,68,68,.4) !important;
            color: #fb7185 !important;
        }

        @media (max-width: 767px){
            .content-wrap{ padding: 16px; }
        }
    </style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="container-fluid px-3 px-md-4 py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="brand-chip">
                <i class="bi bi-p-square-fill fs-5 text-white"></i>
            </div>
            <div>
                <div class="fw-bold">Sistem Parkir</div>
                <div class="subtle small">Admin Panel</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <div class="fw-semibold"><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
                <div class="subtle small"><?= ucfirst(htmlspecialchars($_SESSION['role'])); ?></div>
            </div>
            <a href="../logout.php" class="btn btn-outline-danger btn-pill px-3">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row g-0">

        <!-- ===== SIDEBAR ===== -->
        <?php include 'sidebar.php'; ?>
        <style>
          /* ===== SIDEBAR (HTML TIDAK DIUBAH, CSS AJA) ===== */
        .col-md-2.bg-dark{
            background: linear-gradient(180deg, #0b1220, #070d1c) !important;
            border-right: 1px solid rgba(255,255,255,.08);
            box-shadow: 8px 0 40px rgba(0,0,0,.35);
        }
        .col-md-2.bg-dark h5{
            font-weight: 800;
            letter-spacing: .12em;
            font-size: .95rem;
            margin-bottom: 22px !important;
        }
        .col-md-2.bg-dark .nav-link{
            padding: 11px 12px;
            border-radius: 14px;
            color: rgba(255,255,255,.82) !important;
            transition: .18s ease;
            line-height: 1.25;
        }
        .col-md-2.bg-dark .nav-link:hover{
            background: rgba(255,255,255,.07);
            transform: translateX(3px);
        }
        .col-md-2.bg-dark .nav-link.active{
            background: linear-gradient(135deg, rgba(37,99,235,.35), rgba(168,85,247,.25));
            box-shadow: inset 3px 0 0 rgba(96,165,250,1), 0 10px 26px rgba(37,99,235,.16);
            color: #fff !important;
        }  
        </style>

        <!-- CONTENT -->
        <div class="col-md-10">
            <div class="content-wrap">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Transaksi Masuk Parkir</h2>
                        <div class="subtle">Input kendaraan yang masuk area parkir</div>
                    </div>
                    <a href="transaksi_keluar.php" class="btn btn-soft btn-pill px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Transaksi Keluar
                    </a>
                </div>

                <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'sukses'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Berhasil!</strong> Kendaraan berhasil masuk parkir.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Error!</strong> <?= htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- FORM -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-car-front-fill me-2 text-success"></i> Form Kendaraan Masuk
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="" id="formTransaksi">
                            <div class="row g-3">
                                
                                <div class="col-md-6">
                                    <label class="form-label">Plat Nomor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="plat_nomor" id="plat_nomor"
                                           placeholder="B 1234 XYZ" required 
                                           autocomplete="off"
                                           oninput="this.value = this.value.toUpperCase()">
                                    <small class="text-muted" id="loading_text" style="display:none;">
                                        <i class="bi bi-hourglass-split"></i> Mencari data...
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Jenis Kendaraan <span class="text-danger">*</span></label>
                                    <select class="form-select" name="jenis_kendaraan" id="jenis_kendaraan" required>
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="motor">Motor</option>
                                        <option value="mobil">Mobil</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Warna Kendaraan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="warna" id="warna"
                                           placeholder="Hitam" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Nama Pemilik <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="pemilik" id="pemilik"
                                           placeholder="Nama lengkap pemilik" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Area Parkir <span class="text-danger">*</span></label>
                                    <select class="form-select" name="id_area" required>
                                        <option value="">-- Pilih Area --</option>
                                        <?php while ($area = mysqli_fetch_assoc($query_area)): 
                                            $sisa = $area['kapasitas'] - $area['terisi'];
                                        ?>
                                            <option value="<?= $area['id_area']; ?>">
                                                <?= htmlspecialchars($area['nama_area']); ?> 
                                                (Sisa: <?= $sisa; ?> slot)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tarif <span class="text-danger">*</span></label>
                                    <select class="form-select" name="id_tarif" id="id_tarif" required>
                                        <option value="">-- Pilih Tarif --</option>
                                        <?php while ($tarif = mysqli_fetch_assoc($query_tarif)): ?>
                                            <option value="<?= $tarif['id_tarif']; ?>" data-jenis="<?= $tarif['jenis_kendaraan']; ?>">
                                                <?= ucfirst(htmlspecialchars($tarif['jenis_kendaraan'])); ?> - 
                                                Rp <?= number_format($tarif['tarif_per_jam'], 0, ',', '.'); ?>/jam
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <hr style="border-color: rgba(255,255,255,.1);">
                                    <div class="d-flex gap-3">
                                        <button type="submit" class="btn btn-primary btn-pill px-4">
                                            <i class="bi bi-check-circle me-2"></i> Simpan Transaksi
                                        </button>
                                        <button type="reset" class="btn btn-soft btn-pill px-4">
                                            <i class="bi bi-arrow-clockwise me-2"></i> Reset Form
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <div class="subtle small mt-3">
                    © <?= date('Y'); ?> Sistem Parkir — Admin Panel
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // Auto active menu
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href === currentPage) {
            link.classList.add('active');
        }
    });

    // AUTOCOMPLETE & AUTO-FILL DATA KENDARAAN
    const platInput = document.getElementById('plat_nomor');
    const jenisSelect = document.getElementById('jenis_kendaraan');
    const warnaInput = document.getElementById('warna');
    const pemilikInput = document.getElementById('pemilik');
    const tarifSelect = document.getElementById('id_tarif');
    const loadingText = document.getElementById('loading_text');
    
    let timeoutId;

    platInput.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const platNomor = this.value.trim();
        
        // Reset jika kosong
        if (platNomor.length === 0) {
            resetForm();
            return;
        }
        
        // Tunggu user selesai mengetik (debounce 500ms)
        if (platNomor.length >= 3) {
            loadingText.style.display = 'block';
            
            timeoutId = setTimeout(() => {
                fetchKendaraanData(platNomor);
            }, 500);
        }
    });

    function fetchKendaraanData(platNomor) {
        fetch(`api_get_kendaraan.php?plat=${encodeURIComponent(platNomor)}`)
            .then(response => response.json())
            .then(data => {
                loadingText.style.display = 'none';
                
                if (data.success && data.data) {
                    // Auto-fill form dengan data yang ditemukan
                    jenisSelect.value = data.data.jenis_kendaraan;
                    warnaInput.value = data.data.warna;
                    pemilikInput.value = data.data.pemilik;
                    
                    // Auto-select tarif sesuai jenis kendaraan
                    autoSelectTarif(data.data.jenis_kendaraan);
                    
                    // Tampilkan notifikasi sukses
                    showNotification('Data kendaraan ditemukan! Form otomatis terisi.', 'success');
                } else {
                    // Plat nomor belum terdaftar
                    resetFormFields();
                    showNotification('Plat nomor belum terdaftar. Silakan isi data kendaraan.', 'info');
                }
            })
            .catch(error => {
                loadingText.style.display = 'none';
                console.error('Error:', error);
            });
    }

    function autoSelectTarif(jenisKendaraan) {
        // Cari dan pilih tarif yang sesuai dengan jenis kendaraan
        const options = tarifSelect.querySelectorAll('option');
        options.forEach(option => {
            if (option.dataset.jenis === jenisKendaraan) {
                tarifSelect.value = option.value;
            }
        });
    }

    function resetFormFields() {
        jenisSelect.value = '';
        warnaInput.value = '';
        pemilikInput.value = '';
        tarifSelect.value = '';
    }

    function resetForm() {
        resetFormFields();
        loadingText.style.display = 'none';
    }

    function showNotification(message, type) {
        // Hapus notifikasi lama jika ada
        const oldNotif = document.querySelector('.auto-notification');
        if (oldNotif) oldNotif.remove();
        
        // Buat notifikasi baru
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show auto-notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 8px 24px rgba(0,0,0,0.3);';
        
        const icon = type === 'success' ? 'check-circle-fill' : 'info-circle-fill';
        
        notification.innerHTML = `
            <i class="bi bi-${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-hide setelah 4 detik
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }

    // Auto-select tarif saat jenis kendaraan dipilih manual
    jenisSelect.addEventListener('change', function() {
        if (this.value) {
            autoSelectTarif(this.value);
        }
    });

    // Reset form saat tombol reset diklik
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        setTimeout(() => {
            resetForm();
        }, 10);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>