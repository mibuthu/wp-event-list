<?php
if(!defined('WPINC')) {
	exit;
}

$sc_eventlist_helptexts = array(
	'initial_event_id' => array('val'    => array('all', strtoupper(__('event-id','event-list'))),
	                            'desc'   => sprintf(__('By default the event-list is displayed initially. But if an event-id (e.g. %1$s) is provided for this attribute, directly the event-content view of this event is shown.','event-list'), '"13"')),

	'initial_date'     => array('val'    => array('all', 'upcoming', 'past', strtoupper(__('year','event-list'))),
	                            'desc'   => __('This attribute defines which events are initially shown. The default is to show the upcoming events only.','event-list').'<br />'.
	                                        sprintf(__('Provide a year (e.g. %1$s) to change this behavior. It is still possible to change the displayed event date range via the filterbar or url parameters.','event-list'), '"2017"')),

	'initial_cat'      => array('val'    => array('all', strtoupper(__('category slug','event-list'))),
	                            'desc'   => __('This attribute defines the category of which events are initially shown. The default is to show events of all categories.','event-list').'<br />'.
	                                        __('Provide a category slug to change this behavior. It is still possible to change the displayed categories via the filterbar or url parameters.','event-list')),

	'initial_order'    => array('val'    => array('date_asc', 'date_desc'),
	                            'desc'   => __('This attribute defines the initial order of the events.','event-list').'<br />'.
	                                        sprintf(__('With %1$S (default value) the events are sorted from old to new, with %2$s in the opposite direction (from new to old).','event-list'), '"date_asc"', '"date_desc"')),

	'date_filter'      => array('val'    => array('all', 'upcoming', 'past', strtoupper(__('year','event-list'))),
	                            'desc'   => sprintf(__('This attribute defines the dates and date ranges of which events are displayed. The default is %1$s to show all events.','event-list'), '"all"').'<br />'.
	                                        sprintf(__('Filtered events according to %1$s value are not available in the event list.','event-list'), 'date_filter').'<br />'.
	                                        sprintf(__('You can find all available values with a description and examples in the sections %1$s and %2$s below.','event-list'), '"'.__('Available Date Formats','event-list').'"', '"'.__('Available Date Range Formats','event-list').'"').'<br />'.
	                                        sprintf(__('See %1$s description if you want to define complex filters.','event-list'), '"'.__('Filter Syntax','event-list').'"')),

	'cat_filter'       => array('val'    => array('all', strtoupper(__('category slugs','event-list'))),
	                            'desc'   => sprintf(__('This attribute defines the category filter which filters the events to show. The default is $1$s or an empty string to show all events.','event-list'), '"all"').'<br />'.
	                                        sprintf(__('Events with categories that doesn´t match %1$s are not shown in the event list. They are also not available if a manual url parameter is added.','event-list'), 'cat_filter').'<br />'.
	                                        sprintf(__('The filter is specified via the given category slugs. See %1$s description if you want to define complex filters.','event-list'), '"'.__('Filter Syntax','event-list').'"')),

	'num_events'       => array('val'    => array(strtoupper(__('number','event-list'))),
	                            'desc'   => sprintf(__('This attribute defines how many events should be displayed if upcoming events is selected. With the default value %1$s all events will be displayed.','event-list'), '"0"').'<br />'.
	                                        __('Please not that in the actual version there is no pagination of the events available, so the event list can be very long.','event-list')),

	'show_filterbar'   => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute defines if the filterbar should be displayed. The filterbar allows the users to specify filters for the listed events.','event-list').'<br />'.
	                                        sprintf(__('Choose %1$s to always hide and %2$s to always show the filterbar.','event-list'), '"false"', '"true"').'<br />'.
	                                        sprintf(__('With %1$s the filterbar is only visible in the event list and with %2$s only in the single event view.','event-list'), '"event_list_only"', '"single_event_only"')),

	'filterbar_items'  => array('val'    => array('years_hlist', 'years_dropdown', 'months_hlist', 'months_dropdown', 'daterange_hlist', 'daterange_dropdown', 'cats_hlist', 'cats_dropdown', 'reset_link'),
	                            'desc'   => sprintf(__('This attribute specifies the available items in the filterbar. This options are only valid if the filterbar is displayed (see %1$s attribute).','event-list'), '"show_filterbar"').'<br /><br />'.
	                                        __('Find below an overview of the available filterbar items and their options:','event-list').'<br />'.
	                                        sc_eventlist_helptexts_filterbar_table(array(
	                                          array('<th class="el-filterbar-item">'.__('filterbar item','event-list'), '<th class="el-filterbar-desc">'.__('description','event-list'), '<th class="el-filterbar-options">'.__('item options','event-list'), '<th class="el-filterbar-values">'.__('option values','event-list'), '<th class="el-filterbar-default">'.__('default value','event-list'), '<th class="el-filterbar-desc2">'.__('option description','event-list')),
	                                          array('<td rowspan="4">years', '<td rowspan="4">'. __('Show a list of all available years. Additional there are some special entries available (see item options).','event-list'),
	                                                  'show_all',      'true | false', 'true',   __('Add an entry to show all events.','event-list')),
	                                            array('show_upcoming', 'true | false', 'true',   __('Add an entry to show all upcoming events.','event-list')),
	                                            array('show_past',     'true | false', 'false',  __('Add an entry to show events in the past.','event-list')),
	                                            array('years_order',   'desc | asc',   'desc',   __('Set descending or ascending order of year entries.','event-list')),
	                                          array('<td rowspan="5">months', '<td rowspan="5">'.__('Show a list of all available months.','event-list'),
	                                                  'show_all',      'true | false', 'false',  __('Add an entry to show all events.','event-list')),
	                                            array('show_upcoming', 'true | false', 'false',  __('Add an entry to show all upcoming events.','event-list')),
	                                            array('show_past',     'true | false', 'false',  __('Add an entry to show events in the past.','event-list')),
	                                            array('months_order',  'desc | asc',   'desc',   __('Set descending or ascending order of month entries.','event-list')),
	                                            array('date_format',   '<a href="http://php.net/manual/en/function.date.php">'.__('php date-formats','event-list').'</a>', 'Y-m', __('Set the displayed date format of the month entries.','event-list')),
	                                          array('daterange', sprintf(__('With this item you can display the special entries %1$s, %2$s and %3$s. You can use all or only some of the available values and you can specify their order.','event-list'), '"all"', '"upcoming"', "past"), 'item_order', 'all | upcoming | past', 'all&amp;upcoming&amp;past', sprintf(__('Specifies the displayed values and their order. The items must be seperated by %1$s.','event-list'), '"&amp;"')),
	                                          array('cats', __('Show a list of all available categories.','event-list'), 'show_all', 'true | false', 'true', __('Add an entry to show events from all categories.','event-list')),
	                                          array('reset', __('A link to reset the eventlist filter to standard.','event-list'), 'caption', __('any text','event-list'), __('Reset','event-list'), __('Set the caption of the link.','event-list')))).
	                                         __('Find below an overview of the available filterbar display options:','event-list').'<br />'.
	                                         sc_eventlist_helptexts_filterbar_table(array(
	                                           array('<th class="el-filterbar-doption">'.__('display option','event-list'), '<th class="el-filterbar-desc3">'.__('description','event-list'), '<th class="el-filterbar-for">'.__('available for','event-list')),
	                                           array('hlist', sprintf(__('Shows a horizonal list seperated by %1$s with a link to each item.','event-list'), '"|"'), 'years, months, daterange, cats'),
	                                           array('dropdown', __('Shows a select box where an item can be choosen. After the selection of an item the page is reloaded via javascript to show the filtered events.','event-list'), 'years, months, daterange, cats'),
	                                           array('link', __('Shows a simple link which can be clicked.','event-list'), 'reset'))).
	                                         '<p>'.__('Find below some declaration examples with descriptions:','event-list').'</p>
	                                         <code>years_hlist,cats_dropdown</code><br />
	                                         '.sprintf(__('In this example you can see that the filterbar item and the used display option is joined by an underscore %1$s. You can define several filterbar items seperated by a comma %2$s. These items will be displayed left-aligned.','event-list'), '"_"', '(",")').'
	                                         <p><code>years_dropdown(show_all=false|show_past=true),cats_dropdown;;reset_link</code><br />
	                                         '.sprintf(__('In this example you can see that filterbar options can be added in brackets in format %1$s. You can also add multiple options seperated by a pipe %2$s.','event-list'), '"'.__('option_name','event-list').'='.__('value','event-list').'"', '("|")').'<br />
	                                         '.sprintf(__('The 2 semicolon %1$s devides the bar in 3 section. The first section will be displayed left-justified, the second section will be centered and the third section will be right-aligned. So in this example the 2 dropdown will be left-aligned and the reset link will be on the right side.','event-list'), '(";")').'</p>'),

	'title_length'     => array('val'    => array(__('number','event-list'), 'auto'),
	                            'desc'   => __('This attribute specifies if the title should be truncated to the given number of characters in the event list.','event-list').'<br />'.
	                                        sprintf(__('With the standard value %1$s the full text is displayed, with %2$s the text is automatically truncated via css.','event-list'), '[0]', '[auto]').'<br />'.
	                                        __('This attribute has no influence if only a single event is shown.','event-list')),

	'show_starttime'   => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute specifies if the starttime is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the starttime.<br />
	                                            With "event_list_only" the starttime is only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'show_location'    => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute specifies if the location is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the location.<br />
	                                            With "event_list_only" the location is only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'location_length'  => array('val'    => array(__('number','event-list'), 'auto'),
	                            'desc'   => __('This attribute specifies if the title should be truncated to the given number of characters in the event list.','event-list').'<br />'.
	                                        sprintf(__('With the standard value %1$s the full text is displayed, with %2$s the text is automatically truncated via css.','event-list'), '[0]', '[auto]').'<br />'.
	                                        __('This attribute has no influence if only a single event is shown.','event-list')),

	'show_cat'         => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute specifies if the categories are displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the category.<br />
	                                            With "event_list_only" the categories are only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'show_content'     => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute specifies if the content is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the content.<br />
	                                            With "event_list_only" the content is only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'content_length'   => array('val'    => array(__('number','event-list')),
	                            'desc'   => __('This attribute specifies if the content should be truncate to the given number of characters in the event list.','event-list').'<br />'.
	                                        sprintf(__('With the standard value %1$s the full text is displayed.','event-list'), '[0]').'<br />'.
	                                        __('This attribute has no influence if only a single event is shown.','event-list')),

	'collapse_content' => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute specifies if the content should be collapsed initially.<br />
	                                            Then a link will be displayed instead of the content. By clicking this link the content are getting visible.<br />
	                                            Available option are "false" to always disable collapsing and "true" to always enable collapsing of the content.<br />
	                                            With "event_list_only" the content is only collapsed in the event list view and with "single_event_only" only in single event view.','event-list')),

	'link_to_event'    => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only', 'events_with_content_only'),
	                            'desc'   => __('This attribute specifies if a link to the single event should be added onto the event name in the event list.<br />
	                                            Choose "false" to never add and "true" to always add the link.<br />
	                                            With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event.<br />
	                                            With "events_with_content_only" the link is only added in the event list for events with event content.','event-list')),

	'add_feed_link'    => array('val'    => array('false', 'true', 'event_list_only', 'single_event_only'),
	                            'desc'   => __('This attribute specifies if a rss feed link should be added.<br />
	                                            You have to enable the feed in the eventlist settings to make this attribute workable.<br />
	                                            On that page you can also find some settings to modify the output.<br />
	                                            Choose "false" to never add and "true" to always add the link.<br />
	                                            With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event','event-list')),
	'url_to_page'      => array('val'    => array('url'),
	                            'desc'   => __('This attribute specifies the page or post url for event links.<br />
	                                            The standard is an empty string. Then the url will be calculated automatically.<br />
	                                            An url is normally only required for the use of the shortcode in sidebars. It is also used in the event-list widget.','event-list')),

	// Invisible attributes ('hidden' = true): This attributes are required for the widget but will not be listed in the attributes table on the admin info page
	'sc_id_for_url'    => array('val'    => array('number'),
	                            'hidden' => true,
	                            'desc'   => __('This attribute the specifies shortcode id of the used shortcode on the page specified with "url_to_page" attribute.<br />
	                                            The empty standard value is o.k. for the normal use. This attribute is normally only required for the event-list widget.','event-list')),
);

function sc_eventlist_helptexts_filterbar_table($tabledata_array) {
	// table opening tag
	$out = '
		<small><table class="el-filterbar-table">';
	// Start with th items (table head for first row)
	$tableitem_tag = 'th';
	foreach($tabledata_array as $row) {
		// row opening tag
		$out .= '
			<tr>';
		foreach($row as $column_val) {
			// opening tag (if required)
			$out .= ('<'.$tableitem_tag === substr($column_val, 0, 3)) ? '' : '<'.$tableitem_tag.'>';
			// column value and closing tag
			$out .= $column_val.'</'.$tableitem_tag.'>';
		}
		// row closing tag
		$out .= '</tr>';
		// Change to td items (after table head)
		$tableitem_tag = 'td';
	}
	// table closing tag
	$out .= '
		</table></small>
		';
	return $out;
}
?>
