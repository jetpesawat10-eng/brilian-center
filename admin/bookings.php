<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$success = '';

/* Proses approve/reject */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];
    $action = $_POST['action'];
    $catatan = trim($_POST['catatan_admin'] ?? '');

    if ($action === 'approve') {
        // Ambil detail booking untuk cek bentrok sebelum approve
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        $check = $pdo->prepare("SELECT COUNT(*) c FROM bookings
            WHERE room_id = ? AND tanggal = ? AND status = 'approved' AND id != ?
            AND (jam_mulai < ? AND jam_selesai > ?)");
        $check->execute([
            $booking['room_id'],
            $booking['tanggal'],
            $bookingId,
            $booking['jam_selesai'],
            $booking['jam_mulai']
        ]);

        if ($check->fetch()['c'] > 0) {
            $success = 'Gagal menyetujui: jadwal bentrok dengan peminjaman lain yang sudah disetujui.';
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status='approved', catatan_admin=? WHERE id=?");
            $stmt->execute([$catatan, $bookingId]);
            $success = 'Peminjaman berhasil disetujui.';
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE bookings SET status='rejected', catatan_admin=? WHERE id=?");
        $stmt->execute([$catatan, $bookingId]);
        $success = 'Peminjaman berhasil ditolak.';
    }
}

$filter = $_GET['status'] ?? 'all';

$sql = "SELECT b.*, r.name AS room_name, u.name AS user_name, u.email AS user_email
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        JOIN users u ON u.id = b.user_id";

$params = [];

if (in_array($filter, ['pending', 'approved', 'rejected'])) {
    $sql .= " WHERE b.status = ?";
    $params[] = $filter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Kelola Peminjaman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-heading">
    <div>
        <h2 class="admin-page-title">Kelola Peminjaman</h2>
        <p class="admin-page-subtitle">
            Kelola pengajuan peminjaman ruangan dan proses persetujuannya.
        </p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card admin-bookings-card">

    <div class="admin-booking-filter">
        <a href="bookings.php?status=all"
           class="btn btn-sm <?= $filter === 'all' ? '' : 'btn-outline' ?>">
            Semua
        </a>

        <a href="bookings.php?status=pending"
           class="btn btn-sm <?= $filter === 'pending' ? '' : 'btn-outline' ?>">
            Pending
        </a>

        <a href="bookings.php?status=approved"
           class="btn btn-sm <?= $filter === 'approved' ? '' : 'btn-outline' ?>">
            Disetujui
        </a>

        <a href="bookings.php?status=rejected"
           class="btn btn-sm <?= $filter === 'rejected' ? '' : 'btn-outline' ?>">
            Ditolak
        </a>
    </div>

    <div class="table-wrap admin-bookings-table-wrap">
        <table class="admin-bookings-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Ruangan</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Agenda</th>
                    <th>Divisi</th>
                    <th>Peserta</th>
                    <th>PIC</th>
                    <th>No. Tlp</th>
                    <th>VIP</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="12" class="text-muted">
                            Tidak ada data peminjaman.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($bookings as $b): ?>
                    <tr>

                        <!-- User -->
                        <td class="booking-user-cell">
                            <strong><?= e($b['user_name']) ?></strong>
                            <span><?= e($b['user_email']) ?></span>
                        </td>

                        <!-- Ruangan -->
                        <td>
                            <strong><?= e($b['room_name']) ?></strong>
                        </td>

                        <!-- Tanggal -->
                        <td>
                            <?= e(date('d M Y', strtotime($b['tanggal']))) ?>
                        </td>

                        <!-- Waktu -->
                        <td class="booking-time-cell">
                            <?= e(substr($b['jam_mulai'], 0, 5)) ?>
                            -
                            <?= e(substr($b['jam_selesai'], 0, 5)) ?>
                        </td>

                        <!-- Agenda -->
                        <td class="booking-agenda-cell">
                            <?= e($b['keperluan'] ?: '-') ?>
                        </td>

                        <!-- Divisi -->
                        <td>
                            <?= e($b['divisi'] ?: '-') ?>
                        </td>

                        <!-- Peserta -->
                        <td class="booking-center-cell">
                            <?= $b['peserta'] !== null && $b['peserta'] !== ''
                                ? e($b['peserta']) . ' orang'
                                : '-' ?>
                        </td>

                        <!-- PIC -->
                        <td>
                            <?= e($b['pic_name'] ?: '-') ?>
                        </td>

                        <!-- No. Tlp -->
                        <td>
                            <?= e($b['pic_phone'] ?: '-') ?>
                        </td>

                        <!-- VIP -->
                        <td class="booking-center-cell">
                            <?php if ($b['dihadiri_vip'] === 'Ya'): ?>
                                <span class="vip-badge">Ya</span>
                            <?php elseif ($b['dihadiri_vip'] === 'Tidak'): ?>
                                <span class="vip-badge vip-no">Tidak</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <span class="badge badge-<?= e($b['status']) ?>">
                                <?= ucfirst(e($b['status'])) ?>
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td class="booking-action-cell">

                            <?php if ($b['status'] === 'pending'): ?>

                                <form method="POST" class="booking-action-form">
                                    <input type="hidden"
                                           name="booking_id"
                                           value="<?= e($b['id']) ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="approve">

                                    <button type="submit"
                                            class="btn btn-sm btn-approve">
                                        Setujui
                                    </button>
                                </form>

                                <form method="POST"
                                      class="booking-action-form"
                                      onsubmit="return confirm('Tolak pengajuan ini?');">

                                    <input type="hidden"
                                           name="booking_id"
                                           value="<?= e($b['id']) ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="reject">

                                    <button type="submit"
                                            class="btn btn-sm btn-danger">
                                        Tolak
                                    </button>
                                </form>

                            <?php else: ?>

                                <div class="admin-note">
                                    <span>Catatan:</span>
                                    <?= e($b['catatan_admin'] ?: '-') ?>
                                </div>

                            <?php endif; ?>

                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.admin-bookings-table .badge').forEach(function (badge) {

        badge.addEventListener('click', function () {

            document.querySelectorAll('.admin-bookings-table .badge').forEach(function (item) {
                item.classList.remove('status-selected');
            });

            badge.classList.add('status-selected');
        });

    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>




