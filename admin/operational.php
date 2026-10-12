<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$filter = $_GET['filter'] ?? 'upcoming';

$sql = "
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
        r.name AS room_name,
        u.name AS user_name,
        op.cleaning_status,
        op.technician_status,
        op.layout_status,
        op.projector_status,
        op.microphone_status,
        op.wifi_status,
        op.catering_status,
        ofu.followup_status,
        rh.handover_status
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.user_id
    LEFT JOIN operational_preparations op
        ON op.booking_id = b.id
    LEFT JOIN operational_followups ofu
        ON ofu.booking_id = b.id
    LEFT JOIN room_handovers rh
        ON rh.booking_id = b.id
    WHERE b.status = 'approved'
";

$params = [];

if ($filter === 'today') {
    $sql .= " AND b.tanggal = CURDATE()";
} elseif ($filter === 'tomorrow') {
    $sql .= " AND b.tanggal = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filter === 'upcoming') {
    $sql .= " AND b.tanggal >= CURDATE()";
}

$sql .= " ORDER BY b.tanggal ASC, b.jam_mulai ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Operasional';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-heading">
    <div>
        <h2 class="admin-page-title">Operasional</h2>
        <p class="admin-page-subtitle">
            Pantau peminjaman yang telah disetujui dan persiapan operasionalnya.
        </p>
    </div>
</div>

<div class="card admin-bookings-card">

    <div class="admin-booking-filter">

        <a href="operational.php?filter=upcoming"
           class="btn btn-sm <?= $filter === 'upcoming' ? '' : 'btn-outline' ?>">
            Mendatang
        </a>

        <a href="operational.php?filter=today"
           class="btn btn-sm <?= $filter === 'today' ? '' : 'btn-outline' ?>">
            Hari Ini
        </a>

        <a href="operational.php?filter=tomorrow"
           class="btn btn-sm <?= $filter === 'tomorrow' ? '' : 'btn-outline' ?>">
            Besok
        </a>

        <a href="operational.php?filter=all"
           class="btn btn-sm <?= $filter === 'all' ? '' : 'btn-outline' ?>">
            Semua
        </a>

    </div>

    <div class="table-wrap admin-bookings-table-wrap">

        <table class="admin-bookings-table">

            <thead>
                <tr>
                    <th>Booking</th>
                    <th>User</th>
                    <th>Ruangan</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Persiapan</th>
                    <th>H-1</th>
                    <th>Hari H</th>
                    <th>Overall</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!$bookings): ?>

                <tr>
                    <td colspan="10" style="text-align:center; padding:30px;">
                        Tidak ada peminjaman yang ditemukan.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($bookings as $booking): ?>

                    <tr>

                        <td>
                            <strong>
                                #<?= (int) $booking['id'] ?>
                            </strong>
                        </td>

                        <td>
                            <?= e($booking['user_name']) ?>
                        </td>

                        <td>
                            <?= e($booking['room_name']) ?>
                        </td>

                        <td>
                            <?= e($booking['tanggal']) ?>
                        </td>

                        <td>
                            <?= e($booking['jam_mulai']) ?>
                            -
                            <?= e($booking['jam_selesai']) ?>
                        </td>

                        <?php
                            $preparationStatuses = [
                                $booking['cleaning_status'],
                                $booking['technician_status'],
                                $booking['layout_status'],
                                $booking['projector_status'],
                                $booking['microphone_status'],
                                $booking['wifi_status'],
                                $booking['catering_status']
                            ];

                            $preparationStatuses = array_filter(
                                $preparationStatuses,
                                static fn($status) => $status !== null
                            );

                            if (empty($preparationStatuses)) {
                                $preparationLabel = 'Belum';
                                $preparationClass = 'badge-secondary';
                            } elseif (
                                !array_diff(
                                    $preparationStatuses,
                                    ['completed', 'not_required']
                                )
                            ) {
                                $preparationLabel = 'Selesai';
                                $preparationClass = 'badge-approved';
                            } elseif (in_array('in_progress', $preparationStatuses, true)) {
                                $preparationLabel = 'Proses';
                                $preparationClass = 'badge-warning';
                            } else {
                                $preparationLabel = 'Pending';
                                $preparationClass = 'badge-rejected';
                            }

                            if ($booking['followup_status'] === 'completed') {
                                $followupLabel = 'Selesai';
                                $followupClass = 'badge-approved';
                            } elseif ($booking['followup_status'] === 'pending') {
                                $followupLabel = 'Pending';
                                $followupClass = 'badge-warning';
                            } else {
                                $followupLabel = 'Belum';
                                $followupClass = 'badge-secondary';
                            }

                            if ($booking['handover_status'] === 'completed') {
                                $handoverLabel = 'Selesai';
                                $handoverClass = 'badge-approved';
                            } elseif ($booking['handover_status'] === 'checked_in') {
                                $handoverLabel = 'Check-in';
                                $handoverClass = 'badge-warning';
                            } elseif ($booking['handover_status'] === 'pending') {
                                $handoverLabel = 'Pending';
                                $handoverClass = 'badge-warning';
                            } else {
                                $handoverLabel = 'Belum';
                                $handoverClass = 'badge-secondary';
                            }

                            $stageLabels = [
                                $preparationLabel,
                                $followupLabel,
                                $handoverLabel
                            ];

                            if (
                                $preparationLabel === 'Selesai' &&
                                $followupLabel === 'Selesai' &&
                                $handoverLabel === 'Selesai'
                            ) {
                                $overallLabel = 'Selesai';
                                $overallClass = 'badge-approved';
                            } elseif (
                                in_array('Proses', $stageLabels, true) ||
                                in_array('Check-in', $stageLabels, true)
                            ) {
                                $overallLabel = 'Proses';
                                $overallClass = 'badge-warning';
                            } elseif (
                                in_array('Pending', $stageLabels, true)
                            ) {
                                $overallLabel = 'Pending';
                                $overallClass = 'badge-rejected';
                            } else {
                                $overallLabel = 'Belum';
                                $overallClass = 'badge-secondary';
                            }
                        ?>

                        <td>
                            <span class="badge <?= $preparationClass ?>">
                                <?= e($preparationLabel) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge <?= $followupClass ?>">
                                <?= e($followupLabel) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge <?= $handoverClass ?>">
                                <?= e($handoverLabel) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge <?= $overallClass ?>">
                                <?= e($overallLabel) ?>
                            </span>
                        </td>

                        <td>
                            <a href="operational_detail.php?id=<?= (int) $booking['id'] ?>"
                               class="btn btn-sm">
                                Detail
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>






