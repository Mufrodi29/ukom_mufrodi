<?php
session_start();
require 'config/koneksi.php';
require 'config/log_helper.php'; // <-- TAMBAHAN

if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']); // TIDAK HASH (sesuai permintaan)
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);
    $status   = 1; // default aktif

    // Validasi
    if (empty($nama) || empty($username) || empty($password) || empty($role)) {
        $error = "Semua field wajib diisi!";
    } else {
        // Cek username sudah ada atau belum
        $cek = mysqli_query($koneksi, "SELECT username FROM tb_user WHERE username='$username'");
        
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah digunakan, silakan pilih username lain!";
        } else {
            $query = mysqli_query($koneksi, "
                INSERT INTO tb_user 
                (nama_lengkap, username, password, role, status_aktif)
                VALUES 
                ('$nama', '$username', '$password', '$role', '$status')
            ");

            if ($query) {
                // ==============================
                // LOG AKTIVITAS
                // ==============================
                logAktivitas(
                    $koneksi,
                    $_SESSION['id_user'],
                    "Menambahkan user baru ($nama) sebagai " . ucfirst($role)
                );

                header("Location: index.php?pesan=tambah_sukses");
                exit;
            } else {
                $error = "Gagal menambahkan data: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah User | Sistem Parkir</title>
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

        .form-select-custom {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.875rem 1.25rem;
            font-size: 1rem;
            color: var(--text-white);
            transition: all 0.3s ease;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
            appearance: none;
        }

        .form-select-custom:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
            color: var(--text-white);
        }

        .form-select-custom option {
            background: var(--primary-navy);
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

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--accent-light);
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
            text-decoration: none;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-light));
            color: var(--text-white);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
            color: var(--text-white);
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
            color: var(--text-white);
        }

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .role-admin {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .role-petugas {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        .role-owner {
            background: rgba(168, 85, 247, 0.2);
            color: #c4b5fd;
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
                            <a href="index.php"><i class="bi bi-people"></i> Manajemen User</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah User</li>
                    </ol>
                </nav>
                
                <h1 class="page-title-custom">
                    <i class="bi bi-person-plus-fill"></i>
                    Tambah User Baru
                </h1>
                <p class="page-subtitle-custom">Tambahkan user baru ke dalam sistem manajemen parkir</p>
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
                        Form Tambah User
                    </h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST" action="" id="userForm">
                        
                        <!-- Nama Lengkap -->
                        <div class="form-group-custom">
                            <label for="nama_lengkap" class="form-label-custom">
                                <i class="bi bi-person-fill"></i>
                                Nama Lengkap
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       class="form-control form-control-custom" 
                                       id="nama_lengkap" 
                                       name="nama_lengkap" 
                                       placeholder="Masukkan nama lengkap user" 
                                       value="<?= isset($_POST['nama_lengkap']) ? $_POST['nama_lengkap'] : ''; ?>"
                                       required>
                                <i class="bi bi-person-badge input-icon-right"></i>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="form-group-custom">
                            <label for="username" class="form-label-custom">
                                <i class="bi bi-at"></i>
                                Username
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       class="form-control form-control-custom" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Masukkan username (tanpa spasi)" 
                                       value="<?= isset($_POST['username']) ? $_POST['username'] : ''; ?>"
                                       required>
                                <i class="bi bi-person-circle input-icon-right"></i>
                            </div>
                            <small class="form-text-custom">
                                <i class="bi bi-info-circle-fill"></i>
                                Username digunakan untuk login ke sistem
                            </small>
                        </div>

                        <!-- Password -->
                        <div class="form-group-custom">
                            <label for="password" class="form-label-custom">
                                <i class="bi bi-key-fill"></i>
                                Password
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       class="form-control form-control-custom" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Masukkan password" 
                                       required>
                                <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                            </div>
                            <small class="form-text-custom">
                                <i class="bi bi-shield-fill-check"></i>
                                Minimal 6 karakter untuk keamanan
                            </small>
                        </div>

                        <!-- Role -->
                        <div class="form-group-custom">
                            <label for="role" class="form-label-custom">
                                <i class="bi bi-shield-check"></i>
                                Role / Hak Akses
                                <span class="required-indicator">*</span>
                            </label>
                            <select name="role" 
                                    id="role"
                                    class="form-select form-select-custom" 
                                    required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin">
                                    Admin - Akses penuh sistem
                                </option>
                                <option value="petugas">
                                    Petugas - Kelola transaksi parkir
                                </option>
                                <option value="owner">
                                    Owner - Lihat laporan & statistik
                                </option>
                            </select>
                            <small class="form-text-custom">
                                <i class="bi bi-info-circle-fill"></i>
                                Tentukan level akses user dalam sistem
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="btn-group-custom">
                            <button type="submit" name="simpan" class="btn-custom btn-primary-custom">
                                <i class="bi bi-check-circle-fill"></i>
                                Simpan User
                            </button>
                            <a href="index.php" class="btn-custom btn-secondary-custom">
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

// Password Toggle
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

togglePassword.addEventListener('click', function() {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    
    // Toggle icon
    this.classList.toggle('bi-eye');
    this.classList.toggle('bi-eye-slash');
});

// Username validation (no spaces)
const usernameInput = document.getElementById('username');
usernameInput.addEventListener('input', function() {
    this.value = this.value.replace(/\s/g, '');
});

// Password validation
const form = document.getElementById('userForm');
form.addEventListener('submit', function(e) {
    const password = passwordInput.value;
    
    if (password.length < 2) {
        e.preventDefault();
        alert('Password minimal 6 karakter!');
        passwordInput.focus();
    }
});
</script>

</body>
</html>