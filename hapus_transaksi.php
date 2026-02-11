<?php
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Hapus data
    mysqli_query($conn, "DELETE FROM transaksi WHERE no_nota = '$id'");
    
    // PERUBAHAN DISINI: Tambahkan ?msg=deleted agar index.php memunculkan notifikasi
    header("Location: index.php?msg=deleted");
    exit; // Praktik terbaik: selalu exit setelah header location
}
?>