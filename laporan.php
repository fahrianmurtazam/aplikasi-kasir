<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
// Jika bukan master, tendang balik ke index.php
if ($_SESSION['role'] !== 'master') {
    header("Location: index.php?error=unauthorized");
    exit;
}
include 'koneksi.php';

// Filter Tanggal
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');

// Query LUNAS
$query = mysqli_query($conn, "SELECT * FROM transaksi WHERE status='Lunas' AND tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir' ORDER BY tgl_masuk DESC, no_nota DESC");

function getModal($conn, $nama_barang) {
    $res = mysqli_query($conn, "SELECT harga_modal FROM barang WHERE nama_barang = '$nama_barang' LIMIT 1");
    $data = mysqli_fetch_assoc($res);
    return $data ? (float)$data['harga_modal'] : 0;
}

$isExport = isset($_GET['export']);

if ($isExport) {
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Keuangan_Guzel_$tgl_awal.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi</title>
    <?php if(!$isExport): ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php endif; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #e5e7eb; padding: 10px; }
        
        /* Style Khusus Excel agar menjadi Format Currency */
        .excel-currency { mso-number-format:"\#\,\#\#0"; }
        .excel-text { mso-number-format:"\@"; }
    </style>
</head>
<body class="<?= $isExport ? '' : 'bg-gray-100' ?>">

    <?php if(!$isExport): ?>
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

                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-500 hover:text-blue-800 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 hidden z-[60] transition-opacity duration-300 opacity-0"></div>
        
        <div id="mobile-sidebar" class="fixed top-0 right-0 h-full w-64 bg-white shadow-2xl z-[70] transform translate-x-full transition-transform duration-300 ease-in-out p-6">
            <div class="flex justify-end mb-8">
                <button id="close-sidebar" class="text-gray-500 hover:text-red-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div class="flex flex-col space-y-6 text-xs font-bold uppercase">
                <a href="index.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Input Pesanan</a>
                <a href="barang.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Master Barang</a>
                <a href="laporan.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Laporan Penjualan</a>
                <a href="aruskas.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Arus Kas</a>
                <a href="javascript:void(0)" onclick="confirmLogout()" class="text-red-600 font-black hover:text-red-700 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> LOGOUT
                </a>
                <a href="ganti_password.php" class="flex items-center text-gray-700 hover:bg-blue-50 hover:text-blue-00 rounded-2xl transition group">
                    <div class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 group-hover:bg-blue-100 mr-1">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="font-black text-xs uppercase tracking-widest">Keamanan</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 my-10">
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 no-print">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Periode Awal</label>
                    <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="w-full p-2 border rounded-lg text-sm font-semibold">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Periode Akhir</label>
                    <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="w-full p-2 border rounded-lg text-sm font-semibold">
                </div>
                <button type="submit" class="bg-blue-800 text-white px-8 py-2.5 rounded-lg font-bold text-xs uppercase hover:bg-blue-900 transition">Filter</button>
                <a href="?export=true&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="bg-green-600 text-white px-8 py-2.5 rounded-lg font-bold text-xs uppercase hover:bg-green-700 transition">Excel</a>
            </form>
        </div>
    <?php else: ?>
        <table>
            <tr>
                <th colspan="6" style="font-size: 16pt; font-weight: bold; text-align: center;">Laporan Keuangan transaksi Guzel Apparel</th>
            </tr>
            <tr>
                <th colspan="6" style="font-size: 12pt; text-align: center;">Periode: <?= $tgl_awal ?> s/d <?= $tgl_akhir ?></th>
            </tr>
            <tr><th colspan="6"></th></tr>
        </table>
    <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
            <table class="w-full text-left" border="<?= $isExport ? '1' : '0' ?>">
                <thead class="<?= $isExport ? '' : 'bg-gray-800 text-white' ?>">
                    <tr class="text-[12px] uppercase">
                        <th class="p-4" style="<?= $isExport ? 'background-color: #A9D08E;' : '' ?>">Tgl / Nota / Pelanggan</th>
                        <th style="<?= $isExport ? 'background-color: #A9D08E;' : '' ?>">Nama Barang</th>
                        <th class="text-center" style="<?= $isExport ? 'background-color: #A9D08E;' : '' ?>">Qty</th>
                        <th class="text-right" style="<?= $isExport ? 'background-color: #A9D08E;' : '' ?>">Harga Jual</th>
                        <th class="text-right" style="<?= $isExport ? 'background-color: #A9D08E;' : '' ?>">Harga Modal</th>
                        <th class="text-right" style="<?= $isExport ? 'background-color: #A9D08E;' : '' ?>">Profit Kotor</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    <?php 
                    $total_laba_akhir = 0;

                    while($r = mysqli_fetch_assoc($query)): 
                        $items = json_decode($r['items'], true);
                        if(!$items) continue;

                        $nota_profit_kotor = 0;
                        $first_row = true;
                        $row_count = count($items);
                    ?>
                        <?php foreach($items as $index => $it): 
                            $h_jual = (float)$it['harga'];
                            $h_modal = getModal($conn, $it['nama']);
                            $qty = (float)$it['qty'];
                            $profit_item = ($h_jual - $h_modal) * $qty;
                            $nota_profit_kotor += $profit_item;
                        ?>
                        <tr class="border-b border-gray-100">
                            <?php if($first_row): ?>
                                <td rowspan="<?= $row_count ?>" class="<?= $isExport ? 'excel-text' : 'p-4 align-top bg-gray-50/50 w-64' ?>" valign="top">
                                    <div style="font-weight: 900; color: #1e3a8a;">#<?= str_pad($r['no_nota'], 4, "0", STR_PAD_LEFT) ?></div>
                                    <div style="color: #5a5a5b; font-weight: bold;"><?= date('d/m/Y', strtotime($r['tgl_masuk'])) ?></div>
                                    <div style="text-transform: uppercase; margin-top: 4px; font-weight: bold;"><?= $r['nama_pelanggan'] ?></div>
                                </td>
                            <?php $first_row = false; endif; ?>
                            
                            <td class="p-3 uppercase font-semibold"><?= $it['nama'] ?></td>
                            <td class="text-center font-bold"><?= $qty ?></td>
                            <td class="<?= $isExport ? 'excel-currency' : 'text-right font-semibold' ?>" align="right"><?= $h_jual ?></td>
                            <td class="<?= $isExport ? 'excel-currency' : 'text-right font-semibold text-gray-500' ?>" align="right"><?= $h_modal ?></td>
                            <td class="<?= $isExport ? 'excel-currency' : 'text-right font-bold text-gray-700' ?>" align="right"><?= $isExport ? $profit_item : 'Rp ' . number_format($profit_item) ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <?php 
                        $diskon = (float)$r['diskon'];
                        $profit_bersih = $nota_profit_kotor - $diskon;
                        $total_laba_akhir += $profit_bersih;
                        ?>
                        <tr class="<?= $isExport ? '' : 'bg-blue-50/30' ?>">
                            <td colspan="5" class="p-2 text-right font-bold text-gray-500 uppercase italic">Profit Kotor Nota #<?= str_pad($r['no_nota'], 4, "0", STR_PAD_LEFT) ?></td>
                            <td class="<?= $isExport ? 'excel-currency' : 'text-right p-2 font-bold' ?>" align="right"><?= $isExport ? $nota_profit_kotor : 'Rp ' . number_format($nota_profit_kotor) ?></td>
                        </tr>
                        <?php if($diskon > 0): ?>
                        <tr class="<?= $isExport ? '' : 'bg-red-50/30' ?>">
                            <td colspan="5" class="p-2 text-right font-bold text-red-500 uppercase italic text-[10px]">Diskon Nota</td>
                            <td class="<?= $isExport ? 'excel-currency' : 'text-right p-2 font-bold text-red-600' ?>" align="right" style="<?= $isExport ? 'color: red;' : '' ?>"><?= $isExport ? -$diskon : '- Rp ' . number_format($diskon) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="<?= $isExport ? '' : 'bg-green-100/50 border-b-2 border-gray-200' ?>" style="<?= $isExport ? 'background-color: #E2EFDA; font-weight: bold;' : '' ?>">
                            <td colspan="5" class="p-3 text-right font-black uppercase tracking-widest text-green-800">Profit Bersih Nota</td>
                            <td class="<?= $isExport ? 'excel-currency' : 'text-right p-3 font-black text-green-900 text-sm italic' ?>" align="right"><?= $isExport ? $profit_bersih : 'Rp ' . number_format($profit_bersih) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="<?= $isExport ? '' : 'bg-gray-900 text-white font-black' ?>" style="<?= $isExport ? 'background-color: #000; color: #fff;' : '' ?>">
                    <tr>
                        <td colspan="5" class="p-3 text-right  ">Grand Total Profit Bersih (Seluruh Nota)</td>
                        <td class="<?= $isExport ? 'excel-currency' : 'p-3 text-right text-green-400 italic' ?>" align="right"><?= $isExport ? $total_laba_akhir : 'Rp ' . number_format($total_laba_akhir) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php if(!$isExport): ?>
    </main>
    <?php endif; ?>


    <script>
    const menuBtn = document.getElementById('mobile-menu-button');
    const closeBtn = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    // Tambahkan pengecekan agar tidak error di halaman tanpa navbar
    if (menuBtn && sidebar) {
        function toggleSidebar() {
            const isHidden = sidebar.classList.contains('translate-x-full');
            
            if (isHidden) {
                // Buka Sidebar
                overlay.classList.remove('hidden');
                setTimeout(() => {
                    sidebar.classList.remove('translate-x-full');
                    overlay.classList.remove('opacity-0');
                }, 10);
            } else {
                // Tutup Sidebar
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        menuBtn.addEventListener('click', toggleSidebar);
        closeBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
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