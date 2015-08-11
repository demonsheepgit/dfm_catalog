<?php

class db
{

	private $mysqli = null;
	public static $rows_per_page = 100;

	function __construct() {
		$config = parse_ini_file("config.ini", true);
		$db_config = $config['database'];
		$this->mysqli = new mysqli(
			$db_config['db_host'], 
			$db_config['db_username'],
			$db_config['db_password'],
			$db_config['db_name']
		);
		/* check connection */
		if ($this->mysqli->connect_errno) {
			printf("Connect failed: %s\n", $this->mysqli->connect_error);
			exit();
		}
   	}

	function get_categories() {
		$result = $this->mysqli->query( 
			'SELECT id, name
			 FROM CATEGORY'
			 );

		$categories=[];

		while($row = $result->fetch_assoc()) {
			$categories[$row['id']] = $row['name'];
		}

		$result->close();

		asort($categories, SORT_NATURAL);
		$categories = array('-1' => '== All ==') + $categories;

		return $categories;
	}

	function get_genres() {
		$result = $this->mysqli->query(
			'SELECT
			 distinct(genre) as genre
			 FROM SONGLIST
			 WHERE genre NOT like ""
			 ORDER BY genre'
		);

		$genres[''] = '== All == ';
		while($row = $result->fetch_assoc()) {
			$genres[$row['genre']] = $row['genre'];
		}

		$result->close();

		return $genres;

	}

	function get_songs($genre, $title, $artist, $album, $page=1, $sort_field='title', $sort_direction='DESC') {

		$rpp = self::$rows_per_page;
		$offset = ($page - 1) * $rpp;

		if (!(strtoupper($sort_direction) == 'ASC' || strtoupper($sort_direction) == 'DESC')) {
			$sort_direction = 'ASC';
		}

		// See http://skybluesofa.com/blog/how-implement-natural-sorting-mysql/
		switch ($sort_field) {
			case 'genre':
			case 'artist':
			case 'album':
				$order="$sort_field+0<>0 $sort_direction,
					$sort_field+0 $sort_direction,
					$sort_field $sort_direction,
					order_title+0<>0 $sort_direction,
					order_title+0 $sort_direction,
					order_title $sort_direction
					";
				break;
			case 'id':
				$order="$sort_field $sort_direction";
				break;
			case 'title':
			default:
				# use alternate title field for ordering
				$sf='order_title';
				//$order="$sf REGEXP '^\d*[^\da-z&\.\' \-\"\!\@\#\$\%\^\*\(\)\;\:\<\>\,\?\/\~\`\|\_\-]' $sort_direction, $sf+0, $sf";
				$order="$sf+0<>0 $sort_direction, $sf+0 $sort_direction, $sf $sort_direction";
				break;
		}

		$query = "SELECT
			SQL_CALC_FOUND_ROWS
		 	id, artist, album, title, genre
		FROM SONGLIST
		WHERE
			genre LIKE ?
			AND title LIKE ?
			AND artist LIKE ?
			AND album LIKE ?
		ORDER BY $order
		LIMIT $offset, $rpp
		";

		$stmt = $this->mysqli->prepare($query);

		$genre = $genre ?: '%';
		$title = "%$title%" ?: '%';
		$artist = "%$artist%" ?: '%';
		$album = "%$album%" ?: '%';

		$stmt->bind_param('ssss',
			$genre,
			$title,
			$artist,
			$album
		);

		$stmt->execute();

		$row = array();
		$stmt->bind_result($id, $artist, $album, $title, $genre);

		$songs = array();

		while($stmt->fetch()) {
			array_push(
				$songs,
				array(
					'id' => $id,
					'genre' => $genre ?: '-',
					'title' => $title ?: '-',
					'artist' => $artist ?: '-',
					'album' => $album ?: '-'
				)
			);
		}
		
		//usort($songs, array("db", "cmp"));

		$stmt = $this->mysqli->prepare('SELECT FOUND_ROWS()');
		$stmt->execute();
		$stmt->bind_result($found_rows);
		$stmt->fetch();

		$songs = array(
			'found_rows' => $found_rows,
			'songs' => $songs
		);

		$stmt->close();

		return $songs;

	}

	function get_song_detail($id) {
		$query = "SELECT
		 	artist, album, title, genre, albumyear
		FROM SONGLIST
		WHERE
			id = ?
		";

		$stmt->bind_param('i', $id);

		$stmt->execute();

		$row = array();
		$stmt->bind_result($id, $artist, $album, $title, $genre);

	}
}
?>
