<?php 
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php'; 

// Ambil No Nota Selanjutnya
$res = mysqli_query($conn, "SELECT MAX(no_nota) as max_id FROM transaksi");
$row = mysqli_fetch_assoc($res);
$next_val = ($row['max_id']) ? $row['max_id'] + 1 : 1;
$next_nota = str_pad($next_val, 4, "0", STR_PAD_LEFT);

// Ambil data barang untuk modal
$daftar_barang = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");

// Ambil data transaksi (Urut Tanggal Terbaru)
// Tambahkan ini jika Anda memanggil data transaksi di awal
$data_transaksi = mysqli_query($conn, "SELECT * FROM transaksi ORDER BY tgl_masuk DESC, no_nota DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guzel Apparel_Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }

    @media print {
        @page {
            size: A6;
            margin: 0;
        }

        /* Sembunyikan elemen dashboard */
        body { margin: 0; padding: 0; }
        .no-print { display: none !important; }
        
        .print-area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 105mm !important;
            height: 148mm !important;
            padding-right: 10mm !important; /* Padding dipersempit untuk A6 */
            padding-left: 5mm !important; /* Padding dipersempit untuk A6 */
            padding-top: 5mm !important; /* Padding dipersempit untuk A6 */
            padding-bottom: 5mm !important; /* Padding dipersempit untuk A6 */
            margin: 0 !important;
            box-sizing: border-box;
            background: white !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden !important;
        }
    }
    /* Tampilan Preview di Layar */
    #invoice-sheet {
        width: 105mm;
        height: 148mm;
        background: white;
        font-family: 'Inter', sans-serif;
    }

    html {
        scroll-behavior: smooth;
    }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    
    <nav class="bg-white shadow-sm sticky top-0 z-50 no-print">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php">
                    <img class="w-28 md:w-36" src="img/logo CV.png" alt="Logo">
                </a>

                <div class="hidden md:flex space-x-6 text-sm font-bold uppercase items-center">
                    <a href="index.php" onclick="switchTab('input')" id="btn-input" class="hover:text-blue-800 transition">Input Pesanan</a>
                    <a href="#" onclick="switchTab('transaksi')" id="btn-transaksi" class="hover:text-blue-800 transition">Transaksi</a>
                    <a href="barang.php" class="hover:text-blue-800 transition">Master Barang</a>
                    <?php if ($_SESSION['role'] === 'master'): ?>
                    <a href="laporan.php" class="hover:text-blue-800 transition">Laporan Penjualan</a>
                    <a href="aruskas.php" class="hover:text-blue-800 transition">Arus Kas</a>
                    <?php endif; ?>
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
                <a href="index.php" onclick="switchTab('input')" id="btn-input" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Input Pesanan</a>
                <a href="#" onclick="switchTab('transaksi')" id="btn-transaksi" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Transaksi</a>
                <a href="barang.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Master Barang</a>
                <?php if ($_SESSION['role'] === 'master'): ?>
                <a href="laporan.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Laporan Penjualan</a>
                <a href="aruskas.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Arus Kas</a>
                <?php endif; ?>
                <a href="javascript:void(0)" onclick="confirmLogout()" class="text-red-600 font-black hover:text-red-700 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> LOGOUT
                </a>
                <a href="ganti_password.php" class="flex items-center text-gray-600 hover:bg-blue-50 hover:text-blue-800 rounded-2xl transition group">
                    <div class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 group-hover:bg-blue-100 mr-1">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="font-black text-xs uppercase tracking-widest">Keamanan</span>
                </a>
            </div>
        </div>
    </nav>

    <div id="modalBarang" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="p-4 bg-blue-900 text-white flex justify-between items-center">
                <h3 class="font-bold uppercase text-sm">Pilih Produk</h3>
                <button onclick="closeModal()" class="text-2xl leading-none">&times;</button>
            </div>
            <div class="p-3 border-b bg-gray-50">
                <input type="text" id="searchBarang" placeholder="Cari barang..." class="w-full p-3 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="max-h-80 overflow-y-auto p-2" id="listBarangModal">
                <?php while($b = mysqli_fetch_assoc($daftar_barang)): ?>
                <div class="flex justify-between items-center p-4 hover:bg-blue-50 border-b cursor-pointer active:bg-blue-100 transition" 
                    onclick="selectItem('<?= $b['nama_barang'] ?>', <?= $b['harga_jual'] ?>)">
                    <span class="text-xs font-bold uppercase pr-2"><?= $b['nama_barang'] ?></span>
                    <span class="text-xs text-blue-700 font-black whitespace-nowrap">Rp <?= number_format($b['harga_jual']) ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        <div id="section-input" class="flex flex-col lg:flex-row gap-8">
            <div class="w-full lg:w-1/2 bg-white rounded-xl shadow-md p-5 md:p-6 no-print">
                <h2 id="form-title" class="text-base md:text-lg font-bold mb-6 border-b pb-2 uppercase text-gray-700 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-blue-800"></i> Tambah Pesanan Baru
                </h2>
                <form id="orderForm" action="simpan_transaksi.php" method="POST" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Nama Pelanggan</label>
                            <input type="text" name="nama_pelanggan" id="in-nama" required oninput="updateTotals()" class="w-full p-3 bg-gray-50 border rounded-lg outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Estimasi</label>
                            <select name="estimasi" id="in-status" class="w-full p-3 bg-gray-50 border rounded-lg" onchange="updateDeadline(); updateTotals()">
                                <option value="Normal">Normal (24 Hari)</option>
                                <option value="Express">Express (14 Hari)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">No Nota</label>
                            <input type="text" id="display-nota" value="<?= $next_nota ?>" readonly class="w-full p-3 bg-gray-200 border rounded-lg font-bold text-gray-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Tgl Produksi</label>
                            <input type="date" name="tgl_masuk" id="in-tgl" value="<?= date('Y-m-d') ?>" onchange="updateDeadline()" class="w-full p-3 bg-gray-50 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-red-500 uppercase">Deadline</label>
                            <input type="date" name="deadline" id="in-deadline" readonly class="w-full p-3 bg-red-50 border border-red-100 rounded-lg text-red-600 font-bold">
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 md:p-4 rounded-xl border border-gray-200">
                        <div id="item-container" class="space-y-3">
                            <div class="item-row flex flex-wrap sm:flex-nowrap gap-2 items-center bg-white p-2 border rounded-lg sm:bg-transparent sm:p-0 sm:border-none">
                                <input type="text" name="items[]" placeholder="Klik pilih barang..." readonly onclick="openModal(this)" required class="in-item flex-1 p-2.5 text-xs border rounded-lg bg-white cursor-pointer hover:border-blue-400 transition">
                                <div class="flex gap-2 w-full sm:w-auto">
                                    <input type="number" name="qtys[]" placeholder="Qty" oninput="updateTotals()" required class="in-qty w-full sm:w-16 p-2.5 text-xs border rounded-lg text-center">
                                    <input type="number" name="hargas[]" placeholder="Harga" readonly class="in-harga w-full sm:w-28 p-2.5 text-xs border rounded-lg bg-gray-100 font-bold text-blue-800">
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="addItemRow()" class="mt-4 text-[10px] font-bold text-blue-700 uppercase flex items-center gap-1">
                            <i class="fas fa-plus"></i> Tambah Barang
                        </button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label class="block text-[10px] font-bold uppercase mb-1">Deposit Desain</label>
                            <input type="number" name="deposit" id="in-deposit" oninput="updateTotals()" class="w-full p-3 border rounded-lg bg-blue-50 font-bold text-sm" value="0">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-[10px] font-bold uppercase mb-1">Panjar Produksi</label>
                            <input type="number" name="panjar" id="in-panjar" oninput="updateTotals()" class="w-full p-3 border rounded-lg bg-blue-50 font-bold text-sm" value="0">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[10px] font-bold uppercase mb-1 text-yellow-600">Diskon (Rp)</label>
                            <input type="number" name="diskon" id="in-diskon" oninput="updateTotals()" class="w-full p-3 border rounded-lg bg-yellow-50 font-bold text-sm" value="0">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-4">
                        <button type="button" onclick="setLunas()" class="w-full bg-green-600 text-white p-3 rounded-lg font-black uppercase text-[10px] hover:bg-green-700 transition shadow-md">Bayar Lunas</button>
                        <button type="submit" class="w-full bg-blue-800 text-white p-3 rounded-lg font-black uppercase text-[10px] hover:bg-blue-900 transition shadow-md">Simpan Data</button>
                        <button type="button" onclick="window.print()" class="w-full bg-gray-800 text-white p-3 rounded-lg font-black uppercase text-[10px] hover:bg-black transition shadow-md"><i class="fas fa-print mr-1"></i> Cetak</button>
                    </div>
                </form>
            </div>
            <!-- lembar Invoice -->
            <div class="lg:w-1/2 flex justify-center p-6">
                <div id="invoice-sheet" class="print-area shadow-lg relative flex flex-col justify-between border p-6">
                    <div>
                        <div class="flex justify-between items-start border-b border-black pb-1 mb-2">
                            <img class="w-[80px]" src="img/logo apparel.png" alt="Logo">
                            <div class="text-right">
                                <h1 class="text-sm font-black italic uppercase leading-none">Invoice</h1>
                                <p class="text-[7px] font-bold text-blue-900 uppercase">Guzel Apparel</p>
                                <p class="text-[7px] text-gray-800">0813 6161 6718 | guzelapparell@gmail.com</p>
                                <p class="text-[6px] text-gray-700 leading-tight">Jl. Prof. Ali Hasyimi, Simpang BPKP, Lamteh, Banda Aceh</p>
                            </div>
                        </div>

                        <div class="flex justify-between mb-2 text-[8px]">
                            <div class="leading-tight">
                                <p class="text-gray-700 font-bold uppercase text-[6px]">Customer:</p>
                                <p id="out-nama" class="font-bold text-[10px] uppercase">[Nama Pelanggan]</p>
                                <p class="text-[7px] pt-1"><span class="text-gray-700 text-[6px] font-bold uppercase">Estimasi:</span> <span id="out-estimasi" class="font-bold">NORMAL</span></p>
                            </div>
                            <div class="text-right leading-tight">
                                <p class="font-black text-[10px]" id="out-nota">#<?= $next_nota ?></p>
                                <p class="font-bold text-gray-700">Tanggal: <span id="out-tgl">15/01/2026</span></p>
                                <p class="text-red-600 font-bold">Deadline: <span id="out-deadline">08/02/2026</span></p>
                            </div>
                        </div>

                        <table class="w-full text-[8px] border-collapse">
                            <thead>
                                <tr class="bg-gray-100 border-y border-black uppercase font-bold text-[7px]">
                                    <th class="p-1 text-left">Item</th>
                                    <th class="w-6 text-center">Qty</th>
                                    <th class="w-16 text-right">Harga</th>
                                    <th class="w-16 text-right p-1">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody id="out-items" class="divide-y divide-gray-100"></tbody>
                        </table>

                        <div class="mt-2 flex justify-end relative">
                            <table class="w-32 text-[8px] font-bold">
                                <tr class="border-b">
                                    <td class="py-0.5">Sub Total</td>
                                    <td class="text-right">Rp <span id="out-sub">0</span></td>
                                </tr>
                                <tr id="row-deposit" class="border-b text-blue-800">
                                    <td class="py-0.5">Deposit Desain</td>
                                    <td class="text-right">Rp <span id="out-deposit">0</span></td>
                                </tr>
                                <tr id="row-panjar" class="border-b text-blue-800">
                                    <td class="py-0.5">Panjar Produksi</td>
                                    <td class="text-right">Rp <span id="out-panjar">0</span></td>
                                </tr>
                                <tr class="bg-gray-50 font-black italic">
                                    <td class="py-0.5 text-blue-900">SISA TAGIHAN</td>
                                    <td class="text-right text-blue-900">Rp <span id="out-sisa">0</span></td>
                                </tr>
                            </table>
                            <div id="label-lunas" class="hidden absolute right-2 top-2 border-4 border-slate-800 text-grey-600 text-xl font-black px-2 py-1 rotate-[-20deg] opacity-50 uppercase tracking-widest pointer-events-none">LUNAS</div>
                        </div>
                    </div>
                    <div >
                        <div class="flex justify-between items-end mb-8">
                            <div class="text-[7px] leading-tight">
                                <p class="font-bold mb-1 w-fit">Pembayaran:</p>
                                <p>BSI: 1047820991</p>
                                <p>BCA Syariah: 0670254127</p>
                                <p class="text-gray-700 italic mt-1">a.n. Muhammad Kausar</p>
                            </div>

                            <div class="text-center pr-8">
                                <p class="text-[7px] font-bold uppercase mb-10 text-gray-700">Tanda Terima</p>
                                <div class="border-b border-black w-20 mx-auto"></div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-1">
                            <p class="text-center font-bold italic text-[9px] text-gray-800">Spesialis Custom Jersey Printing</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="section-transaksi" class="hidden no-print">
            <div class="bg-white rounded-xl shadow border overflow-hidden">
                <div class="bg-blue-900 p-4 flex flex-col md:flex-row justify-between items-center text-white gap-4">
                    <h2 class="font-black italic uppercase text-sm md:text-base">Daftar Transaksi</h2>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                        <div class="flex items-center bg-white rounded p-1 w-full sm:w-auto">
                            <input type="date" id="export_awal" value="<?= date('Y-m-01') ?>" class="p-1 text-black text-[10px] outline-none">
                            <span class="text-black px-1">-</span>
                            <input type="date" id="export_akhir" value="<?= date('Y-m-t') ?>" class="p-1 text-black text-[10px] outline-none border-r">
                            <select id="export_status" class="p-1 text-black text-[10px] font-bold bg-transparent">
                                <option value="Semua">SEMUA STATUS</option>
                                <option value="Lunas">LUNAS</option>
                                <option value="Belum Lunas">BELUM LUNAS</option>
                            </select>
                        </div>
                        <button onclick="exportExcel()" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2 rounded text-[10px] font-black flex justify-center items-center gap-2">
                            <i class="fas fa-file-excel"></i> EXPORT
                        </button>
                    </div>
                </div>

                <div class="p-4 border-b bg-gray-50">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="searchTable" placeholder="Cari nama atau nomor nota..." class="w-full pl-10 pr-4 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <button onclick="hapusMasal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-[10px] font-bold uppercase">
                        <i class="fas fa-trash-alt mr-2"></i> Hapus Terpilih
                    </button>
                    <table class="w-full text-[10px] text-left border-collapse min-w-[800px]">
                        <thead class="bg-yellow-400 font-black uppercase text-gray-800">
                            <tr>
                                <th class="p-4 border border-yellow-500 text-center">
                                    <input type="checkbox" id="checkAll" class="w-4 h-4 cursor-pointer">
                                </th>
                                <th class="p-4 border border-yellow-500 font-black">Nota</th>
                                <th class="p-2 border border-yellow-500 font-black">Tgl Produksi</th>
                                <th class="p-4 border border-yellow-500 font-black text-red-700">Deadline</th>
                                <th class="p-4 border border-yellow-500 font-black">Pelanggan</th>
                                <th class="p-4 border border-yellow-500 font-black text-center">Qty</th>
                                <th class="p-4 border border-yellow-500 font-black text-center">Sisa Tagihan</th>
                                
                                <th class="p-4 border border-yellow-500 font-black text-center w-32">PRODUKSI</th>
                                
                                <th class="p-4 border border-yellow-500 font-black text-center">Status</th>
                                <th class="p-4 border border-yellow-500 font-black text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="transaksi-body">
                            <?php 
                            $query_tr = mysqli_query($conn, "SELECT * FROM transaksi ORDER BY tgl_masuk DESC, no_nota DESC");
                            
                            $allowed_items = [
                                'JERSEY TYPE A (FULL PRINT)', 'JERSEY TYPE B (STANDAR)',
                                'JERSEY TYPE C (JERSEY ONLY)', 'JERSEY TYPE D (BASIC)',
                                'BASEBALL JERSEY', 'BASKETBALL JERSEY',
                                'JERSEY PRINTING KIDS', 'JERSEY SETELAN JADI (POLOS)','JERSEY SETELAN JADI (SABLON)',
                            ];

                            if(mysqli_num_rows($query_tr) > 0):
                                while($t = mysqli_fetch_assoc($query_tr)): 
                                    $items_arr = json_decode($t['items'], true);
                                    $filtered_qty = 0; 

                                    if(is_array($items_arr)) { 
                                        foreach($items_arr as $io) { 
                                            $nama_item = strtoupper(trim($io['nama'] ?? ''));
                                            $qty_item = (int)($io['qty'] ?? 0);
                                            if (in_array($nama_item, $allowed_items)) {
                                                $filtered_qty += $qty_item; 
                                            }
                                        } 
                                    }
                            ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 text-center border-l">
                                    <input type="checkbox" name="nota_id[]" value="<?= $t['no_nota']; ?>" class="checkItem w-4 h-4 cursor-pointer">
                                </td>
                                
                                <td class="p-4 font-black text-blue-900">#<?= str_pad($t['no_nota'], 4, "0", STR_PAD_LEFT) ?></td>
                                <td class="p-4 text-[11px] font-semibold text-gray-700"><?= date('d/m/Y', strtotime($t['tgl_masuk'])) ?></td>
                                <td class="p-4 text-[11px] font-bold text-red-600"><?= date('d/m/Y', strtotime($t['deadline'])) ?></td>
                                <td class="p-4 text-sm font-bold uppercase"><?= $t['nama_pelanggan'] ?></td>
                                <td class="p-4 text-center">
                                    <span class="text-blue-700 text-[12px] font-black">
                                        <?= $filtered_qty ?> <small class="text-[10px]">PCS</small>
                                    </span>
                                </td>
                                <td class="p-4 text-right font-black text-blue-900">Rp <?= number_format($t['sisa']) ?></td>
                                
                                <td class="p-4 text-center">
                                    <?php
                                    // Logika Warna Background (Gunakan variabel $t bukan $row)
                                    $prod_status = $t['produksi'] ?? '-'; // Gunakan coalescing operator untuk keamanan
                                    
                                    $bg_class = "bg-gray-100 text-gray-700 border-gray-200"; // Default Abu
                                    if ($prod_status == 'FACTORY') {
                                        $bg_class = "bg-blue-100 text-blue-800 border-blue-200";
                                    } elseif ($prod_status == 'FASTPRINT') {
                                        $bg_class = "bg-orange-100 text-orange-800 border-orange-200";
                                    }
                                    ?>
                                    
                                    <div class="relative">
                                        <select onchange="updateProduksi(this, '<?= $t['no_nota'] ?>')" 
                                                class="appearance-none w-full text-[10px] font-black uppercase py-2 px-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors cursor-pointer <?= $bg_class ?>">
                                            <option value="-" <?= $prod_status == '-' ? 'selected' : '' ?>>-</option>
                                            <option value="FACTORY" <?= $prod_status == 'FACTORY' ? 'selected' : '' ?>>FACTORY</option>
                                            <option value="FASTPRINT" <?= $prod_status == 'FASTPRINT' ? 'selected' : '' ?>>FASTPRINT</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-gray-400">
                                            <i class="fas fa-chevron-down text-[8px]"></i>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded text-[10px] font-black uppercase <?= $t['status'] == 'Lunas' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center space-x-2">
                                    <button onclick='editData(<?= json_encode($t) ?>)' class="text-blue-600 hover:text-blue-900 transition"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirmDeleteTransaksi('<?= $t['no_nota']; ?>')" class="text-red-500 hover:text-red-800 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
    const menuBtn = document.getElementById('mobile-menu-button');
    const closeBtn = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function toggleSidebar() {
        const isOpen = !sidebar.classList.contains('translate-x-full');
        
        if (isOpen) {
            // Tutup Sidebar
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        } else {
            // Buka Sidebar
            overlay.classList.remove('hidden');
            setTimeout(() => {
                sidebar.classList.remove('translate-x-full');
                overlay.classList.remove('opacity-0');
            }, 10);
        }
    }

    menuBtn.addEventListener('click', toggleSidebar);
    closeBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

        let currentTargetRow = null;
        let isForcedLunas = false;
        // modal master barang
        function openModal(el) {
            currentTargetRow = el.closest('.item-row');
            document.getElementById('modalBarang').classList.remove('hidden');
        }

        function closeModal() { document.getElementById('modalBarang').classList.add('hidden'); }

        function selectItem(nama, harga) {
            if(currentTargetRow) {
                currentTargetRow.querySelector('.in-item').value = nama;
                currentTargetRow.querySelector('.in-harga').value = harga;
                // 1. Reset input pencarian di modal
                const searchInput = document.getElementById('searchBarang');
                if (searchInput) searchInput.value = '';

                // 2. Tampilkan kembali semua barang yang sempat terfilter (reset display)
                let rows = document.querySelectorAll('#listBarangModal div');
                rows.forEach(r => {
                    r.style.display = ''; 
                });
                updateTotals();
                closeModal();
            }
        }

        function switchTab(tab) {
            document.getElementById('section-input').classList.toggle('hidden', tab !== 'input');
            document.getElementById('section-transaksi').classList.toggle('hidden', tab !== 'transaksi');
            document.getElementById('btn-input').className = (tab === 'input' ? '' : 'uppercase font-bold');
            document.getElementById('btn-transaksi').className = (tab === 'transaksi' ? '' : 'uppercase font-bold');
        }

        function updateDeadline() {
            const tglVal = document.getElementById('in-tgl').value;
            if(!tglVal) return;
            const tgl = new Date(tglVal);
            const est = document.getElementById('in-status').value;
            tgl.setDate(tgl.getDate() + (est === 'Normal' ? 24 : 14));
            document.getElementById('in-deadline').value = tgl.toISOString().split('T')[0];
            updateTotals();
        }

    function setLunas() {
    let subtotal = 0;
    // Hitung total belanja saat ini
    document.querySelectorAll('.item-row').forEach(r => {
        const qty = parseFloat(r.querySelector('.in-qty').value) || 0;
        const harga = parseFloat(r.querySelector('.in-harga').value) || 0;
        subtotal += (qty * harga);
    });

    if(subtotal <= 0) {
        // Notifikasi Toast untuk error input
        Toast.fire({
            icon: 'warning',
            title: 'Gagal!',
            text: 'Silahkan isi barang dan kuantitas terlebih dahulu!'
        });
        return;
    }

    // Eksekusi pemaksaan pelunasan
    document.getElementById('in-deposit').value = 0;
    document.getElementById('in-panjar').value = subtotal;
    
    isForcedLunas = true; // Flag untuk tampilan invoice
    updateTotals();
    
    // Notifikasi sukses yang lebih cantik
    Swal.fire({
        icon: 'success',
        title: 'Status: Siap Lunas',
        text: 'Nilai panjar telah disesuaikan dengan total tagihan. Jangan lupa klik tombol SIMPAN.',
        confirmButtonColor: '#1e40af',
        confirmButtonText: 'Siap, Mengerti!'
    });
}

