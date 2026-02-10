<?php
session_start();
include "config/koneksi.php";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query = mysqli_query($koneksi, "
        SELECT * FROM tb_user 
        WHERE username='$username' 
        AND password='$password' 
        AND status_aktif=1
    ");

    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);

        // SET SESSION
        $_SESSION['id_user']      = $data['id_user'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['username']     = $data['username'];
        $_SESSION['role']         = $data['role'];
        $_SESSION['login']        = true;

        // REDIRECT BERDASARKAN ROLE
        switch ($data['role']) {
            case 'admin':
                header("Location: index.php");
                break;

            case 'petugas':
                header("Location: transaksi_masuk.php");
                break;

            case 'owner':
                header("Location: riwayat_transaksi.php");
                break;

            default:
                session_destroy();
                header("Location: login.php?error=role_tidak_valid");
                break;
        }
        exit;

    } else {
        $error = "Username atau password salah!";
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Modern - Sistem Parkir</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --accent-color: #4cc9f0;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.8)), 
                        url('moderen.png');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            background: var(--primary-color);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            color: white;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }

        .login-header h2 {
            color: #ffffff;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .form-label {
            color: #e2e8f0;
            font-weight: 500;
            font-size: 0.85rem;
            margin-left: 4px;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            border-radius: 12px 0 0 12px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(76, 201, 240, 0.15);
            color: white;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .btn-login {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 10px;
            transition: all 0.3s;
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.4);
            opacity: 0.9;
        }

        .footer-text {
            margin-top: 30px;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: center;
        }

        /* Animasi Masuk */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="brand-logo">
            <i class="fas fa-parking"></i>
        </div>
        <div class="login-header">
            <h2>DI-PARKING</h2>
            <p>Sistem Manajemen Parkir </p>
        </div>
    </div>

    <?php if (isset($error)) { ?>
        <div class="alert alert-danger border-0 bg-danger text-white bg-opacity-75 small py-2 px-3 mb-4 rounded-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error; ?>
        </div>
    <?php } ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" class="form-control" 
                       placeholder="Masukkan username" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" 
                       placeholder="Masukkan password" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn btn-login w-100">
            Masuk ke Sistem <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </form>

    <div class="footer-text">
        &copy; <?= date('Y'); ?> <strong>Mufrodi</strong> • All Rights Reserved
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>