<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login_admin.php');
    exit;
}
// KONFIGURASI BATAS AMAN (Hanya berlaku untuk WD Owner)
define('MINIMUM_SALDO_OPERASIONAL', 1000000); 
?>