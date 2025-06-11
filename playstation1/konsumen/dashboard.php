<?php
session_start();
require_once '../helper/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 'konsumen') {
    header("Location: ../index.php");
    exit();
}

// Ambil data user dari tb_user
$user_id = $_SESSION['user_id'];
$user_query = "SELECT nama_lengkap, nomor_telepon, username FROM tb_user WHERE id_user = ?";
$stmt_user = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$user_result = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt_user);

$nama_lengkap = $user_data['nama_lengkap'] ?? '';
$nomor_telepon = $user_data['nomor_telepon'] ?? '';
$username = $user_data['username'] ?? '';


// Perbarui status booking yang sudah selesai
$update_status_query = "UPDATE tb_booking 
                        SET status = 'completed' 
                        WHERE tipe_transaksi = 'main_di_tempat' 
                        AND status IN ('pending', 'confirmed') 
                        AND DATE_ADD(waktu_mulai, INTERVAL durasi_sewa HOUR) < NOW()";
mysqli_query($conn, $update_status_query);

// Sinkronkan status di tb_chanel berdasarkan booking aktif
$update_chanel_status = "UPDATE tb_chanel c
                         SET status = IF(
                             EXISTS (
                                 SELECT 1 FROM tb_booking b 
                                 WHERE b.id_chanel = c.id_chanel 
                                 AND b.status IN ('pending', 'confirmed') 
                                 AND NOW() BETWEEN b.waktu_mulai AND DATE_ADD(b.waktu_mulai, INTERVAL b.durasi_sewa HOUR)
                             ), 'unavailable', 'available')";
mysqli_query($conn, $update_chanel_status);

// Ambil filter untuk PS
$filter_jenis = $_GET['filter_jenis'] ?? 'all';
$filter_status = $_GET['filter_status'] ?? 'all';
$ps_query = "SELECT * FROM tb_chanel WHERE 1=1";
$params = [];
$types = "";
if ($filter_jenis !== 'all') {
    $ps_query .= " AND jenis_ps = ?";
    $params[] = $filter_jenis;
    $types .= "s";
}
if ($filter_status !== 'all') {
    $ps_query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
$stmt_ps = mysqli_prepare($conn, $ps_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_ps, $types, ...$params);
}
mysqli_stmt_execute($stmt_ps);
$ps_result = mysqli_stmt_get_result($stmt_ps);
$playstations = mysqli_fetch_all($ps_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_ps);

// Hitung jumlah PS tersedia
$available_count = mysqli_fetch_object(mysqli_query($conn, "SELECT COUNT(*) as count FROM tb_chanel WHERE status = 'available'"))->count;
$total_count = mysqli_fetch_object(mysqli_query($conn, "SELECT COUNT(*) as count FROM tb_chanel"))->count;

// Ambil stok takeaway
$stok_query = "SELECT * FROM tb_takeaway_inventory WHERE status = 'available'";
$stok_result = mysqli_query($conn, $stok_query);
$stok = [];
while ($row = mysqli_fetch_object($stok_result)) {
    $stok[$row->jenis_item] = $row;
}

// Ambil riwayat booking konsumen
$filter_booking_status = $_GET['filter_booking_status'] ?? 'all';
$riwayat_query = "SELECT * FROM tb_booking WHERE id_user = ?";
$params = [$user_id];
$types = "i";
if ($filter_booking_status !== 'all') {
    $riwayat_query .= " AND status = ?";
    $params[] = $filter_booking_status;
    $types .= "s";
}
$riwayat_query .= " ORDER BY tgl_booking DESC";
$stmt_riwayat = mysqli_prepare($conn, $riwayat_query);
mysqli_stmt_bind_param($stmt_riwayat, $types, ...$params);
mysqli_stmt_execute($stmt_riwayat);
$riwayat_result = mysqli_stmt_get_result($stmt_riwayat);
$riwayat = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_riwayat);

