<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Hapus user (tidak bisa hapus diri sendiri)
if (isset($_GET['delete'])) {
    if ((int)$_GET['delete'] === (int)$_SESSION['user_id']) {
        $error = 'Anda tidak bisa menghapus akun sendiri.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$_GET['delete']]);
        header('Location: users.php');
        exit;
    }
}

// Tambah admin/user baru secara manual oleh admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, $role]);
            $success = 'User berhasil ditambahkan.';
        }
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY role DESC, name')->fetchAll();

$pageTitle = 'Kelola User';
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Kelola User</h2>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="max-width: 480px;">
    <h3 class="mt-0">Tambah User / Admin Baru</h3>
    <form method="POST">
        <label>Nama</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Role</label>
        <select name="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit" class="btn">Tambah</button>
    </form>
</div>

<div class="card">
    <h3 class="mt-0">Daftar User</h3>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Terdaftar</th>
            <th>Aksi</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'approved' : 'pending' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?');">Hapus</a>
                <?php else: ?>
                <span class="small text-muted">Anda</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
