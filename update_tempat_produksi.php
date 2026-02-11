<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) { exit; }

if (isset($_POST['id']) && isset($_POST['produksi'])) {
    $id = $_POST['id']; // No Nota
    $produksi = $_POST['produksi'];

    $stmt = $conn->prepare("UPDATE transaksi SET produksi = ? WHERE no_nota = ?");
    $stmt->bind_param("si", $produksi, $id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>