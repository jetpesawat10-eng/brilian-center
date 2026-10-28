<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireUser();

$error = '';
$success = '';
$oldRoomId = $_POST['room_id'] ?? '';

$rooms = $pdo->query("
    SELECT *
    FROM rooms
    WHERE status = 'available'
    ORDER BY name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = $_POST['room_id'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $jamMulai = $_POST['jam_mulai'] ?? '';
    $jamSelesai = $_POST['jam_selesai'] ?? '';
    $agenda = trim($_POST['keperluan'] ?? '');
    $divisi = trim($_POST['divisi'] ?? '');
    $peserta = (int)($_POST['peserta'] ?? 0);
    $picName = trim($_POST['pic_name'] ?? '');
    $picPhone = trim($_POST['pic_phone'] ?? '');
    $dihadiriVip = $_POST['dihadiri_vip'] ?? '';

    if (
        !$roomId ||
        !$tanggal ||
        !$jamMulai ||
        !$jamSelesai ||
        $agenda === '' ||
        $divisi === '' ||
        $peserta <= 0 ||
        $picName === '' ||
        $picPhone === '' ||
        !in_array($dihadiriVip, ['Ya', 'Tidak'], true)
    ) {
        $error = 'Semua field wajib diisi.';
    } elseif ($jamSelesai <= $jamMulai) {
        $error = 'Jam selesai harus setelah jam mulai.';
    } elseif ($tanggal < date('Y-m-d')) {
        $error = 'Tanggal tidak boleh di masa lalu.';
    } else {
        $roomCheck = $pdo->prepare("
            SELECT *
            FROM rooms
            WHERE id = ? AND status = 'available'
            LIMIT 1
        ");
        $roomCheck->execute([$roomId]);
        $selectedRoom = $roomCheck->fetch();

        if (!$selectedRoom) {
            $error = 'Ruangan yang dipilih tidak tersedia.';
        } elseif (
            $selectedRoom['capacity'] !== null &&
            $peserta > (int)$selectedRoom['capacity']
        ) {
            $error = 'Jumlah peserta melebihi kapasitas ruangan.';
        } else {
            $check = $pdo->prepare("
                SELECT COUNT(*) c
                FROM bookings
                WHERE room_id = ?
                  AND tanggal = ?
                  AND status IN ('pending', 'approved')
                  AND (jam_mulai < ? AND jam_selesai > ?)
            ");

            $check->execute([
                $roomId,
                $tanggal,
                $jamSelesai,
                $jamMulai
            ]);

            if ($check->fetch()['c'] > 0) {
                $error = 'Ruangan sudah dipesan pada rentang waktu tersebut. Silakan pilih waktu lain.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO bookings
                    (
                        user_id,
                        room_id,
                        tanggal,
                        jam_mulai,
                        jam_selesai,
                        keperluan,
                        divisi,
                        peserta,
                        pic_name,
                        pic_phone,
                        dihadiri_vip
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $_SESSION['user_id'],
                    $roomId,
                    $tanggal,
                    $jamMulai,
                    $jamSelesai,
                    $agenda,
                    $divisi,
                    $peserta,
                    $picName,
                    $picPhone,
                    $dihadiriVip
                ]);

                $success = 'Pengajuan peminjaman berhasil dikirim. Menunggu persetujuan admin.';
                $oldRoomId = '';
            }
        }
    }
}

$pageTitle = 'Ajukan Peminjaman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="booking-catalog-head">
    <div>
        <div class="booking-eyebrow">BRILiaN CENTER</div>
        <h2 class="booking-page-title">Pilih Ruangan</h2>
        <p class="booking-page-subtitle">
            Pilih ruangan yang sesuai dengan kebutuhan kegiatan Anda.
        </p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger booking-alert">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success booking-alert">
        <?= e($success) ?>
    </div>
<?php endif; ?>

<?php if (empty($rooms)): ?>

    <div class="card booking-empty">
        <h3>Tidak ada ruangan tersedia</h3>
        <p class="text-muted">
            Belum ada ruangan yang tersedia saat ini.
        </p>
    </div>

<?php else: ?>

    <div class="room-grid booking-catalog">

        <?php foreach ($rooms as $r): ?>

            <div class="room-card">

                <div class="room-image">
                    <?php if (!empty($r['photo'])): ?>
                        <img
                            src="../assets/uploads/rooms/<?= e($r['photo']) ?>"
                            alt="<?= e($r['name']) ?>"
                        >
                    <?php else: ?>
                        <div class="room-placeholder">
                            Belum ada foto
                        </div>
                    <?php endif; ?>
                </div>

                <div class="room-card-body">

                    <h3><?= e($r['name']) ?></h3>

                    <div class="room-meta">
                        <i class="bi bi-geo-alt"></i> <?= e($r['location'] ?: 'Lokasi belum diisi') ?>
                        <br>
                        <i class="bi bi-people"></i> Maks. <?= $r['capacity'] !== null ? e($r['capacity']) . ' orang' : 'Tidak ditentukan' ?>
                    </div>

                    <p>
                        <?= e($r['description'] ?: 'Belum ada deskripsi ruangan.') ?>
                    </p>

                    <button
                        type="button"
                        class="btn room-select-btn"
                        data-room-id="<?= e($r['id']) ?>"
                        data-room-name="<?= e($r['name']) ?>"
                        data-room-capacity="<?= $r['capacity'] !== null ? e($r['capacity']) : '' ?>"
                    >
                        Pilih Ruangan
                    </button>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<!-- MODAL FORM PEMINJAMAN -->
<div class="booking-modal" id="bookingModal">

    <div class="booking-modal-overlay" id="bookingModalOverlay"></div>

    <div class="booking-modal-box">

        <button
            type="button"
            class="booking-modal-close"
            id="bookingModalClose"
            aria-label="Tutup"
        >
            ×
        </button>

        <div class="booking-modal-header">
            <div class="booking-modal-kicker">
                FORM PEMINJAMAN
            </div>

            <h3>Detail Peminjaman</h3>

            <div class="selected-room-display">
                <span>Ruangan dipilih</span>
                <strong id="selectedRoomName">-</strong>
            </div>
        </div>

        <form method="POST" id="bookingForm">

            <input
                type="hidden"
                name="room_id"
                id="modalRoomId"
                value="<?= e($oldRoomId) ?>"
            >

            <div class="booking-form-grid">

                <div class="booking-field">
                    <label for="tanggal">Hari / Tanggal</label>
                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        min="<?= date('Y-m-d') ?>"
                        required
                    >
                </div>

                <div class="booking-field booking-field-full">
                    <label>Waktu</label>

                    <div class="time-row">
                        <input
                            type="time"
                            name="jam_mulai"
                            id="jamMulai"
                            required
                        >

                        <span>sampai</span>

                        <input
                            type="time"
                            name="jam_selesai"
                            id="jamSelesai"
                            required
                        >
                    </div>
                </div>

                <div class="booking-field booking-field-full">
                    <label for="keperluan">Agenda</label>
                    <textarea
                        id="keperluan"
                        name="keperluan"
                        rows="2"
                        placeholder="Contoh: Rapat koordinasi tim"
                        required
                    ></textarea>
                </div>

                <div class="booking-field">
                    <label for="divisi">Divisi</label>
                    <input
                        type="text"
                        id="divisi"
                        name="divisi"
                        placeholder="Contoh: Divisi IT"
                        required
                    >
                </div>

                <div class="booking-field">
                    <label for="peserta">
                        Jumlah Peserta
                        <small id="capacityHint"></small>
                    </label>

                    <input
                        type="number"
                        id="peserta"
                        name="peserta"
                        min="1"
                        placeholder="Jumlah orang"
                        required
                    >
                </div>

                <div class="booking-field">
                    <label for="pic_name">Nama PIC</label>
                    <input
                        type="text"
                        id="pic_name"
                        name="pic_name"
                        placeholder="Nama penanggung jawab"
                        required
                    >
                </div>

                <div class="booking-field">
                    <label for="pic_phone">No. Tlp PIC</label>
                    <input
                        type="tel"
                        id="pic_phone"
                        name="pic_phone"
                        placeholder="08xxxxxxxxxx"
                        required
                    >
                </div>

                <div class="booking-field booking-field-full">
                    <label for="dihadiri_vip">Dihadiri VIP</label>

                    <select
                        id="dihadiri_vip"
                        name="dihadiri_vip"
                        required
                    >
                        <option value="">Pilih</option>
                        <option value="Tidak">Tidak</option>
                        <option value="Ya">Ya</option>
                    </select>
                </div>

            </div>

            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn booking-cancel-btn"
                    id="bookingModalCancel"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="btn booking-submit-btn"
                >
                    Kirim Pengajuan
                </button>

            </div>

        </form>

    </div>

</div>


<script>
(function () {

    const modal = document.getElementById('bookingModal');
    const modalRoomId = document.getElementById('modalRoomId');
    const selectedRoomName = document.getElementById('selectedRoomName');
    const capacityHint = document.getElementById('capacityHint');
    const pesertaInput = document.getElementById('peserta');

    const closeButton = document.getElementById('bookingModalClose');
    const cancelButton = document.getElementById('bookingModalCancel');
    const overlay = document.getElementById('bookingModalOverlay');

    function openModal(roomId, roomName, capacity) {

        modalRoomId.value = roomId;
        selectedRoomName.textContent = roomName;

        if (capacity) {
            capacityHint.textContent = '(maks. ' + capacity + ' orang)';
            pesertaInput.max = capacity;
        } else {
            capacityHint.textContent = '';
            pesertaInput.removeAttribute('max');
        }

        modal.classList.add('show');
        document.body.classList.add('modal-open');

        setTimeout(function () {
            document.getElementById('tanggal').focus();
        }, 100);
    }

    function closeModal() {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    document.querySelectorAll('.room-select-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            openModal(
                this.dataset.roomId,
                this.dataset.roomName,
                this.dataset.roomCapacity
            );

        });

    });

    closeButton.addEventListener('click', closeModal);
    cancelButton.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    pesertaInput.addEventListener('input', function () {

        const max = parseInt(this.max || '0', 10);

        if (max > 0 && parseInt(this.value || '0', 10) > max) {
            this.value = max;
        }

    });

    <?php if ($error && $oldRoomId): ?>

    const errorRoom = document.querySelector(
        '.room-select-btn[data-room-id="<?= e($oldRoomId) ?>"]'
    );

    if (errorRoom) {
        openModal(
            errorRoom.dataset.roomId,
            errorRoom.dataset.roomName,
            errorRoom.dataset.roomCapacity
        );
    }

    <?php endif; ?>

})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


