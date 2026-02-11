<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $nama = $_POST['nama_pelanggan'];
    $estimasi = $_POST['estimasi'];
    $tgl_masuk = $_POST['tgl_masuk'];
    $deadline = $_POST['deadline'];
    
    $deposit = isset($_POST['deposit']) ? (float)$_POST['deposit'] : 0;
    $panjar = isset($_POST['panjar']) ? (float)$_POST['panjar'] : 0;
    $diskon = isset($_POST['diskon']) ? (float)$_POST['diskon'] : 0;
    
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

    $sisa = (float)$subtotal - $diskon - $deposit - $panjar;
    if($sisa < 0) $sisa = 0;

    $status = ($sisa <= 0) ? 'Lunas' : 'Belum Lunas';

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
                WHERE no_nota='$id'";

    if (mysqli_query($conn, $query)) {
        // PERUBAHAN DISINI: Redirect dengan parameter msg=updated
        header("Location: index.php?msg=updated");
        exit;
    } else {
        header("Location: index.php?msg=error");
        exit;
    }
}
?>