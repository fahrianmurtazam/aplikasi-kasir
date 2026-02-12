<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bayar_cicilan'])) {
    $no_nota = $_POST['no_nota'];
    $tgl_bayar = $_POST['tgl_bayar'];
    $jumlah = str_replace(['.', ','], '', $_POST['jumlah']); 
    $keterangan = $_POST['keterangan'];
    $metode = $_POST['metode'];
    $admin = $_SESSION['username'] ?? 'Admin';

    // 1. Simpan history pembayaran baru ke tabel pembayaran
    $query_insert = "INSERT INTO pembayaran (no_nota, tgl_bayar, jumlah, keterangan, metode, diterima_oleh) VALUES ('$no_nota', '$tgl_bayar', '$jumlah', '$keterangan', '$metode', '$admin')";
    
    if (mysqli_query($conn, $query_insert)) {
        
        // 2. Ambil nilai Deposit Desain yang tersimpan di transaksi (biar tetap terpisah)
        $cek_trans = mysqli_query($conn, "SELECT subtotal, diskon, deposit_desain FROM transaksi WHERE no_nota = '$no_nota'");
        $data_trans = mysqli_fetch_assoc($cek_trans);
        $deposit_fix = (float)$data_trans['deposit_desain'];
        $subtotal = (float)$data_trans['subtotal'];
        $diskon = (float)$data_trans['diskon'];

        // 3. Hitung TOTAL SEMUA uang masuk dari tabel pembayaran untuk nota ini
        $cek_bayar = mysqli_query($conn, "SELECT SUM(jumlah) as total_masuk FROM pembayaran WHERE no_nota = '$no_nota'");
        $row_bayar = mysqli_fetch_assoc($cek_bayar);
        $total_masuk = (float)$row_bayar['total_masuk'];

        // 4. Logika Kalkulasi:
        // Panjar Produksi di Invoice = Total Uang Masuk - Nilai Deposit Desain
        $panjar_akumulasi = $total_masuk - $deposit_fix;
        
        // 5. Hitung Sisa Akhir
        $sisa_baru = $subtotal - $diskon - $deposit_fix - $panjar_akumulasi;
        if($sisa_baru < 0) $sisa_baru = 0;
        
        $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Belum Lunas';
        $sql_lunas = ($status_baru == 'Lunas') ? ", tgl_pelunasan = '$tgl_bayar'" : ", tgl_pelunasan = NULL";

        // 6. UPDATE TABEL TRANSAKSI (Sesuai permintaan Anda)
        // Nilai panjar_produksi sekarang berisi akumulasi (DP awal + semua cicilan)
        $query_update = "UPDATE transaksi SET panjar_produksi = '$panjar_akumulasi', sisa = '$sisa_baru', status = '$status_baru' $sql_lunas WHERE no_nota = '$no_nota'";

        if (mysqli_query($conn, $query_update)) {
            header("Location: index.php?msg=payment_success");
        } else {
            header("Location: index.php?msg=error_update");
        }
    } else {
        header("Location: index.php?msg=error_insert");
    }
}
?>