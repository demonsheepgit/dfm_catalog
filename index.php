<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include('db.php');
include('Zebra_Pagination.php');

$db = new db();
$pagination = new Zebra_Pagination();

$request['title'] 	= array_key_exists('title', $_GET) ? urldecode($_GET['title']) : '';
$request['genre'] 	= array_key_exists('genre', $_GET) ? urldecode($_GET['genre']) : '0';
$request['artist']  = array_key_exists('artist', $_GET) ? urldecode($_GET['artist']) : '';
$request['album'] 	= array_key_exists('album', $_GET) ? urldecode($_GET['album']) : '';
$request['sort'] 	= array_key_exists('sort', $_GET) ? urldecode($_GET['sort']) : 'title';
$request['sort_direction'] 	= array_key_exists('sort_direction', $_GET) ? urldecode($_GET['sort_direction']) : 'asc';
$request['page']	= array_key_exists('page', $_GET) ? $_GET['page'] : 1;

$sortable_headings=array('Title', 'Artist', 'Album');

$genres=$db->get_genres();
$song_data=$db->get_songs(
	$request['genre'], 
	$request['title'], 
	$request['artist'], 
	$request['album'], 
	$request['page'], 
	$request['sort'],
	$request['sort_direction']);

$pagination->records($song_data['found_rows']);
$pagination->records_per_page($db::$rows_per_page);


function format_genre_list($db, $genre_ids, $target_genre = 0, $dfm_genre) {

	$genre_items_html = '';
	if (count($genre_ids) == 0) {
		//if ($dfm_genre != '') {
		//	return "<div class='alternate_genre'>$dfm_genre</div>";
		//} else {
			return "<div class='genre_list'>-</div>\n";
		//}
	}

	$genre_list = $db->genre_names_sorted($genre_ids);
	foreach($genre_list as $genre_id => $genre_name) {
		if ($genre_id == $target_genre) {
			$genre_items_html .= "<div class='target_genre'>$genre_name</div>\n";
		} else {
			$url = $_SERVER['PHP_SELF'] . '?genre=' . $genre_id;
			$genre_items_html .= "<div><a href='$url'>$genre_name</a></div>\n";
		}

	}

	$genre_html = "<div class='genre_list'>\n";
	$genre_html .= $genre_items_html;
	$genre_html .= "</div>\n";

	return $genre_html;
}

// exit;
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
	<h2>DuaneFM Catalog</h2>
	</div>
	<div>
		<form id='search_form' name='search' method='get' action='index.php'>
			<label for='genre'>Genre:</label>
			<select id='genre' name='genre'>
				<?php
				foreach($genres as $k => $v)
					if ($k == $request['genre']) {
						printf('<option value="%s" selected>%s</option>', $k, $v);
					} else {
						printf('<option value="%s">%s</option>', $k, $v);
					}
				?>
			</select>
			<label for='title'>Title:</label>
			<input type='text' id='title' name='title' value='<?= $request['title'] ?>'/>

			<label for='artist'>Artist:</label>
			<input type='text' id='artist' name='artist' value='<?= $request['artist'] ?>'/>

			<label for='album'>Album:</label>
			<input type='text' id='album' name='album' value='<?= $request['album'] ?>'/>
			<input type='hidden' id='sort' name='sort' value='<?= $request['sort'] ?>'/>
			<input type='hidden' id='sort_direction' name='sort_direction' value='<?= $request['sort_direction'] ?>'/>

			<input type='submit' value='Search'/>
			<input id='clear' type='reset' value='Clear'/>
		</form>
	</div>
	<div style='padding-top:40px;font-weight:bold;'>
		Found <span id='song_count'><?= number_format($song_data['found_rows']) ?></span> tracks
	</div>

	<div style='width:90%'>
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
							if ($request['sort'] == strtolower($heading)) {
								if ($request['sort_direction'] == 'desc') {
									$desc_sorted='active';
								} else {
									$asc_sorted='active';
								}
							} 
							print "<span><img class='$asc_sorted' src='images/arrow-down.png' alt='sort asc'/></span>";
							print "<span><img class='$desc_sorted' src='images/arrow-up.png' alt='sort desc'/></span>";
							print "</th>";
						}
						print "<th>Genre</th>";
					?>
				</thead>
				<?php
				foreach($song_data['songs'] as $song_id => $song) {
					printf("<tr>\n");
					printf("<td>%d</td>", $song['id']);
					printf("<td>%s</td>", $song['title']);
					printf("<td><a href='%s'>%s</a></td>", $_SERVER['PHP_SELF'] . '?artist=' . $song['artist'], $song['artist']);
					printf("<td><a href='%s'>%s</a></td>", $_SERVER['PHP_SELF'] . '?album=' . $song['album'], $song['album']);
					printf("<td class='genre_list'>%s</td>\n", format_genre_list($db, $song['genre_ids'], $request['genre'], $song['dfm_genre']));
					printf("</tr>\n");
				}
				?>
			</table>
		</div>
		<?php $pagination->render(); ?>
	</div>

</body>
</html>
