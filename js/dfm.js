function refresh_table() {
	$('#songs > tbody > tr').remove();
	url = 'data.php'
	url += '?title=' + encodeURIComponent($('#title').val());
	url += '&artist=' + encodeURIComponent($('#artist').val());
	url += '&album=' + encodeURIComponent($('#album').val());
	url += '&genre=' + encodeURIComponent($('#genre option:selected').val());

	$.getJSON(url, function(result) {

		$('#song_count').text(result.total_songs);

		$.each(result.songs, function(i, item) {
			var $tr = $('<tr>').append(
				$('<td>').text(item.id),
				$('<td>').text(item.title),
				$('<td>').text(item.artist),
				$('<td>').text(item.album),
				$('<td>').text(item.genre)
			);
			$('#songs').append($tr);
		});
	});

}

function submit_form() {
	$('#search_form').submit();
}

function set_sort_direction(field) {
	// only change the sort direction if the
	// target field is already the active sort
	if($('#sort').val() != field)
		$('#sort_direction').val('asc');
	else if($('#sort_direction').val() == 'asc') {
		$('#sort_direction').val('desc');
	} else {
		$('#sort_direction').val('asc');
	}
}

$(document).ready(function(){

	$('#clear').click(function() {
		window.location.replace('/');
	});

	$('.sortable').each(function(index) {
		$(this).click(function(){
			set_sort_direction($(this).text().toLowerCase());
			$('#sort').val($(this).text().toLowerCase());
			submit_form();
		});
	});

	$('#genre').change(function() {
		submit_form();
	});

	$('#title').change(function() { 
		submit_form();
	});

	$('#artist').change(function() { 
		submit_form();
	});

	$('#album').change(function() { 
		submit_form();
	});

	switch($('#sort').val()) {
		case 'title':
			th = 1;
			break;
		case 'artist':
			th = 2;
			break;
		case 'album':
			th = 3;
			break;
	}

	

});