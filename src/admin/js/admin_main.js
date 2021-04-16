// Javascript functions for event-list admin_main page

jQuery(document).ready(function($) {
	// Add import button to page title actions
	$("a.page-title-action").first().after('<a href="edit.php?post_type=el_events&action=import" class="add-new-h2">Import</a>');
});
