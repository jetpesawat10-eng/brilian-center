<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak sesuai.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'user')"
            );
            $stmt->execute([$name, $email, $hash]);

            $success = 'Akun berhasil dibuat.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar - SiPeminjaman</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/register-redesign.css">
    <link rel="stylesheet" href="assets/css/auth-cream.css">
</head>

<body>

<div class="register-page">

    <div class="register-card">

        <div class="register-brand">
            <div class="brand-icon"><i class="bi bi-buildings"></i></div>
            <h1>BRILIaN CENTER</h1>
            <p>Buat akun untuk mengajukan peminjaman ruangan</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= e($success) ?>
                <a href="index.php">Login sekarang</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>

        <form method="POST">

            <div class="register-form-group">
                <label>Nama Lengkap</label>
                <input
                    type="text"
                    name="name"
                    value="<?= e($_POST['name'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>

            <div class="register-form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="register-form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    required
                >
            </div>

            <div class="register-form-group">
                <label>Konfirmasi Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    required
                >
            </div>

            <button type="submit" class="register-submit">
                Daftar
            </button>

        </form>

        <?php endif; ?>

        <div class="register-footer">
            Sudah punya akun?
            <a href="index.php">Login di sini</a>
        </div>

    </div>

</div>

</body>
</html>




