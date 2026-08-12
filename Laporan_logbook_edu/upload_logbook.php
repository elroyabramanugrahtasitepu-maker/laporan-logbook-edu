<?php
// Koneksi ke database
$conn = mysqli_connect('localhost', 'root', '', 'db_logbook');

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $deskripsi = $_POST['deskripsi'] ?? '';
    $divisi = $_POST['divisi'];
    
    // Karena multiple upload, $_FILES menjadi array
    $foto_array = $_FILES['foto_logbook'];
    $jumlah_foto = count($foto_array['name']);
    
    $batas_ukuran = 20971520; // 20 MB
    $target_dir = 'uploads/';
    
    // Cek maksimal 3 foto
    if ($jumlah_foto > 0 && $jumlah_foto <= 3 && $foto_array['name'][0] != '') {
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true); 
        }

        $nama_file_tersimpan = [];
        $upload_berhasil = true;

        // Looping untuk memproses setiap foto yang diupload
        for ($i = 0; $i < $jumlah_foto; $i++) {
            $nama_file = $foto_array['name'][$i];
            $ukuran_file = $foto_array['size'][$i];
            $tmp_file = $foto_array['tmp_name'][$i];

            if ($ukuran_file <= $batas_ukuran) {
                // Tambahkan index $i agar nama file tidak bentrok jika diupload bersamaan
                $nama_file_baru = time() . '_' . $i . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $nama_file);
                $direktori = $target_dir . $nama_file_baru;
                
                if (move_uploaded_file($tmp_file, $direktori)) {
                    $nama_file_tersimpan[] = $nama_file_baru; // Simpan nama file ke array
                } else {
                    $upload_berhasil = false;
                }
            } else {
                $upload_berhasil = false;
                $pesan = "<div class='bg-yellow-100 text-yellow-700 p-3 rounded-lg mb-4'>Ada foto yang ukurannya melebihi 20 MB!</div>";
                break;
            }
        }

        // Jika semua foto berhasil dipindah ke folder 'uploads/'
        if ($upload_berhasil && count($nama_file_tersimpan) > 0) {
            
            // Gabungkan nama file menjadi satu string dipisah koma (misal: foto1.jpg,foto2.jpg)
            $string_nama_foto = implode(',', $nama_file_tersimpan);

            // Simpan data ke tabel MySQL
            $query = "INSERT INTO tabel_logbook (divisi, nama_foto, deskripsi) VALUES ('$divisi', '$string_nama_foto', '$deskripsi')";
            mysqli_query($conn, $query);
            
            // Pesan sukses dimunculkan, TIDAK redirect ke manager.php
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'><i class='fas fa-check-circle mr-2'></i> Logbook beserta " . count($nama_file_tersimpan) . " foto berhasil dikirim!</div>";
        } elseif (!$upload_berhasil && empty($pesan)) {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal memproses file. Pastikan server memiliki izin.</div>";
        }

    } elseif ($jumlah_foto > 3) {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Maksimal hanya boleh mengunggah 3 foto sekaligus!</div>";
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Minimal 1 foto wajib diunggah!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Logbook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-100 w-full max-w-lg">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Form Laporan Harian</h2>
                <p class="text-slate-500 text-sm">Upload maksimal 3 bukti foto kegiatan.</p>
            </div>
         <a href="javascript:history.back()" class="text-slate-400 hover:text-indigo-600 transition" title="Kembali ke Dashboard">
    <i class="fas fa-times text-xl"></i>
</a>
        </div>

        <?= $pesan; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Divisi / Peran</label>
                <select name="divisi" class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500" required>
                    <option value="">Pilih Divisi...</option>
                    <option value="IT">IT</option>
                    <option value="Finance">Finance</option>
                    <option value="Staff">Staff Admin</option>
                    <option value="Pengajar_1">Pengajar 1</option>
                    <option value="Pengajar_2">Pengajar 2</option>
                    <option value="Pengajar_3">Pengajar 3</option>
                    <option value="Pengajar_4">Pengajar 4</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Foto Bukti Kegiatan <span class="text-red-500">*</span></label>
                <div class="flex items-center justify-center w-full">
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-images text-2xl text-indigo-400 mb-2"></i>
                            <p id="fileCountText" class="mb-2 text-sm text-slate-500"><span class="font-semibold">Klik untuk upload</span> foto</p>
                            <p class="text-xs text-slate-400">Pilih 1 hingga 3 foto (Maks. 20MB/foto)</p>
                        </div>
                        <input type="file" id="fileInput" name="foto_logbook[]" class="hidden" accept="image/*" multiple required />
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Kegiatan (Opsional)</label>
                <textarea name="deskripsi" rows="3" class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Jelaskan secara singkat (opsional)..."></textarea>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-medium py-2.5 rounded-lg hover:bg-indigo-700 transition duration-200">
                Kirim Laporan
            </button>
        </form>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            var count = e.target.files.length;
            var textDisplay = document.getElementById('fileCountText');
            
            if(count > 3) {
                alert('Maksimal hanya boleh memilih 3 foto!');
                this.value = ''; // Reset input
                textDisplay.innerHTML = '<span class="font-semibold">Klik untuk upload</span> foto';
            } else if(count > 0) {
                textDisplay.innerHTML = '<span class="font-semibold text-indigo-600">' + count + ' foto siap diupload</span>';
            } else {
                textDisplay.innerHTML = '<span class="font-semibold">Klik untuk upload</span> foto';
            }
        });
    </script>

</body>
</html>