<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Build query
$where = [];
if ($filter_status !== '') {
    $where[] = "t.status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}
if ($filter_tanggal !== '') {
    $where[] = "DATE(t.waktu_masuk) = '" . mysqli_real_escape_string($koneksi, $filter_tanggal) . "'";
}
if ($search !== '') {
    $where[] = "(k.plat_nomor LIKE '%$search%' OR k.pemilik LIKE '%$search%')";
}

$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total
$count_query = mysqli_query($koneksi, "
    SELECT COUNT(*) as total
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    $where_clause
");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data transaksi
$query = mysqli_query($koneksi, "
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           a.nama_area, tf.tarif_per_jam, u.nama_lengkap as petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    JOIN tb_user u ON t.id_user = u.id_user
    $where_clause
    ORDER BY t.id_transaksi DESC
    LIMIT $limit OFFSET $offset
");

// Hitung statistik
$stats_query = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN status = 'masuk' THEN 1 ELSE 0 END) as sedang_parkir,
        SUM(CASE WHEN status = 'keluar' THEN 1 ELSE 0 END) as sudah_keluar,
        COALESCE(SUM(CASE WHEN status = 'keluar' THEN biaya_total ELSE 0 END), 0) as total_pendapatan
    FROM tb_transaksi
");


$stats = mysqli_fetch_assoc($stats_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Transaksi | Sistem Parkir</title>
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

        .form-control, .form-select{
            background: rgba(255,255,255,.08) !important;
            border: 1px solid rgba(255,255,255,.14) !important;
            border-radius: 12px !important;
            color: var(--text) !important;
            padding: .6rem .9rem;
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
            background: rgba(34,211,238,.18) !important;
            color: #22d3ee !important;
            border: 1px solid rgba(34,211,238,.40) !important;
        }
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

        .pagination .page-link{
            background: rgba(255,255,255,.08) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: var(--text) !important;
            border-radius: 8px !important;
            margin: 0 4px;
        }
        .pagination .page-link:hover{
            background: rgba(255,255,255,.15) !important;
        }
        .pagination .page-item.active .page-link{
            background: linear-gradient(135deg, #2563eb, #a855f7) !important;
            border-color: transparent !important;
        }

        /* Modal Custom */
        .modal-content{
            background: rgba(15,23,42,.95) !important;
            border: 1px solid rgba(255,255,255,.15) !important;
            border-radius: 20px !important;
            backdrop-filter: blur(20px);
        }
        .modal-header{
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .modal-footer{
            border-top: 1px solid rgba(255,255,255,.10) !important;
        }
        .modal-title{
            color: var(--text) !important;
        }
        .btn-close{
            filter: invert(1);
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

                <div class="mb-4">
                    <h2 class="fw-bold mb-1">Riwayat Transaksi</h2>
                    <div class="subtle">Riwayat dan laporan transaksi parkir</div>
                </div>

                <!-- STATISTIK HARI INI -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <div class="glass p-3">
                            <div class="subtle small">Transaksi Hari Ini</div>
                            <div class="fs-2 fw-bold"><?= (int)$stats['total_transaksi']; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="glass p-3">
                            <div class="subtle small">Sedang Parkir</div>
                            <div class="fs-2 fw-bold text-warning"><?= (int)$stats['sedang_parkir']; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="glass p-3">
                            <div class="subtle small">Sudah Keluar</div>
                            <div class="fs-2 fw-bold text-success"><?= (int)$stats['sudah_keluar']; ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="glass p-3">
                        <div class="subtle small">Total Seluruh Pendapatan</div>
                            <div class="fs-4 fw-bold text-info">Rp <?= number_format($stats['total_pendapatan'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- FILTER -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><i class="bi bi-funnel-fill me-2"></i> Filter & Pencarian</span>
                        <button type="button" class="btn btn-sm btn-success btn-pill" data-bs-toggle="modal" data-bs-target="#laporanModal">
                            <i class="bi bi-file-earmark-text me-1"></i> Cetak Laporan
                        </button>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Cari plat nomor / pemilik" 
                                           value="<?= htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="masuk" <?= $filter_status === 'masuk' ? 'selected' : ''; ?>>Sedang Parkir</option>
                                        <option value="keluar" <?= $filter_status === 'keluar' ? 'selected' : ''; ?>>Sudah Keluar</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control" name="tanggal" 
                                           value="<?= htmlspecialchars($filter_tanggal); ?>">
                                </div>
                                <div class="col-md-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-pill flex-grow-1">
                                        <i class="bi bi-search me-1"></i> Filter
                                    </button>
                                    <a href="riwayat_transaksi.php" class="btn btn-soft btn-pill">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABEL TRANSAKSI -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-clock-history me-2 text-info"></i> Data Transaksi
                        </span>
                        <span class="subtle small">Total: <?= $total_data; ?> transaksi</span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">No</th>
                                        <th>ID</th>
                                        <th>Plat Nomor</th>
                                        <th>Jenis</th>
                                        <th>Pemilik</th>
                                        <th>Area</th>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                        <th>Durasi</th>
                                        <th>Biaya</th>
                                        <th>Status</th>
                                        <th>Petugas</th>
                                        <th style="width:140px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if (mysqli_num_rows($query) === 0) {
                                    echo '<tr><td colspan="13" class="text-center subtle">Tidak ada data transaksi</td></tr>';
                                } else {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($query)) {
                                        $status_badge = $row['status'] === 'masuk' 
                                            ? '<span class="badge bg-warning">Sedang Parkir</span>' 
                                            : '<span class="badge bg-success">Selesai</span>';
                                ?>
                                    <tr>
                                        <td class="subtle"><?= $no++; ?></td>
                                        <td><span class="badge bg-info">#<?= str_pad($row['id_transaksi'], 6, '0', STR_PAD_LEFT); ?></span></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['plat_nomor']); ?></td>
                                        <td><?= ucfirst(htmlspecialchars($row['jenis_kendaraan'])); ?></td>
                                        <td><?= htmlspecialchars($row['pemilik']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_area']); ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['waktu_masuk'])); ?></td>
                                        <td><?= $row['waktu_keluar'] ? date('d/m/Y H:i', strtotime($row['waktu_keluar'])) : '-'; ?></td>
                                        <td><?= $row['durasi_jam'] ? $row['durasi_jam'] . ' jam' : '-'; ?></td>
                                        <td class="fw-bold"><?= $row['biaya_total'] ? 'Rp ' . number_format($row['biaya_total'], 0, ',', '.') : '-'; ?></td>
                                        <td><?= $status_badge; ?></td>
                                        <td><?= htmlspecialchars($row['petugas']); ?></td>
                                        <td>
                                            <a href="cetak_struk.php?id=<?= $row['id_transaksi']; ?>" 
                                               class="btn btn-sm btn-primary btn-pill px-3"
                                               target="_blank">
                                                <i class="bi bi-printer me-1"></i> Struk
                                            </a>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } 
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1; ?>&status=<?= $filter_status; ?>&tanggal=<?= $filter_tanggal; ?>&search=<?= $search; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&status=<?= $filter_status; ?>&tanggal=<?= $filter_tanggal; ?>&search=<?= $search; ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1; ?>&status=<?= $filter_status; ?>&tanggal=<?= $filter_tanggal; ?>&search=<?= $search; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="subtle small mt-3">
                    © <?= date('Y'); ?> Sistem Parkir — Admin Panel
                </div>

            </div>
        </div>
    </div>
</div>

<!-- MODAL LAPORAN -->
<div class="modal fade" id="laporanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-text me-2"></i> Cetak Laporan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formLaporan">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pilih Jenis Laporan</label>
                        <select class="form-select" id="tipeLaporan" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="harian">Laporan Harian</option>
                            <option value="bulanan">Laporan Bulanan</option>
                            <option value="tahunan">Laporan Tahunan</option>
                        </select>
                    </div>

                    <div id="inputHarian" style="display:none;">
                        <label class="form-label">Pilih Tanggal</label>
                        <input type="date" class="form-control" id="tanggalHarian" value="<?= date('Y-m-d'); ?>">
                    </div>

                    <div id="inputBulanan" style="display:none;">
                        <label class="form-label">Pilih Bulan</label>
                        <input type="month" class="form-control" id="bulanBulanan" value="<?= date('Y-m'); ?>">
                    </div>

                    <div id="inputTahunan" style="display:none;">
                        <label class="form-label">Pilih Tahun</label>
                        <select class="form-select" id="tahunTahunan">
                            <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y; ?>"><?= $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-pill" onclick="cetakLaporan()">
                    <i class="bi bi-printer me-1"></i> Cetak
                </button>
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

    // Toggle input berdasarkan tipe laporan
    document.getElementById('tipeLaporan').addEventListener('change', function() {
        document.getElementById('inputHarian').style.display = 'none';
        document.getElementById('inputBulanan').style.display = 'none';
        document.getElementById('inputTahunan').style.display = 'none';

        if (this.value === 'harian') {
            document.getElementById('inputHarian').style.display = 'block';
        } else if (this.value === 'bulanan') {
            document.getElementById('inputBulanan').style.display = 'block';
        } else if (this.value === 'tahunan') {
            document.getElementById('inputTahunan').style.display = 'block';
        }
    });

    // Fungsi cetak laporan
    function cetakLaporan() {
        const tipe = document.getElementById('tipeLaporan').value;
        
        if (!tipe) {
            alert('Silakan pilih jenis laporan terlebih dahulu!');
            return;
        }

        let url = 'cetak_laporan.php?tipe=' + tipe;

        if (tipe === 'harian') {
            const tanggal = document.getElementById('tanggalHarian').value;
            url += '&tanggal=' + tanggal;
        } else if (tipe === 'bulanan') {
            const bulan = document.getElementById('bulanBulanan').value;
            url += '&bulan=' + bulan;
        } else if (tipe === 'tahunan') {
            const tahun = document.getElementById('tahunTahunan').value;
            url += '&tahun=' + tahun;
        }

        window.open(url, '_blank');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>