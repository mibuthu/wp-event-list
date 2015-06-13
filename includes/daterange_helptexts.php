<?php
if(!defined('WPINC')) {
	exit;
}

$date_formats_helptexts = array(
	'year'       => array('name'  => __('Year','event-list'),
	                      'desc'  => __('You can specify a year in 4 digit format.<br />Other formats will not be accepted.','event-list'),
	                      'examp' => '2015'),
	'month'      => array('name'  => __('Month','event-list'),
	                      'desc'  => __('You can specify a month with 4 digits for the year and 2 digits for the month, seperated by a hyphen (-).<br />Other formats will not be accepted.','event-list'),
	                      'examp' => '2015-03'),
	'day'        => array('name'  => __('Day','event-list'),
	                      'desc'  => __('You can specify a day with 4 digits for the year, 2 digits for the month and 2 digets for the day, seperated by a hyphen (-).<br />Other formats will not be accepted.','event-list'),
	                      'examp' => '2015-03-29'),
	'rel_year'   => array('name'  => __('Relative Year','event-list'),
	                      'desc'  => __('You can specify a relative year from now with the following notation: <em>[+-]?[0-9]+_year[s]?</em><br />
	                                     This means you can specify a relativ number of years from now with "+" or "-" with "_year" or "_years" attached (see also the example).<br />
	                                     Instead of a number you can also specify one of the following special values: <em>last_year</em>, <em>next_year</em>, <em>this_year</em>','event-list'),
	                      'examp' => '+1_year'),
	'rel_month'  => array('name'  => __('Relative Month','event-list'),
	                      'desc'  => __('You can specify a relative month from now with the following notation: <em>[+-]?[0-9]+_month[s]?</em><br />
	                                     This means you can specify a relativ number of months from now with "+" or "-" with "_month" or "_months" attached (see also the example).<br />
	                                     Instead of a number you can also specify one of the following special values: <em>last_month</em>, <em>next_month</em>, <em>this_month</em>','event-list'),
	                      'examp' => '-6_months'),
	'rel_week'   => array('name'  => __('Relative Week','event-list'),
	                      'desc'  => __('You can specify a relative week from now with the following notation: <em>[+-]?[0-9]+_week[s]?</em><br />
	                                     This means you can specify a relativ number of weeks from now with "+" or "-" with "_week" or "_weeks" attached (see also the example).<br />
	                                     If you use this value for a start date the first day of the resulting week will be the first day that week. Using this value for an end date is similar, the last day of the resulting week will be used then.<br />
	                                     The first day of the week is depending on the option "Week Starts On" which can be found and changed in Settings &rarr; General.<br />
	                                     Instead of a number you can also specify one of the following special values: <em>last_week</em>, <em>next_week</em>, <em>this_week</em>','event-list'),
	                      'examp' => '+3_weeks'),
	'rel_day'    => array('name'  => __('Relative Day','event-list'),
	                      'desc'  => __('You can specify a relative day from now with the following notation: <em>[+-]?[0-9]+_day[s]?</em><br />
	                                     This means you can specify a relativ number of days from now with "+" or "-" with "_day" or "_days" attached (see also the example).<br />
	                                     Instead of a number you can also specify one of the following special values: <em>last_day</em>, <em>next_day</em>, <em>this_day</em>, <em>yesterday</em>, <em>today</em>, <em>tomorrow</em>','event-list'),
	                      'examp' => '-10_days'),
);

$daterange_formats_helptexts = array(
	'date_range' => array('name'  => __('Date range','event-list'),
	                      'desc'  => __('You can specify a rage or dates seperated by a tilde (~).<br >You can specify any available date format before and after the tilde.','event-list'),
	                      'examp' => '2015-03-29~2016'),
	'all'        => array('name'  => __('All'),
	                      'desc'  => __('"all" specifies the full time range without any limitation.','event-list'),
	                      'value' => 'all'),
	'upcoming'   => array('name'  => __('Upcoming','event-list'),
	                      'desc'  => __('"upcoming" specifies a time range from the actual day to the future.','event-list'),
	                      'value' => 'upcoming'),
	'past'       => array('name'  => __('Past','event-list'),
	                      'desc'  => __('"past" specifies a time rage from the past to the previous day.','event-list'),
	                      'value' => 'past'),
);
?>
