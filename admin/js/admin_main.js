// Javascript functions for event-list admin_main page

// Confirmation for event deletion
function eventlist_deleteEvent (id) {
	if (confirm("Are you sure you want to delete this event from you the database? This is a permanent action.")) {
		document.location.href = "?page=el_admin_main&id=" + id + "&action=delete&noheader=true";
	}
}
