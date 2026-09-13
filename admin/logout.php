<?php
/**
 * admin/logout.php
 * Hapus session admin lalu lempar balik ke login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

header('Location: login.php');
exit;
