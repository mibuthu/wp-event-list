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
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->group = 'event-list';
		add_action('init', array(&$this, 'init_options'), 1);
		add_action('admin_init', array(&$this, 'load_options_helptexts'), 2);
		add_action('admin_init', array(&$this, 'register_options'));
	}

	public function init_options() {
		$this->options = array(
			'el_db_version'         => array('std_val' => ''),
			'el_categories'         => array('std_val' => null),
			'el_sync_cats'          => array('std_val' => ''),
			'el_no_event_text'      => array('std_val' => 'no event'),
			'el_date_once_per_day'  => array('std_val' => ''),
			'el_html_tags_in_time'  => array('std_val' => ''),
			'el_html_tags_in_loc'   => array('std_val' => ''),
			'el_edit_dateformat'    => array('std_val' => ''),
			'el_enable_feed'        => array('std_val' => ''),
			'el_feed_name'          => array('std_val' => 'eventlist'),
			'el_feed_description'   => array('std_val' => 'Eventlist Feed'),
			'el_feed_upcoming_only' => array('std_val' => ''),
			'el_head_feed_link'     => array('std_val' => '1'),
			'el_feed_link_pos'      => array('std_val' => 'bottom'),
			'el_feed_link_align'    => array('std_val' => 'left'),
			'el_feed_link_text'     => array('std_val' => 'RSS Feed'),
			'el_feed_link_img'      => array('std_val' => '1'),
		);

		$this->date_formats = array(
			'year'         => array('name'  => 'Year',
			                        'regex' => '^[12]\d{3}$',
			                        'examp' => '2015',
			                        'start' => '%v%-01-01',
			                        'end'   => '%v%-12-31'),
			'month'        => array('name'  => 'Month',
			                        'regex' => '^[12]\d{3}-(0[1-9]|1[012])$',
			                        'examp' => '2015-03',
			                        'start' => '%v%-01',
			                        'end'   => '%v%-31'),
			'day'          => array('name'  => 'Day',
			                        'regex' => '^[12]\d{3}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$',
			                        'examp' => '2015-03-29',
			                        'start' => '%v%',
			                        'end'   => '%v%'),
		);

		$this->daterange_formats = array(
			'date_range'   => array('name'  => 'Date range',
			                        'regex' => '.+~.+',
			                        'examp' => '2015-03-29~2016'),
			'all'          => array('name'  => 'All',
			                        'regex' => '^all$',
			                        'value' => 'all',
			                        'start' => '1000-01-01',
			                        'end'   => '2999-12-31'),
			'upcoming'     => array('name'  => 'Upcoming',
			                        'regex' => '^upcoming$',
			                        'value' => 'upcoming',
			                        'start' => '--func--date("Y-m-d", current_time("timestamp"));',
			                        'end'   => '2999-12-31'),
			'past'         => array('name'  => 'Past',
			                        'regex' => '^past$',
			                        'value' => 'past',
			                        'start' => '1000-01-01',
			                        'end'   => '--func--date("Y-m-d", current_time("timestamp")-86400);'),  // previous day (86400 seconds = 1*24*60*60 = 1 day
		);
	}

	public function load_options_helptexts() {
		require_once(EL_PATH.'includes/options_helptexts.php');
		foreach($options_helptexts as $name => $values) {
			$this->options[$name] = array_merge($this->options[$name], $values);
		}
		unset($options_helptexts);
		foreach($date_formats_desc as $name => $value) {
			$this->date_formats[$name]['desc'] = $value;
		}
		unset($date_formats_desc);
		foreach($daterange_formats_desc as $name => $value) {
			$this->daterange_formats[$name]['desc'] = $value;
		}
		unset($daterange_formats_desc);
	}

	public function register_options() {
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
