<?php
include 'koneksi.php';

// Ambil parameter filter
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');
$status    = isset($_GET['status']) ? $_GET['status'] : 'Semua';

// Header Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Guzel_" . $status . "_" . $tgl_awal . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Style untuk Excel
?>
<style>
    .str { mso-number-format:"\@"; }
    .currency { mso-number-format:"\#\,\#\#0"; }
    .header-table { background-color: #ffee00; font-weight: bold; }
</style>

<table border="1">
    <thead>
        <tr>
            <th colspan="12" style="font-size: 18px; font-weight: bold; height: 35px; text-align: center;">REKAP TRANSAKSI GUZEL APPAREL</th>
        </tr>
        <tr>
            <th colspan="12" style="text-align: center;">Periode: <?= $tgl_awal ?> s/d <?= $tgl_akhir ?> | Status Pembayaran: <?= $status ?></th>
        </tr>
        <tr class="header-table">
            <th>No</th>
            <th>No. Nota</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Nama Barang</th>
            <th>QTY</th>
            <th>Subtotal</th>
            <th>Diskon</th>
            <th>Total Bayar (DP)</th>
            <th>Sisa Tagihan</th>
            <th>Status Bayar</th>
            <th>Posisi Produksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $n = 1;
        $filter_status = ($status !== 'Semua') ? " AND status = '$status'" : "";
        $query_str = "SELECT * FROM transaksi WHERE tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir' $filter_status ORDER BY tgl_masuk DESC, no_nota DESC";
        $sql = mysqli_query($conn, $query_str);

        while ($r = mysqli_fetch_assoc($sql)) :
            $items = json_decode($r['items'], true);
            $total_dp = (float)$r['deposit_desain'] + (float)$r['panjar_produksi'];
            
            // Logika Warna untuk Posisi Produksi
            $bg_produksi = "#f3f4f6"; // Default Abu-abu
            if ($r['produksi'] == 'FACTORY') $bg_produksi = "#dbeafe"; // Biru Muda
            if ($r['produksi'] == 'FASTPRINT') $bg_produksi = "#ffedd5"; // Orange Muda

            if ($items) :
                foreach ($items as $index => $it) :
        ?>
            <tr>
                <td align="center"><?= ($index === 0) ? $n++ : '' ?></td>
                <td class="str" align="center"><?= ($index === 0) ? "#" . str_pad($r['no_nota'], 4, "0", STR_PAD_LEFT) : '' ?></td>
                <td align="center"><?= ($index === 0) ? date('d/m/Y', strtotime($r['tgl_masuk'])) : '' ?></td>
                <td><?= ($index === 0) ? strtoupper($r['nama_pelanggan']) : '' ?></td>
                
                <td><?= $it['nama'] ?></td>
                <td align="center"><?= $it['qty'] ?></td>
                
                <td class="currency" align="right"><?= ($index === 0) ? $r['subtotal'] : 0 ?></td>
                <td class="currency" align="right"><?= ($index === 0) ? $r['diskon'] : 0 ?></td>
                <td class="currency" align="right"><?= ($index === 0) ? $total_dp : 0 ?></td>
                
                <td class="currency" align="right" style="<?= ($index === 0 && $r['sisa'] > 0) ? 'color:red;' : 'color:green;' ?>">
                    <?= ($index === 0) ? $r['sisa'] : 0 ?>
                </td>

                <td align="center"><?= ($index === 0) ? strtoupper($r['status']) : '' ?></td>
                <td align="center" style="background-color: <?= ($index === 0) ? $bg_produksi : '#ffffff' ?>;">
                    <?= ($index === 0) ? strtoupper($r['produksi']) : '' ?>
                </td>
            </tr>
        <?php 
                endforeach;
            endif;
        endwhile; 
        ?>
    </tbody>
</table>