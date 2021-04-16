function el_toggle_content(event_id) {
	var content_div = document.getElementById('event-content-'.concat(event_id));
	var content_button = document.getElementById('event-content-a'.concat(event_id));
	if (content_div.style.display == 'block') {
		content_div.style.display = 'none';
		content_button.innerHTML = el_content_show_text;
	}
	else {
		content_div.style.display = 'block';
		content_button.innerHTML = el_content_hide_text;
	}
	return false;
}
