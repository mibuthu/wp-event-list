// Javascript functions for event-list admin_new page

// Date helpers
jQuery(document).ready(function( $ ) {	
	
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
	
//	$(".datepicker").datepick({
//	dateFormat: 'yyyy-mm-dd',
//	onSelect: function(dates) { 
//		if ($("#multiday").is('checked')) {
			// check the end day is greater
//			if ($("#start_date").val() > $("#end_date").val()) {
//				$("#end_date").val($("#start_date").val());
//			}
//		}
//		else {
			// single day! make em match
//			$("#end_date").val($("#start_date").val());
//		}
//	}
//});
});