function updateTotals() {
    let subtotal = 0; 
    let rowsHtml = '';
    
    // 1. Hitung Subtotal asli dari barang
    document.querySelectorAll('.item-row').forEach(row => {
        const item = row.querySelector('.in-item').value;
        const qty = parseFloat(row.querySelector('.in-qty').value) || 0;
        const harga = parseFloat(row.querySelector('.in-harga').value) || 0;
        const total = qty * harga; 
        subtotal += total;
        
        if(item) {
            rowsHtml += `<tr class="">
                            <td class="p-1 font-bold uppercase">${item}</td>
                            <td class="text-center">${qty}</td>
                            <td class="text-right">${harga.toLocaleString()}</td>
                            <td class="text-right p-1 font-black">${total.toLocaleString()}</td>
                        </tr>`;
        }
    });

    // 2. Ambil nilai input
    let diskon = parseFloat(document.getElementById('in-diskon').value) || 0;
    let dep = parseFloat(document.getElementById('in-deposit').value) || 0;
    let pan = parseFloat(document.getElementById('in-panjar').value) || 0;

    // VALIDASI: Diskon tidak boleh melebihi subtotal
    if (diskon > subtotal) {
        diskon = subtotal;
        document.getElementById('in-diskon').value = diskon;
    }

    const hargaSetelahDiskon = subtotal - diskon;
    
    // VALIDASI: Total bayar tidak boleh melebihi harga setelah diskon
    if ((dep + pan) > hargaSetelahDiskon) {
        pan = hargaSetelahDiskon - dep;
        document.getElementById('in-panjar').value = pan;
    }

    const sisaAkhir = hargaSetelahDiskon - dep - pan;
    const isLunas = isForcedLunas || (subtotal > 0 && sisaAkhir <= 0);

    // 3. Update UI Dasar
    document.getElementById('out-items').innerHTML = rowsHtml;
    document.getElementById('out-sub').innerText = subtotal.toLocaleString();
    document.getElementById('out-deposit').innerText = dep.toLocaleString();
    document.getElementById('out-panjar').innerText = pan.toLocaleString();

    // 4. MANIPULASI BARIS DINAMIS (Diskon & Total Net)
    const areaSummary = document.getElementById('out-sisa').closest('table').querySelector('tbody') || document.getElementById('out-sisa').closest('table');
    
    // Bersihkan baris dinamis sebelumnya agar tidak duplikat
    if (document.getElementById('row-diskon-area')) document.getElementById('row-diskon-area').remove();
    if (document.getElementById('row-total-akhir')) document.getElementById('row-total-akhir').remove();

    // TAMPILKAN DISKON (Jika ada diskon)
    if (diskon > 0) {
        const trD = document.createElement('tr');
        trD.id = 'row-diskon-area';
        trD.className = 'border-b text-red-600 font-bold';
        trD.innerHTML = `<td>Diskon</td><td class="text-right">- Rp ${diskon.toLocaleString()}</td>`;
        // Sisipkan setelah baris Subtotal (index 1)
        areaSummary.insertBefore(trD, areaSummary.children[1]);
    }

    // TAMPILKAN TOTAL NET (Hanya jika LUNAS dan ADA DISKON)
    if (isLunas && diskon > 0) {
        const trT = document.createElement('tr');
        trT.id = 'row-total-akhir';
        trT.className = 'border-b bg-blue-50 font-black text-blue-900';
        trT.innerHTML = `<td>TOTAL NET</td><td class="text-right">Rp ${hargaSetelahDiskon.toLocaleString()}</td>`;
        // Sisipkan sebelum baris Sisa Tagihan (baris terakhir)
        const rows = areaSummary.querySelectorAll('tr');
        areaSummary.insertBefore(trT, rows[rows.length - 1]);
    }

    // 5. STATUS LUNAS & VISIBILITAS
    if (isLunas) {
        document.getElementById('label-lunas').classList.remove('hidden');
        document.getElementById('row-deposit').classList.add('hidden');
        document.getElementById('row-panjar').classList.add('hidden');
        document.getElementById('out-sisa').innerText = "0";
    } else {
        document.getElementById('label-lunas').classList.add('hidden');
        document.getElementById('row-deposit').classList.remove('hidden');
        document.getElementById('row-panjar').classList.remove('hidden');
        document.getElementById('out-sisa').innerText = Math.max(0, sisaAkhir).toLocaleString();
    }

    // Update Identitas
    document.getElementById('out-nama').innerText = document.getElementById('in-nama').value || '[Nama Pelanggan]';
    document.getElementById('out-tgl').innerText = document.getElementById('in-tgl').value;
    document.getElementById('out-deadline').innerText = document.getElementById('in-deadline').value;

    //6. AMBIL NILAI ESTIMASI DAN TAMPILKAN DI INVOICE
        const estimasiValue = document.getElementById('in-status').value; // 'in-status' adalah ID input estimasi Anda
        const outEstimasi = document.getElementById('out-estimasi');
        outEstimasi.innerText = estimasiValue;
        // OPSIONAL: Memberi warna merah jika Express agar lebih terlihat
        if(estimasiValue.toLowerCase() === 'express') {
            outEstimasi.className = "font-bold text-red-600 uppercase";
        } else {
            outEstimasi.className = "font-bold text-black uppercase";
        }
}

    function addItemRow() {
        const div = document.createElement('div'); 
        div.className = "item-row flex gap-2 mb-2";
        div.innerHTML = `<input type="text" name="items[]" readonly onclick="openModal(this)" required class="in-item flex-1 p-2 text-sm border rounded-lg bg-white">
        <input type="number" name="qtys[]" oninput="updateTotals()" required class="in-qty w-16 p-2 text-sm border rounded-lg">
        <input type="number" name="hargas[]" readonly class="in-harga w-24 p-2 text-sm border rounded-lg bg-gray-100 font-bold">
        <button type="button" onclick="this.parentElement.remove(); updateTotals();" class="text-red-500 px-2"><i class="fas fa-trash"></i></button>`;
        document.getElementById('item-container').appendChild(div);
        }

    function editData(data) {
    switchTab('input');
    // Format nota ke 4 digit (0001)
    let formattedNota = data.no_nota.toString().padStart(4, '0');
    // Set flag lunas jika status lunas tapi tidak ada cicilan manual
    isForcedLunas = (data.status === 'Lunas' && data.deposit_desain == 0 && data.panjar_produksi == 0);
    
    document.getElementById('form-title').innerText = "Edit Pesanan #" + formattedNota;
    document.getElementById('orderForm').action = "update_transaksi.php?id=" + formattedNota;
    
    // Isi data utama ke form
    document.getElementById('in-nama').value = data.nama_pelanggan;
    document.getElementById('in-status').value = data.estimasi;
    document.getElementById('in-tgl').value = data.tgl_masuk;
    document.getElementById('in-deadline').value = data.deadline;
    
    // ISI NILAI DISKON DARI DATABASE
    document.getElementById('in-diskon').value = data.diskon || 0; 
    
    document.getElementById('in-deposit').value = data.deposit_desain;
    document.getElementById('in-panjar').value = data.panjar_produksi;
    document.getElementById('display-nota').value = formattedNota;
    document.getElementById('out-nota').innerText = "#" + formattedNota;
    
    // Isi item barang
    const container = document.getElementById('item-container');
    container.innerHTML = '';
    const items = JSON.parse(data.items);
    if (items) {
        items.forEach(it => {
            const div = document.createElement('div'); 
            div.className = "item-row flex gap-2 mb-2";
            div.innerHTML = `<input type="text" name="items[]" value="${it.nama}" readonly onclick="openModal(this)" class="in-item flex-1 p-2 text-sm border rounded-lg bg-white">
                            <input type="number" name="qtys[]" value="${it.qty}" oninput="updateTotals()" class="in-qty w-16 p-2 text-sm border rounded-lg">
                            <input type="number" name="hargas[]" value="${it.harga}" readonly class="in-harga w-24 p-2 text-sm border rounded-lg bg-gray-100 font-bold">
                            <button type="button" onclick="this.parentElement.remove(); updateTotals();" class="text-red-500 px-2"><i class="fas fa-trash"></i></button>`;
            container.appendChild(div);
        });
    }
    
    // Jalankan updateTotals untuk memperbarui tampilan invoice preview
    updateTotals();
}

        function cetakRow(data) {
            editData(data);
            setTimeout(() => { window.print(); }, 500);
        }

        document.getElementById('searchTable').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#transaksi-body tr');
            rows.forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('searchBarang').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#listBarangModal div');
            rows.forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        window.onload = updateDeadline;

        function exportExcel() {
        const tglAwal = document.getElementById('export_awal').value;
        const tglAkhir = document.getElementById('export_akhir').value;
        const status = document.getElementById('export_status').value;
        
        if (!tglAwal || !tglAkhir) {
            alert("Pilih rentang tanggal terlebih dahulu!");
            return;
        }

        // Mengarahkan ke file export_excel.php dengan 3 parameter
        window.location.href = `export_transaksi.php?tgl_awal=${tglAwal}&tgl_akhir=${tglAkhir}&status=${status}`;
    }

    // Konfirmasi Hapus Satuan
    function confirmDeleteTransaksi(id) {
    Swal.fire({
        title: 'Hapus Transaksi?',
        text: "Nota #" + id + " akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#1e40af',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'hapus_transaksi.php?id=' + id;
        }
    });
}

