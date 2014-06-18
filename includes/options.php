<?php
if(!defined('ABSPATH')) {
	exit;
}

// This class handles all available options
class EL_Options {

	private static $instance;
	public $group;
	public $options;
	public $date_formats;
	public $daterange_formats;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Options();
			self::$instance->init();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->group = 'event-list';

		$this->options = array(
			'el_db_version'         => array('section' => 'system',
			                                 'type'    => 'text',
			                                 'std_val' => '',
			                                 'label'   => '',
			                                 'caption' => '',
			                                 'desc'    => __('Database version')),

			'el_categories'         => array('section' => 'categories',
			                                 'type'    => 'category',
			                                 'std_val' => null,
			                                 'label'   => __('Event Categories'),
			                                 'caption' => '',
			                                 'desc'    => __('This option specifies all event category data.')),

			'el_sync_cats'          => array('section' => 'categories',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => __('Sync Categories'),
			                                 'caption' => __('Keep event categories in sync with post categories automatically'),
			                                 'desc'    => '<table><tr style="vertical-align:top"><td><strong>'.__('Attention').':</strong></td>
			                                               <td>'.__('Please note that this option will delete all categories which are not available in the post categories! Existing Categories with the same slug will be updated.').'</td></tr></table>'),

			'el_no_event_text'      => array('section' => 'general',
			                                 'type'    => 'text',
			                                 'std_val' => 'no event',
			                                 'label'   => __('Text for no events'),
			                                 'caption' => '',
			                                 'desc'    => __('This option defines the text which is displayed if no events are available for the selected view.')),

			'el_date_once_per_day'  => array('section' => 'general',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => __('Date display'),
			                                 'caption' => __('Show date only once per day'),
			                                 'desc'    => __('With this option you can display the date only once per day if multiple events are available on the same day.<br />
			                                                  If this option is enabled the events are ordered in a different way (end date before start time) to allow using the same date for as much events as possible.')),

			'el_html_tags_in_time'  => array('section' => 'general',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => __('HTML tags'),
			                                 'caption' => __('Allow HTML tags in event time field'),
			                                 'desc'    => __('This option specifies if HTML tags are allowed in the event start time field.')),

			'el_html_tags_in_loc'   => array('section' => 'general',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => '', // only one label for all html tags settings
			                                 'caption' => __('Allow HTML tags in event location field'),
			                                 'desc'    => __('This option specifies if HTML tags are allowed in the event location field.')),

			'el_edit_dateformat'    => array('section' => 'admin',
			                                 'type'    => 'text',
			                                 'std_val' => '',
			                                 'label'   => __('Date format in edit form'),
			                                 'caption' => __('Specific date format in new/edit event form'),
			                                 'desc'    => __('This option sets a specific date format for the event date fields in the new/edit event form.<br />
			                                                  The standard is an empty string to use the wordpress standard setting.<br />
			                                                  All available options to specify the format can be found <a href="http://php.net/manual/en/function.date.php" target="_blank">here</a>')),

			'el_enable_feed'        => array('section' => 'feed',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => __('Enable RSS feed'),
			                                 'caption' => __('Enable support for an event RSS feed'),
			                                 'desc'    => __('This option activates a RSS feed for the events.<br />
			                                                  You have to enable this option if you want to use one of the RSS feed features.')),

			'el_feed_upcoming_only' => array('section' => 'feed',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => __('Listed events'),
			                                 'caption' => __('Only show upcoming events in feed'),
			                                 'desc'    => __('If this option is enabled only the upcoming events are listed in the feed.<br />
			                                                  If disabled all events (upcoming and past) will be listed.')),

			'el_head_feed_link'     => array('section' => 'feed',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => __('Add RSS feed link in head'),
			                                 'caption' => __('Add RSS feed link in the html head'),
			                                 'desc'    => __('This option adds a RSS feed in the html head for the events.<br />
			                                                  You have 2 possibilities to include the RSS feed:<br />
			                                                  The first option is to use this option to include a link in the html head. This link will be recognized by browers or feed readers.<br />
			                                                  The second possibility is to include a visible feed link directly in the event list. This can be done by setting the shortcode attribute "add_feed_link" to "true"<br />
			                                                  This option is only valid if the option "Enable RSS feed" is enabled.')),

			'el_feed_link_pos'      => array('section' => 'feed',
			                                 'type'    => 'radio',
			                                 'std_val' => 'bottom',
			                                 'label'   => __('Position of the RSS feed link'),
			                                 'caption' => array('top' => 'at the top (above the navigation bar)', 'below_nav' => 'between navigation bar and events', 'bottom' => 'at the bottom'),
			                                 'desc'    => __('This option specifies the position of the RSS feed link in the event list.<br />
			                                                  The options are to display the link at the top, at the bottom or between the navigation bar and the event list.<br />
			                                                  You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),

			'el_feed_link_align'   => array('section' => 'feed',
			                                'type'    => 'radio',
			                                'std_val' => 'left',
			                                'label'   => __('Align of the RSS feed link'),
			                                'caption' => array('left' => 'left', 'center' => 'center', 'right' => 'right'),
			                                'desc'    => __('This option specifies the align of the RSS feed link in the event list.<br />
			                                                 The link can be displayed on the left side, centered or on the right.<br />
			                                                 You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),

			'el_feed_link_text'    => array('section' => 'feed',
			                                'type'    => 'text',
			                                'std_val' => 'RSS Feed',
			                                'label'   => __('Feed link text'),
			                                'desc'    => __('This option specifies the caption of the RSS feed link in the event list.<br />
			                                                 Insert an empty text to hide any text if you only want to show the rss image.<br />
			                                                 You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),

			'el_feed_link_img'     => array('section' => 'feed',
			                                'type'    => 'checkbox',
			                                'std_val' => '1',
			                                'label'   => __('Feed link image'),
			                                'caption' => __('Show rss image in feed link'),
			                                'desc'    => __('This option specifies if the an image should be dispayed in the feed link in front of the text.<br />
			                                                 You have to set the shortcode attribute "add_feed_link" to "true" if you want to show the feed link.')),
		);

		$this->date_formats = array(
			'year'         => array('name'  => 'Year',
			                        'regex' => '^[12]\d{3}$',
			                        'examp' => '2015',
			                        'start' => '%v%-01-01',
			                        'end'   => '%v%-12-31',
			                        'desc'  => __('You can specify a year in 4 digit format.<br /> Other formats will not be accepted.')),
			'month'        => array('name'  => 'Month',
			                        'regex' => '^[12]\d{3}-(0[1-9]|1[012])$',
			                        'examp' => '2015-03',
			                        'start' => '%v%-01',
			                        'end'   => '%v%-31',
			                        'desc'  => __('You can specify a month with 4 digits for the year and 2 digits for the month, seperated by a hyphen (-).<br />Other formats will not be accepted.')),
			'day'          => array('name'  => 'Day',
			                        'regex' => '^[12]\d{3}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$',
			                        'examp' => '2015-03-29',
			                        'start' => '%v%',
			                        'end'   => '%v%',
			                        'desc'  => __('You can specify a day with 4 digits for the year, 2 digits for the month and 2 digets for the day, seperated by a hyphen (-).<br /> Other formats will not be accepted.')),
		);

		$this->daterange_formats = array(
			'date_range'   => array('name'  => 'Date range',
			                        'regex' => '.+~.+',
			                        'examp' => '2015-03-29~2016',
			                        'desc'  => __('You can specify a rage or dates seperated by a tilde (~).<br >You can specify any available date format before and after the tilde.')),
			'all'          => array('name'  => 'All',
			                        'regex' => '^all$',
			                        'value' => 'all',
			                        'start' => '1000-01-01',
			                        'end'   => '2999-12-31',
			                        'desc'  => __('"all" specifies the full time range without any limitation.')),
			'upcoming'     => array('name'  => 'Upcoming',
			                        'regex' => '^upcoming$',
			                        'value' => 'upcoming',
			                        'start' => '--func--date("Y-m-d", current_time("timestamp"));',
			                        'end'   => '2999-12-31',
			                        'desc'  => __('"upcoming" specifies a time range from the actual day to the future.')),
			'past'         => array('name'  => 'Past',
			                        'regex' => '^past$',
			                        'value' => 'past',
			                        'start' => '1000-01-01',
			                        'end'   => '--func--date("Y-m-d", current_time("timestamp")-86400);',  // previous day (86400 seconds = 1*24*60*60 = 1 day
			                        'desc'  => __('"past" specifies a time rage from the past to the previous day.')),
		);
	}

	public function init() {
		add_action('admin_init', array(&$this, 'register'));
	}

	public function register() {
		foreach($this->options as $oname => $o) {
			register_setting('el_'.$o['section'], $oname);
		}
	}

	public function set($name, $value) {
		if(isset($this->options[$name])) {
			return update_option($name, $value);
		}
		else {
			return false;
		}
	}

	public function get($name) {
		if(isset($this->options[$name])) {
			return get_option($name, $this->options[$name]['std_val']);
		}
		else {
			return null;
		}
	}
}
?>
