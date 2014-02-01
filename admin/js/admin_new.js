// Javascript functions for event-list admin_new page

// Date helpers
jQuery(document).ready(function($) {
	// Read required config data from hidden field json_for_js
	var json = $("#json_for_js").val();
	var conf = eval('(' + json + ')');

	// Show or hide end_date
	if ($("#start_date").val() == $("#end_date").val()) {
		$("#end_date_area").hide();
	}
	else {
		$("#multiday").attr('checked', true);
	}

	// Datepickers
	$("#start_date").datepicker( {
		dateFormat: conf.el_date_format,
		firstDay: 1,
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 3,
		showOn: "both",
		buttonImage: conf.el_url + "admin/images/calendar-icon.png",
		buttonImageOnly: true,
		constrainInput: true,
		altField: "#sql_start_date",
		altFormat: "yy-mm-dd",
		onClose: function(selectedDate) {
			// set minDate for end_date picker
			minDate = $.datepicker.parseDate( conf.el_date_format, selectedDate );
			minDate.setDate(minDate.getDate()+1);
			$("#end_date").datepicker("option", "minDate", minDate);
		}
	});
	$("#end_date").datepicker( {
		dateFormat: conf.el_date_format,
		firstDay: 1,
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 3,
		showOn: "both",
		buttonImage: conf.el_url + "admin/images/calendar-icon.png",
		buttonImageOnly: true,
		constrainInput: true,
		altField: "#sql_end_date",
		altFormat: "yy-mm-dd"
	});

	// Toogle end_date visibility and insert the correct date
	$("#multiday").click(function() {
		var enddate = $("#start_date").datepicker("getDate");
		if (this.checked) {
			timestamp = enddate.getTime() + 1*24*60*60*1000;
			enddate.setTime(timestamp);
			$("#end_date_area").fadeIn();
			$("#end_date").datepicker("option", "minDate", enddate);
		}
		else {
			$("#end_date_area").fadeOut();
		}
		$("#end_date").datepicker("setDate", enddate);
	});

	// Initialize Dates
	$("#start_date").datepicker("setDate", $.datepicker.parseDate('yy-mm-dd', $("#start_date").val()));
	$("#end_date").datepicker("setDate", $.datepicker.parseDate('yy-mm-dd', $("#end_date").val()));
});
