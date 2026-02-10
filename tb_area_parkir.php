<?php

// koneksi database
$conn = mysqli_connect("localhost", "root", "", "db_parkir");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ambil data area parkir
$query = mysqli_query($conn, "SELECT * FROM tb_area_parkir");

// CEK ERROR QUERY
if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}

// CEK JUMLAH DATA
$jumlah_data = mysqli_num_rows($query);
echo "Jumlah data ditemukan: " . $jumlah_data . "<br><br>";

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Area Parkir</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; }
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            background: #0d6efd;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .content {
            margin-left: 240px;
            padding: 20px;
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<?php include 'sidebar.php'; ?>

<!-- CONTENT -->
<div class="content">

    <!-- HEADER -->
    <nav class="navbar navbar-light bg-white shadow-sm mb-4 rounded">
        <div class="container-fluid">
            <span class="navbar-brand">Data Area</span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                Data Area Parkir
            </div>
            <div class="card-body">

                <table class="table table-bordered table-striped text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Area</th>
                            <th>Kapasitas</th>
                            <th>Terisi</th>
                            <th>Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (mysqli_num_rows($query) > 0) {
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) {
                            $sisa = $row['kapasitas'] - $row['terisi'];
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nama_area']); ?></td>
                            <td><?= $row['kapasitas']; ?></td>
                            <td><?= $row['terisi']; ?></td>
                            <td><?= $sisa; ?></td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                <em>Tidak ada data area parkir</em>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

</body>
</html>