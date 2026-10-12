<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

function pdfText($value): string
{
    return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8');
}

$peserta = $booking['peserta'] !== null
    ? pdfText($booking['peserta']) . ' orang'
    : '-';

$agenda = nl2br(pdfText($booking['keperluan'] ?: '-'));

$html = '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<style>
    @page {
        margin: 28px 30px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: DejaVu Sans, Arial, sans-serif;
        color: #3F3A33;
        font-size: 12px;
        background: #ffffff;
    }

    .header {
        background: #E8DCC8;
        border: 1px solid #D8C8AF;
        padding: 22px 24px;
        border-radius: 12px;
    }

    .brand {
        color: #8B7355;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .title {
        margin-top: 7px;
        color: #3F3A33;
        font-size: 22px;
        font-weight: bold;
    }

    .subtitle {
        margin-top: 5px;
        color: #756E63;
        font-size: 10px;
    }

    .top {
        margin-top: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #E3D9C8;
    }

    .booking-label {
        color: #756E63;
        font-size: 10px;
    }

    .booking-number {
        margin-top: 4px;
        color: #3F3A33;
        font-size: 17px;
        font-weight: bold;
    }

    .approved {
        margin-top: 9px;
        display: inline-block;
        padding: 6px 11px;
        background: #E7F1E8;
        color: #4F7A5A;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
    }

    .section {
        margin-top: 22px;
    }

    .section-title {
        margin-bottom: 9px;
        color: #8B7355;
        font-size: 10px;
        font-weight: bold;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    table.info {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #E3D9C8;
    }

    table.info td {
        width: 50%;
        padding: 10px 12px;
        border: 1px solid #E3D9C8;
        vertical-align: top;
    }

    .label {
        display: block;
        margin-bottom: 4px;
        color: #756E63;
        font-size: 9px;
    }

    .value {
        color: #3F3A33;
        font-size: 11px;
        font-weight: bold;
    }

    .agenda {
        padding: 12px;
        border: 1px solid #E3D9C8;
        background: #F7F3EA;
        line-height: 1.55;
        font-size: 11px;
    }

    .footer {
        margin-top: 28px;
        padding-top: 12px;
        border-top: 1px solid #E3D9C8;
        text-align: center;
        color: #756E63;
        font-size: 8px;
    }
</style>
</head>

<body>

<div class="header">
    <div class="brand">BRILIaN CENTER</div>
    <div class="title">Bukti Peminjaman Ruangan</div>
    <div class="subtitle">
        Dokumen konfirmasi peminjaman ruangan dari sistem SiPeminjaman
    </div>
</div>

<div class="top">
    <div class="booking-label">Nomor Peminjaman</div>
    <div class="booking-number">#' . (int) $booking['id'] . '</div>
    <div class="approved">DISETUJUI</div>
</div>

<div class="section">
    <div class="section-title">Informasi Peminjaman</div>

    <table class="info">
        <tr>
            <td>
                <span class="label">Nama Peminjam</span>
                <span class="value">' . pdfText($booking['user_name'] ?: '-') . '</span>
            </td>
            <td>
                <span class="label">Email</span>
                <span class="value">' . pdfText($booking['user_email'] ?: '-') . '</span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Ruangan</span>
                <span class="value">' . pdfText($booking['room_name'] ?: '-') . '</span>
            </td>
            <td>
                <span class="label">Tanggal</span>
                <span class="value">' . pdfText($booking['tanggal'] ?: '-') . '</span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Waktu</span>
                <span class="value">' .
                    pdfText($booking['jam_mulai'] ?: '-') .
                    ' - ' .
                    pdfText($booking['jam_selesai'] ?: '-') .
                '</span>
            </td>
            <td>
                <span class="label">Divisi</span>
                <span class="value">' . pdfText($booking['divisi'] ?: '-') . '</span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Jumlah Peserta</span>
                <span class="value">' . $peserta . '</span>
            </td>
            <td>
                <span class="label">Dihadiri VIP</span>
                <span class="value">' . pdfText($booking['dihadiri_vip'] ?: '-') . '</span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Nama PIC</span>
                <span class="value">' . pdfText($booking['pic_name'] ?: '-') . '</span>
            </td>
            <td>
                <span class="label">No. Tlp PIC</span>
                <span class="value">' . pdfText($booking['pic_phone'] ?: '-') . '</span>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Agenda / Keperluan</div>

    <div class="agenda">
        ' . $agenda . '
    </div>
</div>

<div class="footer">
    BRILIaN CENTER — Bukti peminjaman ini dibuat oleh sistem SiPeminjaman.
</div>

</body>
</html>
';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Bukti-Peminjaman-' . (int) $booking['id'] . '.pdf';

$dompdf->stream($filename, [
    'Attachment' => true
]);

exit;
