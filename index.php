<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include('db.php');
include('Zebra_Pagination.php');

$db = new db();
$pagination = new Zebra_Pagination();

$title 	= array_key_exists('title', $_GET) ? urldecode($_GET['title']) : '';
$genre 	= array_key_exists('genre', $_GET) ? urldecode($_GET['genre']) : '';
$artist = array_key_exists('artist', $_GET) ? urldecode($_GET['artist']) : '';
$album 	= array_key_exists('album', $_GET) ? urldecode($_GET['album']) : '';
$sort 	= array_key_exists('sort', $_GET) ? urldecode($_GET['sort']) : 'title';
$sort_direction 	= array_key_exists('sort_direction', $_GET) ? urldecode($_GET['sort_direction']) : 'asc';
$page = array_key_exists('page', $_GET) ? $_GET['page'] : 1;

$sortable_headings=array('Title', 'Artist', 'Album', 'Genre');

$genres=$db->get_genres();
$song_data=$db->get_songs($genre, $title, $artist, $album, $page, $sort, $sort_direction);

$pagination->records($song_data['found_rows']);
$pagination->records_per_page($db::$rows_per_page);

?>
<!DOCTYPE html>
<html>
<head>
	<title>DuaneFM Catalog</title>
	<link rel="stylesheet" type="text/css" href="css/zebra_pagination.css">
	<link rel="stylesheet" type="text/css" href="css/dfm.css">
	<script src="js/jquery-2.1.4.js"></script>
	<script src="js/dfm.js"></script>
	<script src="js/zebra_pagination.src.js"></script>
</head>
<body>
	<div>
		<form id='search_form' name='search' method='get' action='index.php'>
			<label for='genre'>Genre:</label>
			<select id='genre' name='genre'>
				<?php
				foreach($genres as $k => $v)
					if ($k == $genre) {
						printf('<option value="%s" selected>%s</option>', $k, $v);
					} else {
						printf('<option value="%s">%s</option>', $k, $v);
					}
				?>
			</select>
			<label for='title'>Title:</label>
			<input type='text' id='title' name='title' value='<?= $title ?>'/>

			<label for='artist'>Artist:</label>
			<input type='text' id='artist' name='artist' value='<?= $artist ?>'/>

			<label for='album'>Album:</label>
			<input type='text' id='album' name='album' value='<?= $album ?>'/>
			<input type='hidden' id='sort' name='sort' value='<?= $sort ?>'/>
			<input type='hidden' id='sort_direction' name='sort_direction' value='<?= $sort_direction?>'/>

			<input type='submit' value='Search'/>
			<input id='clear' type='reset' value='Clear'/>
		</form>
	</div>
	<div style='padding-top:40px;font-weight:bold;'>
		Found <span id='song_count'><?= $song_data['found_rows'] ?></span> songs
	</div>

	<div style='width:80%'>
		<?php $pagination->render(); ?>
		<div style='width:100%'>
			<table id='songs' class=''>
				<thead>
					<th>ID</th>
					<?php
						foreach($sortable_headings as $heading) {
							print "<th class='sortable'>";
							print $heading;
							$asc_sorted = '';
							$desc_sorted = '';
							if ($sort == strtolower($heading)) {
								if ($sort_direction == 'desc') {
									$desc_sorted='active';
								} else {
									$asc_sorted='active';
								}
							} 
							print "<span><img class='$asc_sorted' src='images/arrow-down.png' alt='sort asc'/></span>";
							print "<span><img class='$desc_sorted' src='images/arrow-up.png' alt='sort desc'/></span>";
							print "</th>";

						}
					?>
				</thead>
				<?php
				foreach($song_data['songs'] as $song) {
					printf("<tr>\n");
					printf("<td>%d</td>", $song['id']);
					printf("<td>%s</td>", $song['title']);
					printf("<td>%s</td>", $song['artist']);
					printf("<td>%s</td>", $song['album']);
					printf("<td>%s</td>\n", $song['genre']);
					printf("</tr>\n");
				}
				?>
			</table>
		</div>
		<?php $pagination->render(); ?>
	</div>

</body>
</html>
