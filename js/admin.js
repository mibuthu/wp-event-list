// Date helpers
jQuery(document).ready(function( $ ) {	
//	$(".datepicker").datepick({
//		dateFormat: 'yyyy-mm-dd',
//		onSelect: function(dates) { 
//			if ($("#multiday").is('checked')) {
				// check the end day is greater
//				if ($("#start_date").val() > $("#end_date").val()) {
//					$("#end_date").val($("#start_date").val());
//				}
//			}
//			else {
				// single day! make em match
//				$("#end_date").val($("#start_date").val());
//			}
//		}
//	});
	
	if ($("#start_date").val() == $("#end_date").val()) {
		$("#end_date_row").hide();
	}
	else {
		$("#multiday").attr('checked', true);
	}
	
	$("#multiday").click(function() {
		if (this.checked) {
			$("#end_date").val($("#start_date").val());
			$("#end_date_row").fadeIn();
		}
		else {
			$("#end_date_row").fadeOut();
			$("#end_date").val($("#start_date").val());
		}
	});
	

});

// Confirmation for event deletion
function eventlist_deleteEvent (id) {
	if (confirm("Are you sure you want to delete this event from you the database? This is a permanent action.")) {
		document.location.href = "?page=el_admin_main&id=" + id + "&action=delete";
	}
}