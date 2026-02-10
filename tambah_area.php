<?php
session_start();
require 'config/koneksi.php';
require 'config/log_helper.php'; // <-- TAMBAHAN

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// PROSES TAMBAH DATA
if (isset($_POST['simpan'])) {
    $nama_area = mysqli_real_escape_string($koneksi, $_POST['nama_area']);
    $kapasitas = mysqli_real_escape_string($koneksi, $_POST['kapasitas']);
    $terisi    = mysqli_real_escape_string($koneksi, $_POST['terisi']);
    
    // Validasi
    if (empty($nama_area) || empty($kapasitas)) {
        $error = "Nama area dan kapasitas wajib diisi!";
    } elseif (!is_numeric($kapasitas) || $kapasitas < 1) {
        $error = "Kapasitas harus berupa angka lebih dari 0!";
    } elseif (!is_numeric($terisi) || $terisi < 0) {
        $error = "Terisi harus berupa angka 0 atau lebih!";
    } elseif ($terisi > $kapasitas) {
        $error = "Terisi tidak boleh lebih dari kapasitas!";
    } else {

        $query = "INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) 
                  VALUES ('$nama_area', '$kapasitas', '$terisi')";
        
        if (mysqli_query($koneksi, $query)) {

            // ==============================
            // LOG AKTIVITAS
            // ==============================
            logAktivitas(
                $koneksi,
                $_SESSION['id_user'],
                "Menambahkan area parkir baru ($nama_area) dengan kapasitas $kapasitas slot"
            );

            header("Location: area.php?pesan=tambah_sukses");
            exit;

        } else {
            $error = "Gagal menambahkan data: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Area Parkir | Sistem Parkir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-navy: #1e293b;
            --secondary-navy: #2d3b52;
            --dark-navy: #0f172a;
            --light-navy: #334155;
            --accent-blue: #3b82f6;
            --accent-light: #60a5fa;
            --text-white: #ffffff;
            --text-gray: #94a3b8;
            --text-light: #cbd5e1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --card-bg: rgba(255, 255, 255, 0.05);
            --input-bg: rgba(255, 255, 255, 0.08);
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: var(--text-white);
        }

        /* Navbar Styling */
        .navbar-custom {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        .navbar-brand-custom {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-white);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .navbar-brand-custom i {
            color: var(--accent-blue);
            font-size: 1.75rem;
        }

        .user-info-nav {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-avatar-nav {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .user-details-nav {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }

        .user-name-nav {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-white);
        }

        .user-role-nav {
            font-size: 0.8rem;
            color: var(--text-gray);
        }

        .btn-logout-nav {
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-white);
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout-nav:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: var(--danger);
            color: var(--text-white);
            transform: translateY(-2px);
        }

        /* Main Container */
        .main-wrapper {
            padding: 2rem 0;
        }

        /* Page Header */
        .page-header-custom {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            animation: fadeInDown 0.6s ease;
        }

        .page-title-custom {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-white);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-subtitle-custom {
            color: var(--text-gray);
            font-size: 1rem;
            font-weight: 400;
        }

        /* Breadcrumb */
        .breadcrumb-custom {
            background: transparent;
            padding: 0;
            margin-bottom: 0;
        }

        .breadcrumb-custom .breadcrumb-item {
            color: var(--text-gray);
        }

        .breadcrumb-custom .breadcrumb-item a {
            color: var(--accent-light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .breadcrumb-custom .breadcrumb-item a:hover {
            color: var(--text-white);
        }

        .breadcrumb-custom .breadcrumb-item.active {
            color: var(--text-light);
        }

        .breadcrumb-custom .breadcrumb-item + .breadcrumb-item::before {
            color: var(--text-gray);
            content: "›";
        }

        /* Alert Custom */
        .alert-custom {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            color: #fca5a5;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInDown 0.5s ease;
        }

        .alert-custom i {
            font-size: 1.5rem;
        }

        .alert-custom .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card Custom */
        .card-custom {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1.25rem;
            overflow: hidden;
            animation: fadeInUp 0.7s ease;
        }

        .card-header-custom {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(96, 165, 250, 0.1));
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--text-white);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header-custom i {
            color: var(--accent-light);
        }

        .card-body-custom {
            padding: 2.5rem;
        }

        /* Form Styling */
        .form-group-custom {
            margin-bottom: 2rem;
        }

        .form-label-custom {
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .form-label-custom i {
            color: var(--accent-light);
            font-size: 1rem;
        }

        .required-indicator {
            color: var(--danger);
            margin-left: 0.25rem;
        }

        .form-control-custom {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.875rem 1.25rem;
            font-size: 1rem;
            color: var(--text-white);
            transition: all 0.3s ease;
        }

        .form-control-custom::placeholder {
            color: var(--text-gray);
        }

        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
            color: var(--text-white);
        }

        .form-text-custom {
            color: var(--text-gray);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-text-custom i {
            font-size: 0.75rem;
        }

        /* Input with Icon */
        .input-wrapper {
            position: relative;
        }

        .input-icon-right {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            pointer-events: none;
        }

        /* Button Group */
        .btn-group-custom {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
        }

        .btn-custom {
            padding: 0.875rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-light));
            color: var(--text-white);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--text-white);
        }

        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header-custom {
                padding: 1.5rem;
            }

            .page-title-custom {
                font-size: 1.5rem;
            }

            .card-body-custom {
                padding: 1.5rem;
            }

            .btn-group-custom {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
                justify-content: center;
            }

            .user-details-nav {
                display: none;
            }
        }

        /* Decorative Elements */
        .decoration-circle {
            position: fixed;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1), transparent);
            pointer-events: none;
            z-index: -1;
        }

        .decoration-circle-1 {
            width: 500px;
            height: 500px;
            top: -250px;
            right: -250px;
        }

        .decoration-circle-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            left: -200px;
        }
    </style>
