<?php
function logAktivitas($koneksi, $id_user, $aktivitas) {
    $aktivitas = mysqli_real_escape_string($koneksi, $aktivitas);

    mysqli_query(
        $koneksi,
        "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas)
         VALUES ('$id_user', '$aktivitas', NOW())"
    );
}
