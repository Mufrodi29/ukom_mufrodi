<?php
session_start();
require 'config/koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php?pesan=login_dulu");
    exit;
}

// Ambil parameter
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'harian'; // harian, bulanan, tahunan
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Query berdasarkan tipe laporan
$where_clause = '';
$judul_laporan = '';
$periode_laporan = '';

if ($tipe === 'harian') {
    $where_clause = "WHERE DATE(t.waktu_masuk) = '" . mysqli_real_escape_string($koneksi, $tanggal) . "'";
    $judul_laporan = "LAPORAN TRANSAKSI HARIAN";
    $periode_laporan = date('d F Y', strtotime($tanggal));
} elseif ($tipe === 'bulanan') {
    $where_clause = "WHERE DATE_FORMAT(t.waktu_masuk, '%Y-%m') = '" . mysqli_real_escape_string($koneksi, $bulan) . "'";
    $judul_laporan = "LAPORAN TRANSAKSI BULANAN";
    $periode_laporan = date('F Y', strtotime($bulan . '-01'));
} elseif ($tipe === 'tahunan') {
    $where_clause = "WHERE YEAR(t.waktu_masuk) = '" . mysqli_real_escape_string($koneksi, $tahun) . "'";
    $judul_laporan = "LAPORAN TRANSAKSI TAHUNAN";
    $periode_laporan = "Tahun " . $tahun;
}

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
    ORDER BY t.waktu_masuk DESC
");

// Hitung statistik
$stats_query = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN status = 'masuk' THEN 1 ELSE 0 END) as sedang_parkir,
        SUM(CASE WHEN status = 'keluar' THEN 1 ELSE 0 END) as sudah_keluar,
        COALESCE(SUM(CASE WHEN status = 'keluar' THEN biaya_total ELSE 0 END), 0) as total_pendapatan,
        SUM(CASE WHEN k.jenis_kendaraan = 'mobil' THEN 1 ELSE 0 END) as total_mobil,
        SUM(CASE WHEN k.jenis_kendaraan = 'motor' THEN 1 ELSE 0 END) as total_motor
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    $where_clause
");

