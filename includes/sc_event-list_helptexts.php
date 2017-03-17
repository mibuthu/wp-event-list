<?php
if(!defined('WPINC')) {
	exit;
}

$sc_eventlist_helptexts = array(
	'initial_event_id' => array('val'    => 'all<br />'.strtoupper(__('event-id','event-list')),
	                            'desc'   => sprintf(__('By default the event-list is displayed initially. But if an event-id (e.g. %1$s) is provided for this attribute, directly the event-details view of this event is shown.','event-list'), '"13"')),

	'initial_date'     => array('val'    => 'all<br />upcoming<br />past<br />'.strtoupper(__('year','event-list')),
	                            'desc'   => __('This attribute defines which events are initially shown. The default is to show the upcoming events only.','event-list').'<br />'.
	                                        sprintf(__('Provide a year (e.g. %1$s) to change this behavior. It is still possible to change the displayed event date range via the filterbar or url parameters.','event-list'), '"2017"')),

	'initial_cat'      => array('val'    => 'all<br />'.strtoupper(__('category slug','event-list')),
	                            'desc'   => __('This attribute defines the category of which events are initially shown. The default is to show events of all categories.','event-list').'<br />'.
	                                        __('Provide a category slug to change this behavior. It is still possible to change the displayed categories via the filterbar or url parameters.','event-list')),

	'initial_order'    => array('val'    => 'date_asc<br />date_desc',
	                            'desc'   => __('This attribute defines the initial order of the events.','event-list').'<br />'.
	                                        sprintf(__('With %1$S (default value) the events are sorted from old to new, with %2$s in the opposite direction (from new to old).','event-list'), '"date_asc"', '"date_desc"')),

	'date_filter'      => array('val'    => 'all<br />upcoming<br />past<br />'.strtoupper(__('year','event-list')),
	                            'desc'   => sprintf(__('This attribute defines the dates and date ranges of which events are displayed. The default is %1$s to show all events.','event-list'), '"all"').'<br />'.
	                                        sprintf(__('Filtered events according to %1$s value are not available in the event list.','event-list'), 'date_filter').'<br />'.
	                                        sprintf(__('You can find all available values with a description and examples in the sections %1$s and %2$s below.','event-list'), '"'.__('Available Date Formats','event-list').'"', '"'.__('Available Date Range Formats','event-list').'"').'<br />'.
	                                        sprintf(__('See %1$s description if you want to define complex filters.','event-list'), '"'.__('Filter Syntax','event-list').'"')),

	'cat_filter'       => array('val'    => 'all<br />'.strtoupper(__('category slugs','event-list')),
	                            'desc'   => sprintf(__('This attribute defines the category filter which filters the events to show. The default is $1$s or an empty string to show all events.','event-list'), '"all"').'<br />'.
	                                        sprintf(__('Events with categories that doesn´t match %1$s are not shown in the event list. They are also not available if a manual url parameter is added.','event-list'), 'cat_filter').'<br />'.
	                                        sprintf(__('The filter is specified via the given category slugs. See %1$s description if you want to define complex filters.','event-list'), '"'.__('Filter Syntax','event-list').'"')),

	'num_events'       => array('val'    => strtoupper(__('number','event-list')),
	                            'desc'   => sprintf(__('This attribute defines how many events should be displayed if upcoming events is selected. With the default value %1$s all events will be displayed.','event-list'), '"0"').'<br />'.
	                                        __('Please not that in the actual version there is no pagination of the events available, so the event list can be very long.','event-list')),

	'show_filterbar'   => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute defines if the filterbar should be displayed. The filterbar allows the users to specify filters for the listed events.','event-list').'<br />'.
	                                        sprintf(__('Choose %1$s to always hide and %2$s to always show the filterbar.','event-list'), '"false"', '"true"').'<br />'.
	                                        sprintf(__('With %1$s the filterbar is only visible in the event list and with %2$s only in the single event view.','event-list'), '"event_list_only"', '"single_event_only"')),

	'filterbar_items'  => array('val'    => 'years_hlist<br />years_dropdown<br />months_hlist<br />months_dropdown<br />daterange_hlist<br />daterange_dropdown<br />cats_hlist<br />cats_dropdown<br />reset_link',
	                            'desc'   => 'This attribute specifies the available items in the filterbar. This options are only valid if the filterbar is displayed (see show_filterbar attribute).<br /><br />
	                                         Find below an overview of the available filterbar items and their options:<br />
	                                         <small><table class="el-filterbar-table">
	                                             <tr><th class="el-filterbar-item">filterbar item</th><th class="el-filterbar-desc">description</th><th class="el-filterbar-options">item options</th><th class="el-filterbar-values">option values</th><th class="el-filterbar-default">default value</th><th class="el-filterbar-desc2">option description</th></tr>
	                                             <tr><td rowspan="4">years</td><td rowspan="4">Show a list of all available years. Additional there are some special entries available (see item options).</td>
	                                                     <td>show_all</td><td>true | false</td><td>true</td><td>Add an entry to show all events.</td></tr>
	                                                 <tr><td>show_upcoming</td><td>true | false</td><td>true</td><td>Add an entry to show all upcoming events.</td></tr>
	                                                 <tr><td>show_past</td><td>true | false</td><td>false</td><td>Add an entry to show events in the past.</td></tr>
	                                                 <tr><td>years_order</td><td>desc | asc</td><td>desc</td><td>Set descending or ascending order of year entries.</td></tr>
	                                             <tr><td rowspan="5">months</td><td rowspan="5">Show a list of all available months.</td>
	                                                     <td>show_all</td><td>true | false</td><td>false</td><td>Add an entry to show all events.</td></tr>
	                                                 <tr><td>show_upcoming</td><td>true | false</td><td>false</td><td>Add an entry to show all upcoming events.</td></tr>
	                                                 <tr><td>show_past</td><td>true | false</td><td>false</td><td>Add an entry to show events in the past.</td></tr>
	                                                 <tr><td>months_order</td><td>desc | asc</td><td>desc</td><td>Set descending or ascending order of month entries.</td></tr>
	                                                 <tr><td>date_format</td><td><a href="http://php.net/manual/en/function.date.php">php date-formats</a></td><td>Y-m</td><td>Set the displayed date format of the month entries.</td></tr>
	                                             <tr><td>daterange</td><td>With this item you can display the special entries "all", "upcoming" and "past". You can use all or only some of the available values and you can specify their order.</td><td>item_order</td><td>all | upcoming | past</td><td>all&amp;upcoming&amp;past</td><td>Specifies the displayed values and their order. The items must be seperated by "&amp;".</td></tr>
	                                             <tr><td>cats</td><td>Show a list of all available categories.</td><td>show_all</td><td>true | false</td><td>true</td><td>Add an entry to show events from all categories.</td></tr>
	                                             <tr><td>reset</td><td>Only a link to reset the eventlist filter to standard.</td><td>caption</td><td>any text</td><td>Reset</td><td>Set the caption of the link.</td></tr>
	                                         </table></small>
	                                         Find below an overview of the available filterbar display options:<br />
	                                         <small><table class="el-filterbar-table">
	                                            <tr><th class="el-filterbar-doption">display option</th><th class="el-filterbar-desc3">description</th><th class="el-filterbar-for">available for</th></tr>
	                                            <tr><td>hlist</td><td>"hlist" shows a horizonal list seperated by "|" with a link to each item</td><td>years, months, daterange, cats</td></tr>
	                                            <tr><td>dropdown</td><td>"dropdown" shows a select box where an item can be choosen. After the selection of an item the page is reloaded via javascript to show the filtered events.</td><td>years, months, daterange, cats</td></tr>
	                                            <tr><td>link</td><td>"link" shows a simple link which can be clicked.</td><td>reset</td></tr>
	                                         </table></small>
	                                         <p>Find below some declaration examples with descriptions:</p>
	                                         <code>years_hlist,cats_dropdown</code><br />
	                                         In this example you can see that the filterbar item and the used display option is seperated by "_". You can define several filterbar items seperated by comma (","). The items will be aligned on the left side.
	                                         <p><code>years_dropdown(show_all=false|show_past=true),cats_dropdown;;reset_link</code><br />
	                                         In this example you can see that filterbar options can be added in brackets in format "option_name=value". You can also add multiple options seperated by a pipe ("|").<br />
	                                         The 2 semicolon (";") devides the bar in 3 section. The first section will be displayed left-justified, the second section will be centered and the third section will be right-aligned. So in this example the 2 dropdown will be left-aligned and the reset link will be on the right side.</p>'),

	'title_length'     => array('val'    => __('number','event-list').'<br />auto',
	                            'desc'   => __('This attribute specifies if the title should be truncated to the given number of characters in the event list.','event-list').'<br />'.
	                                        sprintf(__('With the standard value %1$s the full text is displayed, with %2$s the text is automatically truncated via css.','event-list'), '[0]', '[auto]').'<br />'.
	                                        __('This attribute has no influence if only a single event is shown.','event-list')),

	'show_starttime'   => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the starttime is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the starttime.<br />
	                                            With "event_list_only" the starttime is only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'show_location'    => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the location is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the location.<br />
	                                            With "event_list_only" the location is only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'location_length'  => array('val'    => __('number','event-list').'<br />auto',
	                            'desc'   => __('This attribute specifies if the title should be truncated to the given number of characters in the event list.','event-list').'<br />'.
	                                        sprintf(__('With the standard value %1$s the full text is displayed, with %2$s the text is automatically truncated via css.','event-list'), '[0]', '[auto]').'<br />'.
	                                        __('This attribute has no influence if only a single event is shown.','event-list')),

	'show_cat'         => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the categories are displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the category.<br />
	                                            With "event_list_only" the categories are only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'show_details'     => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the details are displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the details.<br />
	                                            With "event_list_only" the details are only visible in the event list and with "single_event_only" only for a single event','event-list')),

	'details_length'   => array('val'    => __('number','event-list'),
	                            'desc'   => __('This attribute specifies if the details should be truncate to the given number of characters in the event list.','event-list').'<br />'.
	                                        sprintf(__('With the standard value %1$s the full text is displayed.','event-list'), '[0]').'<br />'.
	                                        __('This attribute has no influence if only a single event is shown.','event-list')),

	'collapse_details' => array('val'    => 'false',
	                            'desc'   => __('This attribute specifies if the details should be collapsed initially.<br />
	                                            Then a link will be displayed instead of the details. By clicking this link the details are getting visible.<br />
	                                            Available option are "false" to always disable collapsing and "true" to always enable collapsing of the details.<br />
	                                            With "event_list_only" the details are only collapsed in the event list view and with "single_event_only" only in single event view.','event-list')),

	'link_to_event'    => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only<br />events_with_details_only',
	                            'desc'   => __('This attribute specifies if a link to the single event should be added onto the event name in the event list.<br />
	                                            Choose "false" to never add and "true" to always add the link.<br />
	                                            With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event.<br />
	                                            With "events_with_details_only" the link is only added in the event list for events with event details.','event-list')),

	'add_feed_link'    => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if a rss feed link should be added.<br />
	                                            You have to enable the feed in the eventlist settings to make this attribute workable.<br />
	                                            On that page you can also find some settings to modify the output.<br />
	                                            Choose "false" to never add and "true" to always add the link.<br />
	                                            With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event','event-list')),
	'url_to_page'      => array('val'    => 'url',
	                            'desc'   => __('This attribute specifies the page or post url for event links.<br />
	                                            The standard is an empty string. Then the url will be calculated automatically.<br />
	                                            An url is normally only required for the use of the shortcode in sidebars. It is also used in the event-list widget.','event-list')),

	// Invisible attributes ('hidden' = true): This attributes are required for the widget but will not be listed in the attributes table on the admin info page
	'sc_id_for_url'    => array('val'    => 'number',
	                            'hidden' => true,
	                            'desc'   => __('This attribute the specifies shortcode id of the used shortcode on the page specified with "url_to_page" attribute.<br />
	                                            The empty standard value is o.k. for the normal use. This attribute is normally only required for the event-list widget.','event-list')),
);
?>
