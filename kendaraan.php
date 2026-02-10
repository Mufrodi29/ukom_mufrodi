<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// ambil data kendaraan
$query_kendaraan = mysqli_query($koneksi, "
    SELECT k.*, u.nama_lengkap as nama_petugas
    FROM tb_kendaraan k
    LEFT JOIN tb_user u ON k.id_user = u.id_user
    ORDER BY k.id_kendaraan DESC
");

// hitung total kendaraan
$total_kendaraan = mysqli_num_rows($query_kendaraan);

// hitung per jenis
$query_motor = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_kendaraan WHERE jenis_kendaraan = 'motor'");
$total_motor = mysqli_fetch_assoc($query_motor)['total'] ?? 0;

$query_mobil = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_kendaraan WHERE jenis_kendaraan = 'mobil'");
$total_mobil = mysqli_fetch_assoc($query_mobil)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kendaraan | Sistem Parkir</title>
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

        /* ===== TOPBAR ===== */
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

        /* ===== CONTENT ===== */
        .content-wrap{ padding: 22px; }

        /* ===== GLASS ===== */
        .glass{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            backdrop-filter: blur(14px);
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
        }

        /* ===== CARD override bootstrap ===== */
        .card{
            background: rgba(255,255,255,.06) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: var(--radius) !important;
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
            overflow: hidden;
        }
        .card-header{
            background: rgba(255,255,255,.08) !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
            color: #fff !important;
        }

        /* ===== ALERT modern ===== */
        .alert{
            border-radius: 16px !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            background: rgba(255,255,255,.08) !important;
            color: rgba(255,255,255,.90) !important;
            box-shadow: 0 14px 40px rgba(0,0,0,.25);
        }
        .alert-success{
            border-color: rgba(34,197,94,.25) !important;
        }
        .alert-danger{
            border-color: rgba(239,68,68,.25) !important;
        }
        .btn-close{
            filter: invert(1);
            opacity: .8;
        }

        /* ===== TABLE DARK (anti putih) ===== */
        .table-responsive{
            border-radius: 16px;
            overflow: hidden;
        }
        .table, .table *{
            background: transparent !important;
        }
        .table th, .table td{
            color: rgba(255,255,255,.88) !important;
            border-color: rgba(255,255,255,.10) !important;
            vertical-align: middle;
        }
        .table thead th{
            background: rgba(255,255,255,.08) !important;
            color: rgba(255,255,255,.70) !important;
            border-bottom: 1px solid rgba(255,255,255,.14) !important;
        }
        .table tbody tr{
            background: rgba(255,255,255,.04) !important;
        }
        .table-striped > tbody > tr:nth-of-type(odd){
            background: rgba(255,255,255,.06) !important;
        }
        .table-hover > tbody > tr:hover{
            background: rgba(255,255,255,.10) !important;
        }

        /* ===== BADGE (biar kelihatan di dark) ===== */
        .badge{
            border-radius: 999px !important;
            padding: .45rem .75rem !important;
            font-weight: 700 !important;
        }
        .badge.bg-success{
            background: rgba(34,197,94,.18) !important;
            color: #22c55e !important;
            border: 1px solid rgba(34,197,94,.40) !important;
        }
        .badge.bg-info{
            background: rgba(56,189,248,.18) !important;
            color: #38bdf8 !important;
            border: 1px solid rgba(56,189,248,.40) !important;
        }
        .badge.bg-primary{
            background: rgba(99,102,241,.18) !important;
            color: #a5b4fc !important;
            border: 1px solid rgba(99,102,241,.40) !important;
        }
        .badge.bg-danger{
            background: rgba(239,68,68,.18) !important;
            color: #fb7185 !important;
            border: 1px solid rgba(239,68,68,.40) !important;
        }
        .badge.bg-warning{
            background: rgba(245,158,11,.18) !important;
            color: #fbbf24 !important;
            border: 1px solid rgba(245,158,11,.40) !important;
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

        <?php include 'sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="col-md-10">
            <div class="content-wrap">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Data Kendaraan</h2>
                        <div class="subtle">Kelola data kendaraan yang parkir</div>
                    </div>
                    <a href="tambah_kendaraan.php" class="btn btn-primary btn-pill px-3">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Kendaraan
                    </a>
                </div>

                <!-- SUMMARY -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="glass p-3">
                            <div class="subtle small">Total Kendaraan</div>
                            <div class="fs-2 fw-bold"><?= (int)$total_kendaraan; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="glass p-3">
                            <div class="subtle small">Motor</div>
                            <div class="fs-2 fw-bold"><?= (int)$total_motor; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="glass p-3">
                            <div class="subtle small">Mobil</div>
                            <div class="fs-2 fw-bold"><?= (int)$total_mobil; ?></div>
                        </div>
                    </div>
                </div>

                <?php
                // Tampilkan pesan notifikasi
                if (isset($_GET['pesan'])) {
                    $pesan = $_GET['pesan'];
                    if ($pesan == 'tambah_sukses') {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Berhasil!</strong> Data kendaraan berhasil ditambahkan.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    } elseif ($pesan == 'update_sukses') {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Berhasil!</strong> Data kendaraan berhasil diupdate.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    } elseif ($pesan == 'hapus_sukses') {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Berhasil!</strong> Data kendaraan berhasil dihapus.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    } elseif ($pesan == 'hapus_gagal') {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Gagal!</strong> Data kendaraan gagal dihapus.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                }
                ?>

                <!-- TABEL KENDARAAN -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-truck-front-fill me-2 text-info"></i> Daftar Kendaraan
                        </span>
                        <span class="subtle small">Total: <?= (int)$total_kendaraan; ?></span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:70px;">No</th>
                                        <th>Plat Nomor</th>
                                        <th>Jenis</th>
                                        <th>Warna</th>
                                        <th>Pemilik</th>
                                        <th>Petugas</th>
                                        <th style="width:200px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if ($total_kendaraan > 0) {
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($query_kendaraan)) {
                                ?>
                                    <tr>
                                        <td class="subtle"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars(strtoupper($row['plat_nomor'])); ?></td>
                                        <td>
                                            <?php if ($row['jenis_kendaraan'] == 'motor') { ?>
                                                <span class="badge bg-success">Motor</span>
                                            <?php } else { ?>
                                                <span class="badge bg-info">Mobil</span>
                                            <?php } ?>
                                        </td>
                                        <td><?= htmlspecialchars(ucfirst($row['warna'] ?? '-')); ?></td>
                                        <td><?= htmlspecialchars($row['pemilik'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['nama_petugas'] ?? 'Sistem'); ?></td>
                                        <td class="d-flex gap-2">
                                            <a href="edit_kendaraan.php?id=<?= (int)$row['id_kendaraan']; ?>"
                                               class="btn btn-sm btn-soft btn-pill px-3">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>

                                            <a href="hapus_kendaraan.php?id=<?= (int)$row['id_kendaraan']; ?>"
                                               class="btn btn-sm btn-outline-danger btn-pill px-3"
                                               onclick="return confirm('Yakin hapus data kendaraan <?= htmlspecialchars(strtoupper($row['plat_nomor'])); ?>?')">
                                               <i class="bi bi-trash3 me-1"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center subtle py-4">Belum ada data kendaraan</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
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
    // Active menu otomatis (tanpa ubah sidebar HTML)
    const path = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(a => {
        const href = (a.getAttribute('href') || '').trim();
        if (!href || href === '#') return;
        if (href === path) a.classList.add('active');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