// Logika hapus masal
const checkAll = document.getElementById('checkAll');
if(checkAll) {
    checkAll.addEventListener('change', function() {
        // Ambil semua checkbox yang ada saat tombol diklik
        const checkItems = document.querySelectorAll('.checkItem');
        checkItems.forEach(item => item.checked = checkAll.checked);
    });
}

    function hapusMasal() {
    // Ambil data terbaru dari DOM
    const selectedCheckboxes = document.querySelectorAll('.checkItem:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Pilih Data',
            text: 'Silahkan pilih minimal satu data yang akan dihapus',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    Swal.fire({
        title: 'Hapus ' + selectedIds.length + ' Transaksi?',
        text: "Semua data terpilih akan dihapus permanen dari sistem!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan loading saat proses
            Swal.showLoading();
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'hapus_masal.php';
            
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

    // 1. Konfigurasi Toast Default
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // 2. Ambil Parameter dari URL
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const error = urlParams.get('error');

        // 3. Logika Notifikasi Sukses & Error
        if (msg === 'saved') {
            Toast.fire({
                icon: 'success',
                title: 'Data berhasil disimpan!'
            });
        } else if (msg === 'updated') {
            Toast.fire({
                icon: 'success',
                title: 'Data berhasil diperbarui!'
            });
        } else if (msg === 'deleted') {
            Toast.fire({
                icon: 'success',
                title: 'Data berhasil dihapus!'
            });
        } else if (msg === 'error') {
            Toast.fire({
                icon: 'error',
                title: 'Terjadi kesalahan sistem!'
            });
        }

        // 4. Logika Notifikasi Error Akses (RBAC)
        if (error === 'unauthorized') {
            Toast.fire({
                icon: 'error',
                title: 'Akses Ditolak! Anda bukan Master.'
            });
        }

        // 5. Bersihkan URL agar saat refresh notifikasi tidak muncul lagi
        if (msg || error) {
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: newUrl}, '', newUrl);
        }
        
        function updateProduksi(selectElement, idNota) {
        const value = selectElement.value;
        
        // 1. Ubah Warna Langsung (UI Feedback)
        selectElement.className = "text-[10px] font-bold border-0 rounded-lg py-2 px-2 cursor-pointer focus:ring-0 w-full transition ";
        
        if (value === 'FACTORY') {
            selectElement.classList.add('bg-blue-100', 'text-blue-800');
        } else if (value === 'FASTPRINT') {
            selectElement.classList.add('bg-orange-100', 'text-orange-800');
        } else {
            selectElement.classList.add('bg-gray-100', 'text-gray-600');
        }

        // 2. Kirim ke Database (AJAX)
        const formData = new FormData();
        formData.append('id', idNota);
        formData.append('produksi', value);

        fetch('update_tempat_produksi.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            if(result.trim() === 'success') {
                // Opsional: Munculkan notifikasi kecil (Toast)
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 1500
                });
                Toast.fire({ icon: 'success', title: 'Status Produksi Diupdate' });
            } else {
                alert('Gagal menyimpan status produksi');
            }
        });
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