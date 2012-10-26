// Javascript functions for event-list admin_new page

// Date helpers
jQuery(document).ready(function( $ ) {	
	
	// Show or hide end_date
	if ($("#start_date").val() == $("#end_date").val()) {
		$("#end_date_area").hide();
	}
	else {
		$("#multiday").attr('checked', true);
	}
	
	// Datepickers
	$("#start_date").datepicker( {
		dateFormat: "yy-mm-dd",
		firstDay: 1,
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 3,
		constrainInput: true,
		onClose: function(selectedDate) {
			minDate = new Date(selectedDate);
			timestamp = minDate.getTime() + 1*24*60*60*1000;
			minDate.setTime(timestamp);
			$("#end_date").datepicker("option", "minDate", minDate);
		}
	});
	$("#end_date").datepicker( {
		dateFormat: "yy-mm-dd",
		firstDay: 1,
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 3,
		constrainInput: true
	});
	
	// Toogle end_date visibility and insert the correct date
	$("#multiday").click(function() {
		var enddate = $("#start_date").datepicker("getDate");
		if (this.checked) {
			timestamp = enddate.getTime() + 1*24*60*60*1000;
			enddate.setTime(timestamp);
			$("#end_date_area").fadeIn();
		}
		else {
			$("#end_date_area").fadeOut();
			$("#end_date").datepicker("option", "minDate", null);
		}
		$("#end_date").datepicker("setDate", enddate);
	});
});