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

// 1. Fungsi Ambil Harga Modal
function getModalPrice($conn, $nama_barang) {
    $nama_barang = mysqli_real_escape_string($conn, $nama_barang);
    $res = mysqli_query($conn, "SELECT harga_modal FROM barang WHERE nama_barang = '$nama_barang' LIMIT 1");
    $data = mysqli_fetch_assoc($res);
    return $data ? (float)$data['harga_modal'] : 0;
}

// 2. Logika Simpan (Tambah / Update) - Mendukung Tipe Masuk & Keluar
if (isset($_POST['save_keluar'])) {
    $tgl = $_POST['tanggal'];
    $desc = $_POST['deskripsi'];
    $qty = $_POST['qty_keluar'] != "" ? $_POST['qty_keluar'] : null;
    $nominal = $_POST['nominal'];
    $id = $_POST['id_pengeluaran'];
    $tipe = $_POST['tipe_transaksi']; // Ambil tipe dari input hidden

    if ($id == "") {
        // Mode Tambah
        $stmt = $conn->prepare("INSERT INTO pengeluaran (tipe, tanggal, deskripsi, pcs, nominal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssid", $tipe, $tgl, $desc, $qty, $nominal);
    } else {
        // Mode Update
        $stmt = $conn->prepare("UPDATE pengeluaran SET tipe=?, tanggal=?, deskripsi=?, pcs=?, nominal=? WHERE id=?");
        $stmt->bind_param("sssidi", $tipe, $tgl, $desc, $qty, $nominal, $id);
    }
    $stmt->execute();
    header("Location: aruskas.php");
}

// 3. Logika Hapus
if (isset($_GET['hapus_keluar'])) {
    $id = $_GET['hapus_keluar'];
    mysqli_query($conn, "DELETE FROM pengeluaran WHERE id=$id");
    header("Location: aruskas.php");
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t');
$data_gabungan = [];

// 4. DAFTAR BARANG YANG DIHITUNG PCS-NYA
$allowed_items = [
    'JERSEY TYPE A (FULL PRINT)', 'JERSEY TYPE B (STANDAR)',
    'JERSEY TYPE C (JERSEY ONLY)', 'JERSEY TYPE D (BASIC)',
    'BASEBALL JERSEY', 'BASKETBALL JERSEY',
    'JERSEY PRINTING KIDS', '',
];

// 5. Ambil Data Uang Masuk (Profit Otomatis dari Nota)
$query_masuk = mysqli_query($conn, "SELECT no_nota, tgl_masuk as tanggal, nama_pelanggan, items, panjar_produksi FROM transaksi WHERE status='Lunas' AND tgl_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'");

while($row = mysqli_fetch_assoc($query_masuk)) {
    $items = json_decode($row['items'], true);
    $pcs_total = 0;

    // Kita tetap hitung PCS untuk laporan jumlah barang
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
        'pcs' => $pcs_total > 0 ? $pcs_total : '-',
        'nominal' => (float)$row['panjar_produksi'], // MENGGUNAKAN TOTAL HARGA (OMZET)
        'tipe' => 'masuk',
        'is_auto' => true, 
        'id' => $row['no_nota']
    ];
}

// 6. Ambil Data Manual (Masuk & Keluar dari tabel pengeluaran)
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

