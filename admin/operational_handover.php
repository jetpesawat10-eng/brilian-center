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

/* Ambil data serah terima */
$stmt = $pdo->prepare("
    SELECT *
    FROM room_handovers
    WHERE booking_id = ?
    LIMIT 1
");
$stmt->execute([$bookingId]);
$handover = $stmt->fetch();

if (!$handover) {
    $handover = [
        'handover_status' => 'pending',
        'checkin_time' => '',
        'room_condition' => '',
        'inventory_condition' => '',
        'notes' => '',
        'admin_name' => '',
        'customer_confirmation' => '',
        'handover_at' => ''
    ];
}

$success = '';

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success = 'Data Hari H / Serah Terima berhasil disimpan.';
}

/* Simpan data serah terima */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handoverStatus = $_POST['handover_status'] ?? 'pending';
    $checkinTime = trim($_POST['checkin_time'] ?? '');
    $roomCondition = trim($_POST['room_condition'] ?? '');
    $inventoryCondition = trim($_POST['inventory_condition'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? '');
    $customerConfirmation = trim($_POST['customer_confirmation'] ?? '');

    $allowedStatuses = ['pending', 'checked_in', 'completed'];

    if (!in_array($handoverStatus, $allowedStatuses, true)) {
        $handoverStatus = 'pending';
    }

    $checkinDateTime = $checkinTime !== '' ? $checkinTime : null;

    $handoverAt = null;

    if ($handoverStatus === 'completed') {
        $handoverAt = date('Y-m-d H:i:s');
    }

    $stmt = $pdo->prepare("
        INSERT INTO room_handovers (
            booking_id,
            checkin_time,
            room_condition,
            inventory_condition,
            handover_status,
            notes,
            admin_name,
            customer_confirmation,
            handover_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            checkin_time = VALUES(checkin_time),
            room_condition = VALUES(room_condition),
            inventory_condition = VALUES(inventory_condition),
            handover_status = VALUES(handover_status),
            notes = VALUES(notes),
            admin_name = VALUES(admin_name),
            customer_confirmation = VALUES(customer_confirmation),
            handover_at = VALUES(handover_at)
    ");

    $stmt->execute([
        $bookingId,
        $checkinDateTime,
        $roomCondition,
        $inventoryCondition,
        $handoverStatus,
        $notes,
        $adminName,
        $customerConfirmation,
        $handoverAt
    ]);

    header('Location: operational_handover.php?id=' . $bookingId . '&saved=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hari H / Serah Terima - BRILIaN CENTER</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme-cream.css">

    <style>
        body {
            background: #F7F3EA;
        }

        .handover-page {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px 40px;
        }

        .handover-header {
            margin-bottom: 22px;
        }

        .handover-header h1 {
            margin: 0 0 6px;
            color: #3F3A33;
        }

        .handover-header p {
            margin: 0;
            color: #756E63;
        }

        .handover-card {
            background: #FFFFFF;
            border: 1px solid #E3D9C8;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 8px 22px rgba(139,115,85,.08);
        }

        .handover-card h2 {
            margin-top: 0;
            color: #3F3A33;
            font-size: 20px;
        }

        .booking-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 20px;
        }

        .summary-item {
            padding: 12px;
            background: #F7F3EA;
            border: 1px solid #E3D9C8;
            border-radius: 10px;
        }

        .summary-label {
            font-size: 12px;
            color: #756E63;
            margin-bottom: 4px;
        }

        .summary-value {
            color: #3F3A33;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #3F3A33;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #E3D9C8;
            border-radius: 9px;
            background: #FFFFFF;
            color: #3F3A33;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8B7355;
            box-shadow: 0 0 0 3px rgba(139,115,85,.10);
        }

        .handover-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .handover-actions a,
        .handover-actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 9px;
            border: 1px solid #8B7355;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
        }

        .btn-save {
            background: #8B7355;
            color: #FFFFFF;
        }

        .btn-save:hover {
            background: #735E46;
        }

        .btn-back {
            background: #FFFFFF;
            color: #8B7355;
        }

        .btn-back:hover {
            background: #EFE7D8;
        }

        .alert-success {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #EDF5EE;
            border: 1px solid #B9D0BC;
            color: #426748;
        }

        @media (max-width: 700px) {
            .booking-summary,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .handover-page {
                padding: 0 12px 30px;
            }

            .handover-card {
                padding: 18px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="handover-page">

    <div class="handover-header">
        <h1>Hari H / Serah Terima Ruangan</h1>
        <p>Pencatatan check-in, kondisi ruangan, inventaris, dan berita acara serah terima.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <div class="handover-card">
        <h2>Informasi Booking</h2>

        <div class="booking-summary">
            <div class="summary-item">
                <div class="summary-label">Nomor Booking</div>
                <div class="summary-value">#<?= (int) $booking['id'] ?></div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Pemohon</div>
                <div class="summary-value"><?= e($booking['user_name']) ?></div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Ruangan</div>
                <div class="summary-value"><?= e($booking['room_name']) ?></div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Lokasi</div>
                <div class="summary-value"><?= e($booking['room_location']) ?></div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Tanggal</div>
                <div class="summary-value"><?= e($booking['tanggal']) ?></div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Waktu</div>
                <div class="summary-value">
                    <?= e($booking['jam_mulai']) ?> - <?= e($booking['jam_selesai']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="handover-card">
        <h2>Form Hari H / Serah Terima</h2>

        <form method="post">
            <div class="form-grid">

                <div class="form-group">
                    <label for="handover_status">Status</label>
                    <select name="handover_status" id="handover_status">
                        <option value="pending" <?= $handover['handover_status'] === 'pending' ? 'selected' : '' ?>>
                            Pending
                        </option>
                        <option value="checked_in" <?= $handover['handover_status'] === 'checked_in' ? 'selected' : '' ?>>
                            Check-in
                        </option>
                        <option value="completed" <?= $handover['handover_status'] === 'completed' ? 'selected' : '' ?>>
                            Selesai
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="checkin_time">Waktu Check-in</label>
                    <input
                        type="datetime-local"
                        name="checkin_time"
                        id="checkin_time"
                        value="<?= $handover['checkin_time'] ? date('Y-m-d\TH:i', strtotime($handover['checkin_time'])) : '' ?>"
                    >
                </div>

                <div class="form-group full">
                    <label for="room_condition">Kondisi Ruangan</label>
                    <textarea
                        name="room_condition"
                        id="room_condition"
                        placeholder="Contoh: Ruangan bersih, meja dan kursi sesuai layout, AC berfungsi."
                    ><?= e($handover['room_condition']) ?></textarea>
                </div>

                <div class="form-group full">
                    <label for="inventory_condition">Kondisi Inventaris / Fasilitas</label>
                    <textarea
                        name="inventory_condition"
                        id="inventory_condition"
                        placeholder="Contoh: Projector, microphone, Wi-Fi, meja, kursi dalam kondisi baik."
                    ><?= e($handover['inventory_condition']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="admin_name">Nama Admin / Petugas</label>
                    <input
                        type="text"
                        name="admin_name"
                        id="admin_name"
                        value="<?= e($handover['admin_name']) ?>"
                        placeholder="Nama petugas"
                    >
                </div>

                <div class="form-group">
                    <label for="customer_confirmation">Konfirmasi Customer</label>
                    <input
                        type="text"
                        name="customer_confirmation"
                        id="customer_confirmation"
                        value="<?= e($handover['customer_confirmation']) ?>"
                        placeholder="Nama / konfirmasi customer"
                    >
                </div>

                <div class="form-group full">
                    <label for="notes">Catatan</label>
                    <textarea
                        name="notes"
                        id="notes"
                        placeholder="Catatan tambahan mengenai serah terima ruangan."
                    ><?= e($handover['notes']) ?></textarea>
                </div>

            </div>

            <div class="handover-actions">
                <button type="submit" class="btn-save">
                    Simpan Serah Terima
                </button>

                <a href="operational_detail.php?id=<?= (int) $booking['id'] ?>" class="btn-back">
                    Kembali ke Detail Operasional
                </a>
            </div>
        </form>
    </div>

</div>

</body>
</html>
