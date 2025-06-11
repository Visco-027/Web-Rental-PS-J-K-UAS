<?php
session_start();
require_once '../helper/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 'konsumen') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['booking_data'])) {
    header("Location: dashboard.php");
    exit();
}

$booking_data = $_SESSION['booking_data'];
$opsi_sewa = $booking_data['opsi_sewa'];
$durasi_sewa = $booking_data['durasi_sewa'];
$nama = $booking_data['nama'];
$nomor = $booking_data['nomor'];
$id_booking = $booking_data['id_booking'];
$tipe_transaksi = $booking_data['tipe_transaksi'] ?? 'takeaway';
$waktu_mulai = $booking_data['waktu_mulai'] ?? null;

// Validasi ulang total harga
$total_harga = 0;
if ($tipe_transaksi === 'takeaway') {
    $total_harga = (in_array('PS3', $opsi_sewa) ? 30000 : 0) + (in_array('TV32', $opsi_sewa) ? 35000 : 0);
} else {
    $chanel_query = "SELECT jenis_ps FROM tb_chanel WHERE nama_chanel = ?";
    $stmt = mysqli_prepare($conn, $chanel_query);
    mysqli_stmt_bind_param($stmt, "s", $opsi_sewa[0]);
    mysqli_stmt_execute($stmt);
    $chanel_result = mysqli_stmt_get_result($stmt);
    $chanel = mysqli_fetch_object($chanel_result);
    mysqli_stmt_close($stmt);

    $harga_query = "SELECT harga FROM tb_harga WHERE jenis_ps = ? AND menit = 60";
    $stmt = mysqli_prepare($conn, $harga_query);
    mysqli_stmt_bind_param($stmt, "s", $chanel->jenis_ps);
    mysqli_stmt_execute($stmt);
    $harga_result = mysqli_stmt_get_result($stmt);
    $harga = mysqli_fetch_object($harga_result)->harga ?? ($chanel->jenis_ps === 'PS3' ? 5000 : 8000);
    mysqli_stmt_close($stmt);

    $total_harga = $durasi_sewa * $harga;
}

$rekening = "1234-5678-9012 a/n J&K PlayStation";
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pembayaran'])) {
    $sewa_ps3 = $tipe_transaksi === 'takeaway' && in_array('PS3', $opsi_sewa) ? 1 : 0;
    $sewa_tv32 = $tipe_transaksi === 'takeaway' && in_array('TV32', $opsi_sewa) ? 1 : 0;

    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $target_dir = "../assets/uploads/";
        $file_name = time() . "_" . basename($_FILES['bukti_transfer']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $target_file)) {
            $bukti_transfer = $file_name;
            $update_query = "UPDATE tb_booking SET bukti_transfer = ?, total_harga = ?, status = 'pending' WHERE id_booking = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sdi", $bukti_transfer, $total_harga, $id_booking);
                if (mysqli_stmt_execute($stmt)) {
                    if ($tipe_transaksi === 'takeaway') {
                        if ($sewa_ps3) {
                            mysqli_query($conn, "UPDATE tb_takeaway_inventory SET stok = stok - 1 WHERE jenis_item = 'PS3' AND stok > 0");
                        }
                        if ($sewa_tv32) {
                            mysqli_query($conn, "UPDATE tb_takeaway_inventory SET stok = stok - 1 WHERE jenis_item = 'TV32' AND stok > 0");
                        }
                        mysqli_query($conn, "UPDATE tb_takeaway_inventory SET status = IF(stok > 0, 'available', 'unavailable')");
                    }
                    unset($_SESSION['booking_data']);
                    header("Location: dashboard.php?success=1");
                    exit();
                } else {
                    $message = "Gagal mengupdate booking: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Gagal menyiapkan pernyataan SQL: " . mysqli_error($conn);
            }
        } else {
            $message = "Gagal mengunggah bukti transfer.";
        }
    } else {
        $message = "Harap unggah bukti transfer.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pembayaran - J&K PlayStation</title>
    <link rel="stylesheet" href="../assets/css/konsumen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <nav style="position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background-color: #111; color: white; display: flex; justify-content: space-between; align-items: center; padding: 10px 30px;">
        <div><strong>J&K PlayStation</strong></div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center dropdown-toggle text-white text-decoration-none" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="../assets/img/saya.png" alt="profile" width="80" height="48" class="rounded-circle me-2">
                    <?php echo htmlspecialchars($_SESSION['user_username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="edit_password.php">Edit Password</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container" style="padding-top: 80px; min-height: 100vh; background: #000; color: #fff; display: flex; justify-content: center; align-items: center;">
        <div class="glass-card" style="background: rgba(30, 30, 30, 0.7); backdrop-filter: blur(10px); box-shadow: 0 8px 24px rgba(206, 54, 155, 0.4); padding: 30px; border-radius: 16px; width: 100%; max-width: 500px; text-align: center;">
            <a href="dashboard.php" class="btn btn-warning mb-3" style="width:100%;font-family:'Orbitron',sans-serif;font-weight:bold;">
                <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h2 class="section-title" style="font-size: 1.8em; color: #ff6600; margin-bottom: 20px;">Konfirmasi Pembayaran</h2>
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="message success" style="margin-bottom: 20px;">
                    Pembayaran berhasil diajukan! Silakan tunggu konfirmasi dari admin. Status: <span class="badge bg-warning">Pending</span>
                </div>
            <?php elseif ($message): ?>
                <div class="message error" style="margin-bottom: 20px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="text-white">Tipe Booking</label>
                    <input type="text" class="form-control" value="<?php echo $tipe_transaksi === 'main_di_tempat' ? 'Main di Tempat' : 'Takeaway'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="text-white">Nama Pemesan</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($nama); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="text-white">Nomor Telepon</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($nomor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="text-white">Pilihan Sewa</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $opsi_sewa)); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="text-white">Durasi Sewa (jam)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($durasi_sewa); ?>" readonly>
                </div>
                <?php if ($tipe_transaksi === 'main_di_tempat' && $waktu_mulai): ?>
                    <div class="form-group">
                        <label class="text-white">Waktu Mulai</label>
                        <input type="text" class="form-control" value="<?php echo date('d-m-Y H:i', strtotime($waktu_mulai)); ?>" readonly>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="text-white">Total Harga</label>
                    <input type="text" class="form-control" value="Rp<?php echo number_format($total_harga, 0, ',', '.'); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="text-white">Nomor Rekening</label>
                    <input type="text" class="form-control" value="<?php echo $rekening; ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="text-white">Unggah Bukti Transfer</label>
                    <input type="file" name="bukti_transfer" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" name="submit_pembayaran" class="btn btn-danger btn-block mt-3">Kirim Bukti Pembayaran</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>