<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../helpers/response.php";

// GET ny ID 
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $q = "SELECT m.id,
                 m.nama, 
                 m.nim,
                 j.nama_jurusan AS jurusan 
          FROM mahasiswa m 
          LEFT JOIN jurusan j ON m.jurusan_id = j.id
          WHERE m.id = '$id'";

    $r = mysqli_query($koneksi, $q);

    if (!$r) {
        sendResponse(
            false,
            "query gagal: " . mysqli_error($koneksi),
            null,
            500
        );
    }


$data = mysqli_fetch_assoc($r);

if (!$data) {
    sendResponse(
        false,
        "mahasiswa tidak ditemukan",
        null,
        404
    );
}


sendResponse(true, "berhasil", $r, 200);
}