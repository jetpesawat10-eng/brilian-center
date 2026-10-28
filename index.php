<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - BRILiaN CENTER</title>
<link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth-cream.css">
</head>
<body class="login-page">
<div class="auth-wrapper">
    <div class="auth-brand">
        <div class="brand-mark" aria-hidden="true">B</div>
        <div>
            <div class="brand-name">BRILiaN CENTER</div>
            <div class="brand-subtitle">Sistem Informasi Peminjaman Ruangan</div>
        </div>
    </div>

    <div class="auth-motto">Mudah <span>|</span> Cepat <span>|</span> Terpercaya</div>

    <main class="auth-box">
        <div class="auth-logo" aria-hidden="true">B</div>
        <h1>BRILiaN CENTER</h1>
        <p class="subtitle">Sistem Informasi Peminjaman Ruangan</p>
        <div class="auth-divider"></div>
        <p class="login-intro">Silakan login untuk melanjutkan</p>

        <?php if ($error): ?>
            <div class="alert alert-danger login-alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <label for="email">Username / Email</label>
            <div class="input-wrap">
                <span class="input-icon" aria-hidden="true"></span>
                <input id="email" type="email" name="email" placeholder="Username / Email" autocomplete="username" required autofocus>
            </div>

            <label for="password">Password</label>
            <div class="input-wrap">
                <span class="input-icon" aria-hidden="true"></span>
                <input id="password" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" id="togglePassword" aria-label="Tampilkan password" title="Tampilkan password">Lihat</button>
            </div>

            <button type="submit" class="btn auth-submit">Login</button>
        </form>

        <p class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
    </main>

    <footer class="auth-footer">(c) 2025 BRILiaN CENTER. All rights reserved.</footer>
</div>

<script>
const password = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');
if (password && togglePassword) {
    togglePassword.addEventListener('click', function () {
        const visible = password.type === 'text';
        password.type = visible ? 'password' : 'text';
        this.textContent = visible ? 'Lihat' : 'Sembunyikan';
        this.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
        this.setAttribute('title', visible ? 'Tampilkan password' : 'Sembunyikan password');
    });
}
</script>
</body>
</html>






