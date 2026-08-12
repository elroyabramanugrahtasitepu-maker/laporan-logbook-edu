<?php
// api_data.php - Endpoint untuk ditarik oleh AJAX
header('Content-Type: application/json');

$conn = mysqli_connect('');

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

if ($filter == 'IT') {
    $query = "SELECT * FROM tabel_logbook WHERE divisi = 'IT' ORDER BY waktu_upload DESC";
} elseif ($filter == 'Finance') {
    $query = "SELECT * FROM tabel_logbook WHERE divisi = 'Finance' ORDER BY waktu_upload DESC";
} elseif ($filter == 'Staff') {
    $query = "SELECT * FROM tabel_logbook WHERE divisi = 'Staff' ORDER BY waktu_upload DESC";
} elseif ($filter == 'Pengajar') {
    $query = "SELECT * FROM tabel_logbook WHERE divisi LIKE 'Pengajar%' ORDER BY waktu_upload DESC";
} else {
    $query = "SELECT * FROM tabel_logbook ORDER BY waktu_upload DESC";
}

$result = mysqli_query($conn, $query);
$count = mysqli_num_rows($result);
$html = '';

if($result && $count > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $tanggal = date('d M Y', strtotime($row['waktu_upload']));
        $jam = date('H:i', strtotime($row['waktu_upload']));
        
        $div = htmlspecialchars($row['divisi']);
        $badge = "bg-slate-100 text-slate-700 border-slate-200"; 
        if($div == 'IT') $badge = "bg-blue-50 text-blue-700 border-blue-200";
        elseif($div == 'Finance') $badge = "bg-emerald-50 text-emerald-700 border-emerald-200";
        elseif($div == 'Staff') $badge = "bg-purple-50 text-purple-700 border-purple-200";
        elseif(strpos($div, 'Pengajar') !== false) $badge = "bg-orange-50 text-orange-700 border-orange-200";

        $deskripsi = !empty($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : '<span class="italic text-slate-400">Tidak ada deskripsi tambahan.</span>';

        // Susun HTML Foto
        $daftar_foto = explode(',', $row['nama_foto']);
        $html_foto = '';
        $hitung = 1;
        foreach($daftar_foto as $foto) {
            $foto = trim($foto);
            if(!empty($foto)) {
                $html_foto .= '<a href="uploads/'.htmlspecialchars($foto).'" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300 transition-colors shadow-sm mb-1 mr-1"><i class="fas fa-image mr-1.5 text-slate-400"></i> Foto '.$hitung.'</a>';
                $hitung++;
            }
        }

        // Susun HTML Baris Tabel (Tailwind)
        $html .= '
        <tr class="hover:bg-slate-50 transition-colors duration-200">
            <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                <div class="font-medium text-slate-800">'.$tanggal.'</div>
                <div class="text-xs text-slate-400 mt-0.5"><i class="far fa-clock mr-1"></i>'.$jam.' WIB</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border '.$badge.'">'.$div.'</span>
            </td>
            <td class="px-6 py-4 text-slate-600 leading-relaxed">'.$deskripsi.'</td>
            <td class="px-6 py-4"><div class="flex flex-wrap gap-2">'.$html_foto.'</div></td>
        </tr>';
    }
} else {
    $html = '
    <tr>
        <td colspan="4" class="px-6 py-16 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                <i class="fas fa-folder-open text-2xl text-slate-400"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada data logbook untuk kategori ini.</p>
        </td>
    </tr>';
}

// Kembalikan data dalam format JSON
echo json_encode([
    'count' => $count,
    'html' => $html
]);
?>