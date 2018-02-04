<?php
if(!defined('WPINC')) {
	exit;
}

$options_helptexts = array(

	// Section: "import"
	'el_import_file'          => array('type'    => 'file-upload',
	                                   'label'   => __('CSV File to import','event-list'),
	                                   'maxsize' => 204800,
	                                   'desc'    => __('Please select the file which contains the event data in CSV format.','event-list')),

	'el_import_date_format'   => array('type'    => 'text',
	                                   'label'   => __('Used date format','event-list'),
	                                   'caption' => '',
	                                   'desc'    => __('With this option the used date format for event start and end date given in the CSV file can be specified.','event-list')),

	// Section: "general"
	'el_no_event_text'        => array('type'    => 'text',
	                                   'label'   => __('Text for no events','event-list'),
	                                   'caption' => '',
	                                   'desc'    => __('This option defines the displayed text when no events are available for the selected view.','event-list')),

	'el_multiday_filterrange' => array('type'    => 'checkbox',
	                                   'label'   => __('Multiday filter range','event-list'),
	                                   'caption' => __('Use the complete event range in the date filter','event-list'),
	                                   'desc'    => __('This option defines if the complete range of a multiday event shall be considered in the date filter.','event-list').'<br />'.
	                                                __('If disabled, only the start day of an event is considered in the filter.','event-list').'<br />'.
	                                                __('For an example multiday event which started yesterday and ends tomorrow this means, that it is displayed in umcoming dates when this option is enabled, but it is hidden when the option is disabled.','event-list')),

	'el_date_once_per_day'    => array('type'    => 'checkbox',
	                                   'label'   => __('Date display','event-list'),
	                                   'caption' => __('Show the date only once per day','event-list'),
	                                   'desc'    => __('With this option enabled the date is only displayed once per day if more than one event is available on the same day.','event-list').'<br />'.
	                                                __('If enabled, the events are ordered in a different way (end date before start time) to allow using the same date for as much events as possible.','event-list')),

	'el_html_tags_in_time'    => array('type'    => 'checkbox',
	                                   'label'   => __('HTML tags','event-list'),
	                                   'caption' => sprintf(__('Allow HTML tags in the event field "%1$s"','event-list'), __('Time','event-list')),
	                                   'desc'    => sprintf(__('This option specifies if HTML tags are allowed in the event field "%1$s".','event-list'), __('Time','event-list'))),

	'el_html_tags_in_loc'     => array('type'    => 'checkbox',
	                                   'label'   => '',
	                                   'caption' => sprintf(__('Allow HTML tags in the event field "%1$s"','event-list'), __('Location','event-list')),
	                                   'desc'    => sprintf(__('This option specifies if HTML tags are allowed in the event field "%1$s".','event-list'), __('Location','event-list'))),

	'el_mo_lang_dir_first'    => array('type'    => 'checkbox',
	                                   'label'   => __('Preferred language file','event-list'),
	                                   'caption' => __('Load translations from general language directory first','event-list'),
	                                   'desc'    => sprintf(__('The default is to load the %1$s translation file from the plugin language directory first (%2$s).','event-list'), '<code>*.mo</code>', '<code>wp-content/plugins/event-list/languages/</code>').'<br />
	                                                '.sprintf(__('If you want to load your own language file from the general language directory %1$s for a language which is already included in the plugin language directory, you have to enable this option.','event-list'), '<code>wp-content/languages/plugins/</code>')),

	// Section: "frontend"
	'el_permalink_slug'       => array('type'    => 'text',
	                                   'label'   => __('Events permalink slug','event-list'),
	                                   'desc'    => __('With this option the slug for the events permalink URLs can be defined.','event-list')),

	'el_content_show_text'    => array('type'    => 'text',
	                                   'label'   => __('Text for "Show content"','event-list'),
	                                   'desc'    => __('With this option the displayed text for the link to show the event content can be changed, when collapsing is enabled.','event-list')),

	'el_content_hide_text'    => array('type'    => 'text',
	                                   'label'   => __('Text for "Hide content"','event-list'),
	                                   'desc'    => __('With this option the displayed text for the link to hide the event content can be changed, when collapsing is enabled.','event-list')),

	'el_disable_css_file'     => array('type'    => 'checkbox',
	                                   'label'   => __('Disable CSS file','event-list'),
	                                   'caption' => sprintf(__('Disable the %1$s file.','event-list'), '"event-list.css"'),
	                                   'desc'    => sprintf(__('With this option you can disable the inclusion of the %1$s file.','event-list'), '"event-list.css"').'<br />'.
	                                                __('This normally only make sense if you have css conflicts with your theme and want to set all required css styles somewhere else (e.g. in the theme css).','event-list')),

	// Section: "admin"
	'el_edit_dateformat'      => array('type'    => 'text',
	                                   'label'   => __('Date format in edit form','event-list'),
	                                   'desc'    => __('This option sets the displayed date format for the event date fields in the event new / edit form.','event-list').'<br />'.
	                                                __('The default is an empty string to use the Wordpress standard setting.','event-list').'<br />'.
	                                                sprintf(__('All available options to specify the date format can be found %1$shere%2$s.','event-list'), '<a href="http://php.net/manual/en/function.date.php" target="_blank" rel="noopener">', '</a>')),

	// Section: "feed"
	'el_enable_feed'          => array('type'    => 'checkbox',
	                                   'label'   => __('Enable RSS feed','event-list'),
	                                   'caption' => __('Enable support for an event RSS feed','event-list'),
	                                   'desc'    => __('This option activates a RSS feed for the events.','event-list').'<br />
	                                                '.__('You have to enable this option if you want to use one of the RSS feed features.','event-list')),

	'el_feed_name'            => array('type'    => 'text',
	                                   'label'   => __('Feed name','event-list'),
	                                   'desc'    => sprintf(__('This option sets the feed name. The default value is %1$s.','event-list'), '"event-list"').'<br />
	                                                '.sprintf(__('This name will be used in the feed url (e.g. %1$s, or %2$s with permalinks enabled).','event-list'), '<code>domain.com/?feed=event-list</code>', '<code>domain.com/feed/eventlist</code>')),

	'el_feed_description'     => array('type'    => 'text',
	                                   'label'   => __('Feed Description','event-list'),
	                                   'desc'    => sprintf(__('This options set the feed description. The default value is %1$s.','event-list'), '"Eventlist Feed"').'<br />
	                                                '.__('This description will be used in the title for the feed link in the html head and for the description in the feed itself.','event-list')),

	'el_feed_upcoming_only'   => array('type'    => 'checkbox',
	                                   'label'   => __('Listed events','event-list'),
	                                   'caption' => __('Only show upcoming events in feed','event-list'),
	                                   'desc'    => __('If this option is enabled only the upcoming events are listed in the feed.','event-list').'<br />
	                                                '.__('If disabled all events (upcoming and past) will be listed.','event-list')),

	'el_head_feed_link'       => array('type'    => 'checkbox',
	                                   'label'   => __('Add RSS feed link in head','event-list'),
	                                   'caption' => __('Add RSS feed link in the html head','event-list'),
	                                   'desc'    => __('This option adds a RSS feed in the html head for the events.','event-list').'<br />
	                                                '.__('There are 2 alternatives to include the RSS feed','event-list').':<br />
	                                                '.__('The first way is this option to include a link in the html head. This link will be recognized by browers or feed readers.','event-list').'<br />
	                                                '.sprintf(__('The second way is to include a visible feed link directly in the event list. This can be done by setting the shortcode attribute %1$s to %2$s.','event-list'), '<code>add_feed_link</code>', '"true"').'<br />
	                                                '.sprintf(__('This option is only valid if the setting %1$s is enabled.','event-list'), '"'.__('Enable RSS feed','event-list').'"')),

	'el_feed_link_pos'        => array('type'    => 'radio',
	                                   'label'   => __('Position of the RSS feed link','event-list'),
	                                   'caption' => array('top' => __('at the top (above the navigation bar)','event-list'), 'below_nav' => __('between navigation bar and events','event-list'), 'bottom' => __('at the bottom','event-list')),
	                                   'desc'    => __('This option specifies the position of the RSS feed link in the event list.','event-list').'<br />
	                                                '.sprintf(__('You have to set the shortcode attribute %1$s to %2$s if you want to show the feed link.','event-list'), '<code>add_feed_link</code>', '"true"')),

	'el_feed_link_align'      => array('type'    => 'radio',
	                                   'label'   => __('Align of the RSS feed link','event-list'),
	                                   'caption' => array('left' => __('left','event-list'), 'center' => __('center','event-list'), 'right' => __('right','event-list')),
	                                   'desc'    => __('This option specifies the align of the RSS feed link in the event list.','event-list').'<br />
	                                                '.sprintf(__('You have to set the shortcode attribute %1$s to %2$s if you want to show the feed link.','event-list'), '<code>add_feed_link</code>', '"true"')),

	'el_feed_link_text'       => array('type'    => 'text',
	                                   'label'   => __('Feed link text','event-list'),
	                                   'desc'    => __('This option specifies the caption of the RSS feed link in the event list.','event-list').'<br />
	                                                '.__('Insert an empty text to hide any text if you only want to show the rss image.','event-list').'<br />
	                                                '.sprintf(__('You have to set the shortcode attribute %1$s to %2$s if you want to show the feed link.','event-list'), '<code>add_feed_link</code>', '"true"')),

	'el_feed_link_img'        => array('type'    => 'checkbox',
	                                   'label'   => __('Feed link image','event-list'),
	                                   'caption' => __('Show rss image in feed link','event-list'),
	                                   'desc'    => __('This option specifies if the an image should be dispayed in the feed link in front of the text.','event-list').'<br />
	                                                '.sprintf(__('You have to set the shortcode attribute %1$s to %2$s if you want to show the feed link.','event-list'), '<code>add_feed_link</code>', '"true"')),

	// Section: taxonomy
	'el_use_post_cats'        => array('type'    => 'checkbox',
	                                   'disable' => true,
	                                   'label'   => __('Event Category handling','event-list'),
	                                   'caption' => __('Use Post Categories','event-list'),
	                                   'desc'    => __('Do not maintain seperate categories for the events, and use the existing post categories instead.','event-list').'<br /><br />
	                                                <strong>'.__('Attention','event-list').':</strong><br />
	                                                '.__('This option cannot be changed directly, but you can go to the Event Category switching page from here.','event-list')),
);
?>
