<?php
session_start(); // Tambahkan session_start() untuk mengakses $_SESSION
require_once 'helper/connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi sederhana
    if ($new_password !== $confirm_password) {
        $message = "Password baru dan konfirmasi password tidak cocok!";
    } else {
        // Cek password lama
        $query = "SELECT password FROM tb_user WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && $user['password'] === $current_password) {
            $update_query = "UPDATE tb_user SET password = ? WHERE username = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ss", $new_password, $username);
            mysqli_stmt_execute($stmt);
            $message = "Password berhasil diperbarui!";
        } else {
            $message = "Password lama salah atau username tidak ditemukan!";
        }
    }
}

// Set dashboard link based on user level
$dashboard_link = "#";
if ($_SESSION['user_level'] == 'admin') {
    $dashboard_link = "admin/dashboard.php";
} elseif ($_SESSION['user_level'] == 'konsumen') {
    $dashboard_link = "konsumen/dashboard.php";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Password - J&K PlayStation</title>
    <link rel="stylesheet" href="assets/css/konsumen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: url('assets/img/bground.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        .edit-password-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 80px 20px;
            box-sizing: border-box;
        }

        .glass-card {
            background: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 24px rgba(206, 54, 155, 0.4);
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .glass-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            color: #ff6600;
            margin-bottom: 20px;
        }

        .form-group label {
            font-family: 'Orbitron', sans-serif;
            font-size: 1em;
            color: #fff;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .form-control::placeholder {
            color: #ccc;
        }

        .btn-danger {
            transition: all 0.3s ease;
            background-color: #e63946;
            border: none;
            font-family: 'Orbitron', sans-serif;
        }

        .btn-danger:hover {
            background-color: #d62839;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(230, 57, 70, 0.4);
        }

        .btn-warning {
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
        }

        .message {
            margin-top: 15px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1em;
        }

        .message.success {
            color: #00ff00;
        }

        .message.error {
            color: #ff2222;
        }
    </style>
</head>

<body>
    <section class="edit-password-container">
        <div class="glass-card">
            <a href="<?php echo $dashboard_link; ?>" class="btn btn-warning mb-3" style="width:100%;font-family:'Orbitron',sans-serif;font-weight:bold;">
                <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h3>Edit Password</h3>
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Masukkan Password Lama" required>
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Masukkan Password Baru" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi Password Baru" required>
                </div>
                <button type="submit" class="btn btn-danger btn-block mt-3">Simpan Perubahan</button>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>