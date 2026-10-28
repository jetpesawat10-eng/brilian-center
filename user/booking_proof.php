<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireUser();

$userId = (int) $_SESSION['user_id'];
$bookingId = (int) ($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    exit('Bukti peminjaman tidak ditemukan.');
}

$stmt = $pdo->prepare("
    SELECT
        b.*,
        r.name AS room_name,
        u.name AS user_name,
        u.email AS user_email
    FROM bookings b
    LEFT JOIN rooms r ON r.id = b.room_id
    LEFT JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
      AND b.user_id = ?
      AND b.status = 'approved'
    LIMIT 1
");

$stmt->execute([$bookingId, $userId]);

$booking = $stmt->fetch();

if (!$booking) {
    exit('Bukti peminjaman tidak tersedia.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Peminjaman #<?= (int) $booking['id'] ?> - BRILiaN CENTER</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px;
            background: #eef3f8;
            font-family: Arial, Helvetica, sans-serif;
            color: #243447;
        }

        .proof-wrapper {
            max-width: 850px;
            margin: 0 auto;
        }

        .proof-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(15, 42, 70, .12);
        }

        .proof-header {
            padding: 30px 34px;
            background: linear-gradient(135deg, #0f4c81, #2563eb);
            color: #fff;
        }

        .brand {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: .9;
        }

        .proof-header h1 {
            margin: 8px 0 5px;
            font-size: 28px;
        }

        .proof-header p {
            margin: 0;
            opacity: .9;
            font-size: 14px;
        }

        .proof-body {
            padding: 32px 34px;
        }

        .proof-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
            padding-bottom: 22px;
            border-bottom: 1px solid #e5eaf0;
        }

        .booking-number {
            font-size: 14px;
            color: #718096;
        }

        .booking-number strong {
            display: block;
            margin-top: 4px;
            color: #243447;
            font-size: 20px;
        }

        .status-approved {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 700;
        }

        .section {
            margin-bottom: 28px;
        }

        .section-title {
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #e5eaf0;
            border-radius: 12px;
            overflow: hidden;
        }

        .info-item {
            padding: 15px 17px;
            border-bottom: 1px solid #e5eaf0;
        }

        .info-item:nth-child(odd) {
            border-right: 1px solid #e5eaf0;
        }

        .info-label {
            display: block;
            margin-bottom: 5px;
            color: #718096;
            font-size: 12px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #243447;
        }

        .agenda {
            padding: 16px;
            border-radius: 12px;
            background: #f5f8fc;
            border: 1px solid #e5eaf0;
            font-size: 14px;
            line-height: 1.6;
        }

        .proof-footer {
            padding: 22px 34px;
            background: #f8fafc;
            border-top: 1px solid #e5eaf0;
            color: #718096;
            font-size: 12px;
            text-align: center;
        }

        .print-actions {
            max-width: 850px;
            margin: 18px auto 0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .print-btn,
        .back-btn {
            border-radius: 9px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .print-btn {
            border: 0;
            background: #2563eb;
            color: #fff;
        }

        .back-btn {
            display: inline-block;
            background: #fff;
            color: #2563eb;
            text-decoration: none;
            border: 1px solid #dbe3ec;
        }

        @media (max-width: 650px) {
            body { padding: 12px; }

            .proof-header,
            .proof-body,
            .proof-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .proof-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item:nth-child(odd) {
                border-right: none;
            }

            .print-actions {
                flex-direction: column;
            }

            .print-btn,
            .back-btn {
                width: 100%;
                text-align: center;
            }
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .proof-card {
                box-shadow: none;
                border-radius: 0;
            }

            .print-actions {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="proof-wrapper">

    <div class="proof-card">

        <div class="proof-header">
            <div class="brand">BRILiaN CENTER</div>
            <h1>Bukti Peminjaman Ruangan</h1>
            <p>Dokumen konfirmasi peminjaman ruangan</p>
        </div>

        <div class="proof-body">

            <div class="proof-top">
                <div class="booking-number">
                    Nomor Peminjaman
                    <strong>#<?= (int) $booking['id'] ?></strong>
                </div>

                <div class="status-approved">
                    <i class="bi bi-check-circle-fill"></i> DISETUJUI
                </div>
            </div>

            <div class="section">

                <div class="section-title">
                    Informasi Peminjaman
                </div>

                <div class="info-grid">

                    <div class="info-item">
                        <span class="info-label">Nama Peminjam</span>
                        <span class="info-value">
                            <?= e($booking['user_name'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value">
                            <?= e($booking['user_email'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Ruangan</span>
                        <span class="info-value">
                            <?= e($booking['room_name'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Tanggal</span>
                        <span class="info-value">
                            <?= e($booking['tanggal'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Waktu</span>
                        <span class="info-value">
                            <?= e($booking['jam_mulai'] ?: '-') ?>
                            -
                            <?= e($booking['jam_selesai'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Divisi</span>
                        <span class="info-value">
                            <?= e($booking['divisi'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Jumlah Peserta</span>
                        <span class="info-value">
                            <?= $booking['peserta'] !== null ? e($booking['peserta']) . ' orang' : '-' ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Dihadiri VIP</span>
                        <span class="info-value">
                            <?= e($booking['dihadiri_vip'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Nama PIC</span>
                        <span class="info-value">
                            <?= e($booking['pic_name'] ?: '-') ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">No. Tlp PIC</span>
                        <span class="info-value">
                            <?= e($booking['pic_phone'] ?: '-') ?>
                        </span>
                    </div>

                </div>

            </div>

            <div class="section">

                <div class="section-title">
                    Agenda / Keperluan
                </div>

                <div class="agenda">
                    <?= nl2br(e($booking['keperluan'] ?: '-')) ?>
                </div>

            </div>

        </div>

        <div class="proof-footer">
            BRILiaN CENTER — Bukti peminjaman ini dibuat oleh sistem SiPeminjaman.
        </div>

    </div>

    <div class="print-actions">

        <button type="button"
                class="print-btn"
                onclick="window.print();">
            🖨 Cetak / Simpan PDF
        </button>

        <a href="my_bookings.php" class="back-btn">
            ← Kembali
        </a>

    </div>

</div>

</body>
</html>

