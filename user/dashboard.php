<?php
require_once __DIR__ . '/../includes/auth.php';

requireUser();

header('Location: book.php');
exit;
