<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($bookingId <= 0) {
    header('Location: operational.php');
    exit;
}

/* Ambil booking yang sudah disetujui */
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

$allowedStatuses = [
    'pending' => 'Pending',
    'in_progress' => 'Sedang Diproses',
    'completed' => 'Selesai',
    'not_required' => 'Tidak Diperlukan'
];

$fields = [
    'cleaning_status',
    'technician_status',
    'layout_status',
    'projector_status',
    'microphone_status',
    'wifi_status',
    'catering_status'
];

$success = '';

/* Simpan checklist */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $values = [];

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? 'pending';

        if (!array_key_exists($value, $allowedStatuses)) {
            $value = 'pending';
        }

        $values[$field] = $value;
    }

    $notes = trim($_POST['notes'] ?? '');

    $stmt = $pdo->prepare("
        INSERT INTO operational_preparations (
            booking_id,
            cleaning_status,
            technician_status,
            layout_status,
            projector_status,
            microphone_status,
            wifi_status,
            catering_status,
            notes
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            cleaning_status = VALUES(cleaning_status),
            technician_status = VALUES(technician_status),
            layout_status = VALUES(layout_status),
            projector_status = VALUES(projector_status),
            microphone_status = VALUES(microphone_status),
            wifi_status = VALUES(wifi_status),
            catering_status = VALUES(catering_status),
            notes = VALUES(notes)
    ");

    $stmt->execute([
        $bookingId,
        $values['cleaning_status'],
        $values['technician_status'],
        $values['layout_status'],
        $values['projector_status'],
        $values['microphone_status'],
        $values['wifi_status'],
        $values['catering_status'],
        $notes !== '' ? $notes : null
    ]);

    $success = 'Checklist persiapan berhasil disimpan.';
}

/* Ambil data checklist terbaru */
$stmt = $pdo->prepare("
    SELECT *
    FROM operational_preparations
    WHERE booking_id = ?
    LIMIT 1
");
$stmt->execute([$bookingId]);
$preparation = $stmt->fetch();

if (!$preparation) {
    $preparation = [
        'cleaning_status' => 'pending',
        'technician_status' => 'pending',
        'layout_status' => 'pending',
        'projector_status' => 'pending',
        'microphone_status' => 'pending',
        'wifi_status' => 'pending',
        'catering_status' => 'pending',
        'notes' => ''
    ];
}

$pageTitle = 'Detail Operasional';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-heading">
    <div>
        <h2 class="admin-page-title">Detail Operasional</h2>
        <p class="admin-page-subtitle">
            Checklist persiapan untuk booking #<?= (int) $booking['id'] ?>.
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
            <span class="operational-label">Waktu</span>
            <strong>
                <?= e($booking['jam_mulai']) ?> -
                <?= e($booking['jam_selesai']) ?>
            </strong>
        </div>

        <div>
            <span class="operational-label">Peserta</span>
            <strong>
                <?= $booking['peserta'] !== null
                    ? e($booking['peserta']) . ' orang'
                    : '-' ?>
            </strong>
        </div>

    </div>

    <div class="operational-section-title">
        Persiapan Ruangan & Fasilitas
    </div>

    <form method="post">

        <div class="operational-checklist">

            <?php
            $checklist = [
                'cleaning_status' => 'Cleaning Service',
                'layout_status' => 'Layout Ruangan',
                'technician_status' => 'Teknisi / Fasilitas',
                'projector_status' => 'Projector',
                'microphone_status' => 'Microphone',
                'wifi_status' => 'Wi-Fi',
                'catering_status' => 'Konsumsi'
            ];
            ?>

            <?php foreach ($checklist as $field => $label): ?>

                <div class="operational-check-item">

                    <div>
                        <strong><?= e($label) ?></strong>
                        <small>
                            Status persiapan <?= e(strtolower($label)) ?>.
                        </small>
                    </div>

                    <select name="<?= e($field) ?>" class="operational-status-select">

                        <?php foreach ($allowedStatuses as $value => $statusLabel): ?>

                            <option
                                value="<?= e($value) ?>"
                                <?= $preparation[$field] === $value ? 'selected' : '' ?>
                            >
                                <?= e($statusLabel) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="form-group operational-notes">

            <label for="notes">Catatan Operasional</label>

            <textarea
                id="notes"
                name="notes"
                rows="4"
                placeholder="Contoh: layout classroom, projector HDMI, microphone 2 unit."
            ><?= e($preparation['notes']) ?></textarea>

        </div>

        <div class="operational-actions">

            <a href="operational.php" class="btn btn-outline">
                Kembali
            </a>

            <a href="operational_followup.php?id=<?= (int) $booking['id'] ?>"
               class="btn btn-outline">
                Follow-up H-1
            </a>

            <a href="operational_handover.php?id=<?= (int) $booking['id'] ?>"
               class="btn btn-outline">
                Hari H / Serah Terima
            </a>

            <button type="submit" class="btn">
                Simpan Checklist
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
    margin-bottom: 14px;
    font-size: 18px;
    font-weight: 800;
    color: #3F3A33;
}

.operational-checklist {
    display: grid;
    gap: 10px;
}

.operational-check-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 18px;
    background: #FFFFFF;
    border: 1px solid #E3D9C8;
    border-radius: 10px;
}

.operational-check-item > div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.operational-check-item strong {
    color: #3F3A33;
}

.operational-check-item small {
    color: #756E63;
}

.operational-status-select {
    min-width: 180px;
}

.operational-notes {
    margin-top: 20px;
}

.operational-notes textarea {
    width: 100%;
    box-sizing: border-box;
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

    .operational-check-item {
        align-items: stretch;
        flex-direction: column;
    }

    .operational-status-select {
        width: 100%;
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


