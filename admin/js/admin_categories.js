// Javascript functions for event-list admin_settings page

// Confirmation for event deletion
function eventlist_deleteCategory (id) {
	if (confirm("Are you sure you want to delete this event category? This is a permanent action.")) {
		document.location.href = "?page=el_admin_settings&slug=" + id + "&action=delete";
	}
}