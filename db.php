<?php

class db
{


	private $mysqli = null;
	private $genre_cache = array();
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

		$this->genre_cache = $this->get_genres();
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

	function genre_name($genre_id) {
		return $this->genre_cache[$genre_id];
	}

	function genre_names_sorted($genre_id_list) {
		$genre_list = array();
		foreach($genre_id_list as $genre_id)
			$genre_list[$genre_id] = $this->genre_name($genre_id);

		natcasesort($genre_list);

		return $genre_list;
	}


	function get_genres() {

		if (count($this->genre_cache) > 0)
			return $this->genre_cache;

		$result = $this->mysqli->query(
			'SELECT
			 genre_id, genre_name
			 FROM genres
			 ORDER BY genre_name'
		);

		$genres['0'] = '[ All ]';
	    $genres['-1'] = '[ None Specified ]';
		while($row = $result->fetch_assoc()) {
			$genres[$row['genre_id']] = $row['genre_name'];
		}

		$result->close();

		return $genres;

	}

	private static function get_sort_logic($sort_field, $sort_direction) {
		if (!(strtoupper($sort_direction) == 'ASC' || strtoupper($sort_direction) == 'DESC')) {
			$sort_direction = 'ASC';
		}

		// See http://skybluesofa.com/blog/how-implement-natural-sorting-mysql/
		switch ($sort_field) {
			case 'title':
				$sort_field = 'order_title';
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
				$order="$sf+0<>0 $sort_direction, $sf+0 $sort_direction, $sf $sort_direction";
				break;
		}

		return $order;
	}

	private static function set_param_binding(&$stmt, $genre_id, $title, $artist, $album) {
		$title = "%$title%" ?: '%';
		$artist = "%$artist%" ?: '%';
		$album = "%$album%" ?: '%';

		if ($genre_id > 0) {
			$stmt->bind_param('isss', $genre_id, $title, $artist, $album);
		} else {
			$stmt->bind_param('sss', $title, $artist, $album);
		}

		return;
	}

	private static function get_song_query($genre_id, $sort_field, $sort_direction, $offset, $rpp) {

		$order = self::get_sort_logic($sort_field, $sort_direction);

		switch ($genre_id) {
			case -1: # none
				$query = 
				"SELECT SQL_CALC_FOUND_ROWS id, artist, album, title, genre as dfm_genre
    			FROM SONGLIST LEFT OUTER JOIN genre ON SONGLIST.id = genre.song_id
    			WHERE genre.song_id is NULL
					AND title LIKE ?
					AND artist LIKE ?
					AND album LIKE ?
				LIMIT $offset, $rpp";
			break;

			case 0: # all
				$query = 

				"SELECT SQL_CALC_FOUND_ROWS id, artist, album, title, genre as dfm_genre
				FROM SONGLIST
				WHERE
					title LIKE ?
					AND artist LIKE ?
					AND album LIKE ?
				ORDER BY $order
				LIMIT $offset, $rpp";
			break;

			default: # any other
				$query =
			 	"SELECT SQL_CALC_FOUND_ROWS id, artist, album, title, genre as dfm_genre
				FROM SONGLIST
				INNER JOIN genre ON (SONGLIST.id = genre.song_id)
				WHERE
					genre_id = ?
					AND title LIKE ?
					AND artist LIKE ?
					AND album LIKE ?
				ORDER BY $order
				LIMIT $offset, $rpp";
			break;
		}

		return $query;

		
	}

	private function set_genres(&$songs) {
		$song_id_list = implode(',', array_keys($songs));

		$query = 
		"SELECT genre_id, song_id 
		FROM genre
		WHERE song_id IN ($song_id_list)
		";


		$stmt = $this->mysqli->prepare($query);
		$stmt->execute();

		$results = $stmt->get_result();
	
		while($genre = $results->fetch_assoc()) {
			array_push($songs[$genre['song_id']]['genre_ids'], $genre['genre_id']);
		}

		return;

	}

	function get_songs($genre_id, $title, $artist, $album, $page=1, $sort_field='title', $sort_direction='DESC') {

		$rpp = self::$rows_per_page;
		$offset = ($page - 1) * $rpp;

		$query = self::get_song_query($genre_id, $sort_field, $sort_direction, $offset, $rpp);

		$stmt = $this->mysqli->prepare($query);
		self::set_param_binding($stmt, $genre_id, $title, $artist, $album);

		$stmt->execute();

		$songs = array();

		$results = $stmt->get_result();
		while($track = $results->fetch_assoc()) {
			$songs[$track['id']] = array(
				'id' => $track['id'],
				'title' => $track['title'] ?: '-',
				'artist' => $track['artist'] ?: '-',
				'album' => $track['album'] ?: '-',
				'dfm_genre' => $track['dfm_genre'],
				'genre_ids' => array()
			);
		}
		
		$stmt = $this->mysqli->prepare('SELECT FOUND_ROWS()');
		$stmt->execute();
		$stmt->bind_result($found_rows);
		$stmt->fetch();

		$stmt->close();

		self::set_genres($songs);

		$songs = array(
			'found_rows' => $found_rows,
			'songs' => $songs
		);
		return $songs;

	}

	function get_song_count($genre_id, $title, $artist, $album) {
		$genre_filter = self::get_genre_filter($genre_id);

		$query_count = "SELECT
			count(distinct(SONGLIST.id)) as count
		FROM SONGLIST
		LEFT JOIN genre ON (SONGLIST.id = genre.song_id)
		WHERE
			$genre_filter
			title LIKE ?
			AND artist LIKE ?
			AND album LIKE ?
		";

		print_r($query_count);

		$stmt = $this->mysqli->prepare($query_count);

		self::set_param_binding($stmt, $genre_id, $title, $artist, $album);

		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();

		return $count;

	}

}
?>
