<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Logika Tambah Produk
if (isset($_POST['add'])) {
    $nama = $_POST['nama'];
    $modal = $_POST['modal'];
    $jual = $_POST['jual'];
    mysqli_query($conn, "INSERT INTO barang (nama_barang, harga_modal, harga_jual) VALUES ('$nama', '$modal', '$jual')");
    header("Location: barang.php");
}

// Logika Update Produk
if (isset($_POST['update'])) {
    $id = $_POST['id_barang'];
    $nama = $_POST['nama'];
    $modal = $_POST['modal'];
    $jual = $_POST['jual'];
    mysqli_query($conn, "UPDATE barang SET nama_barang='$nama', harga_modal='$modal', harga_jual='$jual' WHERE id=$id");
    header("Location: barang.php");
}

// Logika Hapus Produk
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM barang WHERE id=$id");
    header("Location: barang.php");
}

$query = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Barang - Guzel Apparel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .nav-container::-webkit-scrollbar { display: none; }
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
                    <a href="index.php" class="hover:text-blue-800 transition">Input Pesanan</a>
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
                <a href="index.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Input Pesanan</a>
                <a href="barang.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Master Barang</a>
                <?php if ($_SESSION['role'] === 'master'): ?>
                <a href="laporan.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Laporan Penjualan</a>
                <a href="aruskas.php" class="pb-2 border-b border-gray-100 hover:text-blue-800 transition">Arus Kas</a>
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
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        <div class="flex flex-col lg:flex-row gap-6">
            
            <div class="w-full lg:w-1/3">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                    <h2 id="form-label" class="text-sm font-black uppercase text-gray-700 mb-6 flex items-center gap-2">
                        <i class="fas fa-box-open text-blue-800"></i> Tambah Barang Baru
                    </h2>
                    
                    <form id="formBarang" action="" method="POST" class="space-y-4">
                        <input type="hidden" name="id_barang" id="id_barang">
                        
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Barang</label>
                            <input type="text" name="nama" id="in_nama" required class="w-full p-3 bg-gray-50 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Harga Modal</label>
                                <input type="number" name="modal" id="in_modal" required class="w-full p-3 bg-gray-50 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1 text-blue-600">Harga Jual</label>
                                <input type="number" name="jual" id="in_jual" required class="w-full p-3 bg-blue-50 border border-blue-100 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition font-bold text-blue-700">
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" name="add" id="btnSubmit" class="flex-1 bg-blue-800 hover:bg-blue-900 text-white font-bold py-3 rounded-lg transition uppercase text-[10px] tracking-wider shadow-lg">
                                <i class="fas fa-plus mr-2"></i>Tambah Barang
                            </button>
                            <button type="button" id="btnBatal" onclick="resetForm()" class="hidden bg-gray-500 hover:bg-gray-600 text-white font-bold px-4 rounded-lg transition uppercase text-[10px]">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="w-full lg:w-2/3">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-blue-900 p-4 flex justify-between items-center">
                        <h2 class="text-white font-black uppercase text-xs tracking-widest">Database Produk</h2>
                        <span class="bg-blue-800 text-white text-[10px] px-3 py-1 rounded-full border border-blue-700">
                            Total: <?= mysqli_num_rows($query) ?> Item
                        </span>
                    </div>

                    <div class="p-4 border-b bg-gray-50">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="searchBarang" placeholder="Cari nama barang..." class="w-full pl-10 pr-4 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-100 border-b">
                                <tr class="text-[10px] font-black uppercase text-gray-600">
                                    <th class="p-4">Nama Produk</th>
                                    <th class="p-4 text-right">Modal</th>
                                    <th class="p-4 text-right">Jual</th>
                                    <th class="p-4 text-center text-green-600">Laba</th>
                                    <th class="p-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="divide-y divide-gray-100">
                                <?php while($row = mysqli_fetch_assoc($query)): 
                                    $laba = $row['harga_jual'] - $row['harga_modal'];
                                ?>
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="p-4">
                                        <div class="text-xs font-black uppercase text-blue-900 leading-tight"><?= $row['nama_barang'] ?></div>
                                    </td>
                                    <td class="p-4 text-right text-[11px] font-semibold text-gray-500">
                                        Rp <?= number_format($row['harga_modal']) ?>
                                    </td>
                                    <td class="p-4 text-right text-[11px] font-black text-blue-800">
                                        Rp <?= number_format($row['harga_jual']) ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold block text-center">
                                            +<?= number_format($laba) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='editBarang(<?= json_encode($row) ?>)' class="w-8 h-8 flex items-center justify-center bg-yellow-400 text-yellow-900 rounded-lg hover:bg-yellow-500 transition shadow-sm">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                            <button type="button" 
                                                    onclick="confirmDeleteBarang(<?= $row['id']; ?>, '<?= addslashes($row['nama_barang']); ?>')" 
                                                    class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition shadow-sm">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
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

        // Real-time Search
        document.getElementById('searchBarang').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tableBody tr');
            rows.forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        // Function Edit
        function editBarang(data) {
            document.getElementById('form-label').innerHTML = `<i class="fas fa-edit text-yellow-600"></i> Edit: ${data.nama_barang}`;
            document.getElementById('id_barang').value = data.id;
            document.getElementById('in_nama').value = data.nama_barang;
            document.getElementById('in_modal').value = data.harga_modal;
            document.getElementById('in_jual').value = data.harga_jual;

            const btn = document.getElementById('btnSubmit');
            btn.name = 'update';
            btn.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan Perubahan';
            btn.className = "flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition uppercase text-[10px] shadow-lg";

            document.getElementById('btnBatal').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Reset Form
        function resetForm() {
            document.getElementById('form-label').innerHTML = `<i class="fas fa-box-open text-blue-800"></i> Tambah Barang Baru`;
            document.getElementById('formBarang').reset();
            document.getElementById('id_barang').value = '';
            
            const btn = document.getElementById('btnSubmit');
            btn.name = 'add';
            btn.innerHTML = '<i class="fas fa-plus mr-2"></i>Tambah Barang';
            btn.className = "flex-1 bg-blue-800 hover:bg-blue-900 text-white font-bold py-3 rounded-lg transition uppercase text-[10px] shadow-lg";

            document.getElementById('btnBatal').classList.add('hidden');
        }

        function confirmDeleteBarang(id, nama) {
            Swal.fire({
                title: 'Hapus Barang?',
                text: "Apakah Anda yakin ingin menghapus '" + nama + "'? Data yang dihapus tidak bisa dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#1e40af',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Arahkan ke link penghapusan
                    window.location.href = 'barang.php?hapus=' + id;
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