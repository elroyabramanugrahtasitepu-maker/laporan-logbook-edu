<?php
// 1. Memulai Sesi (Wajib ditaruh di baris paling atas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Tangani Proses Login & Logout
$login_error = false;

// Jika form password disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manager_pass'])) {
    if ($_POST['manager_pass'] === 'eduventure') {
        $_SESSION['is_manager'] = true; // Pintu dibuka
        
        // Langsung arahkan ke menu yang tadi ingin diklik
        $redirect = !empty($_POST['redirect_url']) ? $_POST['redirect_url'] : 'manager.php';
        header("Location: " . $redirect);
        exit();
    } else {
        $login_error = true; // Password salah
    }
}

// Jika tombol "Kunci Kembali" diklik
if (isset($_GET['action']) && $_GET['action'] == 'lock') {
    session_destroy(); // Hancurkan sesi
    header("Location: index.php"); // Refresh halaman
    exit();
}

// Cek apakah saat ini statusnya sedang login sebagai manager
$is_manager = isset($_SESSION['is_manager']) && $_SESSION['is_manager'] === true;

// Helper function untuk mencetak link (terkunci vs terbuka)
function cetakLink($url, $is_manager) {
    if ($is_manager) {
        return 'href="' . $url . '"'; // Jika terbuka, link langsung aktif
    } else {
        return 'href="javascript:void(0)" onclick="bukaModalAuth(\'' . $url . '\')"'; // Jika terkunci, panggil modal
    }
}


// 3. Konfigurasi Koneksi Database
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_logbook';

$conn = mysqli_connect($host, $user, $pass, $db);

// 4. Logika untuk Menghitung Notifikasi (Laporan Hari Ini) saat halamat dimuat pertama kali
$notif_counts = ['IT' => 0, 'Finance' => 0, 'Staff' => 0, 'Pengajar' => 0, 'Semua' => 0];

