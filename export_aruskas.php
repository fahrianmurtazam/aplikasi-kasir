<?php
include 'koneksi.php';

// Ambil parameter tanggal dari URL
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');

$filename = "Laporan_Arus_Kas_" . $tgl_awal . "_to_" . $tgl_akhir . ".xls";

// Header untuk Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

$data_gabungan = [];

// 1. AMBIL DATA DARI TABEL PEMBAYARAN (Cicilan, DP, Pelunasan Otomatis)
$query_pembayaran = mysqli_query($conn, "
    SELECT p.*, t.nama_pelanggan 
    FROM pembayaran p 
    JOIN transaksi t ON p.no_nota = t.no_nota 
    WHERE p.tgl_bayar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");

while($row = mysqli_fetch_assoc($query_pembayaran)) {
    $data_gabungan[] = [
        'tanggal' => $row['tgl_bayar'],
        'deskripsi' => "NOTA #".str_pad($row['no_nota'], 4, "0", STR_PAD_LEFT)." ({$row['nama_pelanggan']}) - {$row['keterangan']}",
        'pcs' => '-', // Pembayaran tidak terkait langsung dengan hitungan stok pcs di arus kas
        'nominal' => (float)$row['jumlah'],
        'tipe' => 'masuk'
    ];
}

// 2. AMBIL DATA PENGELUARAN & PEMASUKAN MANUAL (Dari tabel pengeluaran)
$query_manual = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'");
while($row = mysqli_fetch_assoc($query_manual)) {
    $data_gabungan[] = [
        'tanggal' => $row['tanggal'],
        'deskripsi' => $row['deskripsi'],
        'pcs' => ($row['pcs'] != null && $row['pcs'] > 0) ? $row['pcs'] : '-',
        'nominal' => (float)$row['nominal'],
        'tipe' => $row['tipe']
    ];
}

// 3. SORT DATA BERDASARKAN TANGGAL
usort($data_gabungan, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});
?>

<style>
    .table-header { background-color: #1e40af; color: white; font-weight: bold; }
    .text-green { color: #059669; }
    .text-red { color: #dc2626; }
    .title { font-size: 16px; font-weight: bold; }
</style>

<table border="1">
    <thead>
        <tr>
            <th colspan="7" class="title">LAPORAN ARUS KAS GUZEL APPAREL</th>
        </tr>
        <tr>
            <th colspan="7">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></th>
        </tr>
        <tr><td colspan="7"></td></tr>
        <tr class="table-header">
            <th width="50">No</th>
            <th width="100">Tanggal</th>
            <th width="400">Deskripsi Transaksi</th>
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
            <td align="center"><?= $d['pcs'] ?></td>
            <td align="right" class="text-green"><?= $masuk ?: 0 ?></td>
            <td align="right" class="text-red"><?= $keluar ?: 0 ?></td>
            <td align="right" style="font-weight:bold;"><?= $saldo_kumulatif ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4" align="right">TOTAL SALDO AKHIR</th>
            <th align="right"><?= array_sum(array_column(array_filter($data_gabungan, fn($i) => $i['tipe'] == 'masuk'), 'nominal')) ?></th>
            <th align="right"><?= array_sum(array_column(array_filter($data_gabungan, fn($i) => $i['tipe'] == 'keluar'), 'nominal')) ?></th>
            <th align="right" style="background-color: #fef08a;">Rp <?= number_format($saldo_kumulatif, 0, ',', '.') ?></th>
        </tr>
    </tfoot>
</table>