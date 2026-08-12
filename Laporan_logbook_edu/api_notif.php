<?php
// api_notif.php - Endpoint untuk menghitung notifikasi badge
header('Content-Type: application/json');

$conn = mysqli_connect('');
$notif_counts = ['IT' => 0, 'Finance' => 0, 'Staff' => 0, 'Pengajar' => 0, 'Semua' => 0];

if ($conn) {
    // Ambil data laporan khusus hari ini
    $query = "SELECT divisi, COUNT(*) as jumlah FROM tabel_logbook WHERE DATE(waktu_upload) = CURDATE() GROUP BY divisi";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
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

// Kirim hasil hitungan dalam format JSON
echo json_encode($notif_counts);
?>