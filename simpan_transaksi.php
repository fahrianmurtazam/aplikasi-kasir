<?php
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

    $query = "INSERT INTO transaksi (nama_pelanggan, estimasi, tgl_masuk, deadline, items, subtotal, deposit_desain, panjar_produksi, diskon, sisa, status) 
              VALUES ('$nama', '$estimasi', '$tgl_masuk', '$deadline', '$items_json', '$subtotal', '$deposit', '$panjar', '$diskon', '$sisa', '$status')";

    if (mysqli_query($conn, $query)) {
        // PERUBAHAN DISINI: Redirect dengan parameter msg=saved
        header("Location: index.php?msg=saved");
        exit;
    } else {
        // Redirect dengan pesan error
        header("Location: index.php?msg=error");
        exit;
    }
}
?>