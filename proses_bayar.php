<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bayar_cicilan'])) {
    $no_nota = $_POST['no_nota'];
    $tgl_bayar = $_POST['tgl_bayar'];
    $jumlah = str_replace(['.', ','], '', $_POST['jumlah']); // Hapus format ribuan jika ada
    $keterangan = $_POST['keterangan'];
    $metode = $_POST['metode'];
    $admin = $_SESSION['username'] ?? 'Admin'; // Ambil siapa yang login

    // 1. Simpan ke tabel pembayaran
    $query_insert = "INSERT INTO pembayaran (no_nota, tgl_bayar, jumlah, keterangan, metode, diterima_oleh) 
                     VALUES ('$no_nota', '$tgl_bayar', '$jumlah', '$keterangan', '$metode', '$admin')";
    
    if (mysqli_query($conn, $query_insert)) {
        
        // 2. Hitung Ulang Total Terbayar untuk Nota ini
        $cek_total = mysqli_query($conn, "SELECT SUM(jumlah) as total_masuk FROM pembayaran WHERE no_nota = '$no_nota'");
        $row_total = mysqli_fetch_assoc($cek_total);
        $total_sudah_bayar = $row_total['total_masuk'];

        // 3. Ambil Tagihan Asli
        $cek_tagihan = mysqli_query($conn, "SELECT subtotal, diskon FROM transaksi WHERE no_nota = '$no_nota'");
        $row_tagihan = mysqli_fetch_assoc($cek_tagihan);
        $total_harus_bayar = $row_tagihan['subtotal'] - $row_tagihan['diskon'];

        // 4. Update Sisa & Status di Tabel Transaksi Utama
        $sisa_baru = $total_harus_bayar - $total_sudah_bayar;
        $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Belum Lunas';
        
        // Jika lunas, catat tgl pelunasan
        $sql_lunas = ($status_baru == 'Lunas') ? ", tgl_pelunasan = '$tgl_bayar'" : "";

        // Update Transaksi
        // Kita update field panjar_produksi menjadi total yg sudah dibayar agar kompatibel dengan kode lama jika masih ada yg pakai
        $update_transaksi = "UPDATE transaksi SET 
                             sisa = '$sisa_baru', 
                             status = '$status_baru',
                             panjar_produksi = '$total_sudah_bayar' 
                             $sql_lunas
                             WHERE no_nota = '$no_nota'";
                             
        mysqli_query($conn, $update_transaksi);

        header("Location: index.php?msg=payment_success");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>