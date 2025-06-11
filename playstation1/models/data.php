<?php
class PS {
    public $id_chanel, $nama_chanel, $jenis_ps, $is_booked, $waktu_selesai;
}

class Game {
    public $id_game, $nama_game, $genre, $cover_image;
}

class PSTakeaway {
    public $id_ps, $nama_ps, $is_booked, $waktu_selesai;
}

class TVTakeaway {
    public $id_tv, $nama_tv, $is_booked, $waktu_selesai;
}

function getPSAvailability($conn, $jenis_ps) {
    $query = "SELECT p.id_chanel, p.nama_chanel, p.jenis_ps, 
                     CASE WHEN s.status = 'CONFIRMED' AND s.waktu_selesai > NOW() THEN 1 ELSE 0 END AS is_booked,
                     s.waktu_selesai
              FROM tb_playstation p
              LEFT JOIN tb_sewa s ON p.id_chanel = s.id_chanel 
              WHERE p.jenis_ps = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $jenis_ps);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $channels = [];
    while ($row = mysqli_fetch_object($result, 'PS')) {
        $channels[] = $row;
    }
    return $channels;
}

function getGames($conn) {
    $query = "SELECT id_game, nama_game, genre, cover_image FROM tb_game";
    $result = mysqli_query($conn, $query);
    $games = [];
    while ($row = mysqli_fetch_object($result, 'Game')) {
        $games[] = $row;
    }
    return $games;
}

function getPSTakeawayAvailability($conn) {
    $query = "SELECT id_ps, nama_ps, is_booked, waktu_selesai 
              FROM tb_ps_takeaway 
              WHERE is_booked = 0 OR waktu_selesai <= NOW()";
    $result = mysqli_query($conn, $query);
    $ps_takeaway = [];
    while ($row = mysqli_fetch_object($result, 'PSTakeaway')) {
        $ps_takeaway[] = $row;
    }
    return $ps_takeaway;
}

function getTVTakeawayAvailability($conn) {
    $query = "SELECT id_tv, nama_tv, is_booked, waktu_selesai 
              FROM tb_tv_takeaway 
              WHERE is_booked = 0 OR waktu_selesai <= NOW()";
    $result = mysqli_query($conn, $query);
    $tv_takeaway = [];
    while ($row = mysqli_fetch_object($result, 'TVTakeaway')) {
        $tv_takeaway[] = $row;
    }
    return $tv_takeaway;
}
?>