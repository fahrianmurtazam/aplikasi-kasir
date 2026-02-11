<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$alert_script = ""; // Variabel penampung script SweetAlert

if (isset($_POST['update'])) {
    $username = $_SESSION['username'];
    $pw_lama = $_POST['pw_lama'];
    $pw_baru = $_POST['pw_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    $result = mysqli_query($conn, "SELECT password FROM users WHERE username = '$username'");
    $row = mysqli_fetch_assoc($result);

    if (password_verify($pw_lama, $row['password'])) {
        if ($pw_baru === $konfirmasi) {
            $hash_baru = password_hash($pw_baru, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password = '$hash_baru' WHERE username = '$username'");
            
            // SKENARIO SUKSES: Tampilkan Popup Sukses lalu pindah ke Index
            $alert_script = "
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Password Anda telah diperbarui.',
                confirmButtonColor: '#1e40af',
                confirmButtonText: 'Kembali ke Dashboard'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });";
            
        } else {
            // SKENARIO ERROR: Konfirmasi tidak cocok
            $alert_script = "
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Cocok',
                text: 'Konfirmasi password baru tidak sama!',
                confirmButtonColor: '#1e40af'
            });";
        }
    } else {
        // SKENARIO ERROR: Password lama salah
        $alert_script = "
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Password lama yang Anda masukkan salah!',
            confirmButtonColor: '#1e40af'
        });";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Guzel Apparel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-md mx-auto mt-20 px-4">
        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
            <div class="text-center mb-8">
                <i class="fas fa-key text-4xl text-blue-800 mb-4"></i>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Ganti Password</h2>
            </div>

            <form action="" method="POST" class="space-y-4">
                <input type="password" name="pw_lama" placeholder="PASSWORD SAAT INI" required class="w-full p-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-800 outline-none text-xs font-bold uppercase transition">
                <input type="password" name="pw_baru" placeholder="PASSWORD BARU" required class="w-full p-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-800 outline-none text-xs font-bold uppercase transition">
                <input type="password" name="konfirmasi" placeholder="KONFIRMASI PASSWORD BARU" required class="w-full p-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-800 outline-none text-xs font-bold uppercase transition">
                
                <button type="submit" name="update" class="w-full bg-blue-800 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-900 transition uppercase tracking-widest text-xs">
                    Update Password
                </button>
                <a href="index.php" class="block text-center text-[12px] font-black text-gray-400 uppercase tracking-widest mt-4">Batal & Kembali</a>
            </form>
        </div>
    </div>

    <?php if(!empty($alert_script)): ?>
        <script>
            <?= $alert_script ?>
        </script>
    <?php endif; ?>

</body>
</html>