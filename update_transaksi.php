<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    $id = $_GET['id']; // no_nota
    $nama = $_POST['nama_pelanggan'];
    $estimasi = $_POST['estimasi'];
    $tgl_masuk = $_POST['tgl_masuk'];
    $deadline = $_POST['deadline'];
    
    $deposit = isset($_POST['deposit']) ? (float)$_POST['deposit'] : 0;
    $panjar = isset($_POST['panjar']) ? (float)$_POST['panjar'] : 0;
    $diskon = isset($_POST['diskon']) ? (float)$_POST['diskon'] : 0;
    $admin = $_SESSION['username'] ?? 'Admin';

    $items_array = [];
    if (isset($_POST['items'])) {
        foreach($_POST['items'] as $key => $val) {
            if($val != "") {
                $items_array[] = [
                    'nama' => $val,
                    'qty' => (float)$_POST['qtys'][$key],
                    'harga' => (float)$_POST['hargas'][$key]
                ];
            }
        }
    }
    $items_json = json_encode($items_array);

    $subtotal = 0;
    foreach($items_array as $item) { 
        $subtotal += ($item['qty'] * $item['harga']); 
    }

    // --- LOGIKA UPDATE TABEL PEMBAYARAN (SINKRONISASI) ---

    // 1. Update atau Insert Deposit Desain (Menggunakan no_nota sebagai pengganti id)
    $cek_dep = mysqli_query($conn, "SELECT no_nota FROM pembayaran WHERE no_nota = '$id' AND keterangan = 'Deposit Desain'");
    if (mysqli_num_rows($cek_dep) > 0) {
        mysqli_query($conn, "UPDATE pembayaran SET jumlah = '$deposit', tgl_bayar = '$tgl_masuk' WHERE no_nota = '$id' AND keterangan = 'Deposit Desain'");
    } elseif ($deposit > 0) {
        mysqli_query($conn, "INSERT INTO pembayaran (no_nota, tgl_bayar, jumlah, keterangan, metode, diterima_oleh) VALUES ('$id', '$tgl_masuk', '$deposit', 'Deposit Desain', 'Tunai', '$admin')");
    }

    // 2. Update atau Insert Panjar Produksi
    $cek_panj = mysqli_query($conn, "SELECT no_nota FROM pembayaran WHERE no_nota = '$id' AND keterangan = 'Panjar Produksi'");
    if (mysqli_num_rows($cek_panj) > 0) {
        mysqli_query($conn, "UPDATE pembayaran SET jumlah = '$panjar', tgl_bayar = '$tgl_masuk' WHERE no_nota = '$id' AND keterangan = 'Panjar Produksi'");
    } elseif ($panjar > 0) {
        mysqli_query($conn, "INSERT INTO pembayaran (no_nota, tgl_bayar, jumlah, keterangan, metode, diterima_oleh) VALUES ('$id', '$tgl_masuk', '$panjar', 'Panjar Produksi', 'Tunai', '$admin')");
    }

    // --- HITUNG ULANG SISA ---
    $query_total_bayar = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM pembayaran WHERE no_nota = '$id'");
    $data_bayar = mysqli_fetch_assoc($query_total_bayar);
    $total_terbayar = (float)($data_bayar['total'] ?? 0);

    $sisa = $subtotal - $diskon - $total_terbayar;
    if($sisa < 0) $sisa = 0;

    $status = ($sisa <= 0) ? 'Lunas' : 'Belum Lunas';
    $sql_lunas = ($status == 'Lunas') ? ", tgl_pelunasan = '$tgl_bayar'" : ", tgl_pelunasan = NULL";

    // 3. Update Tabel Transaksi Utama
    $query = "UPDATE transaksi SET 
                nama_pelanggan='$nama', 
                estimasi='$estimasi', 
                tgl_masuk='$tgl_masuk', 
                deadline='$deadline', 
                items='$items_json', 
                subtotal='$subtotal', 
                deposit_desain='$deposit', 
                panjar_produksi='$panjar', 
                diskon='$diskon',
                sisa='$sisa', 
                status='$status'
                $sql_lunas
            WHERE no_nota='$id'";

    if (mysqli_query($conn, $query)) {
        header("Location: index.php?msg=updated");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>