<?php
session_start(); // Ditambahkan untuk mengambil data admin yang sedang login
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_pelanggan'];
    $estimasi = $_POST['estimasi'];
    $tgl_masuk = $_POST['tgl_masuk'];
    $deadline = $_POST['deadline'];
    
    $deposit = isset($_POST['deposit']) ? (float)$_POST['deposit'] : 0;
    $panjar = isset($_POST['panjar']) ? (float)$_POST['panjar'] : 0;
    $diskon = isset($_POST['diskon']) ? (float)$_POST['diskon'] : 0;
    
    $items_array = [];
    if(isset($_POST['items'])) {
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

    $sisa = $subtotal - $diskon - $deposit - $panjar;
    if($sisa < 0) $sisa = 0; 

    $status = ($sisa <= 0) ? 'Lunas' : 'Belum Lunas';

    // 1. Simpan ke Tabel Transaksi Utama
    $query = "INSERT INTO transaksi (nama_pelanggan, estimasi, tgl_masuk, deadline, items, subtotal, deposit_desain, panjar_produksi, diskon, sisa, status) 
              VALUES ('$nama', '$estimasi', '$tgl_masuk', '$deadline', '$items_json', '$subtotal', '$deposit', '$panjar', '$diskon', '$sisa', '$status')";

    if (mysqli_query($conn, $query)) {
        // Ambil No Nota yang baru saja dibuat
        $no_nota_baru = mysqli_insert_id($conn);
        $admin = $_SESSION['username'] ?? 'Admin';

        // 2. OTOMATIS INPUT KE TABEL PEMBAYARAN (SINKRONISASI ARUS KAS)
        
        // Input untuk Deposit Desain
        if ($deposit > 0) {
            $query_dep = "INSERT INTO pembayaran (no_nota, tgl_bayar, jumlah, keterangan, metode, diterima_oleh) 
                          VALUES ('$no_nota_baru', '$tgl_masuk', '$deposit', 'Deposit Desain', 'Tunai', '$admin')";
            mysqli_query($conn, $query_dep);
        }

        // Input untuk Panjar Produksi (DP Awal)
        if ($panjar > 0) {
            $query_panj = "INSERT INTO pembayaran (no_nota, tgl_bayar, jumlah, keterangan, metode, diterima_oleh) 
                           VALUES ('$no_nota_baru', '$tgl_masuk', '$panjar', 'Panjar Produksi', 'Tunai', '$admin')";
            mysqli_query($conn, $query_panj);
        }

        header("Location: index.php?msg=success");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>