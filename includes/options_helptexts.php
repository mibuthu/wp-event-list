<?php
if(!defined('WPINC')) {
	exit;
}

$options_helptexts = array(
	'el_db_version'         => array('section' => 'system',
	                                 'type'    => 'text'),

	'el_categories'         => array('section' => 'categories',
	                                 'type'    => 'category',
	                                 'label'   => __('Event Categories','event-list'),
	                                 'caption' => '',
	                                 'desc'    => __('This option specifies all event category data.','event-list')),

	'el_sync_cats'          => array('section' => 'categories',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('Sync Categories','event-list'),
	                                 'caption' => __('Keep event categories in sync with post categories automatically','event-list'),
	                                 'desc'    => '<table><tr style="vertical-align:top"><td><strong>'.__('Attention','event-list').':</strong></td>
	                                               <td>'.__('Please note that this option will delete all categories which are not available in the post categories! Existing Categories with the same slug will be updated.','event-list').'</td></tr></table>'),

	'el_no_event_text'      => array('section' => 'general',
	                                 'type'    => 'text',
	                                 'label'   => __('Text for no events','event-list'),
	                                 'caption' => '',
	                                 'desc'    => __('This option defines the text which is displayed if no events are available for the selected view.','event-list')),

	'el_date_once_per_day'  => array('section' => 'general',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('Date display','event-list'),
	                                 'caption' => __('Show date only once per day','event-list'),
	                                 'desc'    => __('With this option you can display the date only once per day if multiple events are available on the same day.<br />
	                                                  If this option is enabled the events are ordered in a different way (end date before start time) to allow using the same date for as much events as possible.')),

	'el_html_tags_in_time'  => array('section' => 'general',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('HTML tags','event-list'),
	                                 'caption' => __('Allow HTML tags in event time field','event-list'),
	                                 'desc'    => __('This option specifies if HTML tags are allowed in the event start time field.','event-list')),

	'el_html_tags_in_loc'   => array('section' => 'general',
	                                 'type'    => 'checkbox',
	                                 'label'   => '',
	                                 'caption' => __('Allow HTML tags in event location field','event-list'),
	                                 'desc'    => __('This option specifies if HTML tags are allowed in the event location field.','event-list')),

	'el_edit_dateformat'    => array('section' => 'admin',
	                                 'type'    => 'text',
	                                 'label'   => __('Date format in edit form','event-list'),
	                                 'desc'    => __('This option sets a specific date format for the event date fields in the new/edit event form.<br />
	                                                  The standard is an empty string to use the wordpress standard setting.<br />
	                                                  All available options to specify the format can be found <a href="http://php.net/manual/en/function.date.php" target="_blank">here</a>')),

	'el_enable_feed'        => array('section' => 'feed',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('Enable RSS feed','event-list'),
	                                 'caption' => __('Enable support for an event RSS feed','event-list'),
	                                 'desc'    => __('This option activates a RSS feed for the events.<br />
	                                                  You have to enable this option if you want to use one of the RSS feed features.')),

	'el_feed_name'          => array('section' => 'feed',
	                                 'type'    => 'text',
	                                 'label'   => __('Feed name','event-list'),
	                                 'desc'    => __('This options sets the feed name. The standard value is "event-list".<br />
	                                                  This name will be used in the feed url (e.g. <code>domain.com/?feed=event-list</code> or <code>domain.com/feed/eventlist</code> for an installation with permalinks')),

	'el_feed_description'   => array('section' => 'feed',
	                                 'type'    => 'text',
	                                 'label'   => __('Feed Description','event-list'),
	                                 'desc'    => __('This options sets the feed description. The standard value is "Eventlist Feed".<br />
	                                                  This description will be used in the title for the feed link in the html head and for the description in the feed itself.')),

	'el_feed_upcoming_only' => array('section' => 'feed',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('Listed events','event-list'),
	                                 'caption' => __('Only show upcoming events in feed','event-list'),
	                                 'desc'    => __('If this option is enabled only the upcoming events are listed in the feed.<br />
	                                                  If disabled all events (upcoming and past) will be listed.')),

	'el_head_feed_link'     => array('section' => 'feed',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('Add RSS feed link in head','event-list'),
	                                 'caption' => __('Add RSS feed link in the html head','event-list'),
	                                 'desc'    => __('This option adds a RSS feed in the html head for the events.<br />
	                                                  You have 2 possibilities to include the RSS feed:<br />
	                                                  The first option is to use this option to include a link in the html head. This link will be recognized by browers or feed readers.<br />
	                                                  The second possibility is to include a visible feed link directly in the event list. This can be done by setting the shortcode attribute "add_feed_link" to "true"<br />
	                                                  This option is only valid if the option "Enable RSS feed" is enabled.')),

	'el_feed_link_pos'      => array('section' => 'feed',
	                                 'type'    => 'radio',
	                                 'label'   => __('Position of the RSS feed link','event-list'),
	                                 'caption' => array('top' => 'at the top (above the navigation bar)', 'below_nav' => 'between navigation bar and events', 'bottom' => 'at the bottom'),
	                                 'desc'    => __('This option specifies the position of the RSS feed link in the event list.<br />
	                                                  The options are to display the link at the top, at the bottom or between the navigation bar and the event list.<br />
	                                                  You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),

	'el_feed_link_align'    => array('section' => 'feed',
	                                 'type'    => 'radio',
	                                 'label'   => __('Align of the RSS feed link','event-list'),
	                                 'caption' => array('left' => 'left', 'center' => 'center', 'right' => 'right'),
	                                 'desc'    => __('This option specifies the align of the RSS feed link in the event list.<br />
	                                                  The link can be displayed on the left side, centered or on the right.<br />
	                                                  You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),

	'el_feed_link_text'     => array('section' => 'feed',
	                                 'type'    => 'text',
	                                 'label'   => __('Feed link text','event-list'),
	                                 'desc'    => __('This option specifies the caption of the RSS feed link in the event list.<br />
	                                                  Insert an empty text to hide any text if you only want to show the rss image.<br />
	                                                  You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),

	'el_feed_link_img'      => array('section' => 'feed',
	                                 'type'    => 'checkbox',
	                                 'label'   => __('Feed link image','event-list'),
	                                 'caption' => __('Show rss image in feed link','event-list'),
	                                 'desc'    => __('This option specifies if the an image should be dispayed in the feed link in front of the text.<br />
	                                                  You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),
);

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
