<?php
session_start();
require_once '../helper/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Inisialisasi filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'month';
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m'); // Default: bulan saat ini
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y'); // Default: tahun saat ini
$week = isset($_GET['week']) ? (int)$_GET['week'] : date('W'); // Default: minggu saat ini

// Validasi nilai filter
if ($month < 1 || $month > 12) $month = date('m');
if ($year < 2020 || $year > date('Y')) $year = date('Y');
if ($week < 1 || $week > 53) $week = date('W');

// Query berdasarkan filter
$where_clause = "WHERE b.status IN ('confirmed', 'completed')";
if ($filter === 'month') {
    $where_clause .= " AND MONTH(b.tgl_booking) = $month AND YEAR(b.tgl_booking) = $year";
} elseif ($filter === 'week') {
    $where_clause .= " AND WEEK(b.tgl_booking, 1) = $week AND YEAR(b.tgl_booking) = $year";
}

// Ambil data histori transaksi
$booking_query = "SELECT b.*, u.nama_lengkap AS nama_pemesan, u.nomor_telepon 
                 FROM tb_booking b 
                 JOIN tb_user u ON b.id_user = u.id_user 
                 $where_clause 
                 ORDER BY b.tgl_booking DESC";
$booking_result = mysqli_query($conn, $booking_query);

if (!$booking_result) {
    die("Error dalam query booking: " . mysqli_error($conn));
}

$bookings = [];
while ($row = mysqli_fetch_object($booking_result)) {
    // Hitung tgl_selesai berdasarkan waktu_mulai + durasi_sewa
    if ($row->waktu_mulai && $row->durasi_sewa) {
        $row->tgl_selesai = date('Y-m-d H:i:s', strtotime($row->waktu_mulai) + ($row->durasi_sewa * 3600));
    }
    $bookings[] = $row;
}

// Statistik sederhana berdasarkan filter
$total_query = "SELECT COUNT(*) as total, SUM(durasi_sewa) as total_durasi, SUM(total_harga) as total_pendapatan 
                FROM tb_booking b 
                $where_clause";
error_log("Total Query: " . $total_query); // Debugging
$total_result = mysqli_query($conn, $total_query);
if (!$total_result) {
    die("Error dalam query statistik: " . mysqli_error($conn));
}
$stats = mysqli_fetch_assoc($total_result);

// Daftar bulan untuk dropdown
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Histori Transaksi - J&K PlayStation</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-form select, .filter-form button {
            background-color: #333;
            color: #fff;
            border: 1px solid #555;
        }
        .filter-form button:hover {
            background-color: #ff6600;
            border-color: #ff6600;
        }
    </style>
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
                    <?php echo htmlspecialchars($_SESSION['user_username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1" style="background: rgba(30, 30, 30, 0.7); backdrop-filter: blur(10px);">
                    <li><a class="dropdown-item" href="edit_password.php" style="color: #fff;">Edit Password</a></li>
                    <li><a class="dropdown-item" href="../logout.php" style="color: #fff;">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container" style="padding-top: 80px; min-height: 100vh; color: #fff;">
        <h1 class="dashboard-title" style="font-size: 2.5em; color: #ff6600; text-transform: uppercase; text-shadow: 0 0 10px rgba(255, 102, 0, 0.5); margin-bottom: 20px; text-align: center;">Histori Transaksi</h1>

        <!-- Filter Form -->
        <div class="filter-form">
            <select name="filter" id="filter" onchange="toggleFilterOptions()">
                <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Per Bulan</option>
                <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Per Minggu</option>
            </select>
            <div id="month-filter" style="display: <?php echo $filter === 'month' ? 'flex' : 'none'; ?>; gap: 10px;">
                <select name="month" id="month">
                    <?php foreach ($months as $m => $month_name): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo $month_name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" id="year">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div id="week-filter" style="display: <?php echo $filter === 'week' ? 'flex' : 'none'; ?>; gap: 10px;">
                <select name="week" id="week">
                    <?php for ($w = 1; $w <= 53; $w++): ?>
                        <option value="<?php echo $w; ?>" <?php echo $w == $week ? 'selected' : ''; ?>><?php echo sprintf("Minggu %d", $w); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year_week" id="year_week">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button onclick="applyFilter()">Tampilkan</button>
        </div>

        <!-- Statistik -->
        <div style="margin-bottom: 20px;">
            <strong>Total Transaksi:</strong> <?php echo $stats['total']; ?> | 
            <strong>Total Durasi:</strong> <?php echo $stats['total_durasi'] ?? 0; ?> jam | 
            <strong>Total Pendapatan:</strong> Rp <?php echo number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.'); ?>
        </div>

        <!-- Daftar Histori Transaksi -->
        <section class="ps-section">
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>ID Booking</th>
                            <th>Nama Pemesan</th>
                            <th>Nomor Telepon</th>
                            <th>Opsi Sewa</th>
                            <th>Durasi (jam)</th>
                            <th>Tanggal Booking</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Total Harga</th>
                            <th>Bukti Transfer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking->id_booking; ?></td>
                                <td><?php echo htmlspecialchars($booking->nama_pemesan); ?></td>
                                <td><?php echo htmlspecialchars($booking->nomor_telepon); ?></td>
                                <td><?php echo $booking->opsi_sewa ?: '-'; ?></td>
                                <td><?php echo $booking->durasi_sewa; ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($booking->tgl_booking)); ?></td>
                                <td><?php echo $booking->waktu_mulai ? date('d-m-Y H:i', strtotime($booking->waktu_mulai)) : '-'; ?></td>
                                <td><?php echo isset($booking->tgl_selesai) ? date('d-m-Y H:i', strtotime($booking->tgl_selesai)) : '-'; ?></td>
                                <td>Rp <?php echo number_format($booking->total_harga ?? 0, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($booking->bukti_transfer): ?>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalBukti<?php echo $booking->id_booking; ?>">
                                            <i class="fas fa-image"></i> Lihat Bukti
                                        </button>
                                    <?php else: ?>
                                        <span>Belum Ada</span>
                                    <?php endif; ?>
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
                <p class="no-data">Tidak ada histori transaksi yang tersedia untuk periode ini.</p>
            <?php endif; ?>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleFilterOptions() {
            const filter = document.getElementById('filter').value;
            document.getElementById('month-filter').style.display = filter === 'month' ? 'flex' : 'none';
            document.getElementById('week-filter').style.display = filter === 'week' ? 'flex' : 'none';
        }

        function applyFilter() {
            const filter = document.getElementById('filter').value;
            let url = 'histori_transaksi.php?filter=' + filter;
            if (filter === 'month') {
                const month = document.getElementById('month').value;
                const year = document.getElementById('year').value;
                url += '&month=' + month + '&year=' + year;
            } else if (filter === 'week') {
                const week = document.getElementById('week').value;
                const year = document.getElementById('year_week').value;
                url += '&week=' + week + '&year=' + year;
            }
            window.location.href = url;
        }
    </script>
</body>
</html>