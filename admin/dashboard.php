<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$totalRooms = $pdo->query("SELECT COUNT(*) c FROM rooms")->fetch()['c'];
$totalUsers = $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'user'")->fetch()['c'];
$pendingCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE status = 'pending'")->fetch()['c'];
$approvedCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE status = 'approved'")->fetch()['c'];

$recent = $pdo->query("SELECT b.*, r.name AS room_name, u.name AS user_name FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.user_id
    ORDER BY b.created_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mt-0">Dashboard Admin</h2>

<div class="grid" style="margin-bottom: 24px;">
    <div class="stat-box">
        <div class="num"><?= $totalRooms ?></div>
        <div class="label">Total Ruangan</div>
    </div>
    <div class="stat-box">
        <div class="num"><?= $totalUsers ?></div>
        <div class="label">Total User</div>
    </div>
    <div class="stat-box">
        <div class="num"><?= $pendingCount ?></div>
        <div class="label">Menunggu Persetujuan</div>
    </div>
    <div class="stat-box">
        <div class="num"><?= $approvedCount ?></div>
        <div class="label">Disetujui</div>
    </div>
</div>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Pengajuan Terbaru</h3>
        <a href="bookings.php" class="btn btn-sm btn-outline">Lihat Semua</a>
    </div>
    <div class="table-wrap">
    <table>
        <tr>
            <th>User</th>
            <th>Ruangan</th>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Status</th>
        </tr>
        <?php if (empty($recent)): ?>
        <tr><td colspan="5" class="text-muted">Belum ada pengajuan.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $b): ?>
        <tr>
            <td><?= e($b['user_name']) ?></td>
            <td><?= e($b['room_name']) ?></td>
            <td><?= e(date('d M Y', strtotime($b['tanggal']))) ?></td>
            <td><?= e(substr($b['jam_mulai'],0,5)) ?> - <?= e(substr($b['jam_selesai'],0,5)) ?></td>
            <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