$stats = mysqli_fetch_assoc($stats_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan <?= ucfirst($tipe); ?> - Sistem Parkir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .laporan-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header-laporan {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-laporan h1 {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .header-laporan h2 {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
        }

        .header-laporan .info {
            font-size: 14px;
            color: #888;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box.success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        .stat-box.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-box.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .stat-box.info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        .stat-box .label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table thead {
            background: #2563eb;
            color: white;
        }

        table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }

        table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
        }

        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        table tbody tr:hover {
            background: #e0e7ff;
        }

        .footer-laporan {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            margin-top: 80px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .action-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 15px;
            z-index: 1000;
        }

        .action-btn {
            background: linear-gradient(135deg, #2563eb, #a855f7);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(37, 99, 235, 0.5);
            color: white;
        }

        .btn-print {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .btn-pdf {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }

        .btn-back {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            text-align: center;
            color: white;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .laporan-container {
                box-shadow: none;
                padding: 20px;
            }

            .action-buttons,
            .loading-overlay {
                display: none !important;
            }

            table {
                font-size: 10px;
            }

            .stat-box .value {
                font-size: 24px;
            }
        }

        @media (max-width: 768px) {
            .laporan-container {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 10px;
            }

            .action-buttons {
                bottom: 20px;
                right: 20px;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="laporan-container">
    <!-- HEADER -->
    <div class="header-laporan">
        <h1>SISTEM PARKIR</h1>
        <h2><?= $judul_laporan; ?></h2>
        <div class="info">
            Periode: <strong><?= $periode_laporan; ?></strong><br>
            Dicetak pada: <?= date('d F Y H:i:s'); ?>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="label">Total Transaksi</div>
            <div class="value"><?= (int)$stats['total_transaksi']; ?></div>
        </div>
        <div class="stat-box warning">
            <div class="label">Sedang Parkir</div>
            <div class="value"><?= (int)$stats['sedang_parkir']; ?></div>
        </div>
        <div class="stat-box success">
            <div class="label">Selesai</div>
            <div class="value"><?= (int)$stats['sudah_keluar']; ?></div>
        </div>
        <div class="stat-box info">
            <div class="label">Total Pendapatan</div>
            <div class="value" style="font-size: 20px;">Rp <?= number_format($stats['total_pendapatan'], 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- DETAIL KENDARAAN -->
    <div class="stats-grid" style="margin-top: 20px;">
        <div class="stat-box" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
            <div class="label">Total Mobil</div>
            <div class="value"><?= (int)$stats['total_mobil']; ?></div>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #ec4899, #db2777);">
            <div class="label">Total Motor</div>
            <div class="value"><?= (int)$stats['total_motor']; ?></div>
        </div>
    </div>

    <!-- TABEL DATA -->
    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2563eb;">Detail Transaksi</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
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
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($query) === 0) {
                    echo '<tr><td colspan="12" style="text-align: center; padding: 20px; color: #999;">Tidak ada data transaksi</td></tr>';
                } else {
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query)) {
                        $status_badge = $row['status'] === 'masuk' 
                            ? '<span class="badge badge-warning">Parkir</span>' 
                            : '<span class="badge badge-success">Selesai</span>';
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>#<?= str_pad($row['id_transaksi'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><strong><?= htmlspecialchars($row['plat_nomor']); ?></strong></td>
                        <td><?= ucfirst(htmlspecialchars($row['jenis_kendaraan'])); ?></td>
                        <td><?= htmlspecialchars($row['pemilik']); ?></td>
                        <td><?= htmlspecialchars($row['nama_area']); ?></td>
                        <td><?= date('d/m/y H:i', strtotime($row['waktu_masuk'])); ?></td>
                        <td><?= $row['waktu_keluar'] ? date('d/m/y H:i', strtotime($row['waktu_keluar'])) : '-'; ?></td>
                        <td><?= $row['durasi_jam'] ? $row['durasi_jam'] . ' jam' : '-'; ?></td>
                        <td style="font-weight: bold;"><?= $row['biaya_total'] ? 'Rp ' . number_format($row['biaya_total'], 0, ',', '.') : '-'; ?></td>
                        <td><?= $status_badge; ?></td>
                        <td><?= htmlspecialchars($row['petugas']); ?></td>
                    </tr>
                <?php 
                    } 
                } 
                ?>
            </tbody>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer-laporan">
        <div style="text-align: right; margin-bottom: 10px;">
            <strong style="font-size: 16px;">Total Pendapatan: Rp <?= number_format($stats['total_pendapatan'], 0, ',', '.'); ?></strong>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div>Mengetahui,</div>
                <div><strong>Manajer</strong></div>
                <div class="signature-line">
                    <strong>(.......................)</strong>
                </div>
            </div>
            
            <div class="signature-box">
                <div>Petugas,</div>
                <div><strong>Admin</strong></div>
                <div class="signature-line">
                    <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #888;">
            <p>Dokumen ini dicetak otomatis oleh Sistem Parkir</p>
            <p>© <?= date('Y'); ?> Sistem Parkir - Admin Panel</p>
        </div>
    </div>
</div>

<!-- TOMBOL AKSI -->
<div class="action-buttons">
    <button class="action-btn btn-print" onclick="window.print()">
        🖨️ Print
    </button>
    <button class="action-btn btn-pdf" onclick="exportToPDF()">
        📄 PDF
    </button>
    <a href="riwayat_transaksi.php" class="action-btn btn-back">
        ← Kembali
    </a>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Membuat PDF...</p>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    async function exportToPDF() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        const laporanContainer = document.querySelector('.laporan-container');
        const actionButtons = document.querySelector('.action-buttons');
        
        try {
            loadingOverlay.style.display = 'flex';
            actionButtons.style.display = 'none';
            
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const canvas = await html2canvas(laporanContainer, {
                scale: 2,
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: true
            });
            
            actionButtons.style.display = 'flex';
            
            const { jsPDF } = window.jspdf;
            const imgData = canvas.toDataURL('image/png');
            
            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            let heightLeft = imgHeight;
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            const tipe = '<?= $tipe; ?>';
            const periode = '<?= $tipe === "harian" ? date("Ymd", strtotime($tanggal)) : ($tipe === "bulanan" ? date("Ym", strtotime($bulan . "-01")) : $tahun); ?>';
            const filename = `Laporan_${tipe}_${periode}.pdf`;
            
            pdf.save(filename);
            
            loadingOverlay.style.display = 'none';
            
        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Gagal membuat PDF. Silakan coba lagi.');
            loadingOverlay.style.display = 'none';
            actionButtons.style.display = 'flex';
        }
    }
</script>

</body>
</html>