<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'master') {
    header("Location: index.php?error=unauthorized");
    exit;
}
include 'koneksi.php';

// --- LOGIKA SIMPAN PENGELUARAN MANUAL (TETAP SAMA) ---
if (isset($_POST['save_keluar'])) {
    $tgl = $_POST['tanggal'];
    $desc = $_POST['deskripsi'];
    $qty = $_POST['qty_keluar'] != "" ? $_POST['qty_keluar'] : null;
    $nominal = $_POST['nominal'];
    $id = $_POST['id_pengeluaran'];
    $tipe = $_POST['tipe_transaksi']; 

    if ($id == "") {
        $stmt = $conn->prepare("INSERT INTO pengeluaran (tipe, tanggal, deskripsi, pcs, nominal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssid", $tipe, $tgl, $desc, $qty, $nominal);
    } else {
        $stmt = $conn->prepare("UPDATE pengeluaran SET tipe=?, tanggal=?, deskripsi=?, pcs=?, nominal=? WHERE id=?");
        $stmt->bind_param("sssidi", $tipe, $tgl, $desc, $qty, $nominal, $id);
    }
    $stmt->execute();
    header("Location: aruskas.php");
}

if (isset($_GET['hapus_keluar'])) {
    $id = $_GET['hapus_keluar'];
    mysqli_query($conn, "DELETE FROM pengeluaran WHERE id=$id");
    header("Location: aruskas.php");
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');
$data_gabungan = [];

// --- LOGIKA BARU: AMBIL UANG MASUK DARI TABEL PEMBAYARAN ---
// Query Join ke Transaksi untuk ambil nama pelanggan
$query_bayar = mysqli_query($conn, "
    SELECT p.*, t.nama_pelanggan, t.no_nota as nota_asli 
    FROM pembayaran p 
    JOIN transaksi t ON p.no_nota = t.no_nota 
    WHERE p.tgl_bayar BETWEEN '$tgl_awal' AND '$tgl_akhir'
");

while($row = mysqli_fetch_assoc($query_bayar)) {
    $data_gabungan[] = [
        'tanggal' => $row['tgl_bayar'],
        'deskripsi' => "Nota #{$row['nota_asli']} ({$row['nama_pelanggan']}) - {$row['keterangan']}",
        'pcs' => '-', // Pembayaran cicilan tidak ada hubungannya dengan stok barang keluar
        'nominal' => (float)$row['jumlah'],
        'tipe' => 'masuk',
        'is_auto' => true, 
        'id' => 'PAY-'.$row['id_bayar']
    ];
}

// --- AMBIL PENGELUARAN/PEMASUKAN MANUAL ---
$query_manual = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'");
while($row = mysqli_fetch_assoc($query_manual)) {
    $data_gabungan[] = [
        'tanggal' => $row['tanggal'],
        'deskripsi' => $row['deskripsi'],
        'pcs' => ($row['pcs'] != null) ? $row['pcs'] : '-',
        'nominal' => (float)$row['nominal'],
        'tipe' => $row['tipe'],
        'is_auto' => false,
        'id' => $row['id']
    ];
}

// Sort berdasarkan tanggal
usort($data_gabungan, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arus Kas Pro - Guzel Apparel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap'); body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm sticky top-0 z-50 no-print">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php">
                    <img class="w-28 md:w-36" src="img/logo CV.png" alt="Logo">
                </a>

                <div class="hidden md:flex space-x-6 text-sm font-bold uppercase items-center">
                    <a href="index.php" class="hover:text-blue-800 transition">Input Pesanan</a>
                    <a href="barang.php" class="hover:text-blue-800 transition">Master Barang</a>
                    <a href="laporan.php" class="hover:text-blue-800 transition">Laporan Penjualan</a>
                    <a href="aruskas.php" class="hover:text-blue-800 transition">Arus Kas</a>
                    <a href="javascript:void(0)" onclick="confirmLogout()" class="text-red-600 font-black hover:text-red-700 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i> LOGOUT
                    </a>    
                    <a href="ganti_password.php" class="flex items-center text-gray-700 hover:bg-blue-50 hover:text-blue-800 rounded-2xl transition group">
                        <div class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 group-hover:bg-blue-100 mr-1">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest">Keamanan</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="w-full lg:w-1/3">
                <div class="flex gap-2 mb-4">
                    <button type="button" onclick="switchMode('keluar')" id="btnTabKeluar" class="flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-red-600 text-white border-2 border-red-600">
                        <i class="fas fa-minus-circle mr-1"></i> Pengeluaran
                    </button>
                    <button type="button" onclick="switchMode('masuk')" id="btnTabMasuk" class="flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-white text-green-600 border-2 border-green-600">
                        <i class="fas fa-plus-circle mr-1"></i> Pemasukan Lain
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-red-500 sticky top-24" id="container-form">
                    <h2 class="text-lg font-bold mb-6 uppercase text-red-600" id="form-title">Catat Pengeluaran</h2>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="id_pengeluaran" id="id_pengeluaran">
                        <input type="hidden" name="tipe_transaksi" id="tipe_transaksi" value="keluar">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Tanggal</label>
                            <input type="date" name="tanggal" id="in_tgl" value="<?= date('Y-m-d') ?>" required class="w-full p-2 bg-gray-50 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Deskripsi</label>
                            <input type="text" name="deskripsi" id="in_desc" required class="w-full p-2 bg-gray-50 border rounded-lg">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase">Nominal</label>
                                <input type="number" name="nominal" id="in_total" required class="w-full p-2 bg-gray-50 border rounded-lg font-bold">
                            </div>
                        </div>
                        <button type="submit" name="save_keluar" id="btnSubmit" class="w-full bg-red-600 text-white p-3 rounded-lg font-black uppercase text-xs hover:bg-red-700 shadow-lg">Simpan</button>
                    </form>
                </div>
            </div>

            <div class="w-full lg:w-2/3">
                <div class="bg-white rounded-xl shadow-md overflow-hidden border-t-4 border-blue-900">
                    <div class="bg-blue-900 p-4 flex justify-between items-center text-white font-black uppercase text-sm">
                        <span>Laporan Keuangan (Realtime)</span>
                        <form method="GET" class="flex gap-2 text-black">
                            <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="text-[10px] p-1 rounded">
                            <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="text-[10px] p-1 rounded">
                            <button class="bg-yellow-500 px-3 rounded text-[10px]"><i class="fas fa-search"></i></button>
                             <a href="export_aruskas.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-[10px] font-bold transition flex items-center gap-1 shadow-md">
                                <i class="fas fa-file-excel"></i> EXCEL
                            </a>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-[11px] border-collapse">
                            <thead>
                                <tr class="bg-gray-100 uppercase font-bold text-gray-600 border-b">
                                    <th class="p-4 text-left">Tgl</th>
                                    <th class="p-4 text-left">Keterangan</th>
                                    <th class="p-4 text-right text-green-600">Masuk</th>
                                    <th class="p-4 text-right text-red-600">Keluar</th>
                                    <th class="px-2 text-right">Saldo</th>
                                    <th class="p-4 text-center">Act</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $saldo = 0;
                                foreach($data_gabungan as $d): 
                                    $masuk = ($d['tipe'] == 'masuk') ? $d['nominal'] : 0;
                                    $keluar = ($d['tipe'] == 'keluar') ? $d['nominal'] : 0;
                                    $saldo += ($masuk - $keluar);
                                ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-4"><?= date('d/m/y', strtotime($d['tanggal'])) ?></td>
                                    <td class="p-4 font-semibold uppercase <?= $d['tipe'] == 'keluar' ? 'text-red-500' : 'text-blue-800' ?>">
                                        <?= $d['deskripsi'] ?>
                                    </td>
                                    <td class="p-4 text-right text-green-600 font-bold"><?= $masuk > 0 ? number_format($masuk) : '-' ?></td>
                                    <td class="p-4 text-right text-red-600 font-bold"><?= $keluar > 0 ? number_format($keluar) : '-' ?></td>
                                    <td class="px-2 text-right font-bold text-gray-800"><?= number_format($saldo) ?></td>
                                    <td class="p-4 text-center">
                                        <?php if(!$d['is_auto']): ?>
                                            <button type="button" onclick="confirmDeleteKas(<?= $d['id']; ?>)" class="text-red-500 hover:text-red-600">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <i class="fas fa-receipt text-gray-300" title="Dari Transaksi"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-900 text-white font-black">
                                    <td colspan="4" class="p-4 text-right">Saldo Akhir</td>
                                    <td class="px-2 text-right text-yellow-400">Rp <?= number_format($saldo) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const btnTabKeluar = document.getElementById('btnTabKeluar');
        const btnTabMasuk = document.getElementById('btnTabMasuk');
        const tipeTransaksi = document.getElementById('tipe_transaksi');
        const containerForm = document.getElementById('container-form');
        const formTitle = document.getElementById('form-title');
        const btnSubmit = document.getElementById('btnSubmit');

        function switchMode(tipe) {
            tipeTransaksi.value = tipe;
            if(tipe == 'masuk'){
                btnTabMasuk.classList.replace('bg-white', 'bg-green-600');
                btnTabMasuk.classList.replace('text-green-600', 'text-white');
                btnTabKeluar.classList.replace('bg-red-600', 'bg-white');
                btnTabKeluar.classList.replace('text-white', 'text-red-600');
                
                containerForm.classList.replace('border-red-500', 'border-green-500');
                formTitle.innerText = "CATAT PEMASUKAN LAIN";
                formTitle.classList.replace('text-red-600', 'text-green-600');
                btnSubmit.classList.replace('bg-red-600', 'bg-green-600');
                btnSubmit.classList.replace('hover:bg-red-700', 'hover:bg-green-700');
            } else {
                btnTabMasuk.classList.replace('bg-green-600', 'bg-white');
                btnTabMasuk.classList.replace('text-white', 'text-green-600');
                btnTabKeluar.classList.replace('bg-white', 'bg-red-600');
                btnTabKeluar.classList.replace('text-red-600', 'text-white');

                containerForm.classList.replace('border-green-500', 'border-red-500');
                formTitle.innerText = "CATAT PENGELUARAN";
                formTitle.classList.replace('text-green-600', 'text-red-600');
                btnSubmit.classList.replace('bg-green-600', 'bg-red-600');
                btnSubmit.classList.replace('hover:bg-green-700', 'hover:bg-red-700');
            }
        }

        function confirmDeleteKas(id) {
        Swal.fire({
            title: 'Hapus Data Kas?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mengarahkan ke link penghapusan (Pastikan nama parameter di PHP sesuai)
                window.location.href = 'aruskas.php?hapus_keluar=' + id;
            }
        })
    }

    function confirmLogout() {
        Swal.fire({
            title: 'Yakin ingin keluar?',
            text: "Anda harus login kembali untuk mengakses sistem.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Warna merah untuk hapus/keluar
            cancelButtonColor: '#3085d6', // Warna biru untuk batal
            confirmButtonText: 'Ya, Logout!',
            cancelButtonText: 'Batal',
            reverseButtons: true, // Menukar posisi tombol agar Batal di kiri
            background: '#fff',
            borderRadius: '20px',
            customClass: {
                title: 'font-black text-xl uppercase tracking-normal',
                popup: 'rounded-3xl shadow-2xl'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Animasi loading sebelum pindah halaman
                Swal.fire({
                    title: 'Sedang Keluar...',
                    timer: 800,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                }).then(() => {
                    window.location.href = 'logout.php';
                });
            }
        })
    }
    </script>
</body>
</html>