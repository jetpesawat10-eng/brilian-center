<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireUser();

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Peminjaman Saya';

$success = '';
$error = '';

/* Batalkan peminjaman yang masih Pending */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($bookingId > 0) {
        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'rejected',
                catatan_admin = 'Dibatalkan oleh pengguna'
            WHERE id = ?
              AND user_id = ?
              AND status = 'pending'
        ");

        $stmt->execute([$bookingId, $userId]);

        if ($stmt->rowCount() > 0) {
            $success = 'Peminjaman berhasil dibatalkan.';
        } else {
            $error = 'Peminjaman tidak dapat dibatalkan.';
        }
    }
}

/* Statistik */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
    FROM bookings
    WHERE user_id = ?
");

$stmt->execute([$userId]);

$statsRow = $stmt->fetch();

$stats = [
    'total' => (int) ($statsRow['total'] ?? 0),
    'pending' => (int) ($statsRow['pending'] ?? 0),
    'approved' => (int) ($statsRow['approved'] ?? 0),
    'rejected' => (int) ($statsRow['rejected'] ?? 0)
];

/* Seluruh riwayat peminjaman user */
$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.tanggal,
        b.jam_mulai,
        b.jam_selesai,
        b.keperluan,
        b.divisi,
        b.peserta,
        b.pic_name,
        b.pic_phone,
        b.dihadiri_vip,
        b.status,
        b.catatan_admin,
        r.name AS room_name
    FROM bookings b
    LEFT JOIN rooms r ON r.id = b.room_id
    WHERE b.user_id = ?
    ORDER BY b.tanggal DESC, b.jam_mulai DESC, b.id DESC
");

$stmt->execute([$userId]);

$bookings = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="user-page-heading">
    <div class="user-page-eyebrow">BRILiaN CENTER</div>
    <h2>Peminjaman Saya</h2>
    <p>Kelola dan lihat seluruh riwayat pengajuan peminjaman ruangan Anda.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= e($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="stats-grid user-booking-stats">

    <div class="stat-box">
        <div class="num"><?= $stats['total'] ?></div>
        <div class="label">Total Pengajuan</div>
    </div>

    <div class="stat-box">
        <div class="num"><?= $stats['pending'] ?></div>
        <div class="label">Menunggu Persetujuan</div>
    </div>

    <div class="stat-box">
        <div class="num"><?= $stats['approved'] ?></div>
        <div class="label">Disetujui</div>
    </div>

    <div class="stat-box stat-rejected">
        <div class="num"><?= $stats['rejected'] ?></div>
        <div class="label">Ditolak</div>
    </div>

</div>

<div class="card user-history-card">

    <div class="history-card-heading">
        <div>
            <div class="user-page-eyebrow">HISTORY</div>
            <h3>Riwayat Peminjaman</h3>
        </div>

        <a href="<?= basePath() ?>user/book.php" class="btn">
            + Pilih Ruangan
        </a>
    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Ruangan</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Agenda</th>
                    <th>Divisi</th>
                    <th>Peserta</th>
                    <th>PIC</th>
                    <th>VIP</th>
                    <th>Status</th>
                    <th>Catatan Admin</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php if (count($bookings) > 0): ?>

                <?php foreach ($bookings as $booking): ?>

                    <?php
                    $status = strtolower($booking['status'] ?? '');

                    $statusClass = 'badge-pending';

                    if ($status === 'approved') {
                        $statusClass = 'badge-approved';
                    } elseif ($status === 'rejected') {
                        $statusClass = 'badge-rejected';
                    }

                    $statusText = ucfirst($status);
                    ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($booking['room_name'] ?: '-') ?>
                            </strong>
                        </td>

                        <td>
                            <?= e($booking['tanggal'] ?: '-') ?>
                        </td>

                        <td>
                            <?= e($booking['jam_mulai'] ?: '-') ?>
                            -
                            <?= e($booking['jam_selesai'] ?: '-') ?>
                        </td>

                        <td>
                            <?= e($booking['keperluan'] ?: '-') ?>
                        </td>

                        <td>
                            <?= e($booking['divisi'] ?: '-') ?>
                        </td>

                        <td>
                            <?= $booking['peserta'] !== null ? e($booking['peserta']) : '-' ?>
                        </td>

                        <td>
                            <?php if (!empty($booking['pic_name'])): ?>
                                <?= e($booking['pic_name']) ?><br>
                                <small>
                                    <?= e($booking['pic_phone'] ?: '-') ?>
                                </small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($booking['dihadiri_vip'] ?: '-') ?>
                        </td>

                        <td>
                            <span class="badge <?= $statusClass ?>">
                                <?= e($statusText) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($booking['catatan_admin'] ?: '-') ?>
                        </td>

                        <td>

                            <?php if ($status === 'pending'): ?>

                                <form method="post"
                                      onsubmit="return confirm('Yakin ingin membatalkan peminjaman ini?');">

                                    <input type="hidden"
                                           name="booking_id"
                                           value="<?= (int) $booking['id'] ?>">

                                    <button type="submit"
                                            name="cancel_booking"
                                            class="btn btn-danger">
                                        Batalkan
                                    </button>

                                </form>

                            <?php else: ?>

                                <?php if ($status === 'approved'): ?>

                                    <a href="booking_download.php?id=<?= (int) $booking['id'] ?>"
                                       class="btn btn-download-proof"
                                       target="_blank">
                                        Download Bukti
                                    </a>

                                <?php else: ?>

                                    <span style="color:#8a9aaa;">-</span>

                                <?php endif; ?>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="11" style="text-align:center; padding:35px;">
                        Belum ada riwayat peminjaman.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


