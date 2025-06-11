<?php
session_start();
require_once '../helper/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ambil data booking dengan status pending
$booking_query = "SELECT b.*, u.nama_lengkap AS nama_pemesan, u.nomor_telepon 
                 FROM tb_booking b 
                 JOIN tb_user u ON b.id_user = u.id_user 
                 WHERE b.status = 'pending' 
                 ORDER BY b.tgl_booking DESC";
$booking_result = mysqli_query($conn, $booking_query);
$bookings = [];
while ($row = mysqli_fetch_object($booking_result)) {
    $bookings[] = $row;
}
mysqli_free_result($booking_result);

// Proses perubahan status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id_booking = $_POST['id_booking'];
    $status = $_POST['status'];

    // Ambil data booking untuk cek tipe_transaksi, sewa_ps3, sewa_tv32
    $check_query = "SELECT tipe_transaksi, sewa_ps3, sewa_tv32 FROM tb_booking WHERE id_booking = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $id_booking);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $booking_data = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);

    // Update status dan waktu_mulai jika confirmed
    $update_query = "UPDATE tb_booking SET status = ?, waktu_mulai = NOW() WHERE id_booking = ?";
    if ($status === 'canceled') {
        $update_query = "UPDATE tb_booking SET status = ? WHERE id_booking = ?";
    }
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $status, $id_booking);
    if (mysqli_stmt_execute($stmt)) {
        if ($status === 'canceled' && $booking_data['tipe_transaksi'] === 'takeaway') {
            // Tambah stok kalau takeaway dibatalkan
            if ($booking_data['sewa_ps3'] == 1) {
                $update_ps3_query = "UPDATE tb_takeaway_inventory SET stok = stok + 1 WHERE jenis_item = 'PS3'";
                mysqli_query($conn, $update_ps3_query);
            }
            if ($booking_data['sewa_tv32'] == 1) {
                $update_tv32_query = "UPDATE tb_takeaway_inventory SET stok = stok + 1 WHERE jenis_item = 'TV32'";
                mysqli_query($conn, $update_tv32_query);
            }
            // Update status inventory berdasarkan stok
            $update_inventory_status = "UPDATE tb_takeaway_inventory SET status = IF(stok > 0, 'available', 'unavailable')";
            mysqli_query($conn, $update_inventory_status);
        }
    }
    mysqli_stmt_close($stmt);
    header("Location: konfirmasi_booking.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Booking - J&K PlayStation</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav style="position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background-color: #111; color: white; display: flex; justify-content: space-between; align-items: center; padding: 10px 30px; font-family: 'Orbitron', sans-serif;">
        <div><strong>J&K PlayStation - Admin Panel</strong></div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <a href="dashboard.php" style="color: white; text-decoration: none; font-weight: bold;">Dashboard</a>
            <a href="konfirmasi_booking.php" style="color: white; text-decoration: none; font-weight: bold;">Konfirmasi Booking</a>
            <a href="histori_transaksi.php" style="color: white; text-decoration: none; font-weight: bold;">Histori Transaksi</a>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center dropdown-toggle text-white text-decoration-none" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="../assets/img/saya.png" alt="profile" width="80" height="48" class="rounded-circle me-2">
                    <?php echo htmlspecialchars($_SESSION['user_username'] ?? 'Admin'); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1" style="background: rgba(30, 30, 30, 0.7); backdrop-filter: blur(10px);">
                    <li><a class="dropdown-item" href="edit_password.php" style="color: #fff;">Edit Password</a></li>
                    <li><a class="dropdown-item" href="../logout.php" style="color: #fff;">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container" style="padding-top: 80px; min-height: 100vh; color: #fff;">
        <h1 class="dashboard-title" style="font-size: 2.5em; color: #ff6600; text-transform: uppercase; text-shadow: 0 0 10px rgba(255, 102, 0, 0.5); margin-bottom: 20px; text-align: center;">Konfirmasi Booking</h1>

        <!-- Daftar Booking -->
        <section class="ps-section">
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>ID Booking</th>
                            <th>Nama Pemesan</th>
                            <th>Nomor Telepon</th>
                            <th>Tipe</th>
                            <th>Detail</th>
                            <th>Durasi (jam)</th>
                            <th>Tanggal Booking</th>
                            <th>Bukti Transfer</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking->id_booking; ?></td>
                                <td><?php echo htmlspecialchars($booking->nama_pemesan); ?></td>
                                <td><?php echo htmlspecialchars($booking->nomor_telepon); ?></td>
                                <td><?php echo $booking->tipe_transaksi === 'main_di_tempat' ? 'Main di Tempat' : 'Takeaway'; ?></td>
                                <td>
                                    <?php
                                    if ($booking->tipe_transaksi === 'main_di_tempat') {
                                        echo htmlspecialchars($booking->opsi_sewa) . ', Mulai: ' . ($booking->waktu_mulai ? date('d-m-Y H:i', strtotime($booking->waktu_mulai)) : '-');
                                    } else {
                                        $opsi = [];
                                        if ($booking->sewa_ps3) $opsi[] = 'PS3';
                                        if ($booking->sewa_tv32) $opsi[] = 'TV32';
                                        echo implode(', ', $opsi);
                                    }
                                    ?>
                                </td>
                                <td><?php echo $booking->durasi_sewa; ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($booking->tgl_booking)); ?></td>
                                <td>
                                    <?php if ($booking->bukti_transfer): ?>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalBukti<?php echo $booking->id_booking; ?>">
                                            <i class="fas fa-image"></i> Lihat Bukti
                                        </button>
                                    <?php else: ?>
                                        <span>Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="id_booking" value="<?php echo $booking->id_booking; ?>">
                                        <select name="status" class="form-select form-select-sm custom-select" style="display: inline-block; width: auto;" onchange="this.form.submit()">
                                            <option value="null">Aksi</option>
                                            <option value="confirmed">Acc</option>
                                            <option value="canceled">Reject</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                            <!-- Modal untuk Bukti Transfer -->
                            <?php if ($booking->bukti_transfer): ?>
                                <div class="modal fade" id="modalBukti<?php echo $booking->id_booking; ?>" tabindex="-1" aria-labelledby="modalBuktiLabel<?php echo $booking->id_booking; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalBuktiLabel<?php echo $booking->id_booking; ?>">Bukti Transfer - ID Booking <?php echo $booking->id_booking; ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <img src="../assets/uploads/<?php echo htmlspecialchars($booking->bukti_transfer); ?>" alt="Bukti Transfer" class="img-fluid">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($bookings)): ?>
                <p class="no-data">Tidak ada booking yang tersedia untuk dikonfirmasi.</p>
            <?php endif; ?>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>