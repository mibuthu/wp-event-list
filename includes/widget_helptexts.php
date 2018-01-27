<?php
if(!defined('WPINC')) {
	exit;
}

$widget_items_helptexts = array(
	'title' =>                array('type'          => 'text',
	                                'caption'       => __('Title','event-list'),
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines the displayed title for the widget.','event-list'),
	                                'form_style'    => null,
	                                'form_width'    => null),

	'cat_filter' =>           array('type'          => 'text',
	                                'caption'       => __('Category Filter','event-list').':',
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines the categories of which events are shown. The standard is all or an empty string to show all events. Specify a category slug or a list of category slugs to only show events of the specified categories. See description of the shortcode attribute cat_filter for detailed info about all possibilities.','event-list'),
	                                'form_style'    => 'margin:0 0 0.8em 0',
	                                'form_width'    => null),

	'num_events' =>           array('type'          => 'text',
	                                'caption'       => __('Number of listed events','event-list').':',
	                                'caption_after' => null,
	                                'tooltip'       => __('The number of upcoming events to display','event-list'),
	                                'form_style'    => '',
	                                'form_width'    => 30),

	'title_length' =>         array('type'          => 'text',
	                                'caption'       => __('Truncate event title to','event-list'),
	                                'caption_after' => __('characters','event-list'),
	                                'tooltip'       => __('This option defines the number of displayed characters for the event title.','event-list').' '.
	                                                   sprintf(__('Set this value to %1$s to view the full text, or set it to %2$s to automatically truncate the text via css.','event-list'), '[0]', '[auto]'),
	                                'form_style'    => null,
	                                'form_width'    => 40),

	'show_starttime' =>       array('type'          => 'checkbox',
	                                'caption'       => __('Show event starttime','event-list'),
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines if the event start time will be displayed.','event-list'),
	                                'form_style'    => null,
	                                'form_width'    => null),

	'show_location' =>        array('type'          => 'checkbox',
	                                'caption'       => __('Show event location','event-list'),
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines if the event location will be displayed.','event-list'),
	                                'form_style'    => 'margin:0 0 0.2em 0',
	                                'form_width'    => null),

	'location_length' =>      array('type'          => 'text',
	                                'caption'       => __('Truncate location to','event-list'),
	                                'caption_after' => __('characters','event-list'),
	                                'tooltip'       => __('If the event location is diplayed this option defines the number of displayed characters.','event-list').' '.
	                                                   sprintf(__('Set this value to %1$s to view the full text, or set it to %2$s to automatically truncate the text via css.','event-list'), '[0]', '[auto]'),
	                                'form_style'    => 'margin:0 0 0.6em 0.9em',
	                                'form_width'    => 40),

	'show_content' =>         array('type'          => 'checkbox',
	                                'caption'       => __('Show event content','event-list'),
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines if the event content will be displayed.','event-list'),
	                                'form_style'    => 'margin:0 0 0.2em 0',
	                                'form_width'    => null),

	'content_length' =>       array('type'          => 'text',
	                                'caption'       => __('Truncate content to','event-list'),
	                                'caption_after' => __('characters','event-list'),
	                                'tooltip'       => __('If the event content are diplayed this option defines the number of diplayed characters.','event-list').' '.
	                                                   sprintf(__('Set this value to %1$s to view the full text.','event-list'), '[0]'),
	                                'form_style'    => 'margin:0 0 0.6em 0.9em',
	                                'form_width'    => 40),

	'url_to_page' =>          array('type'          => 'text',
	                                'caption'       => __('URL to the linked Event List page','event-list').':',
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines the url to the linked Event List page. This option is required if you want to use one of the options below.','event-list'),
	                                'form_style'    => 'margin:0 0 0.4em 0',
	                                'form_width'    => null),

	'sc_id_for_url' =>        array('type'          => 'text',
	                                'caption'       => __('Shortcode ID on linked page','event-list').':',
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines the shortcode-id for the Event List on the linked page. Normally the standard value 1 is correct, you only have to change it if you use multiple event-list shortcodes on the linked page.','event-list'),
	                                'form_style'    => null,
	                                'form_width'    => 35),

	'link_to_event' =>        array('type'          => 'checkbox',
	                                'caption'       => __('Add links to the single events','event-list'),
	                                'caption_after' => null,
	                                'tooltip'       => __('With this option you can add a link to the single event page for every displayed event. You have to specify the url to the page and the shortcode id option if you want to use it.','event-list'),
	                                'form_style'    => 'margin-left:0.8em',
	                                'form_width'    => null),

	'link_to_page' =>         array('type'          => 'checkbox',
	                                'caption'       => __('Add a link to the Event List page','event-list'),
	                                'caption_after' => null,
	                                'tooltip'       => __('With this option you can add a link to the event-list page below the diplayed events. You have to specify the url to page option if you want to use it.','event-list'),
	                                'form_style'    => 'margin:0 0 0.2em 0.8em',
	                                'form_width'    => null),

	'link_to_page_caption' => array('type'          => 'text',
	                                'caption'       => __('Caption for the link','event-list').':',
	                                'caption_after' => null,
	                                'tooltip'       => __('This option defines the text for the link to the Event List page if the approriate option is selected.','event-list'),
	                                'form_style'    => 'margin:0 0 1em 2.5em',
	                                'form_width'    => null),
);
?>
