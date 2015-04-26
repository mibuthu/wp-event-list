<?php
if(!defined('WPINC')) {
	exit;
}

$date_formats_helptexts = array(
	'year'  => array('name'  => __('Year', 'event-list'),
	                 'desc'  => __('You can specify a year in 4 digit format.<br /> Other formats will not be accepted.','event-list'),
	                 'examp' => '2015'),
	'month' => array('name'  => __('Month', 'event-list'),
	                 'desc'  => __('You can specify a month with 4 digits for the year and 2 digits for the month, seperated by a hyphen (-).<br />Other formats will not be accepted.','event-list'),
	                 'examp' => '2015-03'),
	'day'   => array('name'  => __('Day', 'event-list'),
	                 'desc'  => __('You can specify a day with 4 digits for the year, 2 digits for the month and 2 digets for the day, seperated by a hyphen (-).<br /> Other formats will not be accepted.','event-list'),
	                 'examp' => '2015-03-29'),
);

$daterange_formats_helptexts = array(
	'date_range' => array('name'  => __('Date range'),
	                      'desc'  => __('You can specify a rage or dates seperated by a tilde (~).<br >You can specify any available date format before and after the tilde.','event-list'),
	                      'examp' => '2015-03-29~2016'),
	'all'        => array('name'  => __('All'),
	                      'desc'  => __('"all" specifies the full time range without any limitation.','event-list'),
	                      'value' => 'all'),
	'upcoming'   => array('name'  => __('Upcoming'),
	                      'desc'  => __('"upcoming" specifies a time range from the actual day to the future.','event-list'),
	                      'value' => 'upcoming'),
	'past'       => array('name'  => __('Past'),
	                      'desc'  => __('"past" specifies a time rage from the past to the previous day.','event-list'),
	                      'value' => 'past'),
);
?>
