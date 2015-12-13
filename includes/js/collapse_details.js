function toggle_event_details(event_id) {
	var details_div = document.getElementById('event-details-'.concat(event_id));
	var details_button = document.getElementById('event-detail-a'.concat(event_id));
	if (details_div.style.display == 'block') {
		details_div.style.display = 'none';
		details_button.innerHTML = el_show_details_text;
	}
	else {
		details_div.style.display = 'block';
		details_button.innerHTML = el_hide_details_text;
	}
	return false;
}
