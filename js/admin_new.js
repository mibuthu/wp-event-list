// Javascript functions for event-list admin_new page

// Date helpers
jQuery(document).ready(function( $ ) {	
	
	// Show or hide end_date
	if ($("#start_date").val() == $("#end_date").val()) {
		$("#end_date_row").hide();
	}
	else {
		$("#multiday").attr('checked', true);
	}
	
	// Toogle end_date view
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
	
	// Datepicker
	$("#start_date").datepicker( {
		dateFormat: "yy-mm-dd",
		firstDay: 1,
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 3,
		constrainInput: true,
		onClose: function(selectedDate) {
			$("#end_date").datepicker("option", "minDate", selectedDate);
		}
	});
	$("#end_date").datepicker( {
		dateFormat: "yy-mm-dd",
		firstDay: 1,
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 3,
		constrainInput: true,
		minDate: $("#start_date").val(),
		onClose: function(selectedDate) {
			$("#start_date").datepicker("option", "maxDate", selectedDate);
		}
	});
});