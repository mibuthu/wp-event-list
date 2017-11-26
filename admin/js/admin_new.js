// Javascript functions for event-list admin_new page

// Date helpers
jQuery(document).ready(function($) {
	// Read required config data from hidden field json_for_js
	var json = $("#json_for_js").val();
	var conf = JSON.parse(json);

	// Add copy button if required
	if(conf.el_copy_url.length > 0) {
		$("h1.wp-heading-inline").first().after('<a href="' + conf.el_copy_url + '" class="add-new-h2">' + conf.el_copy_text + '</a>');
	}

	// Show or hide end_date
	if ($("#startdate").val() == $("#enddate").val()) {
		$("#enddate-area").hide();
	}
	else {
		$("#multiday").attr('checked', true);
	}

	$.datepicker.setDefaults({
		"dateFormat": conf.el_date_format,
		"firstDay": conf.el_start_of_week,
		"changeMonth": true,
		"changeYear": true,
		"numberOfMonths": 3,
		"constrainInput": true,
		"altFormat": "yy-mm-dd",
		"minDate": $.datepicker.parseDate('yy-mm-dd', "1970-01-01"),
		"maxDate": $.datepicker.parseDate('yy-mm-dd', "2999-12-31"),
	});

	// Datepickers
	$("#startdate").datepicker( {
		dateFormat: conf.el_date_format, // don't work when only set with setDefaults
		altField: "#startdate-iso",
		onClose: function(selectedDate) {
			// set minDate for end_date picker
			minDate = $.datepicker.parseDate(conf.el_date_format, selectedDate);
			minDate.setDate(minDate.getDate() + 1);
			console.log(minDate);
			$("#enddate").datepicker("option", "minDate", minDate);
		}
	});
	$("#enddate").datepicker( {
		dateFormat: conf.el_date_format, // don't work when only set with setDefaults
		altField: "#enddate-iso",
	});

	// Toogle end_date visibility and insert the correct date
	$("#multiday").click(function() {
		var enddate = $("#startdate").datepicker("getDate");
		if (this.checked) {
			enddate.setDate(enddate.getDate() + 1);
			$("#enddate").datepicker("option", "minDate", enddate);
			$("#enddate-area").fadeIn();
		}
		else {
			$("#enddate-area").fadeOut();
		}
		$("#enddate").datepicker("setDate", enddate);
	});

	// Initialize Dates
	$("#startdate").datepicker("setDate", $.datepicker.parseDate('yy-mm-dd', $("#startdate").val()));
	$("#enddate").datepicker("setDate", $.datepicker.parseDate('yy-mm-dd', $("#enddate").val()));
});
