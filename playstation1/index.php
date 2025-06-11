<?php
session_start();
require_once 'helper/connection.php';

$message = '';

if (isset($_SESSION['user_id'])) {
    $user_level = $_SESSION['user_level'] ?? '';
    if ($user_level === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($user_level === 'konsumen') {
        header("Location: konsumen/dashboard.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $query = "SELECT * FROM tb_user WHERE username = ? AND password = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_level'] = $user['level'];

        if ($user['level'] === 'admin') {
            header("Location: admin/dashboard.php");
        } elseif ($user['level'] === 'konsumen') {
            header("Location: konsumen/dashboard.php");
        }
        exit();
    } else {
        $message = "Username atau password salah!";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - J&K PlayStation</title>
    <link rel="stylesheet" href="assets/css/konsumen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <section class="register-container">
        <div class="glass-card">
            <h3 class="text-center text-danger mb-4">Login</h3>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                </div>
                <button type="submit" class="btn btn-danger btn-block mt-3">Login</button>
                <div class="text-center mt-3">
                    <span class="text-white">Belum punya akun? </span>
                    <a href="register.php" class="text-danger">Daftar di sini</a>
                </div>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>