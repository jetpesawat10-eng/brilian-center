<?php
require_once __DIR__ . '/auth.php';
$base = basePath();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>BRILIaN CENTER</title>
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="navbar">

    <div class="brand">
        <span class="brand-building"><i class="bi bi-buildings"></i></span>
        <span>BRILIaN CENTER</span>
    </div>

    <nav>

        <?php if (isLoggedIn()): ?>

            <?php if (isAdmin()): ?>

                <a href="<?= $base ?>admin/dashboard.php">Dashboard</a>
                <a href="<?= $base ?>admin/rooms.php">Kelola Ruangan</a>
                <a href="<?= $base ?>admin/bookings.php">Kelola Peminjaman</a>
                <a href="<?= $base ?>admin/operational.php">Operasional</a>
                <a href="<?= $base ?>admin/users.php">Kelola User</a>

            <?php else: ?>

                <a href="<?= $base ?>user/book.php">Pilih Ruangan</a>
                <a href="<?= $base ?>user/my_bookings.php">Peminjaman Saya</a>

            <?php endif; ?>

            <a href="<?= $base ?>logout.php">
                Logout (<?= e($_SESSION['name']) ?>)
            </a>

        <?php endif; ?>

    </nav>

</div>

<div class="container">


<link rel="stylesheet" href="../assets/css/theme-cream.css">





