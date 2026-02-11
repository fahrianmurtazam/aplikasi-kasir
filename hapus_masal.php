<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    // Pastikan semua ID adalah angka untuk keamanan (Sanitize)
    $clean_ids = array_map('intval', $_POST['ids']);
    $ids_string = implode(',', $clean_ids);
    
    // Query menggunakan IN tanpa tanda kutip untuk tipe data INT
    $query = "DELETE FROM transaksi WHERE no_nota IN ($ids_string)";
    
    if (mysqli_query($conn, $query)) {
        header("Location: index.php?msg=deleted");
        exit;
    } else {
        header("Location: index.php?msg=error");
        exit;
    }
} else {
    // Jika mencoba akses langsung tanpa POST ids
    header("Location: index.php");
    exit;
}
?>