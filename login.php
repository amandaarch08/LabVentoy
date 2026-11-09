<?php
/* Amanda Damayanti_202432027 */
require_once "config.php";

// Kalau sudah login, langsung lempar ke dashboard sesuai role
if (isset($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "admin.php" : "user.php"));
    exit;
}

$loginError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $loginError = "Username dan password wajib diisi!";
    } else {
        // Gunakan prepared statement (PDO) supaya aman dari SQL Injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // password_verify() dipakai karena password di database disimpan dalam bentuk hash
        if ($user && password_verify($password, $user['password'])) {
            // $_SESSION dipakai untuk menyimpan status login + role selama user berpindah halaman.
            // Server menyimpan data ini di sisi server, browser hanya menyimpan session id (cookie),
            // sehingga tiap halaman lain tinggal cek $_SESSION untuk tahu siapa yang login & rolenya apa.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            header("Location: " . ($user['role'] === 'admin' ? "admin.php" : "user.php"));
            exit;
        } else {
            $loginError = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login | Lab-Ventory</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="navbar">
    <div class="brand">🧪 Lab-Ventory</div>
</div>

<div class="login-wrapper">
    <div class="login-card">
        <h2>🧪 Lab-Ventory</h2>
        <p class="subtitle">Silakan login ke akun Anda</p>

        <form method="POST" action="login.php" id="loginForm">
            <div class="form-group">
                <label>👤 Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label>🔒 Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn">➜] Masuk</button>
        </form>
        <a href="index.php" class="back-link">← Kembali ke Beranda</a>
    </div>
</div>

<?php if ($loginError !== ""): ?>
<script>
// Validasi/notifikasi ditampilkan lewat SweetAlert2 tanpa mengarahkan user ke halaman baru
Swal.fire({
    icon: 'error',
    title: 'Gagal Login!',
    text: '<?php echo addslashes($loginError); ?>',
    confirmButtonText: 'Coba Lagi',
    confirmButtonColor: '#dc2626'
});
</script>
<?php endif; ?>

</body>
</html>