usort($data_gabungan, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arus Kas - Guzel Apparel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
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
                <a href="ganti_password.php" class="flex items-center text-gray-700 hover:bg-blue-50 hover:text-blue-800 rounded-2xl transition group">
                    <div class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 group-hover:bg-blue-100 mr-1">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="font-black text-xs uppercase tracking-widest">Keamanan</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="w-full lg:w-1/3">
                <div class="flex gap-2 mb-4">
                    <button type="button" onclick="switchMode('keluar')" id="btnTabKeluar" class="flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-red-600 text-white border-2 border-red-600 transition-all">
                        <i class="fas fa-minus-circle mr-1"></i> Transaksi Keluar
                    </button>
                    <button type="button" onclick="switchMode('masuk')" id="btnTabMasuk" class="flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-white text-green-600 border-2 border-green-600 transition-all">
                        <i class="fas fa-plus-circle mr-1"></i> Transaksi Masuk
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-red-500 sticky top-24 transition-all duration-300" id="container-form">
                    <h2 class="text-lg font-bold mb-6 uppercase text-red-600 flex justify-between items-center" id="form-title">
                        <span><i class="fas fa-minus-circle mr-2"></i>Catat Keluar</span>
                        <button type="button" onclick="resetForm()" id="btnBatal" class="hidden text-[10px] bg-gray-500 hover:bg-gray-700 text-white px-3 py-1 rounded-md transition-all uppercase font-bold">BATAL</button>
                    </h2>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="id_pengeluaran" id="id_pengeluaran">
                        <input type="hidden" name="tipe_transaksi" id="tipe_transaksi" value="keluar">
                        
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Tanggal</label>
                            <input type="date" name="tanggal" id="in_tgl" value="<?= date('Y-m-d') ?>" required class="w-full p-2 bg-gray-50 border rounded-lg outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase">Deskripsi</label>
                            <input type="text" name="deskripsi" id="in_desc" required placeholder="" class="w-full p-2 bg-gray-50 border rounded-lg outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase">Harga Satuan</label>
                                <input type="number" id="in_harga" placeholder="0" class="w-full p-2 bg-gray-50 border rounded-lg outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase">Qty (Pcs)</label>
                                <input type="number" name="qty_keluar" id="in_qty" placeholder="Opsional" class="w-full p-2 bg-gray-50 border rounded-lg outline-none">
                            </div>
                        </div>
                        <div id="box-nominal" class="bg-red-50 p-4 rounded-lg border border-red-100 transition-colors">
                            <label id="label-nominal" class="block text-[10px] font-bold text-red-400 uppercase mb-1">Total Nominal Keluar</label>
                            <input type="number" name="nominal" id="in_total" required placeholder="0" class="w-full bg-transparent text-xl font-black text-red-600 outline-none">
                        </div>
                        <button type="submit" name="save_keluar" id="btnSubmit" class="w-full bg-red-600 text-white p-3 rounded-lg font-black uppercase text-xs hover:bg-red-700 transition shadow-lg">
                            Simpan Transaksi
                        </button>
                    </form>
                </div>
            </div>

            <div class="w-full lg:w-2/3">
                <div class="bg-white rounded-xl shadow-md overflow-hidden border-t-4 border-blue-900">
                    <div class="bg-blue-900 p-4 flex justify-between items-center text-white font-black uppercase text-sm ">
                        <span>Rekapitulasi Arus Kas</span>
                        <form method="GET" class="flex flex-wrap items-center gap-2 text-black px-4">
                            <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="text-[10px] p-1 rounded border">
                            <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="text-[10px] p-1 rounded border">
                            
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-[10px] font-bold transition flex items-center gap-1">
                                <i class="fas fa-search"></i> FILTER
                            </button>

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
                                    <th class="p-4 text-left">Deskripsi Transaksi</th>
                                    <th class="p-4 text-center">Pcs</th>
                                    <th class="p-4 text-right text-green-600">Masuk(+)</th>
                                    <th class="p-4 text-right text-red-600">Keluar(-)</th>
                                    <th class="px-2 text-right">Saldo Kas</th>
                                    <th class="p-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $saldo_kumulatif = 0;
                                if(empty($data_gabungan)): ?>
                                    <tr><td colspan="7" class="p-10 text-center text-gray-400 italic">Tidak ada data pada periode ini.</td></tr>
                                <?php endif;
                                
                                foreach($data_gabungan as $d): 
                                    $masuk = ($d['tipe'] == 'masuk') ? $d['nominal'] : 0;
                                    $keluar = ($d['tipe'] == 'keluar') ? $d['nominal'] : 0;
                                    $saldo_kumulatif += ($masuk - $keluar);
                                ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="p-4"><?= date('d/m/y', strtotime($d['tanggal'])) ?></td>
                                    <td class="p-4 font-semibold uppercase <?= $d['tipe'] == 'keluar' ? 'text-red-500' : 'text-blue-800' ?>">
                                        <?= $d['deskripsi'] ?>
                                        <?php if(!$d['is_auto'] && $d['tipe'] == 'masuk'): ?>
                                            <span class="text-[9px] bg-green-100 text-green-600 px-1 rounded ml-1">MANUAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center font-bold"><?= $d['pcs'] ?></td>
                                    <td class="p-4 text-right text-green-600 font-bold"><?= $masuk > 0 ? number_format($masuk) : '-' ?></td>
                                    <td class="p-4 text-right text-red-600 font-bold"><?= $keluar > 0 ? number_format($keluar) : '-' ?></td>
                                    <td class="px-2 text-right font-black italic text-gray-800">Rp <?= number_format($saldo_kumulatif) ?></td>
                                    <td class="p-4 text-center flex justify-center gap-3">
                                        <?php if(!$d['is_auto']): ?>
                                            <button onclick="editKeluar(<?= htmlspecialchars(json_encode($d)) ?>)" class="text-blue-500 hover:text-blue-600">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <button type="button" onclick="confirmDeleteKas(<?= $d['id']; ?>)" class="text-red-500 hover:text-red-600">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle text-green-300" title="Profit Otomatis"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-900 text-white font-black">
                                <tr>
                                    <td colspan="5" class="p-4 text-right uppercase tracking-wider text-[12px]">Total Saldo Kas Akhir Periode</td>
                                    <td class="px-2 text-right text-yellow-400 text-[12px] italic">Rp <?= number_format($saldo_kumulatif) ?></td>
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

    const inHarga = document.getElementById('in_harga');
    const inQty = document.getElementById('in_qty');
    const inTotal = document.getElementById('in_total');
    const btnBatal = document.getElementById('btnBatal');
    const btnSubmit = document.getElementById('btnSubmit');
    const formTitle = document.getElementById('form-title');
    const containerForm = document.getElementById('container-form');
    const tipeTransaksi = document.getElementById('tipe_transaksi');
    const boxNominal = document.getElementById('box-nominal');
    const labelNominal = document.getElementById('label-nominal');
    const btnTabKeluar = document.getElementById('btnTabKeluar');
    const btnTabMasuk = document.getElementById('btnTabMasuk');

    function switchMode(tipe) {
        tipeTransaksi.value = tipe;
        if (tipe === 'masuk') {
            containerForm.classList.replace('border-red-500', 'border-green-500');
            formTitle.innerHTML = "<span><i class='fas fa-plus-circle mr-2'></i>Catat Masuk</span>";
            formTitle.classList.replace('text-red-600', 'text-green-600');
            boxNominal.className = "bg-green-50 p-4 rounded-lg border border-green-100 transition-colors";
            labelNominal.className = "block text-[10px] font-bold text-green-400 uppercase mb-1";
            labelNominal.innerText = "Total Nominal Masuk";
            inTotal.classList.replace('text-red-600', 'text-green-600');
            btnSubmit.classList.replace('bg-red-600', 'bg-green-600');
            btnSubmit.classList.replace('hover:bg-red-700', 'hover:bg-green-700');
            // Tab Style
            btnTabMasuk.className = "flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-green-600 text-white border-2 border-green-600";
            btnTabKeluar.className = "flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-white text-red-600 border-2 border-red-600";
        } else {
            containerForm.classList.replace('border-green-500', 'border-red-500');
            formTitle.innerHTML = "<span><i class='fas fa-minus-circle mr-2'></i>Catat Keluar</span>";
            formTitle.classList.replace('text-green-600', 'text-red-600');
            boxNominal.className = "bg-red-50 p-4 rounded-lg border border-red-100 transition-colors";
            labelNominal.className = "block text-[10px] font-bold text-red-400 uppercase mb-1";
            labelNominal.innerText = "Total Nominal Keluar";
            inTotal.classList.replace('text-green-600', 'text-red-600');
            btnSubmit.classList.replace('bg-green-600', 'bg-red-600');
            btnSubmit.classList.replace('hover:bg-green-700', 'hover:bg-red-700');
            // Tab Style
            btnTabKeluar.className = "flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-red-600 text-white border-2 border-red-600";
            btnTabMasuk.className = "flex-1 py-2 rounded-lg font-bold text-[10px] uppercase shadow-md bg-white text-green-600 border-2 border-green-600";
        }
    }

    function hitung() {
        const harga = parseFloat(inHarga.value) || 0;
        const qty = parseFloat(inQty.value) || 1; 
        if (harga > 0) {
            inTotal.value = Math.round(harga * qty);
            inTotal.readOnly = true;
            inTotal.classList.add('bg-gray-100');
        } else {
            inTotal.readOnly = false;
            inTotal.classList.remove('bg-gray-100');
        }
    }

    inHarga.addEventListener('input', hitung);
    inQty.addEventListener('input', hitung);

    function editKeluar(data) {
        switchMode(data.tipe);
        if(btnBatal) btnBatal.classList.remove('hidden');
        btnSubmit.innerText = "Simpan Perubahan";
        
        document.getElementById('id_pengeluaran').value = data.id;
        document.getElementById('in_tgl').value = data.tanggal;
        document.getElementById('in_desc').value = data.deskripsi;
        const qtyValue = (data.pcs === '-' || data.pcs === null) ? '' : data.pcs;
        document.getElementById('in_qty').value = qtyValue;
        document.getElementById('in_total').value = data.nominal;
        
        if (qtyValue > 0) {
            document.getElementById('in_harga').value = Math.round(data.nominal / qtyValue);
            inTotal.readOnly = true;
        } else {
            document.getElementById('in_harga').value = '';
            inTotal.readOnly = false;
        }
        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    function resetForm() {
        window.location.href = 'aruskas.php';
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