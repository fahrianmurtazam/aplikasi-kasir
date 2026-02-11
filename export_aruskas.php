<?php
include 'koneksi.php';

// Ambil parameter tanggal dari URL untuk filter data yang diekspor
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');

// 1. Fungsi Ambil Harga Modal (Sama dengan di aruskas.php)
function getModalPrice($conn, $nama_barang) {
    $nama_barang = mysqli_real_escape_string($conn, $nama_barang);
    $res = mysqli_query($conn, "SELECT harga_modal FROM barang WHERE nama_barang = '$nama_barang' LIMIT 1");
    $data = mysqli_fetch_assoc($res);
    return $data ? (float)$data['harga_modal'] : 0;
}

$data_gabungan = [];
$allowed_items = [
    'JERSEY TYPE A (FULL PRINT)', 'JERSEY TYPE B (STANDAR)',
    'JERSEY TYPE C (JERSEY ONLY)', 'JERSEY TYPE D (BASIC)',
    'BASEBALL JERSEY', 'BASKETBALL JERSEY',
    'JERSEY PRINTING KIDS', '',
];

// 2. Ambil Data Uang Masuk (Profit Otomatis)
$query_masuk = mysqli_query($conn, "SELECT no_nota, tgl_masuk as tanggal, nama_pelanggan, items, panjar_produksi FROM transaksi WHERE status='Lunas' AND tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'");

while($row = mysqli_fetch_assoc($query_masuk)) {
    $items = json_decode($row['items'], true);
    $pcs_total = 0;

    if(is_array($items)) {
        foreach($items as $it) {
            $nama_item = strtoupper($it['nama']);
            $qty = (int)$it['qty'];
            if (in_array($nama_item, $allowed_items)) $pcs_total += $qty;
        }
    }
    $data_gabungan[] = [
        'tanggal' => $row['tanggal'],
        'deskripsi' => 'Pembayaran Nota #' . str_pad($row['no_nota'], 4, '0', STR_PAD_LEFT) . ' (' . $row['nama_pelanggan'] . ')',
        'pcs' => $pcs_total,
        'nominal' => (float)$row['panjar_produksi'], // TOTAL BAYAR CUSTOMER
        'tipe' => 'masuk'
    ];
}

// 3. Ambil Data Manual (Tabel pengeluaran)
$query_manual = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'");
while($row = mysqli_fetch_assoc($query_manual)) {
    $data_gabungan[] = [
        'tanggal' => $row['tanggal'],
        'deskripsi' => $row['deskripsi'],
        'pcs' => $row['pcs'],
        'nominal' => (float)$row['nominal'],
        'tipe' => $row['tipe']
    ];
}

// Urutkan berdasarkan tanggal
usort($data_gabungan, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});

// 4. Set Header agar file didownload sebagai Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Arus_Kas_".$tgl_awal."_s_d_".$tgl_akhir.".xls");
?>

<style>
    .title { font-size: 16pt; font-weight: bold; text-align: center; }
    .table-header { background-color: #1e3a8a; color: #ffffff; font-weight: bold; text-align: center; }
    .text-red { color: #dc2626; }
    .text-green { color: #16a34a; }
    .footer-row { background-color: #f3f4f6; font-weight: bold; }
    td, th { border: 1px solid #000000; padding: 5px; }
</style>

<table>
    <tr>
        <td colspan="7" class="title">REKAPITULASI ARUS KAS GUZEL APPAREL</td>
    </tr>
    <tr>
        <td colspan="7" style="text-align: center; font-weight: bold;">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></td>
    </tr>
    <tr><td colspan="7"></td></tr> <thead>
        <tr class="table-header">
            <th width="50">No</th>
            <th width="100">Tanggal</th>
            <th width="350">Deskripsi Transaksi</th>
            <th width="70">Pcs</th>
            <th width="120">Masuk (+)</th>
            <th width="120">Keluar (-)</th>
            <th width="150">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $saldo_kumulatif = 0;
        $no = 1;
        foreach($data_gabungan as $d): 
            $masuk = ($d['tipe'] == 'masuk') ? $d['nominal'] : 0;
            $keluar = ($d['tipe'] == 'keluar') ? $d['nominal'] : 0;
            $saldo_kumulatif += ($masuk - $keluar);
        ?>
        <tr>
            <td align="center"><?= $no++ ?></td>
            <td align="center"><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
            <td><?= strtoupper($d['deskripsi']) ?></td>
            <td align="center"><?= $d['pcs'] ?: '-' ?></td>
            <td align="right" class="text-green"><?= $masuk ?: 0 ?></td>
            <td align="right" class="text-red"><?= $keluar ?: 0 ?></td>
            <td align="right" style="font-weight: bold;"><?= $saldo_kumulatif ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="footer-row">
            <td colspan="6" align="right">TOTAL SALDO AKHIR :</td>
            <td align="right">Rp <?= number_format($saldo_kumulatif, 0, ',', '.') ?></td>
        </tr>
    </tfoot>
</table>