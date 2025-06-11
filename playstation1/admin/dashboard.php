<?php
session_start();
require_once '../helper/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ambil data user admin
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username FROM tb_user WHERE id_user = ?";
$stmt_user = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$user_result = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt_user);
$username = $user_data['username'] ?? 'Admin';

// Ambil data ketersediaan PlayStation
$ps_query = "SELECT c.id_chanel, c.nama_chanel, c.jenis_ps, c.status, 
                    MAX(DATE_ADD(b.waktu_mulai, INTERVAL b.durasi_sewa HOUR)) as ready_on
             FROM tb_chanel c
             LEFT JOIN tb_booking b ON c.id_chanel = b.id_chanel 
             WHERE (b.status = 'confirmed' AND NOW() <= DATE_ADD(b.waktu_mulai, INTERVAL b.durasi_sewa HOUR))
             OR b.id_booking IS NULL
             GROUP BY c.id_chanel";
$ps_result = mysqli_query($conn, $ps_query);
$playstations = [];
while ($row = mysqli_fetch_object($ps_result)) {
    $playstations[] = $row;
}
mysqli_free_result($ps_result);

// Proses ubah status PS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_ps_status'])) {
    $id_chanel = $_POST['id_chanel'];
    $status = $_POST['status'];
    $update_query = "UPDATE tb_chanel SET status = ? WHERE id_chanel = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $status, $id_chanel);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: dashboard.php");
    exit();
}

// Ambil stok takeaway
$stok_query = "SELECT jenis_item, stok FROM tb_takeaway_inventory";
$stok_result = mysqli_query($conn, $stok_query);
$stok = [];
while ($row = mysqli_fetch_object($stok_result)) {
    $stok[$row->jenis_item] = $row;
}
mysqli_free_result($stok_result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - J&K PlayStation</title>
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
                    <?php echo htmlspecialchars($username); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1" style="background: rgba(30, 30, 30, 0.7); backdrop-filter: blur(10px);">
                    <li><a class="dropdown-item" href="../edit_password.php" style="color: #fff;">Edit Password</a></li>
                    <li><a class="dropdown-item" href="../logout.php" style="color: #fff;">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container" style="padding-top: 80px; min-height: 100vh; color: #fff;">
        <h1 class="dashboard-title" style="font-size: 2.5em; color: #ff6600; text-transform: uppercase; text-shadow: 0 0 10px rgba(255, 102, 0, 0.5); margin-bottom: 20px; text-align: center;">Admin Dashboard</h1>

        <!-- Ketersediaan PlayStation -->
        <section class="ps-section">
            <h2 class="section-title">Ketersediaan PlayStation</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-ps">PS</th>
                            <th class="col-jenis">Jenis</th>
                            <th class="col-status">Status</th>
                            <th class="col-ready">Ready On</th>
                            <th class="col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($playstations)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($playstations as $ps): ?>
                                <tr>
                                    <td class="col-no"><?php echo $no++; ?></td>
                                    <td class="col-ps"><?php echo htmlspecialchars($ps->nama_chanel); ?></td>
                                    <td class="col-jenis"><?php echo htmlspecialchars($ps->jenis_ps); ?></td>
                                    <td class="col-status">
                                        <span class="ps-status <?php echo $ps->status === 'unavailable' ? 'unavailable' : ''; ?>">
                                            <?php echo $ps->status === 'available' ? 'READY' : 'NOT READY'; ?>
                                        </span>
                                    </td>
                                    <td class="col-ready">
                                        <?php echo $ps->ready_on ? date('d-m-Y H:i', strtotime($ps->ready_on)) : '-'; ?>
                                    </td>
                                    <td class="col-aksi">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="id_chanel" value="<?php echo $ps->id_chanel; ?>">
                                            <select name="status" class="form-select form-select-sm custom-select" style="display: inline-block; width: auto;" onchange="this.form.submit()">
                                                <option value="available" <?php echo $ps->status === 'available' ? 'selected' : ''; ?>>Ready</option>
                                                <option value="unavailable" <?php echo $ps->status === 'unavailable' ? 'selected' : ''; ?>>Not Ready</option>
                                            </select>
                                            <input type="hidden" name="update_ps_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">Tidak ada data PlayStation tersedia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Stok Takeaway -->
        <section class="ps-section">
            <h2 class="section-title">Stok Takeaway</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-jenis">Jenis</th>
                            <th>Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stok)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($stok as $item): ?>
                                <tr>
                                    <td class="col-no"><?php echo $no++; ?></td>
                                    <td class="col-jenis"><?php echo htmlspecialchars($item->jenis_item); ?></td>
                                    <td><?php echo $item->stok; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-data">Tidak ada data stok takeaway tersedia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>