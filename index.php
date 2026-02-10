<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// contoh data (dashboard)
$qCount = mysqli_query($koneksi, "SELECT id_user FROM tb_user");
$total_user = mysqli_num_rows($qCount);

$query = mysqli_query($koneksi, "SELECT * FROM tb_user ORDER BY id_user DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Sistem Parkir</title>
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

        /* ===== CONTENT AREA ===== */
        .content-wrap{ padding: 22px; }

        .glass{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            backdrop-filter: blur(14px);
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
        }
        .subtle{ color: var(--muted); }

        .stat{
            padding: 18px;
            border-radius: var(--radius);
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
        }
        .stat .icon{
            width:50px;height:50px;border-radius:18px;
            display:grid;place-items:center;
            background:rgba(34,211,238,.12);
            border:1px solid rgba(34,211,238,.22);
            color:rgba(34,211,238,.95);
        }
        .stat .value{ font-size:38px;font-weight:800;line-height:1; }

        /* TABLE LOOK */
        .table{
            color: rgba(255,255,255,.88) !important;
        }
        .table thead th{
            color: rgba(255,255,255,.65) !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .table td, .table th{
            border-color: rgba(255,255,255,.08) !important;
            vertical-align: middle;
        }

        /* badges */
        .badge-soft{
            border-radius:999px;
            padding:.45rem .7rem;
            font-weight:650;
            border:1px solid transparent;
            display:inline-flex;
            align-items:center;
            gap:.35rem;
        }
        .badge-active{
            background: rgba(34,197,94,.12);
            border-color: rgba(34,197,94,.22);
            color: rgba(34,197,94,.95);
        }
        .badge-role{
            background: rgba(99,102,241,.12);
            border-color: rgba(99,102,241,.22);
            color: rgba(165,180,252,.95);
        }

        /* search */
        .searchbox .input-group-text{
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
            color: rgba(255,255,255,.70);
        }
        .searchbox .form-control{
            color: var(--text);
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
            border-left: 0;
        }
        .searchbox .form-control:focus{
            box-shadow:none;
            border-color: rgba(34,211,238,.35);
        }

        @media (max-width: 767px){
            .content-wrap{ padding: 16px; }
        }

        /* =========================================================
   FIX: TABLE PUTIH -> DARK + TEKS KELIHATAN (FINAL)
   Tempel paling bawah
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
   BADGE: Aktif & Tugas tetap kelihatan (jangan ilang)
   ========================================================= */
.badge{
  border-radius: 999px !important;
  padding: .45rem .75rem !important;
  font-weight: 700 !important;
}

/* Aktif (hijau) */
.badge.bg-success{
  background: rgba(34,197,94,.18) !important;
  color: #22c55e !important;
  border: 1px solid rgba(34,197,94,.40) !important;
}

/* Tugas/Admin (biru) */
.badge.bg-primary{
  background: rgba(99,102,241,.18) !important;
  color: #a5b4fc !important;
  border: 1px solid rgba(99,102,241,.40) !important;
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

        <!-- ===== CONTENT ===== -->
        <div class="col-md-10">
            <div class="content-wrap">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Dashboard</h2>
                        <div class="subtle">Selamat datang di Sistem Parkir — kelola user dengan cepat & rapi.</div>
                    </div>
                    <a href="tambah_user.php" class="btn btn-primary btn-pill px-3">
                        <i class="bi bi-person-plus me-1"></i> Tambah User
                    </a>
                </div>

                <!-- STAT -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="stat">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="subtle small">Total User</div>
                                    <div class="value"><?= $total_user; ?></div>
                                    <div class="subtle small">Jumlah user terdaftar</div>
                                </div>
                                <div class="icon">
                                    <i class="bi bi-people-fill fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="glass p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="fw-bold">
                            <i class="bi bi-shield-lock-fill me-2 text-info"></i> Data User
                        </div>

                        <div class="searchbox" style="max-width: 380px; width: 100%;">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input id="searchUser" type="text" class="form-control" placeholder="Cari username / nama / role...">
                                <button class="btn btn-soft" type="button" onclick="resetSearch()">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0" id="userTable">
                            <thead>
                                <tr>
                                    <th style="width:70px;">No</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th style="width:140px;">Role</th>
                                    <th style="width:140px;">Status</th>
                                    <th style="width:220px;" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row=mysqli_fetch_assoc($query)) { ?>
                                    <tr>
                                        <td class="subtle"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['username']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td>
                                            <span class="badge-soft badge-role">
                                                <i class="bi bi-person-badge"></i>
                                                <?= ucfirst(htmlspecialchars($row['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-soft badge-active">
                                                <i class="bi bi-check-circle"></i> Aktif
                                            </span>
                                        </td>
                                        <td class="text-end d-flex justify-content-end gap-2">
                                            <a href="edit_user.php?id=<?= (int)$row['id_user']; ?>" class="btn btn-sm btn-soft btn-pill px-3">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </a>
                                            <a href="hapus_user.php?id=<?= (int)$row['id_user']; ?>" class="btn btn-sm btn-outline-danger btn-pill px-3"
                                               onclick="return confirm('Yakin hapus user ini?')">
                                                <i class="bi bi-trash3 me-1"></i>Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="noData" class="text-center subtle py-4 d-none">
                        Tidak ada data yang cocok.
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
    // ACTIVE MENU otomatis (tanpa ubah HTML)
    const path = window.location.pathname.split('/').pop(); // contoh: index.php
    document.querySelectorAll('.col-md-2 .nav-link').forEach(a => {
        const href = (a.getAttribute('href') || '').trim();
        if (!href || href === '#') return;
        if (href === path) a.classList.add('active');
    });

    // Search table
    const searchInput = document.getElementById('searchUser');
    const table = document.getElementById('userTable');
    const noData = document.getElementById('noData');

    function filterTable(){
        const q = (searchInput.value || '').toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr');
        let visible = 0;

        rows.forEach(r => {
            const text = r.innerText.toLowerCase();
            const show = text.includes(q);
            r.style.display = show ? '' : 'none';
            if(show) visible++;
        });

        noData.classList.toggle('d-none', visible !== 0);
    }

    function resetSearch(){
        searchInput.value = '';
        filterTable();
        searchInput.focus();
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
</script>

</body>
</html>
