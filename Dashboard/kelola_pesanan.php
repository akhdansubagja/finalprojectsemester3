<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

// Cek apakah pengguna adalah admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { // Menggunakan user_role
    header("Location: ../unauthorized.html"); // Ganti dengan halaman yang sesuai
    exit();
}

require_once '../backend/koneksi.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Atur header untuk mencegah caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // Untuk HTTP 1.1
header("Pragma: no-cache"); // Untuk HTTP 1.0
header("Expires: 0"); // Untuk semua

// Fungsi untuk memperbarui status pesanan

// Panggil fungsi untuk memperbarui status pesanan
updateOrderStatus($conn);

// Ambil status pemesanan dari parameter URL jika ada
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';

// Query untuk mengambil data dari tabel pesanan dan nama paket
$sql = "SELECT p.id_pesanan, p.id_paket, p.user_id, p.nama_pemesan, p.email, p.jumlah_peserta, p.harga_total, p.tanggal_pesan, p.status_pembayaran, p.status_pesanan, pk.nama_paket 
FROM pesanan p 
JOIN paket pk ON p.id_paket = pk.id_paket";

// Tambahkan filter berdasarkan status jika tidak 'semua'
if ($status_filter !== 'semua') {
    $sql .= " WHERE p.status_pesanan = '" . $conn->real_escape_string($status_filter) . "'";
}

// Urutkan berdasarkan tanggal pemesanan terbaru
$sql .= " ORDER BY p.tanggal_pesan DESC"; // Menambahkan urutan berdasarkan tanggal pemesanan

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin.php">Manajemen Pesanan</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Kelola Pesanan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../Backend/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container my-5">
    <h1 class="text-center mb-4">Daftar Pesanan</h1>

    <!-- Tampilkan pesan jika ada -->
    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <!-- Dropdown untuk menyortir berdasarkan status -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="semua" <?= $status_filter == 'semua' ? 'selected' : '' ?>>Semua</option>
                <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Dikonfirmasi" <?= $status_filter == 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                <option value="Dibatalkan" <?= $status_filter == 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                <option value="Selesai" <?= $status_filter == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
            </select>
        </div>
    </form>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Nomor Pemesanan</th>
                <th>Nama Pemesan</th>
                <th>Email</th>
                <th>Jumlah Peserta</th>
                <th>Harga Total</th>
                <th>Tanggal Pesan</th>
                <th>Status Pembayaran</th>
                <th>Status</th>
                <th>Nama Paket</th> <!-- Kolom untuk nama paket -->
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>
                            <td>' . $row['id_pesanan'] . '</td>
                            <td>' . htmlspecialchars($row['nama_pemesan']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . htmlspecialchars($row['jumlah_peserta']) . '</td>
                            <td>Rp ' . number_format($row['harga_total'], 2, ',', '.') . '</td>
                            <td>' . htmlspecialchars($row['tanggal_pesan']) . '</td>
                            <td>' . htmlspecialchars($row['status_pembayaran']) . '</td>
                            <td>' . (isset($row['status_pesanan']) ? htmlspecialchars($row['status_pesanan']) : 'Tidak ada status') . '</td>
                            <td>' . htmlspecialchars($row['nama_paket']) . '</td> <!-- Tampilkan nama paket -->
                            <td>
                                <div class="btn-group" role="group" aria-label="Aksi">
                                    <a href="edit_pesanan.php?id=' . htmlspecialchars($row['id_pesanan']) . '" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="delete_pesanan.php?id=' . htmlspecialchars($row['id_pesanan']) . '" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm(\'Yakin ingin menghapus pesanan ini?\')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                    <a href="detail_pesanan.php?id=' . htmlspecialchars($row['id_pesanan']) . '" 
                                       class="btn btn-info btn-sm">
                                        <i class="bi bi-eye-fill"></i> Detail
                                    </a>
                                </div>
                            </td>
                          </tr>';
                }
            } else {
                echo '<tr><td colspan="10" class="text-center">Tidak ada pesanan tersedia.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