</head>
<body>

<!-- Decorative Elements -->
<div class="decoration-circle decoration-circle-1"></div>
<div class="decoration-circle decoration-circle-2"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-custom">
    <div class="container-fluid px-4">
        <span class="navbar-brand-custom">
            <i class="bi bi-car-front-fill"></i>
            Sistem Parkir
        </span>
        <div class="user-info-nav">
            <div class="user-avatar-nav">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
            </div>
            <div class="user-details-nav">
                <span class="user-name-nav"><?= $_SESSION['nama_lengkap']; ?></span>
                <span class="user-role-nav"><?= $_SESSION['role']; ?></span>
            </div>
            <a href="../logout.php" class="btn-logout-nav">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid main-wrapper">
    <div class="row">

        <?php include 'sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="col-md-10 px-4">

            <!-- Page Header -->
            <div class="page-header-custom">
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb breadcrumb-custom">
                        <li class="breadcrumb-item">
                            <a href="index.php"><i class="bi bi-house-door"></i> Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="area.php"><i class="bi bi-grid"></i> Area Parkir</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah Area</li>
                    </ol>
                </nav>
                
                <h1 class="page-title-custom">
                    <i class="bi bi-plus-circle-fill"></i>
                    Tambah Area Parkir
                </h1>
                <p class="page-subtitle-custom">Monitoring area dan kapasitas parkir yang tersedia</p>
            </div>

            <!-- Alert -->
            <?php if (isset($error)) { ?>
                <div class="alert alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Error!</strong> <?= $error; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <!-- Form Card -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <h5>
                        <i class="bi bi-pencil-square"></i>
                        Form Tambah Area Parkir
                    </h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST" action="">
                        
                        <!-- Nama Area -->
                        <div class="form-group-custom">
                            <label for="nama_area" class="form-label-custom">
                                <i class="bi bi-tag-fill"></i>
                                Nama Area
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       class="form-control form-control-custom" 
                                       id="nama_area" 
                                       name="nama_area" 
                                       placeholder="Contoh: Area A, Area VIP, Basement 1" 
                                       value="<?= isset($_POST['nama_area']) ? $_POST['nama_area'] : ''; ?>"
                                       required>
                                <i class="bi bi-signpost-2 input-icon-right"></i>
                            </div>
                        </div>

                        <!-- Kapasitas -->
                        <div class="form-group-custom">
                            <label for="kapasitas" class="form-label-custom">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                                Kapasitas
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="number" 
                                       class="form-control form-control-custom" 
                                       id="kapasitas" 
                                       name="kapasitas" 
                                       placeholder="Jumlah slot parkir tersedia" 
                                       min="1"
                                       value="<?= isset($_POST['kapasitas']) ? $_POST['kapasitas'] : ''; ?>"
                                       required>
                                <i class="bi bi-calculator input-icon-right"></i>
                            </div>
                            <small class="form-text-custom">
                                <i class="bi bi-info-circle-fill"></i>
                                Jumlah maksimal kendaraan yang dapat parkir di area ini
                            </small>
                        </div>

                        <!-- Terisi -->
                        <div class="form-group-custom">
                            <label for="terisi" class="form-label-custom">
                                <i class="bi bi-car-front"></i>
                                Terisi
                            </label>
                            <div class="input-wrapper">
                                <input type="number" 
                                       class="form-control form-control-custom" 
                                       id="terisi" 
                                       name="terisi" 
                                       placeholder="Jumlah slot yang sudah terisi" 
                                       min="0"
                                       value="<?= isset($_POST['terisi']) ? $_POST['terisi'] : '0'; ?>">
                                <i class="bi bi-check2-circle input-icon-right"></i>
                            </div>
                            <small class="form-text-custom">
                                <i class="bi bi-info-circle-fill"></i>
                                Jumlah kendaraan yang saat ini parkir (default: 0)
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="btn-group-custom">
                            <button type="submit" name="simpan" class="btn-custom btn-primary-custom">
                                <i class="bi bi-check-circle-fill"></i>
                                Simpan Data
                            </button>
                            <a href="area.php" class="btn-custom btn-secondary-custom">
                                <i class="bi bi-arrow-left-circle-fill"></i>
                                Kembali
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-hide alert
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-custom');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Form validation enhancement
const form = document.querySelector('form');
const kapasitasInput = document.getElementById('kapasitas');
const terisiInput = document.getElementById('terisi');

terisiInput.addEventListener('input', function() {
    const kapasitas = parseInt(kapasitasInput.value) || 0;
    const terisi = parseInt(this.value) || 0;
    
    if (terisi > kapasitas) {
        this.setCustomValidity('Terisi tidak boleh lebih dari kapasitas!');
    } else {
        this.setCustomValidity('');
    }
});

kapasitasInput.addEventListener('input', function() {
    terisiInput.dispatchEvent(new Event('input'));
});
</script>

</body>
</html>