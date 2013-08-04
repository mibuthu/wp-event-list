// Javascript functions for event-list admin_settings page

// Confirmation for event deletion
function eventlist_deleteCategory(id) {
	if(confirm("Are you sure you want to delete this event category? This is a permanent action.")) {
		document.location.href = "?page=el_admin_categories&slug=" + id + "&action=delete";
	}
}

jQuery(document).ready(function($) {
	// Confirmation for manual syncing with post categories
	$("#manualcatsync").submit(function() {
		if(!confirm("Are you sure you want to manually sync the event categories with the post categories?\n\nWarning: Please not that this will delete all categories which are not available in post categories!")) {
			return false;
		}
		return true;
	});
	// Confirmation for automatic syncing with post categories
	$("#catsync").submit(function() {
		if($("#el_sync_cats").prop('checked')) {
			if(!confirm("Are you sure you want to automatically sync the event categories with the post categories?\n\nWarning: Please not that this will delete all categories which are not available in post categories!")) {
				return false;
			}
		}
		return true;
	});
});
