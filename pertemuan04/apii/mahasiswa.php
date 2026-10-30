<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':

        // GET berdasarkan ID
        if (isset($_GET['Id'])) {
            $id = (int) $_GET['Id'];

            $q = "SELECT
                    m.Id,
                    m.Nama,
                    m.Nim,
                    j.Nama_jurusan AS jurusan
                  FROM mahasiswa m
                  LEFT JOIN jurusan j ON m.Jurusan_id = j.Id
                  WHERE m.Id = '$id'";

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
                    "Mahasiswa tidak ditemukan",
                    null,
                    404
                );
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // Search
        if (isset($_GET['search'])) {
            $search = mysqli_real_escape_string(
                $koneksi,
                $_GET['search']
            );

            $q = "SELECT
                    m.Id,
                    m.Nama,
                    m.Nim,
                    j.Nama_jurusan AS jurusan
                  FROM mahasiswa m
                  LEFT JOIN jurusan j ON m.Jurusan_id = j.Id
                  WHERE m.Nama LIKE '%$search%'
                     OR m.Nim LIKE '%$search%'
                  ORDER BY m.Id DESC";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
                sendResponse(
                    false,
                    "query gagal: " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];

            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // Pagination
        if (isset($_GET['page']) || isset($_GET['limit'])) {

            $page = isset($_GET['page'])
                ? (int) $_GET['page']
                : 1;

            $limit = isset($_GET['limit'])
                ? (int) $_GET['limit']
                : 10;

            if ($page < 1) {
                $page = 1;
            }

            if ($limit < 1) {
                $limit = 10;
            }

            $offset = ($page - 1) * $limit;

            $q = "SELECT
                    m.Id,
                    m.Nama,
                    m.Nim,
                    j.Nama_jurusan AS jurusan
                  FROM mahasiswa m
                  LEFT JOIN jurusan j ON m.Jurusan_id = j.Id
                  ORDER BY m.Id DESC
                  LIMIT $limit OFFSET $offset";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
                sendResponse(
                    false,
                    "query gagal: " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];

            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // GET semua mahasiswa
        $q = "SELECT
                m.Id,
                m.Nama,
                m.Nim,
                j.Nama_jurusan AS jurusan
              FROM mahasiswa m
              LEFT JOIN jurusan j ON m.Jurusan_id = j.Id
              ORDER BY m.Id DESC";

        $r = mysqli_query($koneksi, $q);

        if (!$r) {
            sendResponse(
                false,
                "query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        $data = [];

        while ($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

        sendResponse(true, "berhasil", $data, 200);

        break;

    case 'POST':

        // Membaca input JSON
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (
            !$input ||
            !isset($input['Nama']) ||
            !isset($input['Nim']) ||
            !isset($input['Jurusan_id'])
        ) {
            sendResponse(
                false,
                "field Nama, Nim, Jurusan_id wajib diisi",
                null,
                400
            );
        }

        $Nama = trim($input['Nama']);
        $Nim = trim($input['Nim']);
        $Jurusan_id = (int) $input['Jurusan_id'];

        if ($Nama === '' || $Nim === '' || $Jurusan_id < 1) {
            sendResponse(
                false,
                "Nama, Nim, dan Jurusan_id tidak boleh kosong",
                null,
                400
            );
        }

        // Mengecek apakah jurusan tersedia
        $cek = mysqli_query(
            $koneksi,
            "SELECT Id FROM jurusan WHERE Id = $Jurusan_id"
        );

        if (!$cek) {
            sendResponse(
                false,
                "query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        if (mysqli_num_rows($cek) == 0) {
            sendResponse(
                false,
                "Jurusan_id tidak ditemukan",
                null,
                404
            );
        }

        // Menyimpan data mahasiswa
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO mahasiswa (Nama, Nim, Jurusan_id)
             VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            sendResponse(
                false,
                "query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $Nama,
            $Nim,
            $Jurusan_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            sendResponse(
                false,
                "gagal menambahkan mahasiswa: " .
                mysqli_stmt_error($stmt),
                null,
                500
            );
        }

        $idBaru = mysqli_insert_id($koneksi);

        sendResponse(
            true,
            "mahasiswa berhasil ditambahkan",
            [
                "Id" => $idBaru,
                "Nama" => $Nama,
                "Nim" => $Nim,
                "Jurusan_id" => $Jurusan_id
            ],
            201
        );

        break;

    default:

        sendResponse(
            false,
            "Method tidak diizinkan",
            null,
            405
        );

        break;
}

die;