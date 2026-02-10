        <!-- ===== SIDEBAR (HTML KAMU, TIDAK DIUBAH) ===== -->
        <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
            <h5 class="text-center mb-4">ADMIN PARKIR </h5>
            <ul class="nav flex-column">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-item mb-2">
                    <a href="index.php" class="nav-link text-white">Data User</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="tarif.php" class="nav-link text-white">Data Tarif</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="area.php" class="nav-link text-white">Data Area Parkir</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kendaraan.php" class="nav-link text-white">Data Kendaraan</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="log_aktivitas.php" class="nav-link text-white">Log Aktivitas</a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'petugas'): ?>
                <li class="nav-item mb-2">
                    <a href="transaksi_masuk.php" class="nav-link text-white">Transaksi Masuk</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="transaksi_keluar.php" class="nav-link text-white">Transaksi Keluar</a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'owner'): ?>
                <li class="nav-item mb-2">
                    <a href="riwayat_transaksi.php" class="nav-link text-white">Riwayat Transaksi</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
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
        