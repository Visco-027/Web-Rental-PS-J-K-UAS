<?php
require_once 'helper/connection.php';
$update_query = "UPDATE tb_booking SET status = 'completed' WHERE status = 'confirmed' AND waktu_mulai IS NOT NULL AND NOW() > DATE_ADD(waktu_mulai, INTERVAL durasi_sewa HOUR)";
mysqli_query($conn, $update_query);
?>