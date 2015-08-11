<?php
error_reporting(E_ERROR);
ini_set('display_errors', '1');
include('db.php');
$db = new db();

$title = $_GET['title'] ?: '';
$genre = $_GET['genre'] ?: '';
$artist = $_GET['artist'] ?: '';
$album = $_GET['album'] ?: '';
$page = $_GET['page'] ?: 1;

$song_data=$db->get_songs($genre, $title, $artist, $album, $page);

$data = array(
	'total_songs' => $song_data['found_rows'],
	'rows_per_page' => $db::$rows_per_page,
	'total_pages' => ceil($song_data['found_rows'] / $db::$rows_per_page),
	'songs' => $song_data['songs']
);

header('Content-type:application/json');

print json_encode($data, JSON_PRETTY_PRINT);

?>