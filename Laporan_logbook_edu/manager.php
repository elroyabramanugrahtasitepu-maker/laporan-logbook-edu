<?php
// 1. Tangkap parameter filter dari URL jika ada
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Judul Halaman berdasarkan filter
if ($filter == 'IT') {
    $judul_halaman = "Rekapitulasi Logbook: Divisi IT";
} elseif ($filter == 'Finance') {
    $judul_halaman = "Rekapitulasi Logbook: Finance";
} elseif ($filter == 'Staff') {
    $judul_halaman = "Rekapitulasi Logbook: Staff Admin";
} elseif ($filter == 'Pengajar') {
    $judul_halaman = "Rekapitulasi Logbook: Tim Pengajar";
} else {
    $judul_halaman = "Daftar Semua Logbook";
}

// Cek notifikasi sukses upload (dari file upload_logbook.php)
$notif = '';
if (isset($_GET['status']) && $_GET['status'] == 'sukses') {
    $notif = '<div id="notifAlert" class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm transition-opacity duration-500">
                <i class="fas fa-check-circle mr-3 text-lg"></i>
                <span class="font-medium">Laporan logbook baru berhasil ditambahkan!</span>
              </div>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="text-slate-400 hover:text-indigo-600 transition p-2 rounded-lg hover:bg-slate-100 mr-2" title="Kembali">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <div class="bg-indigo-600 text-white p-2 rounded-lg relative">
                        <i class="fas fa-chart-pie text-xl"></i>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    </div>
                    <span class="font-bold text-xl text-slate-800 tracking-tight">Manager Space</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?= $notif ?>

        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $judul_halaman ?></h1>
                <p class="text-slate-500 mt-2 text-sm flex items-center">
                    Rekapitulasi riwayat aktivitas. <span class="ml-2 text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded flex items-center"><i class="fas fa-sync fa-spin mr-1 text-[10px]"></i> Auto-sync on</span>
                </p>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-white border border-slate-200 px-4 py-3 rounded-xl shadow-sm min-w-[120px] text-center">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">Total Data</p>
                    <p id="totalData" class="text-2xl font-bold text-indigo-600">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Waktu Upload</th>
                            <th class="px-6 py-4 font-semibold">Divisi / Peran</th>
                            <th class="px-6 py-4 font-semibold w-1/3">Deskripsi Kegiatan</th>
                            <th class="px-6 py-4 font-semibold">Bukti Lampiran</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="text-sm divide-y divide-slate-100">
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Memuat data terbaru...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Ambil parameter filter dari URL saat ini
        const urlParams = new URLSearchParams(window.location.search);
        const currentFilter = urlParams.get('filter') || '';

        // Fungsi untuk mengambil data dari API
        function fetchLogbookData() {
            fetch(`api_data.php?filter=${currentFilter}`)
                .then(response => response.json())
                .then(data => {
                    // Update jumlah total data
                    document.getElementById('totalData').innerText = data.count;
                    // Update isi tabel
                    document.getElementById('tableBody').innerHTML = data.html;
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Panggil fungsi pertama kali saat halaman dimuat
        fetchLogbookData();

        // Jalankan fetch otomatis setiap 3000 milidetik (3 detik)
        setInterval(fetchLogbookData, 3000);

        // Hilangkan notifikasi hijau secara otomatis setelah 4 detik
        const notifAlert = document.getElementById('notifAlert');
        if(notifAlert) {
            setTimeout(() => {
                notifAlert.classList.add('opacity-0');
                setTimeout(() => notifAlert.style.display = 'none', 500);
            }, 4000);
            
            // Bersihkan URL dari ?status=sukses agar notif tidak muncul lagi jika di-refresh manual
            window.history.replaceState(null, null, window.location.pathname + (currentFilter ? '?filter='+currentFilter : ''));
        }
    </script>
</body>
</html>