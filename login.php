<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    // Ambil data
    $row = mysqli_fetch_assoc($result);

    // CEK: Jika $row tidak null (username ditemukan)
    if ($row) {
        // Baru verifikasi password
        if (password_verify($password, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            header("Location: index.php");
            exit;
        } else {
            $error_msg = "Password salah!";
        }
    } else {
        // Jika $row null
        $error_msg = "Username tidak ditemukan di database!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Guzel Apparel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen px-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">
        <div class="text-center mb-8">
            <img src="img/logo CV.png" class="w-32 mx-auto mb-4" alt="Logo">
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Admin Login</h2>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-2">Management System</p>
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-6 text-sm flex items-center italic">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-1 ml-1">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" required class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-800 focus:border-transparent outline-none transition text-sm font-semibold" placeholder="Username">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-1 ml-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-800 focus:border-transparent outline-none transition text-sm font-semibold" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" name="login" class="w-full bg-blue-800 hover:bg-blue-900 text-white font-black py-4 rounded-xl transition uppercase text-xs tracking-[0.2em] shadow-lg shadow-blue-200">
                Masuk Sekarang <i class="fas fa-arrow-right ml-2"></i>
            </button>
            <div class="text-center mt-6">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Bermasalah dengan akses?</p>
                <a href="https://wa.me/6285261996471?text=Halo%20Admin%20Guzel,%20saya%20lupa%20password%20untuk%20sistem%20invoice.%20Mohon%20bantuannya." 
                target="_blank"
                class="text-xs font-black text-blue-800 hover:text-blue-600 transition uppercase tracking-tighter">
                <i class="fab fa-whatsapp mr-1"></i> Hubungi Owner via WhatsApp
                </a>
            </div>
        </form>
    </div>
</body>
</html>