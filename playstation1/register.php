<?php
require_once 'helper/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $nomor_telepon = $_POST['nomor_telepon'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi nomor telepon (hanya angka, panjang 10-15 karakter)
    if (!preg_match('/^[0-9]{10,15}$/', $nomor_telepon)) {
        $error = "Nomor telepon harus berisi 10-15 angka!";
    } else {
        // Query menggunakan prepared statement
        $query = "INSERT INTO tb_user (nama_lengkap, nomor_telepon, username, password, level) VALUES (?, ?, ?, ?, 'konsumen')";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $nama_lengkap, $nomor_telepon, $username, $password);
        
        if (mysqli_stmt_execute($stmt)) {
            // Registrasi berhasil, redirect ke index.php
            header("Location: index.php");
            exit();
        } else {
            // Registrasi gagal
            $error = "Registrasi gagal: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - J&K PlayStation</title>
    <link rel="stylesheet" href="assets/css/konsumen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #000;
        }

        .register-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('assets/img/bground.jpg') no-repeat center center fixed;
            background-size: cover;
            color: white;
        }

        .glass-card {
            background: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 24px rgba(206, 54, 155, 0.4);
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
        }

        .glass-card h3 {
            font-weight: 600;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
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
        }

        .btn-danger:hover {
            background-color: #d62839;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(230, 57, 70, 0.4);
        }

        .register-container a {
            color: #bbb;
        }

        .register-container a:hover {
            color: white;
            text-decoration: none;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <section class="register-container">
        <div class="glass-card">
            <h3 class="text-center text-danger mb-4">Daftar Konsumen</h3>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="post" action="register.php">
                <div class="form-group mb-3">
                    <label class="text-white">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required>
                </div>
                <div class="form-group mb-3">
                    <label class="text-white">Nomor Telepon</label>
                    <input type="text" name="nomor_telepon" class="form-control" placeholder="Contoh: 081234567890" required pattern="[0-9]{10,15}" title="Nomor telepon harus berisi 10-15 angka">
                </div>
                <div class="form-group mb-3">
                    <label class="text-white">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group mb-3">
                    <label class="text-white">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Password" required minlength="4">
                </div>
                <button type="submit" class="btn btn-danger btn-block mt-3 w-100">Daftar</button>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-white">Kembali ke login</a>
                </div>
            </form>
        </div>
    </section>
</body>
</html>