// Ambil daftar game
$games = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM tb_game"), MYSQLI_ASSOC);

// Ambil harga untuk main di tempat
$prices = [];
$harga_result = mysqli_query($conn, "SELECT jenis_ps, harga FROM tb_harga WHERE menit = 60");
while ($row = mysqli_fetch_assoc($harga_result)) {
    $prices[$row['jenis_ps']] = $row['harga'];
}

// Tentukan waktu mulai minimum
$min_waktu_mulai = [];
$chanel_result = mysqli_query($conn, "SELECT id_chanel, status FROM tb_chanel");
while ($chanel = mysqli_fetch_object($chanel_result)) {
    if ($chanel->status === 'available') {
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $now->modify('+15 minutes');
        $min_waktu_mulai[$chanel->id_chanel] = $now->format('Y-m-d H:i');
    } else {
        $latest_query = "SELECT MAX(DATE_ADD(waktu_mulai, INTERVAL durasi_sewa HOUR)) as selesai 
                        FROM tb_booking 
                        WHERE id_chanel = ? AND status IN ('pending', 'confirmed')";
        $stmt = mysqli_prepare($conn, $latest_query);
        mysqli_stmt_bind_param($stmt, "i", $chanel->id_chanel);
        mysqli_stmt_execute($stmt);
        $latest_result = mysqli_stmt_get_result($stmt);
        $latest = mysqli_fetch_object($latest_result);
        $min_waktu_mulai[$chanel->id_chanel] = $latest->selesai
            ? date('Y-m-d H:i', strtotime($latest->selesai))
            : (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->modify('+15 minutes')->format('Y-m-d H:i');
        mysqli_stmt_close($stmt);
    }
}

// Proses booking takeaway
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $opsi_sewa = $_POST['opsi_sewa'] ?? [];
    $durasi_sewa = (int)($_POST['durasi_sewa'] ?? 0);
    $nama = $_POST['nama'] ?? $user_data['nama_lengkap'];
    $nomor = $_POST['nomor'] ?? $user_data['nomor_telepon'];

    if (empty($opsi_sewa)) {
        $message = "Harap pilih minimal satu opsi sewa (PS3 atau TV32)!";
    } elseif ($durasi_sewa < 12) {
        $message = "Durasi sewa minimal 12 jam!";
    } elseif (empty($nama)) {
        $message = "Nama wajib diisi di profil Anda!";
    } elseif (empty($nomor)) {
        $message = "Nomor telepon wajib diisi di profil Anda!";
    } elseif (in_array('PS3', $opsi_sewa) && (!isset($stok['PS3']) || $stok['PS3']->stok <= 0)) {
        $message = "Stok PS3 sudah habis!";
    } elseif (in_array('TV32', $opsi_sewa) && (!isset($stok['TV32']) || $stok['TV32']->stok <= 0)) {
        $message = "Stok TV32 sudah habis!";
    } else {
        $sewa_ps3 = in_array('PS3', $opsi_sewa) ? 1 : 0;
        $sewa_tv32 = in_array('TV32', $opsi_sewa) ? 1 : 0;
        $total_harga = ($sewa_ps3 ? 30000 : 0) + ($sewa_tv32 ? 35000 : 0);
        $opsi_sewa_str = implode(', ', $opsi_sewa);
        $tipe_transaksi = 'takeaway';

        $insert_query = "INSERT INTO tb_booking (id_user, durasi_sewa, sewa_ps3, sewa_tv32, opsi_sewa, total_harga, status, tgl_booking, tipe_transaksi) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "iiissis", $user_id, $durasi_sewa, $sewa_ps3, $sewa_tv32, $opsi_sewa_str, $total_harga, $tipe_transaksi);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['booking_data'] = [
                'id_booking' => mysqli_insert_id($conn),
                'opsi_sewa' => $opsi_sewa,
                'durasi_sewa' => $durasi_sewa,
                'nama' => $nama,
                'nomor' => $nomor
            ];
            header("Location: konfirmasi_pembayaran.php");
            exit();
        } else {
            $message = "Gagal menyimpan booking: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Proses booking main di tempat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_onsite'])) {
    $id_chanel = $_POST['id_chanel'] ?? '';
    $durasi_sewa = (int)($_POST['durasi_sewa'] ?? 0);
    $waktu_mulai = $_POST['waktu_mulai'] ?? '';
    $tipe_transaksi = 'main_di_tempat';
    $status = 'pending';
    $tgl_booking = date('Y-m-d H:i:s');

    if (empty($id_chanel)) {
        $message = "Harap pilih PlayStation!";
    } elseif ($durasi_sewa < 1 || $durasi_sewa > 12) {
        $message = "Durasi sewa harus antara 1 hingga 12 jam!";
    } elseif (empty($waktu_mulai)) {
        $message = "Harap pilih waktu mulai!";
    } else {
        $waktu_input = new DateTime($waktu_mulai, new DateTimeZone('Asia/Jakarta'));
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $min_waktu = new DateTime($min_waktu_mulai[$id_chanel], new DateTimeZone('Asia/Jakarta'));

        if ($waktu_input < $now) {
            $message = "Waktu mulai tidak boleh di masa lalu!";
        } elseif ($waktu_input < $min_waktu) {
            $message = "Waktu mulai harus minimal " . date('d-m-Y H:i', strtotime($min_waktu_mulai[$id_chanel])) . "!";
        } else {
            $chanel_query = "SELECT nama_chanel, jenis_ps FROM tb_chanel WHERE id_chanel = ?";
            $stmt = mysqli_prepare($conn, $chanel_query);
            mysqli_stmt_bind_param($stmt, "i", $id_chanel);
            mysqli_stmt_execute($stmt);
            $chanel_result = mysqli_stmt_get_result($stmt);
            if ($chanel = mysqli_fetch_object($chanel_result)) {
                $opsi_sewa = $chanel->nama_chanel;
                $total_harga = $durasi_sewa * ($prices[$chanel->jenis_ps] ?? ($chanel->jenis_ps === 'PS3' ? 5000 : 8000));

                // Cek double booking hanya untuk booking yang masih aktif
                $check_query = "SELECT * FROM tb_booking 
                               WHERE id_chanel = ? 
                               AND status IN ('pending', 'confirmed') 
                               AND waktu_mulai <= DATE_ADD(?, INTERVAL ? HOUR) 
                               AND DATE_ADD(waktu_mulai, INTERVAL durasi_sewa HOUR) >= ? 
                               AND DATE_ADD(waktu_mulai, INTERVAL durasi_sewa HOUR) >= NOW()";
                $stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($stmt, "issi", $id_chanel, $waktu_mulai, $durasi_sewa, $waktu_mulai);
                mysqli_stmt_execute($stmt);
                $check_result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $message = "PlayStation sudah dipesan pada waktu tersebut!";
                } else {
                    $insert_query = "INSERT INTO tb_booking (id_user, id_chanel, durasi_sewa, waktu_mulai, tgl_booking, status, tipe_transaksi, opsi_sewa, total_harga) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "iissssssi", $user_id, $id_chanel, $durasi_sewa, $waktu_mulai, $tgl_booking, $status, $tipe_transaksi, $opsi_sewa, $total_harga);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['booking_data'] = [
                            'id_booking' => mysqli_insert_id($conn),
                            'opsi_sewa' => [$opsi_sewa],
                            'durasi_sewa' => $durasi_sewa,
                            'nama' => $user_data['nama_lengkap'],
                            'nomor' => $user_data['nomor_telepon'],
                            'tipe_transaksi' => $tipe_transaksi,
                            'waktu_mulai' => $waktu_mulai,
                            'total_harga' => $total_harga
                        ];
                        header("Location: konfirmasi_pembayaran.php");
                        exit();
                    } else {
                        $message = "Gagal menyimpan booking: " . mysqli_error($conn);
                    }
                }
            } else {
                $message = "PlayStation tidak ditemukan!";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>J&K PlayStation - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/konsumen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav>
        <div><strong>J&K PlayStation</strong></div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <a href="#hero" style="color: white; text-decoration: none;">BERANDA</a>
            <a href="#fasilitas" style="color: white; text-decoration: none;">FASILITAS</a>
            <a href="#ketersediaan" style="color: white; text-decoration: none;">CEK KETERSEDIAAN</a>
            <a href="#booking" style="color: white; text-decoration: none;">MAIN & SEWA</a>
            <a href="#riwayat" style="color: white; text-decoration: none;">RIWAYAT</a>
            <a href="#games" style="color: white; text-decoration: none;">GAMES</a>
            <a href="#kontak" style="color: white; text-decoration: none;">ABOUT</a>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center dropdown-toggle text-white text-decoration-none" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="../assets/img/saya.png" alt="profile" width="80" height="48" class="rounded-circle me-2">
                    <?php echo htmlspecialchars($username); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="../edit_password.php">Edit Password</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="hero" class="text-center text-white py-5">
        <div class="logo display-4">J&K PlayStation</div>
        <hr class="mx-auto my-3" style="width: 500px; border: 1px solid white;">
        <div class="subtext fs-4">Games for Fun</div>
        <div class="scroll-down mt-3">
            <a href="#fasilitas" class="fs-1 text-white text-decoration-none">⬇</a>
        </div>
    </section>

    <section id="fasilitas" class="text-center text-white py-5">
        <h2 class="display-5 mb-5">J&K PLAYSTATION</h2>
        <div class="d-flex flex-wrap justify-content-center gap-5">
            <div>
                <i class="fas fa-tags fa-3x text-warning"></i>
                <p>HARGA MULAI DARI<br><strong>IDR 5.000</strong></p>
            </div>
            <div>
                <i class="fas fa-gamepad fa-3x text-warning"></i>
                <p>GAME PS3 & PS4<br><strong>TERBARU</strong></p>
            </div>
            <div>
                <i class="fas fa-tv fa-3x text-warning"></i>
                <p>TV 32 - 50<br><strong>INCH</strong></p>
            </div>
            <div>
                <i class="fas fa-wifi fa-3x text-warning"></i>
                <p><strong>FREE WIFI</strong></p>
            </div>
        </div>
    </section>

    <section id="ketersediaan" class="ketersediaan-section text-white py-5">
        <h2 class="section-title text-center mb-4">Ketersediaan PlayStation</h2>
        <div class="ps-grid d-flex flex-wrap justify-content-center gap-4">
            <?php if ($playstations): ?>
                <?php foreach ($playstations as $ps): ?>
                    <div class="ps-card text-center p-3 <?php echo $ps['status'] === 'unavailable' ? 'unavailable' : 'available'; ?>">
                        <div class="ps-name"><?php echo htmlspecialchars($ps['nama_chanel']); ?></div>
                        <div class="ps-status <?php echo $ps['status'] === 'unavailable' ? 'unavailable' : ''; ?>">
                            <?php echo $ps['status'] === 'unavailable' ? 'NOT READY' : 'READY'; ?>
                        </div>
                        <div class="ps-type small" style="color:rgb(170, 168, 168);">Jenis: <?php echo htmlspecialchars($ps['jenis_ps']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data text-center">Tidak ada PlayStation tersedia.</p>
            <?php endif; ?>
        </div>
    </section>

    <section id="booking" class="booking-section text-white py-5">
        <h2 class="section-title text-center mb-4">Booking Sewa Online (Takeaway)</h2>
        <div class="glass-card p-4 mx-auto" style="max-width: 500px;">
            <?php if ($message && isset($_POST['submit_booking'])): ?>
                <div class="alert alert-<?php echo strpos($message, 'berhasil') !== false ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Pilih Opsi Sewa</label>
                    <div>
                        <label><input type="checkbox" name="opsi_sewa[]" value="PS3" <?php echo in_array('PS3', $_POST['opsi_sewa'] ?? []) ? 'checked' : ''; ?>> PS3 (Rp30.000, Stok: <?php echo $stok['PS3']->stok ?? 0; ?>)</label>
                    </div>
                    <div>
                        <label><input type="checkbox" name="opsi_sewa[]" value="TV32" <?php echo in_array('TV32', $_POST['opsi_sewa'] ?? []) ? 'checked' : ''; ?>> TV 32 inch (Rp35.000, Stok: <?php echo $stok['TV32']->stok ?? 0; ?>)</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Durasi Sewa (jam, minimal 12 jam)</label>
                    <input type="number" name="durasi_sewa" class="form-control" placeholder="Masukkan durasi" required min="12" value="<?php echo $_POST['durasi_sewa'] ?? 12; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="nomor" class="form-control" value="<?php echo htmlspecialchars($user_data['nomor_telepon']); ?>" readonly>
                </div>
                <button type="submit" name="submit_booking" class="btn btn-warning btn-block w-100">Pesan Takeaway</button>
            </form>
        </div>
    </section>

    <section id="booking_onsite" class="booking-section text-white py-5">
        <h2 class="section-title text-center mb-4">Booking Main di Tempat</h2>
        <div class="glass-card p-4 mx-auto" style="max-width: 500px;">
            <?php if ($message && isset($_POST['book_onsite'])): ?>
                <div class="alert alert-<?php echo strpos($message, 'berhasil') !== false ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Pilih PlayStation</label>
                    <select name="id_chanel" class="form-control" required>
                        <option value="">-- Pilih PlayStation --</option>
                        <?php
                        $chanel_result = mysqli_query($conn, "SELECT id_chanel, nama_chanel, jenis_ps, status FROM tb_chanel");
                        while ($chanel = mysqli_fetch_object($chanel_result)) {
                            $selected = ($_POST['id_chanel'] ?? '') == $chanel->id_chanel ? 'selected' : '';
                            echo "<option value='{$chanel->id_chanel}' {$selected}>{$chanel->nama_chanel} ({$chanel->jenis_ps}) " . ($chanel->status === 'unavailable' ? '[Sedang Digunakan]' : '') . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Durasi Sewa (jam, maksimal 12 jam)</label>
                    <select name="durasi_sewa" class="form-control" required>
                        <option value="">-- Pilih Durasi --</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($_POST['durasi_sewa'] ?? '') == $i ? 'selected' : ''; ?>><?php echo $i; ?> Jam</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" class="form-control" required value="<?php echo htmlspecialchars($_POST['waktu_mulai'] ?? ''); ?>">
                    <?php if (isset($_POST['id_chanel']) && isset($min_waktu_mulai[$_POST['id_chanel']])): ?>
                        <small class="text-white">Waktu mulai minimal: <?php echo date('d-m-Y H:i', strtotime($min_waktu_mulai[$_POST['id_chanel']])); ?></small>
                    <?php else: ?>
                        <small class="text-white">Pilih PlayStation untuk melihat waktu minimal</small>
                    <?php endif; ?>
                </div>
                <button type="submit" name="book_onsite" class="btn btn-warning btn-block w-100">Pesan Main di Tempat</button>
            </form>
        </div>
    </section>

    <section id="riwayat" class="riwayat-section text-white py-5">
        <h2 class="section-title text-center mb-4">Riwayat Booking Anda</h2>
        <?php if ($riwayat): ?>
            <div class="table-responsive mx-auto" style="max-width: 900px;">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal Booking</th>
                            <th>Tipe</th>
                            <th>Detail</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $booking): ?>
                            <tr>
                                <td><?php echo date('d-m-Y H:i', strtotime($booking['tgl_booking'])); ?></td>
                                <td><?php echo $booking['tipe_transaksi'] === 'main_di_tempat' ? 'Main di Tempat' : 'Takeaway'; ?></td>
                                <td>
                                    <?php
                                    if ($booking['tipe_transaksi'] === 'main_di_tempat') {
                                        echo htmlspecialchars($booking['opsi_sewa']) . ', ' . $booking['durasi_sewa'] . ' jam, Mulai: ' . date('d-m-Y H:i', strtotime($booking['waktu_mulai']));
                                    } else {
                                        $opsi = [];
                                        if ($booking['sewa_ps3']) $opsi[] = 'PS3';
                                        if ($booking['sewa_tv32']) $opsi[] = 'TV32';
                                        echo implode(', ', $opsi) . ', ' . $booking['durasi_sewa'] . ' jam';
                                    }
                                    ?>
                                </td>
                                <td>Rp<?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    $badge_class = '';
                                    switch ($booking['status']) {
                                        case 'pending':
                                            $badge_class = 'bg-warning';
                                            break;
                                        case 'confirmed':
                                            $badge_class = 'bg-success';
                                            break;
                                        case 'completed':
                                            $badge_class = 'bg-primary';
                                            break;
                                        case 'canceled':
                                            $badge_class = 'bg-danger';
                                            break;
                                        default:
                                            $badge_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data text-center">Belum ada riwayat booking.</p>
        <?php endif; ?>
    </section>

    <section id="games" class="text-white py-5">
        <h2 class="section-title text-center mb-4">Daftar Game</h2>
        <div class="scroll-wrapper d-flex justify-content-center gap-4 flex-wrap">
            <?php if ($games): ?>
                <?php foreach ($games as $game): ?>
                    <div class="game-card bg-dark rounded p-3" style="width: 220px;">
                        <img src="../assets/img/<?php echo htmlspecialchars($game['cover']); ?>" alt="<?php echo htmlspecialchars($game['nama_game']); ?>" class="w-100 rounded">
                        <div class="game-info mt-3">
                            <h5 class="font-orbitron"><?php echo htmlspecialchars($game['nama_game']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($game['genre']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">Tidak ada game tersedia.</p>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4 text-warning fs-5 font-orbitron">Masih Banyak Game Menarik Lainnya</div>
    </section>

    <section id="kontak" class="text-white py-5">
        <h2 class="section-title text-center mb-4">About Us</h2>
        <div class="mx-auto mb-4" style="max-width: 600px; box-shadow: 0 0 20px rgba(255, 102, 0, 0.3); border-radius: 12px; overflow: hidden;">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63361.584860172414!2d110.26992795820313!3d-6.997615299999987!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708bafc4f7f653%3A0x884cd65364618e02!2sJ%26K%20playsation!5e0!3m2!1sen!2sid!4v1747807991591!5m2!1sen!2sid" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <p class="text-center mx-auto mb-4" style="max-width: 700px; line-height: 1.7;">
            <span class="text-warning fw-bold">J&K PlayStation</span> adalah rental PlayStation di kawasan <b>Bringin, Semarang</b> yang menyediakan layanan rental <b>PS3</b> dan <b>PS4</b>. Kami berkomitmen memberikan pengalaman bermain yang seru, nyaman, dan menyenangkan untuk semua pelanggan.
        </p>
        <div class="d-flex justify-content-center gap-5 flex-wrap">
            <div>
                <strong>No. HP:</strong><br>
                <a href="https://wa.me/6281329380387" target="_blank" class="text-warning text-decoration-none fw-bold">0813-2938-0387</a>
            </div>
            <div>
                <strong>Instagram:</strong><br>
                <a href="https://instagram.com/jk_playstation" target="_blank" class="text-warning text-decoration-none fw-bold">@jk_playstation</a>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>