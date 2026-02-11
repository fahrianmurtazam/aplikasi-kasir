<?php
$conn = mysqli_connect("localhost", "root", "", "guzel_apparel");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
$host = "localhost"; // Tetap localhost karena script PHP dan DB ada di komputer yang sama
$user = "admin_guzel"; // Nama user baru Anda
$pass = "apparel_terbaik"; // Password user baru
$db   = "guzel_apparel";
?>