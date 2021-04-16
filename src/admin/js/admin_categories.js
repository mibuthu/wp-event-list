// Javascript functions for event-list admin_categories page

jQuery(document).ready(function($) {
	// Move sync button next to table action button
	$("#sync-cats").first().insertAfter($("div.bulkactions").first());
});

function el_show_syncform(syncform_url) {
	// Redirect to execute action
	window.location.assign(syncform_url);
	return false;
}
