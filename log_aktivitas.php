<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

$query = mysqli_query($koneksi, "
    SELECT l.*, u.nama_lengkap, u.role
    FROM tb_log_aktivitas l
    JOIN tb_user u ON l.id_user = u.id_user
    ORDER BY l.waktu_aktivitas DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas | Sistem Parkir</title>
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

        /* ===== TOPBAR (modern) ===== */
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

        /* ===== CONTENT AREA ===== */
        .content-wrap{ padding: 22px; }

        .glass{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            backdrop-filter: blur(14px);
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
        }

        /* ===== CARD / TABLE ===== */
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

        .table{
            color: rgba(255,255,255,.88) !important;
            margin-bottom: 0;
        }
        .table thead th{
            color: rgba(255,255,255,.65) !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .table td, .table th{
            border-color: rgba(255,255,255,.08) !important;
            vertical-align: middle;
        }

        @media (max-width: 767px){
            .content-wrap{ padding: 16px; }
        }

        /* =========================================================
   FIX: TABLE PUTIH -> DARK + TEKS KELIHATAN (FINAL)
   ========================================================= */

/* container tabel (biar nyatu sama card glass) */
.table-responsive{
  border-radius: 16px;
  overflow: hidden;
}

/* paksa semua bagian tabel transparan/dark */
.table,
.table *{
  background: transparent !important;
}

/* warna teks default tabel */
.table th,
.table td{
  color: rgba(255,255,255,.88) !important;
  border-color: rgba(255,255,255,.10) !important;
}

/* header */
.table thead th{
  background: rgba(255,255,255,.08) !important;
  color: rgba(255,255,255,.70) !important;
  border-bottom: 1px solid rgba(255,255,255,.14) !important;
}

/* body rows (normal) */
.table tbody tr{
  background: rgba(255,255,255,.04) !important;
}

/* striped rows */
.table-striped > tbody > tr:nth-of-type(odd){
  background: rgba(255,255,255,.06) !important;
}

/* hover */
.table-hover > tbody > tr:hover{
  background: rgba(255,255,255,.10) !important;
}

/* jika masih ada yang maksa putih dari bootstrap */
.table-striped > tbody > tr > *{
  background: transparent !important;
}

/* =========================================================
   BADGE: tetap kelihatan
   ========================================================= */
.badge{
  border-radius: 999px !important;
  padding: .45rem .75rem !important;
  font-weight: 700 !important;
}

/* Admin/Petugas (biru) */
.badge.bg-primary{
  background: rgba(99,102,241,.18) !important;
  color: #a5b4fc !important;
  border: 1px solid rgba(99,102,241,.40) !important;
}

/* Success (hijau) */
.badge.bg-success{
  background: rgba(34,197,94,.18) !important;
  color: #22c55e !important;
  border: 1px solid rgba(34,197,94,.40) !important;
}

/* warning/danger kalau kepake */
.badge.bg-warning{
  background: rgba(245,158,11,.18) !important;
  color: #fbbf24 !important;
  border: 1px solid rgba(245,158,11,.40) !important;
}
.badge.bg-danger{
  background: rgba(239,68,68,.18) !important;
  color: #fb7185 !important;
  border: 1px solid rgba(239,68,68,.40) !important;
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
                        <h2 class="fw-bold mb-1">Log Aktivitas Sistem</h2>
                        <div class="subtle">Riwayat aktivitas pengguna sistem parkir</div>
                    </div>
                </div>

                <!-- TABEL LOG AKTIVITAS -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-clock-history me-2 text-info"></i>Log Aktivitas Sistem
                        </span>
                        <span class="subtle small">Total: <?= mysqli_num_rows($query); ?></span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:80px;">No</th>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Aktivitas</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($query) > 0) {
                                    while ($row = mysqli_fetch_assoc($query)) {
                                ?>
                                    <tr>
                                        <td class="subtle"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= ucfirst($row['role']); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                                        <td class="subtle">
                                            <?= date('d M Y H:i', strtotime($row['waktu_aktivitas'])); ?>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center subtle py-4">
                                            <em>Belum ada data log aktivitas</em>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Active menu otomatis (tanpa ubah sidebar HTML)
    const path = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(a => {
        const href = (a.getAttribute('href') || '').trim();
        if (!href || href === '#') return;
        if (href === path) a.classList.add('active');
    });
</script>

</body>
</html>