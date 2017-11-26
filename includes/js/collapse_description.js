function toggle_event_description(event_id) {
	var desc_div = document.getElementById('event-description-'.concat(event_id));
	var desc_button = document.getElementById('event-description-a'.concat(event_id));
	if (desc_div.style.display == 'block') {
		desc_div.style.display = 'none';
		desc_button.innerHTML = el_show_details_text;
	}
	else {
		details_div.style.display = 'block';
		details_button.innerHTML = el_hide_details_text;
	}
	return false;
}
