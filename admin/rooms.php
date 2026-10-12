<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';
$editRoom = null;
$uploadDir = __DIR__ . '/../assets/uploads/rooms/';
$uploadWebPath = '../assets/uploads/rooms/';
$allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$maxFileSize = 3 * 1024 * 1024;

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('SELECT photo FROM rooms WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    $old = $stmt->fetch();
    $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    if (!empty($old['photo'])) { $file = $uploadDir . basename($old['photo']); if (is_file($file)) @unlink($file); }
    header('Location: rooms.php'); exit;
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
    $stmt->execute([$_GET['edit']]);
    $editRoom = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'available';
    $roomId = $_POST['room_id'] ?? '';
    $oldPhoto = '';

    if ($roomId) {
        $stmt = $pdo->prepare('SELECT photo FROM rooms WHERE id = ?'); $stmt->execute([$roomId]);
        $oldRow = $stmt->fetch(); $oldPhoto = $oldRow['photo'] ?? '';
    }

    if ($name === '' || $capacity <= 0) {
        $error = 'Nama ruangan dan kapasitas wajib diisi dengan benar.';
    } elseif (!in_array($status, ['available','unavailable'], true)) {
        $error = 'Status ruangan tidak valid.';
    } else {
        $newPhoto = $oldPhoto;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) $error = 'Foto gagal diunggah.';
            elseif ($_FILES['photo']['size'] > $maxFileSize) $error = 'Ukuran foto maksimal 3 MB.';
            else {
                $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($_FILES['photo']['tmp_name']);
                if (!isset($allowedMime[$mime])) $error = 'Format foto harus JPG, PNG, atau WEBP.';
                else {
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $newPhoto = bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newPhoto)) $error = 'Foto tidak dapat disimpan.';
                    elseif ($oldPhoto && is_file($uploadDir . basename($oldPhoto))) @unlink($uploadDir . basename($oldPhoto));
                }
            }
        }
        if ($error === '') {
            if ($roomId) {
                $stmt = $pdo->prepare('UPDATE rooms SET name=?, location=?, capacity=?, description=?, photo=?, status=? WHERE id=?');
                $stmt->execute([$name,$location,$capacity,$description,$newPhoto,$status,$roomId]); $success='Ruangan berhasil diperbarui.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO rooms (name, location, capacity, description, photo, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name,$location,$capacity,$description,$newPhoto,$status]); $success='Ruangan berhasil ditambahkan.';
            }
            $editRoom = null;
        }
    }
}
$rooms = $pdo->query('SELECT * FROM rooms ORDER BY name')->fetchAll();
$pageTitle = 'Kelola Ruangan'; require_once __DIR__ . '/../includes/header.php';
?>
<h2>Kelola Ruangan</h2>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<div class="card" style="max-width: 560px;">
<h3 class="mt-0"><?= $editRoom ? 'Edit Ruangan' : 'Tambah Ruangan Baru' ?></h3>
<form method="POST" enctype="multipart/form-data">
<?php if ($editRoom): ?><input type="hidden" name="room_id" value="<?= e($editRoom['id']) ?>"><?php endif; ?>
<label>Nama Ruangan</label><input type="text" name="name" value="<?= e($editRoom['name'] ?? '') ?>" required>
<label>Lokasi</label><input type="text" name="location" value="<?= e($editRoom['location'] ?? '') ?>">
<label>Kapasitas Maksimal</label><input type="number" name="capacity" min="1" value="<?= e($editRoom['capacity'] ?? '') ?>" required>
<label>Deskripsi / Detail Ruangan</label><textarea name="description" rows="3"><?= e($editRoom['description'] ?? '') ?></textarea>
<label>Foto Ruangan <span class="text-muted small">(JPG/PNG/WEBP, maks. 3 MB)</span></label>
<?php if (!empty($editRoom['photo'])): ?><div class="room-photo-preview"><img src="<?= $uploadWebPath . e($editRoom['photo']) ?>" alt="Foto <?= e($editRoom['name']) ?>"></div><?php endif; ?>
<input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
<label>Status</label><select name="status"><option value="available" <?= (($editRoom['status'] ?? '') === 'available') ? 'selected' : '' ?>>Tersedia</option><option value="unavailable" <?= (($editRoom['status'] ?? '') === 'unavailable') ? 'selected' : '' ?>>Tidak Tersedia</option></select>
<button type="submit" class="btn"><?= $editRoom ? 'Simpan Perubahan' : 'Tambah Ruangan' ?></button>
<?php if ($editRoom): ?><a href="rooms.php" class="btn btn-outline">Batal</a><?php endif; ?>
</form></div>
<div class="card"><h3 class="mt-0">Daftar Ruangan</h3><div class="table-wrap"><table><tr><th>Foto</th><th>Nama</th><th>Lokasi</th><th>Kapasitas</th><th>Status</th><th>Aksi</th></tr>
<?php if (empty($rooms)): ?><tr><td colspan="6" class="text-muted">Belum ada ruangan.</td></tr><?php endif; ?>
<?php foreach ($rooms as $r): ?><tr><td><?php if (!empty($r['photo'])): ?><img class="room-thumb" src="<?= $uploadWebPath . e($r['photo']) ?>" alt="<?= e($r['name']) ?>"><?php else: ?><span class="text-muted">Belum ada</span><?php endif; ?></td><td><?= e($r['name']) ?></td><td><?= e($r['location']) ?></td><td><?= e($r['capacity']) ?></td><td><span class="badge badge-<?= e($r['status']) ?>"><?= $r['status'] === 'available' ? 'Tersedia' : 'Tidak Tersedia' ?></span></td><td><a href="rooms.php?edit=<?= e($r['id']) ?>" class="btn btn-sm btn-outline">Edit</a> <a href="rooms.php?delete=<?= e($r['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus ruangan ini?');">Hapus</a></td></tr><?php endforeach; ?></table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
