<?php
if(!defined('WPINC')) {
	exit;
}

$sc_eventlist_helptexts = array(
	'initial_event_id' => array('val'    => 'all<br />event-id',
	                            'desc'   => __('With this attribute you can specify an event from which the event-details are shown initially. The standard is to show the event-list.<br />
	                                            Specify an event-id e.g. "13" to change this behavior. It is still possible to go back to the event-list via the filterbar or url parameters.','eventlist')),

	'initial_date'     => array('val'    => 'all<br />upcoming<br />past<br />year',
	                            'desc'   => __('This attribute specifies which events are initially shown. The standard is to show the upcoming events.<br />
	                                            Specify a year e.g. "2014" to change this behavior. It is still possible to change the displayed event date range via the filterbar or url parameters.','eventlist')),

	'initial_cat'      => array('val'    => 'all<br />category slug',
	                            'desc'   => __('This attribute specifies the category of which events are initially shown. The standard is to show events of all categories.<br />
	                                            Specify a category slug to change this behavior. It is still possible to change the displayed categories via the filterbar or url parameters.','eventlist')),

	'date_filter'      => array('val'    => 'all<br />upcoming<br />past<br />year',
	                            'desc'   => __('This attribute specifies the dates and date ranges of which events are displayed. The standard is "all" to show all events.<br />
	                                            Filtered events according to date_filter value are not available in the event list.<br />
	                                            You can find all available values with a description and examples in "Available Date Formats" and "Available Date Range Formats" below.<br />
	                                            See "Filter Syntax" description if you want to define complex filters.<br />
	                                            When you only use OR connections (no AND connection) the years selection in the filterbar will also be filtered accordingly.','eventlist')),

	'cat_filter'       => array('val'    => 'all<br />category slugs',
	                            'desc'   => __('This attribute specifies the categories of which events are shown. The standard is "all" or an empty string to show all events.<br />
	                                            Filtered events defined in categories which doesn´t match cat_filter are not shown in the event list. They are also not available if a manual url parameter is added.<br />
	                                            The filter is specified via the given category slug. See "Filter Syntax" description if you want to define complex filters.<br />
	                                            When you only use OR connections (no AND connection) the category selection in the filterbar will also be filtered accordingly.','eventlist')),

	'num_events'       => array('val'    => 'number',
	                            'desc'   => __('This attribute specifies how many events should be displayed if upcoming events is selected.<br />
	                                            0 is the standard value which means that all events will be displayed.<br />
	                                            Please not that in the actual version there is no pagination of the events available.','eventlist')),

	'show_filterbar'   => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the filterbar should be displayed. The filterbar allows the users to select filters to limit the listed events.<br />
	                                            Choose "false" to always hide and "true" to always show the navigation.<br />
	                                            With "event_list_only" the filterbar is only visible in the event list and with "single_event_only" only for a single event','eventlist')),

	'filterbar_items'  => array('val'    => 'years_hlist<br />years_dropdown<br />months_hlist<br />months_dropdown<br />daterange_hlist<br />daterange_dropdown<br />cats_hlist<br />cats_dropdown<br />reset_link',
	                            'desc'   => 'This attribute specifies the available items in the filterbar. This options are only valid if the filterbar is displayed (see show_filterbar attribute).<br /><br />
	                                         Find below an overview of the available filterbar items and their options:<br />
	                                         <small><table class="el-filterbar-table">
	                                             <th class="el-filterbar-item">filterbar item</th><th class="el-filterbar-desc">description</th><th class="el-filterbar-options">item options</th><th class="el-filterbar-values">option values</th><th class="el-filterbar-default">default value</th><th class="el-filterbar-desc2">option description</th></thead>
	                                             <tr><td>years</td><td>Show a list of all available years. Additional there are some special entries available (see item options).</td><td>show_all<br />show_upcoming<br />show_past<br />years_order</td><td>true | false<br />true | false<br />true | false<br />desc | asc</td><td>true<br />true<br />false<br />desc</td><td>Add an entry to show all events.<br />Add an entry to show all upcoming events.<br />Add an entry to show events in the past.<br />Set descending or ascending order of year entries.</tr>
	                                             <tr><td>months</td><td>Show a list of all available months.</td><td></td><td></td><td></td><td></td></tr>
	                                             <tr><td>daterange</td><td>With this item you can display the special entries "all", "upcoming" and "past". You can use all or only some of the available values and you can specify their order.</td><td>item_order</td><td>all | upcoming | past</td><td>all&amp;upcoming&amp;past</td><td>Specifies the displayed values and their order. The items must be seperated by "&amp;".</td></tr>
	                                             <tr><td>cats</td><td>Show a list of all available categories.</td><td>show_all</td><td>true | false</td><td>true</td><td>Add an entry to show events from all categories.</td></tr>
	                                             <tr><td>reset</td><td>Only a link to reset the eventlist filter to standard.</td><td>caption</td><td>any text</td><td>Reset</td><td>Set the caption of the link.</td></tr>
	                                         </table></small>
	                                         Find below an overview of the available filterbar display options:<br />
	                                         <small><table class="el-filterbar-table">
	                                            <th class="el-filterbar-doption">display option</th><th class="el-filterbar-desc3">description</th><th class="el-filterbar-for">available for</th></thead>
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

	'show_starttime'   => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the starttime is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the starttime.<br />
	                                            With "event_list_only" the starttime is only visible in the event list and with "single_event_only" only for a single event','eventlist')),

	'show_location'    => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the location is displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the location.<br />
	                                            With "event_list_only" the location is only visible in the event list and with "single_event_only" only for a single event','eventlist')),

	'show_cat'         => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the categories are displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the category.<br />
	                                            With "event_list_only" the categories are only visible in the event list and with "single_event_only" only for a single event','eventlist')),

	'show_details'     => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if the details are displayed in the event list.<br />
	                                            Choose "false" to always hide and "true" to always show the details.<br />
	                                            With "event_list_only" the details are only visible in the event list and with "single_event_only" only for a single event','eventlist')),

	'details_length'   => array('val'    => 'number',
	                            'desc'   => __('This attribute specifies if the details should be truncate to the given number of characters in the event list.<br />
	                                            With the standard value 0 the full details are displayed.<br />
	                                            This attribute has no influence if only a single event is shown.','eventlist')),

	'link_to_event'    => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if a link to the single event should be added onto the event name in the event list.<br />
	                                            Choose "false" to never add and "true" to always add the link.<br />
	                                            With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event','eventlist')),

	'add_feed_link'    => array('val'    => 'false<br />true<br />event_list_only<br />single_event_only',
	                            'desc'   => __('This attribute specifies if a rss feed link should be added.<br />
	                                            You have to enable the feed in the eventlist settings to make this attribute workable.<br />
	                                            On that page you can also find some settings to modify the output.<br />
	                                            Choose "false" to never add and "true" to always add the link.<br />
	                                            With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event','eventlist')),
	// Invisible attributes ('hidden' = true): This attributes are required for the widget but will not be listed in the attributes table on the admin info page
	'title_length'     => array('val'    => 'number',
	                            'hidden' => true,
	                            'desc'   => __('This attribute specifies if the title should be truncate to the given number of characters in the event list.<br />
	                                            With the standard value 0 the full details are displayed.<br />
	                                            This attribute has no influence if only a single event is shown.','eventlist')),

	'location_length'  => array('val'    => 'number',
	                            'hidden' => true,
	                            'desc'   => __('This attribute specifies if the title should be truncate to the given number of characters in the event list.<br />
	                                            With the standard value 0 the full details are displayed.<br />
	                                            This attribute has no influence if only a single event is shown.','eventlist')),

	'url_to_page'      => array('val'    => 'url',
	                            'hidden' => true,
	                            'desc'   => __('This attribute specifies that the link should follow the given url.<br />
	                                            The standard is to leave this attribute empty, then the url will be calculated automatically from the actual page or post url.<br />
	                                            This is o.k. for the normal use of the shortcode. This attribute is normally only required for the event-list widget.','eventlist')),

	'sc_id_for_url'    => array('val'    => 'number',
	                            'hidden' => true,
	                            'desc'   => __('This attribute the specifies shortcode id of the used shortcode on the page specified with "url_to_page" attribute.<br />
	                                            The empty standard value is o.k. for the normal use. This attribute is normally only required for the event-list widget.','eventlist')),
);
?>
