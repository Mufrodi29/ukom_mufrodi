<?php
session_start();
require 'config/koneksi.php';

// Cek login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Ambil parameter
$jenis_laporan = isset($_POST['jenis_laporan']) ? $_POST['jenis_laporan'] : 'semua';

// Query berdasarkan jenis laporan
$where = "1=1";
$judul_laporan = "Semua Area Parkir";

if($jenis_laporan == 'tersedia') {
    $where = "status = 'Tersedia'";
    $judul_laporan = "Area Parkir Tersedia";
    
} elseif($jenis_laporan == 'hampir_penuh') {
    $where = "(terisi / kapasitas * 100) > 70 AND (terisi / kapasitas * 100) < 100";
    $judul_laporan = "Area Parkir Hampir Penuh (>70%)";
    
} elseif($jenis_laporan == 'penuh') {
    $where = "terisi >= kapasitas";
    $judul_laporan = "Area Parkir Penuh";
}

$query = mysqli_query($koneksi, "
    SELECT * FROM tb_area_parkir 
    WHERE $where
    ORDER BY nama_area
");

$data_area = [];
$total_kapasitas = 0;
$total_terisi = 0;
$total_tersedia = 0;

while($row = mysqli_fetch_assoc($query)) {
    // Pastikan semua key ada dengan default value
    $row['kapasitas'] = isset($row['kapasitas']) ? $row['kapasitas'] : 0;
    $row['terisi'] = isset($row['terisi']) ? $row['terisi'] : 0;
    $row['tersedia'] = isset($row['tersedia']) ? $row['tersedia'] : 0;
    $row['status'] = isset($row['status']) ? $row['status'] : 'Tersedia';
    $row['nama_area'] = isset($row['nama_area']) ? $row['nama_area'] : 'N/A';
    
    $data_area[] = $row;
    $total_kapasitas += $row['kapasitas'];
    $total_terisi += $row['terisi'];
    $total_tersedia += $row['tersedia'];
}

$total_okupansi = $total_kapasitas > 0 ? ($total_terisi / $total_kapasitas * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Area Parkir - <?= $judul_laporan ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            @page { margin: 1.5cm; }
            body {
                background: white !important;
                color: #000 !important;
            }
            .container {
                background: white !important;
                border: none !important;
                box-shadow: none !important;
            }
            .header h1 {
                color: #1e40af !important;
                -webkit-text-fill-color: #1e40af !important;
            }
            table th, table td {
                color: #000 !important;
            }
            .stat-card .value {
                color: #3b82f6 !important;
                -webkit-text-fill-color: #3b82f6 !important;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: 
                radial-gradient(900px 520px at 15% 8%, rgba(99,102,241,.30), transparent 60%),
                radial-gradient(800px 480px at 88% 14%, rgba(34,211,238,.20), transparent 58%),
                radial-gradient(900px 600px at 50% 110%, rgba(168,85,247,.18), transparent 60%),
                #070d1c;
            min-height: 100vh;
            color: rgba(255,255,255,.92);
            padding: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 18px;
            padding: 40px;
            backdrop-filter: blur(14px);
            box-shadow: 0 18px 55px rgba(0,0,0,.32);
        }
        
        .btn-print {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }
        
        .header {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 2px solid rgba(255,255,255,.15);
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header h2 {
            color: rgba(255,255,255,.65);
            font-size: 16px;
            font-weight: normal;
        }
        
        .info-box {
            background: rgba(255,255,255,.08);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,.12);
        }
        
        .info-box p {
            margin: 8px 0;
            color: rgba(255,255,255,.85);
            font-size: 14px;
        }
        
        .info-box strong {
            color: #60a5fa;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .stat-card h3 {
            font-size: 13px;
            margin-bottom: 12px;
            color: rgba(255,255,255,.65);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card .label {
            font-size: 12px;
            color: rgba(255,255,255,.50);
            margin-top: 5px;
        }
        
        .table-container {
            background: rgba(255,255,255,.04);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.10);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: rgba(255,255,255,.08);
        }
        
        table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: rgba(255,255,255,.70);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,.14);
        }
        
        table td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            color: rgba(255,255,255,.88);
        }
        
        table tbody tr {
            background: rgba(255,255,255,.04);
            transition: background 0.2s;
        }
        
        table tbody tr:hover {
            background: rgba(255,255,255,.10);
        }
        
        .progress-bar {
            height: 24px;
            background: rgba(255,255,255,.10);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255,255,255,.15);
        }
        
        .progress-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
            transition: width 0.3s;
        }
        
        .progress-success { 
            background: linear-gradient(90deg, #10b981, #059669);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        
        .progress-warning { 
            background: linear-gradient(90deg, #f59e0b, #d97706);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
        }
        
        .progress-danger { 
            background: linear-gradient(90deg, #ef4444, #dc2626);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-success {
            background: rgba(34,197,94,.18);
            color: #22c55e;
            border: 1px solid rgba(34,197,94,.40);
        }
        
        .badge-warning {
            background: rgba(245,158,11,.18);
            color: #fbbf24;
            border: 1px solid rgba(245,158,11,.40);
        }
        
        .badge-danger {
            background: rgba(239,68,68,.18);
            color: #fb7185;
            border: 1px solid rgba(239,68,68,.40);
        }
        
        .circle-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        
        .circle-primary {
            background: rgba(99,102,241,.18);
            color: #a5b4fc;
            border: 1px solid rgba(99,102,241,.40);
        }
        
        .circle-danger {
            background: rgba(239,68,68,.18);
            color: #fb7185;
            border: 1px solid rgba(239,68,68,.40);
        }
        
        .circle-success {
            background: rgba(34,197,94,.18);
            color: #22c55e;
            border: 1px solid rgba(34,197,94,.40);
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: rgba(255,255,255,.50);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid rgba(255,255,255,.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-left {
            color: rgba(255,255,255,.50);
            font-size: 13px;
        }
        
        .signature {
            text-align: center;
            min-width: 200px;
        }
        
        .signature-line {
            margin-top: 70px;
            border-top: 2px solid rgba(255,255,255,.30);
            padding-top: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,.50);
        }
        
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Tombol Print -->
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">
                🖨️ Cetak / Download PDF
            </button>
        </div>
        
        <!-- Header -->
        <div class="header">
            <h1>LAPORAN AREA PARKIR</h1>
            <h2><?= $judul_laporan ?></h2>
        </div>
        
        <!-- Info Laporan -->
        <div class="info-box">
            <p><strong>Tanggal Cetak:</strong> <?= date('d F Y, H:i') ?> WIB</p>
            <p><strong>Dicetak Oleh:</strong> <?= isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin' ?> (<?= isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Admin' ?>)</p>
            <p><strong>Total Area:</strong> <?= count($data_area) ?> area parkir</p>
        </div>
        
        <!-- Statistik -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Kapasitas</h3>
                <div class="value"><?= $total_kapasitas ?></div>
                <div class="label">Slot Total</div>
            </div>
            <div class="stat-card">
                <h3>Total Terisi</h3>
                <div class="value"><?= $total_terisi ?></div>
                <div class="label">Kendaraan</div>
            </div>
            <div class="stat-card">
                <h3>Total Tersedia</h3>
                <div class="value"><?= $total_tersedia ?></div>
                <div class="label">Slot Kosong</div>
            </div>
            <div class="stat-card">
                <h3>Tingkat Okupansi</h3>
                <div class="value"><?= number_format($total_okupansi, 0) ?>%</div>
                <div class="label">Keseluruhan</div>
            </div>
        </div>
        
        <!-- Tabel -->
        <?php if(!empty($data_area)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="20%">Nama Area</th>
                        <th width="10%" class="text-center">Kapasitas</th>
                        <th width="10%" class="text-center">Terisi</th>
                        <th width="10%" class="text-center">Tersedia</th>
                        <th width="30%">Tingkat Okupansi</th>
                        <th width="15%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach($data_area as $data): 
                        $okupansi = $data['kapasitas'] > 0 ? ($data['terisi'] / $data['kapasitas'] * 100) : 0;
                        
                        // Progress bar color
                        if($okupansi < 50) {
                            $progress_class = 'progress-success';
                        } elseif($okupansi < 80) {
                            $progress_class = 'progress-warning';
                        } else {
                            $progress_class = 'progress-danger';
                        }
                        
                        // Badge color
                        $badge_class = 'badge-success';
                        if($data['status'] == 'Hampir Penuh') {
                            $badge_class = 'badge-warning';
                        } elseif($data['status'] == 'Penuh') {
                            $badge_class = 'badge-danger';
                        }
                    ?>
                    <tr>
                        <td class="text-center text-muted"><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($data['nama_area']) ?></strong></td>
                        <td class="text-center">
                            <span class="circle-badge circle-primary"><?= $data['kapasitas'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="circle-badge circle-danger"><?= $data['terisi'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="circle-badge circle-success"><?= $data['tersedia'] ?></span>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $progress_class ?>" style="width: <?= $okupansi ?>%">
                                    <?= number_format($okupansi, 0) ?>%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $badge_class ?>">
                                <?= $data['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">🅿️</div>
            <h3>Tidak ada data area parkir</h3>
            <p>Belum ada data untuk kategori yang dipilih</p>
        </div>
        <?php endif; ?>
        
        <!-- Footer & Tanda Tangan -->
        <div class="footer">
            <div class="footer-left">
                © <?= date('Y') ?> Sistem Parkir — Admin Panel
            </div>
            <div class="signature">
                <p style="color: rgba(255,255,255,.65);"><?= date('d F Y') ?></p>
                <div class="signature-line">
                    <strong><?= isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin' ?></strong><br>
                    <small style="color: rgba(255,255,255,.60);"><?= isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Admin' ?></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>