if ($conn) {
    $notif_query = "SELECT divisi, COUNT(*) as jumlah FROM tabel_logbook WHERE DATE(waktu_upload) = CURDATE() GROUP BY divisi";
    $notif_result = mysqli_query($conn, $notif_query);

    if ($notif_result) {
        while ($row = mysqli_fetch_assoc($notif_result)) {
            $div = $row['divisi'];
            $jml = (int)$row['jumlah'];
            
            if (strpos($div, 'Pengajar') !== false) {
                $notif_counts['Pengajar'] += $jml;
            } elseif (isset($notif_counts[$div])) {
                $notif_counts[$div] += $jml;
            }
            $notif_counts['Semua'] += $jml; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Logbook & Laporan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 font-sans antialiased overflow-x-hidden">

    <nav class="bg-indigo-700 text-white shadow-md sticky top-0 z-40 backdrop-blur-md bg-indigo-700/95 border-b border-indigo-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="bg-white/10 p-2.5 rounded-xl shadow-inner border border-white/20">
                        <i class="fas fa-layer-group text-xl text-indigo-100"></i>
                    </div>
                    <div>
                        <span class="font-bold text-xl block leading-tight tracking-wide">Eduventure</span>
                        <span class="text-[11px] font-medium text-indigo-200 flex items-center uppercase tracking-wider">
                            <span class="relative flex h-2 w-2 mr-1.5">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Live Dashboard
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-5">
                    <div class="hidden sm:flex items-center text-sm font-semibold bg-indigo-900/50 px-4 py-2 rounded-xl border border-indigo-500/30 shadow-inner text-indigo-100">
                        <i class="far fa-clock mr-2 text-indigo-300"></i>
                        <span id="realtimeClock" class="tracking-wider">00:00:00 WIB</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-[0_2px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-100 mb-10 flex flex-col md:flex-row justify-between items-start md:items-center bg-gradient-to-br from-white via-white to-indigo-50/50 relative overflow-hidden animate-fade-in-up">
            <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-100/40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
            
            <div class="relative z-10">
                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight mb-2">
                    Halo, Tim Eduventure <span class="text-2xl animate-bounce inline-block ml-2">👋</span>
                </h1>
                <p class="text-slate-500 text-base md:text-lg max-w-2xl">Pilih menu di bawah untuk mulai mengisi logbook harian atau melihat rekapitulasi data operasional.</p>
            </div>
            
            <div class="hidden md:block text-right relative z-10 border-l-2 border-slate-100 pl-8">
                <div class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Tanggal Hari Ini</div>
                <div class="text-2xl font-bold text-indigo-600">
                    <?php 
                        $hari = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
                        $bulan = array("","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
                        echo $hari[date("w")] . ", " . date("d") . " " . $bulan[date("n")] . " " . date("Y");
                    ?>
                </div>
            </div>
        </div>

        <div class="flex items-center mb-6 animate-fade-in-up delay-100">
            <div class="h-8 w-1.5 bg-indigo-500 rounded-full mr-3"></div>
            <h2 class="text-xl font-extrabold text-slate-700 uppercase tracking-wide">Form Input Logbook</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-14 animate-fade-in-up delay-100">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group">
                <div class="flex items-center justify-between mb-5">
                    <div class="bg-blue-50 text-blue-600 w-14 h-14 flex items-center justify-center rounded-2xl group-hover:scale-110 transition-transform duration-300 ring-4 ring-white border border-blue-100 shadow-sm"><i class="fas fa-network-wired text-2xl"></i></div>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Divisi IT</h3>
                <p class="text-slate-500 text-sm mt-1 mb-6 line-clamp-2">Laporan sistem, server, jaringan & maintenance harian.</p>
                <a href="upload_logbook.php" class="flex items-center justify-center w-full text-sm font-bold text-blue-600 bg-blue-50/80 py-3 rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-300 border border-blue-100 hover:border-blue-600">
                    Isi Logbook <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group">
                <div class="flex items-center justify-between mb-5">
                    <div class="bg-emerald-50 text-emerald-600 w-14 h-14 flex items-center justify-center rounded-2xl group-hover:scale-110 transition-transform duration-300 ring-4 ring-white border border-emerald-100 shadow-sm"><i class="fas fa-wallet text-2xl"></i></div>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Finance</h3>
                <p class="text-slate-500 text-sm mt-1 mb-6 line-clamp-2">Pencatatan arus kas, pengeluaran & anggaran harian.</p>
                <a href="upload_logbook.php" class="flex items-center justify-center w-full text-sm font-bold text-emerald-600 bg-emerald-50/80 py-3 rounded-xl hover:bg-emerald-600 hover:text-white transition-all duration-300 border border-emerald-100 hover:border-emerald-600">
                    Isi Logbook <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group">
                <div class="flex items-center justify-between mb-5">
                    <div class="bg-purple-50 text-purple-600 w-14 h-14 flex items-center justify-center rounded-2xl group-hover:scale-110 transition-transform duration-300 ring-4 ring-white border border-purple-100 shadow-sm"><i class="fas fa-users-cog text-2xl"></i></div>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Staff</h3>
                <p class="text-slate-500 text-sm mt-1 mb-6 line-clamp-2">Kegiatan operasional, pendaftaran & administrasi.</p>
                <a href="upload_logbook.php" class="flex items-center justify-center w-full text-sm font-bold text-purple-600 bg-purple-50/80 py-3 rounded-xl hover:bg-purple-600 hover:text-white transition-all duration-300 border border-purple-100 hover:border-purple-600">
                    Isi Logbook <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group">
                <div class="flex items-center justify-between mb-5">
                    <div class="bg-orange-50 text-orange-600 w-14 h-14 flex items-center justify-center rounded-2xl group-hover:scale-110 transition-transform duration-300 ring-4 ring-white border border-orange-100 shadow-sm"><i class="fas fa-chalkboard-teacher text-2xl"></i></div>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Tim Pengajar</h3>
                <p class="text-slate-500 text-sm mt-1 mb-6 line-clamp-2">Jurnal materi, evaluasi kelas & aktivitas instruktur.</p>
                <a href="upload_logbook.php" class="flex items-center justify-center w-full text-sm font-bold text-orange-600 bg-orange-50/80 py-3 rounded-xl hover:bg-orange-600 hover:text-white transition-all duration-300 border border-orange-100 hover:border-orange-600">
                    Isi Logbook <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>
        </div>

        <div class="flex items-center mb-6 animate-fade-in-up delay-200">
            <div class="h-8 w-1.5 bg-slate-800 rounded-full mr-3"></div>
            <h2 class="text-xl font-extrabold text-slate-700 uppercase tracking-wide flex-shrink-0">Ruang Manager & Rekapitulasi</h2>
            
            <?php if($is_manager): ?>
                <span class="ml-3 bg-emerald-100 text-emerald-600 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider flex-shrink-0"><i class="fas fa-unlock mr-1"></i> Akses Terbuka</span>
                <a href="?action=lock" class="ml-auto text-xs font-bold text-rose-500 hover:text-white border border-rose-200 hover:bg-rose-500 px-3 py-1.5 rounded-lg transition-colors flex items-center shadow-sm">
                    <i class="fas fa-lock mr-1.5"></i> Kunci Kembali
                </a>
            <?php else: ?>
                <span class="ml-3 bg-rose-100 text-rose-600 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider flex-shrink-0"><i class="fas fa-lock mr-1"></i> Terkunci</span>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 animate-fade-in-up delay-200">
            
            <a <?= cetakLink('manager.php', $is_manager) ?> class="relative overflow-visible flex flex-col items-center justify-center p-6 bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-800 rounded-3xl hover:-translate-y-1 group transition-all duration-300 shadow-lg shadow-slate-900/20 cursor-pointer">
                <span id="badge-semua-container" class="absolute -top-3 -right-3 h-8 w-8 z-20 <?= $notif_counts['Semua'] > 0 ? 'flex' : 'hidden' ?>">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span id="badge-semua-angka" class="relative inline-flex rounded-full h-8 w-8 bg-rose-500 text-white text-xs font-bold items-center justify-center shadow-lg border-2 border-white"><?= $notif_counts['Semua'] ?></span>
                </span>
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-white mb-4 border border-white/20 backdrop-blur-sm group-hover:scale-110 transition-transform">
                    <i class="fas fa-folder-open text-3xl"></i>
                </div>
                <span class="font-bold text-white text-lg text-center relative z-10">Semua Laporan</span>
            </a>

            <a <?= cetakLink('manager.php?filter=IT', $is_manager) ?> class="relative overflow-visible flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-3xl hover:border-blue-400 hover:shadow-lg hover:shadow-blue-100 hover:-translate-y-1 group transition-all duration-300 cursor-pointer">
                <span id="badge-IT" class="absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 items-center justify-center rounded-full shadow-md border-2 border-white z-20 animate-bounce <?= $notif_counts['IT'] > 0 ? 'flex' : 'hidden' ?>"><?= $notif_counts['IT'] ?></span>
                <div class="w-14 h-14 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform"><i class="fas fa-network-wired text-2xl"></i></div>
                <span class="font-bold text-slate-700 text-center group-hover:text-blue-600 transition-colors">Rekap IT</span>
            </a>

            <a <?= cetakLink('manager.php?filter=Finance', $is_manager) ?> class="relative overflow-visible flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-3xl hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-100 hover:-translate-y-1 group transition-all duration-300 cursor-pointer">
                <span id="badge-Finance" class="absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 items-center justify-center rounded-full shadow-md border-2 border-white z-20 animate-bounce <?= $notif_counts['Finance'] > 0 ? 'flex' : 'hidden' ?>"><?= $notif_counts['Finance'] ?></span>
                <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform"><i class="fas fa-wallet text-2xl"></i></div>
                <span class="font-bold text-slate-700 text-center group-hover:text-emerald-600 transition-colors">Rekap Finance</span>
            </a>

            <a <?= cetakLink('manager.php?filter=Staff', $is_manager) ?> class="relative overflow-visible flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-3xl hover:border-purple-400 hover:shadow-lg hover:shadow-purple-100 hover:-translate-y-1 group transition-all duration-300 cursor-pointer">
                <span id="badge-Staff" class="absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 items-center justify-center rounded-full shadow-md border-2 border-white z-20 animate-bounce <?= $notif_counts['Staff'] > 0 ? 'flex' : 'hidden' ?>"><?= $notif_counts['Staff'] ?></span>
                <div class="w-14 h-14 bg-purple-50 text-purple-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform"><i class="fas fa-users-cog text-2xl"></i></div>
                <span class="font-bold text-slate-700 text-center group-hover:text-purple-600 transition-colors">Rekap Staff</span>
            </a>

            <a <?= cetakLink('manager.php?filter=Pengajar', $is_manager) ?> class="relative overflow-visible flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-3xl hover:border-orange-400 hover:shadow-lg hover:shadow-orange-100 hover:-translate-y-1 group transition-all duration-300 cursor-pointer">
                <span id="badge-Pengajar" class="absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 items-center justify-center rounded-full shadow-md border-2 border-white z-20 animate-bounce <?= $notif_counts['Pengajar'] > 0 ? 'flex' : 'hidden' ?>"><?= $notif_counts['Pengajar'] ?></span>
                <div class="w-14 h-14 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform"><i class="fas fa-chalkboard-user text-2xl"></i></div>
                <span class="font-bold text-slate-700 text-center group-hover:text-orange-600 transition-colors">Rekap Pengajar</span>
            </a>

        </div>

    </div>

    <div id="authModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 opacity-0">
        <form method="POST" action="index.php" id="authCard" class="bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl transform scale-95 transition-all duration-300 border border-slate-100">
            
            <input type="hidden" name="redirect_url" id="redirectUrlInput" value="">
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm">
                    <i class="fas fa-lock text-2xl"></i>
                </div>
                <h3 class="text-xl font-extrabold text-slate-800">Otorisasi Diperlukan</h3>
                <p class="text-slate-500 text-sm mt-1">Sesi terkunci. Masukkan sandi manager.</p>
            </div>
            
            <div class="relative mb-2">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-key text-slate-400"></i>
                </div>
                <input type="password" name="manager_pass" id="passInput" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-10 pr-4 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white outline-none transition-all text-slate-700 font-medium tracking-widest placeholder-slate-400" placeholder="••••••••" required>
            </div>
            
            <p id="errorMsg" class="text-rose-500 text-xs text-center hidden font-semibold mb-4 animate-pulse"><i class="fas fa-exclamation-circle mr-1"></i> Kata sandi salah!</p>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="tutupModalAuth()" class="w-1/2 py-3 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors focus:outline-none">Batal</button>
                <button type="submit" class="w-1/2 py-3 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all active:scale-95 focus:outline-none">Buka</button>
            </div>
            
        </form>
    </div>

    <script>
        // --- 1. Fungsi Jam Real-time ---
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('realtimeClock').innerText = timeString + ' WIB';
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- 2. AJAX Fetch API untuk Notifikasi Live (Setiap 3 Detik) ---
        function updateNotifBadges() {
            fetch('api_notif.php')
                .then(response => response.json())
                .then(data => {
                    // Update Badge: Semua Laporan
                    let badgeSemuaContainer = document.getElementById('badge-semua-container');
                    let badgeSemuaAngka = document.getElementById('badge-semua-angka');
                    if(data['Semua'] > 0) {
                        badgeSemuaContainer.classList.remove('hidden');
                        badgeSemuaContainer.classList.add('flex');
                        badgeSemuaAngka.innerText = data['Semua'];
                    } else {
                        badgeSemuaContainer.classList.remove('flex');
                        badgeSemuaContainer.classList.add('hidden');
                    }

                    // Update Badge: Divisi Lainnya
                    ['IT', 'Finance', 'Staff', 'Pengajar'].forEach(div => {
                        let badge = document.getElementById('badge-' + div);
                        if(data[div] > 0) {
                            badge.classList.remove('hidden');
                            badge.classList.add('flex');
                            badge.innerText = data[div];
                        } else {
                            badge.classList.remove('flex');
                            badge.classList.add('hidden');
                        }
                    });
                })
                .catch(error => console.error('Gagal mengambil data notifikasi:', error));
        }
        setInterval(updateNotifBadges, 3000);


        // --- 3. Logika Modal Otorisasi Manager ---
        const modal = document.getElementById('authModal');
        const card = document.getElementById('authCard');
        const inputSandi = document.getElementById('passInput');
        const pesanError = document.getElementById('errorMsg');
        const inputUrl = document.getElementById('redirectUrlInput');

        function bukaModalAuth(url) {
            inputUrl.value = url; // Simpan tujuan link ke input tersembunyi
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
                inputSandi.focus(); 
            }, 10);
        }

        function tutupModalAuth() {
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                inputSandi.value = ''; 
                pesanError.classList.add('hidden'); 
                inputSandi.classList.remove('border-rose-500', 'ring-rose-200', 'ring-2');
            }, 300);
        }

        // Tampilkan modal otomatis & goyangkan jika password salah saat disubmit
        <?php if($login_error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Ambil kembali URL sebelumnya agar tidak hilang saat diredirect
                const lastUrl = '<?= isset($_POST['redirect_url']) ? htmlspecialchars($_POST['redirect_url']) : 'manager.php' ?>';
                bukaModalAuth(lastUrl); 
                
                pesanError.classList.remove('hidden');
                inputSandi.classList.add('border-rose-500', 'ring-rose-200', 'ring-2');
                
                card.classList.add('-translate-x-2');
                setTimeout(() => card.classList.replace('-translate-x-2', 'translate-x-2'), 50);
                setTimeout(() => card.classList.replace('translate-x-2', '-translate-x-2'), 100);
                setTimeout(() => card.classList.replace('-translate-x-2', 'translate-x-0'), 150);
            });
        <?php endif; ?>
    </script>

</body>
</html>