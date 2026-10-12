<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($bookingId <= 0) {
    header('Location: operational.php');
    exit;
}

/* Ambil booking approved */
$stmt = $pdo->prepare("
    SELECT
        b.*,
        r.name AS room_name,
        r.location AS room_location,
        u.name AS user_name,
        u.email AS user_email
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
      AND b.status = 'approved'
    LIMIT 1
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: operational.php');
    exit;
}

$success = '';

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success = 'Follow-up H-1 berhasil disimpan.';
}

/* Simpan follow-up */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $followupStatus = $_POST['followup_status'] ?? 'pending';

    if (!in_array($followupStatus, ['pending', 'completed'], true)) {
        $followupStatus = 'pending';
    }

    $arrivalTime = trim($_POST['arrival_time'] ?? '');
    $participantConfirmation = $_POST['participant_confirmation'] ?? '';
    $technicalConfirmation = trim($_POST['technical_confirmation'] ?? '');
    $checkinReminded = isset($_POST['checkin_reminded']) ? 1 : 0;
    $baReminded = isset($_POST['ba_reminded']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');

    $participantConfirmation =
        $participantConfirmation !== ''
        ? (int) $participantConfirmation
        : null;

    $arrivalTime =
        $arrivalTime !== ''
        ? $arrivalTime
        : null;

    $followedUpAt =
        $followupStatus === 'completed'
        ? date('Y-m-d H:i:s')
        : null;

    $stmt = $pdo->prepare("
        INSERT INTO operational_followups (
            booking_id,
            followup_status,
            arrival_time,
            participant_confirmation,
            technical_confirmation,
            checkin_reminded,
            ba_reminded,
            notes,
            followed_up_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            followup_status = VALUES(followup_status),
            arrival_time = VALUES(arrival_time),
            participant_confirmation = VALUES(participant_confirmation),
            technical_confirmation = VALUES(technical_confirmation),
            checkin_reminded = VALUES(checkin_reminded),
            ba_reminded = VALUES(ba_reminded),
            notes = VALUES(notes),
            followed_up_at = VALUES(followed_up_at)
    ");

    $stmt->execute([
        $bookingId,
        $followupStatus,
        $arrivalTime,
        $participantConfirmation,
        $technicalConfirmation !== '' ? $technicalConfirmation : null,
        $checkinReminded,
        $baReminded,
        $notes !== '' ? $notes : null,
        $followedUpAt
    ]);

    header('Location: operational_followup.php?id=' . $bookingId . '&saved=1');
    exit;
}

/* Ambil data follow-up */
$stmt = $pdo->prepare("
    SELECT *
    FROM operational_followups
    WHERE booking_id = ?
    LIMIT 1
");
$stmt->execute([$bookingId]);

$followup = $stmt->fetch();

if (!$followup) {
    $followup = [
        'followup_status' => 'pending',
        'arrival_time' => '',
        'participant_confirmation' => '',
        'technical_confirmation' => '',
        'checkin_reminded' => 0,
        'ba_reminded' => 0,
        'notes' => ''
    ];
}

$pageTitle = 'Follow-up H-1';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-heading">
    <div>
        <h2 class="admin-page-title">Follow-up H-1</h2>
        <p class="admin-page-subtitle">
            Konfirmasi customer sebelum hari pelaksanaan booking.
        </p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= e($success) ?>
    </div>
<?php endif; ?>

<div class="card operational-detail-card">

    <div class="operational-booking-summary">

        <div>
            <span class="operational-label">Booking</span>
            <strong>#<?= (int) $booking['id'] ?></strong>
        </div>

        <div>
            <span class="operational-label">User</span>
            <strong><?= e($booking['user_name']) ?></strong>
        </div>

        <div>
            <span class="operational-label">Ruangan</span>
            <strong><?= e($booking['room_name']) ?></strong>
        </div>

        <div>
            <span class="operational-label">Tanggal</span>
            <strong><?= e($booking['tanggal']) ?></strong>
        </div>

        <div>
            <span class="operational-label">Waktu Booking</span>
            <strong>
                <?= e($booking['jam_mulai']) ?> -
                <?= e($booking['jam_selesai']) ?>
            </strong>
        </div>

        <div>
            <span class="operational-label">PIC</span>
            <strong><?= e($booking['pic_name'] ?: '-') ?></strong>
        </div>

    </div>

    <div class="operational-section-title">
        Konfirmasi H-1
    </div>

    <form method="post">

        <div class="form-group">
            <label for="followup_status">Status Follow-up</label>

            <select id="followup_status" name="followup_status">
                <option value="pending"
                    <?= $followup['followup_status'] === 'pending' ? 'selected' : '' ?>>
                    Pending
                </option>

                <option value="completed"
                    <?= $followup['followup_status'] === 'completed' ? 'selected' : '' ?>>
                    Selesai
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="arrival_time">Perkiraan Jam Kedatangan</label>

            <input
                type="time"
                id="arrival_time"
                name="arrival_time"
                value="<?= e($followup['arrival_time'] ?? '') ?>"
            >
        </div>

        <div class="form-group">
            <label for="participant_confirmation">
                Jumlah Peserta yang Dikonfirmasi
            </label>

            <input
                type="number"
                id="participant_confirmation"
                name="participant_confirmation"
                min="0"
                value="<?= e($followup['participant_confirmation'] ?? '') ?>"
                placeholder="Jumlah peserta"
            >
        </div>

        <div class="form-group">
            <label for="technical_confirmation">
                Kebutuhan Teknis yang Dikonfirmasi
            </label>

            <textarea
                id="technical_confirmation"
                name="technical_confirmation"
                rows="4"
                placeholder="Contoh: projector HDMI, microphone 2 unit, Wi-Fi."
            ><?= e($followup['technical_confirmation'] ?? '') ?></textarea>
        </div>

        <div class="operational-followup-checks">

            <label class="operational-followup-check">
                <input
                    type="checkbox"
                    name="checkin_reminded"
                    value="1"
                    <?= !empty($followup['checkin_reminded']) ? 'checked' : '' ?>
                >
                <span>Customer sudah diingatkan mengenai check-in.</span>
            </label>

            <label class="operational-followup-check">
                <input
                    type="checkbox"
                    name="ba_reminded"
                    value="1"
                    <?= !empty($followup['ba_reminded']) ? 'checked' : '' ?>
                >
                <span>Customer sudah diingatkan mengenai BA / serah terima.</span>
            </label>

        </div>

        <div class="form-group operational-notes">
            <label for="notes">Catatan Follow-up</label>

            <textarea
                id="notes"
                name="notes"
                rows="4"
                placeholder="Catatan hasil komunikasi dengan customer."
            ><?= e($followup['notes'] ?? '') ?></textarea>
        </div>

        <div class="operational-actions">

            <a href="operational.php" class="btn btn-outline">
                Kembali
            </a>

            <button type="submit" class="btn">
                Simpan Follow-up
            </button>

        </div>

    </form>

</div>

<style>
.operational-detail-card {
    padding: 24px;
}

.operational-booking-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    padding: 18px;
    margin-bottom: 26px;
    background: #F7F3EA;
    border: 1px solid #E3D9C8;
    border-radius: 12px;
}

.operational-booking-summary > div {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.operational-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #756E63;
    font-weight: 700;
}

.operational-booking-summary strong {
    color: #3F3A33;
}

.operational-section-title {
    margin-bottom: 18px;
    font-size: 18px;
    font-weight: 800;
    color: #3F3A33;
}

.operational-followup-checks {
    display: grid;
    gap: 10px;
    margin: 18px 0;
}

.operational-followup-check {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    background: #F7F3EA;
    border: 1px solid #E3D9C8;
    border-radius: 10px;
    color: #3F3A33;
    cursor: pointer;
}

.operational-followup-check input {
    width: auto;
    margin: 0;
}

.operational-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

@media (max-width: 800px) {
    .operational-booking-summary {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .operational-booking-summary {
        grid-template-columns: 1fr;
    }

    .operational-actions {
        flex-direction: column-reverse;
    }

    .operational-actions .btn {
        width: 100%;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


