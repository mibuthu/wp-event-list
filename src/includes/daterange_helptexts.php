<?php
if(!defined('WPINC')) {
	exit;
}

$date_formats_helptexts = array(
	'year'       => array('name'  => __('Year','event-list'),
	                      'desc'  => __('A year can be specified in 4 digit format.','event-list').'<br />'.
	                                 sprintf(__('For a start date filter the first day of %1$s is used, in an end date the last day.','event-list'), __('the resulting year','event-list')),
	                      'examp' => '2015'),

	'month'      => array('name'  => __('Month','event-list'),
	                      'desc'  => __('A month can be specified with 4 digits for the year and 2 digits for the month, seperated by a hyphen (-).','event-list').'<br />'.
	                                 sprintf(__('For a start date filter the first day of %1$s is used, in an end date the last day.','event-list'), __('the resulting month','event-list')),
	                      'examp' => '2015-03'),

	'day'        => array('name'  => __('Day','event-list'),
	                      'desc'  => __('A day can be specified in the format 4 digits for the year, 2 digits for the month and 2 digets for the day, seperated by hyphens (-).','event-list'),
	                      'examp' => '2015-03-29'),

	'rel_year'   => array('name'  => __('Relative Year','event-list'),
	                      'desc'  => sprintf(__('%1$s from now can be specified in the following notation: %2$s','event-list'), __('A relative year','event-list'), '<em>[+-]?[0-9]+_year[s]?</em>').'<br />'.
	                                 sprintf(__('This means you can specify a positive or negative (%1$s) %2$s from now with %3$s or %4$s attached (see also the example below).','event-list'), '+/-', __('number of years','event-list'), '"_year"', '"_years"').'<br />'.
	                                 sprintf(__('Additionally the following values are available: %1$s','event-list'), '<em>last_year</em>, <em>next_year</em>, <em>this_year</em>'),
	                      'examp' => '+1_year'),

	'rel_month'  => array('name'  => __('Relative Month','event-list'),
	                      'desc'  => sprintf(__('%1$s from now can be specified in the following notation: %2$s','event-list'), __('A relative month','event-list'), '<em>[+-]?[0-9]+_month[s]?</em>').'<br />'.
	                                 sprintf(__('This means you can specify a positive or negative (%1$s) %2$s from now with %3$s or %4$s attached (see also the example below).','event-list'), '+/-', __('number of months','event-list'), '"_month"', '"_months"').'<br />'.
	                                 sprintf(__('Additionally the following values are available: %1$s','event-list'), '<em>last_month</em>, <em>next_month</em>, <em>this_month</em>'),
	                      'examp' => '-6_months'),

	'rel_week'   => array('name'  => __('Relative Week','event-list'),
	                      'desc'  => sprintf(__('%1$s from now can be specified in the following notation: %2$s','event-list'), __('A relative week','event-list'), '<em>[+-]?[0-9]+_week[s]?</em>').'<br />'.
	                                 sprintf(__('This means you can specify a positive or negative (%1$s) %2$s from now with %3$s or %4$s attached (see also the example below).','event-list'), '+/-', __('number of weeks','event-list'), '"_week"', '"_weeks"').'<br />'.
	                                 sprintf(__('For a start date filter the first day of %1$s is used, in an end date the last day.','event-list'), __('the resulting week','event-list')).'<br />'.
	                                 sprintf(__('The first day of the week is depending on the option %1$s which can be found and changed in %2$s.','event-list'), '"'.__('Week Starts On').'"', '"'.__('Settings').'" &rarr; "'.__('General').'"').'<br />'.
	                                 sprintf(__('Additionally the following values are available: %1$s','event-list'), '<em>last_week</em>, <em>next_week</em>, <em>this_week</em>'),
	                      'examp' => '+3_weeks'),

	'rel_day'    => array('name'  => __('Relative Day','event-list'),
	                      'desc'  => sprintf(__('%1$s from now can be specified in the following notation: %2$s','event-list'), __('A relative day','event-list'), '<em>[+-]?[0-9]+_day[s]?</em>').'<br />'.
	                                 sprintf(__('This means you can specify a positive or negative (%1$s) %2$s from now with %3$s or %4$s attached (see also the example below).','event-list'), '+/-', __('number of days','event-list'), '"_day"', '"_days"').'<br />'.
	                                 sprintf(__('Additionally the following values are available: %1$s','event-list'), '<em>last_day</em>, <em>next_day</em>, <em>this_day</em>, <em>yesterday</em>, <em>today</em>, <em>tomorrow</em>'),
	                      'examp' => '-10_days'),
);

$daterange_formats_helptexts = array(
	'date_range' => array('name'  => __('Date range','event-list'),
	                      'desc'  => __('A date rage can be specified via a start date and end date seperated by a tilde (~).<br />
	                                     For the start and end date any available date format can be used.','event-list'),
	                      'examp' => '2015-03-29~2016'),

	'all'        => array('name'  => __('All'),
	                      'desc'  => __('This value defines a range without any limits.','event-list').'<br />'.
	                                 sprintf(__('The corresponding date_range format is: %1$s','event-list'), '<em>1970-01-01~2999-12-31</em>'),
	                      'value' => 'all'),

	'upcoming'   => array('name'  => __('Upcoming','event-list'),
	                      'desc'  => __('This value defines a range from the actual day to the future.','event-list').'<br />'.
	                                 sprintf(__('The corresponding date_range format is: %1$s','event-list'), '<em>today~2999-12-31</em>'),
	                      'value' => 'upcoming'),

	'past'       => array('name'  => __('Past','event-list'),
	                      'desc'  => __('This value defines a range from the past to the previous day.','event-list').'<br />'.
	                                 sprintf(__('The corresponding date_range format is: %1$s','event-list'), '<em>1970-01-01~yesterday</em>'),
	                      'value' => 'past'),
);
?>
