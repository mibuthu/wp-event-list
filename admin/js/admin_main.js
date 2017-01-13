// Javascript functions for event-list admin_main page

// Confirmation for event deletion
function eventlist_deleteEvent (del_ids, referer_url) {
	if (del_ids == "") {
		window.alert("No event selected for deletion! Deletion aborted!");
	}
	else if (window.confirm("Are you sure you want to delete this event?")) {
		document.location.href = referer_url + "&id=" + del_ids + "&action=delete&noheader=true";
		return;
	}
	document.location.href = referer_url;
}
