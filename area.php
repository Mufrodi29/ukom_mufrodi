<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// ambil data area parkir
$query_area = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir ORDER BY id_area ASC");

// hitung total kapasitas dan terisi
$result_area = mysqli_query($koneksi, "SELECT SUM(kapasitas) as total_kapasitas, SUM(terisi) as total_terisi FROM tb_area_parkir");
$data_total = mysqli_fetch_assoc($result_area);
$total_kapasitas = $data_total['total_kapasitas'] ?? 0;
$total_terisi = $data_total['total_terisi'] ?? 0;
$slot_tersedia = $total_kapasitas - $total_terisi;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Area Parkir | Sistem Parkir</title>
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

        /* ===== GLASS CARD ===== */
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
        .badge.bg-warning.text-dark{ color: #fbbf24 !important; } /* jangan jadi item dark */

        /* ===== PROGRESS modern ===== */
        .progress{
            height: 22px !important;
            background: rgba(255,255,255,.08) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 999px !important;
            overflow: hidden;
        }
        .progress-bar{
            font-weight: 800;
        }
        .progress-bar.bg-success{ background: rgba(34,197,94,.85) !important; }
        .progress-bar.bg-warning{ background: rgba(245,158,11,.90) !important; color:#111827 !important; }
        .progress-bar.bg-danger{  background: rgba(239,68,68,.90) !important; }

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
                        <h2 class="fw-bold mb-1">Dashboard Area Parkir</h2>
                        <div class="subtle">Monitoring area dan kapasitas parkir</div>
                    </div>
                    <a href="tambah_area.php" class="btn btn-primary btn-pill px-3">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Area
                    </a>
                </div>

                <!-- SUMMARY -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="glass p-3">
                            <div class="subtle small">Total Kapasitas</div>
                            <div class="fs-2 fw-bold"><?= (int)$total_kapasitas; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="glass p-3">
                            <div class="subtle small">Total Terisi</div>
                            <div class="fs-2 fw-bold"><?= (int)$total_terisi; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="glass p-3">
                            <div class="subtle small">Slot Tersedia</div>
                            <div class="fs-2 fw-bold"><?= (int)$slot_tersedia; ?></div>
                        </div>
                    </div>
                </div>

                <!-- TABEL AREA PARKIR -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-geo-alt-fill me-2 text-info"></i> Data Area Parkir
                        </span>
                        <span class="subtle small">Total: <?= mysqli_num_rows($query_area); ?></span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:70px;">No</th>
                                        <th>Nama Area</th>
                                        <th>Kapasitas</th>
                                        <th>Terisi</th>
                                        <th>Tersedia</th>
                                        <th style="min-width:220px;">Tingkat Okupansi</th>
                                        <th>Status</th>
                                        <th style="width:200px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($query_area)) {
                                    $tersedia = (int)$row['kapasitas'] - (int)$row['terisi'];
                                    $persentase = ((int)$row['kapasitas'] > 0) ? round(((int)$row['terisi'] / (int)$row['kapasitas']) * 100) : 0;

                                    if ($persentase >= 90) {
                                        $status_badge = '<span class="badge bg-danger">Penuh</span>';
                                    } elseif ($persentase >= 70) {
                                        $status_badge = '<span class="badge bg-warning text-dark">Hampir Penuh</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-success">Tersedia</span>';
                                    }
                                ?>
                                    <tr>
                                        <td class="subtle"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_area']); ?></td>
                                        <td><span class="badge bg-primary"><?= (int)$row['kapasitas']; ?></span></td>
                                        <td><span class="badge bg-danger"><?= (int)$row['terisi']; ?></span></td>
                                        <td><span class="badge bg-success"><?= (int)$tersedia; ?></span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar <?= $persentase >= 90 ? 'bg-danger' : ($persentase >= 70 ? 'bg-warning' : 'bg-success'); ?>"
                                                     style="width: <?= (int)$persentase; ?>%">
                                                    <?= (int)$persentase; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $status_badge; ?></td>
                                        <td class="d-flex gap-2">
                                            <a href="edit_area.php?id=<?= (int)$row['id_area']; ?>"
                                               class="btn btn-sm btn-soft btn-pill px-3">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>

                                            <a href="hapus_area.php?id=<?= (int)$row['id_area']; ?>"
                                               class="btn btn-sm btn-outline-danger btn-pill px-3"
                                               onclick="return confirm('Yakin hapus area parkir ini?')">
                                                <i class="bi bi-trash3 me-1"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
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
