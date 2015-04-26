<?php
if(!defined('WPINC')) {
	exit;
}

$date_formats_desc = array(
	'year'  => __('You can specify a year in 4 digit format.<br /> Other formats will not be accepted.','event-list'),
	'month' => __('You can specify a month with 4 digits for the year and 2 digits for the month, seperated by a hyphen (-).<br />Other formats will not be accepted.','event-list'),
	'day'   => __('You can specify a day with 4 digits for the year, 2 digits for the month and 2 digets for the day, seperated by a hyphen (-).<br /> Other formats will not be accepted.','event-list'),
);

$daterange_formats_desc = array(
	'date_range'   => __('You can specify a rage or dates seperated by a tilde (~).<br >You can specify any available date format before and after the tilde.','event-list'),
	'all'          => __('"all" specifies the full time range without any limitation.','event-list'),
	'upcoming'     => __('"upcoming" specifies a time range from the actual day to the future.','event-list'),
	'past'         => __('"past" specifies a time rage from the past to the previous day.','event-list'),
);
?>
