<?php
if(!defined('WPINC')) {
	exit;
}

// This class handles all available options
class EL_Options {

	private static $instance;
	public $group;
	public $options;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->group = 'event-list';
		add_action('init', array(&$this, 'init_options'), 1);
		add_action('admin_init', array(&$this, 'register_options'));
	}

	public function init_options() {
		$this->options = array(
			'el_db_version'           => array('std_val' => '',               'section' => 'system'),

			'el_categories'           => array('std_val' => null,             'section' => 'categories'),
			'el_sync_cats'            => array('std_val' => '',               'section' => 'categories'),

			'el_no_event_text'        => array('std_val' => 'no event',       'section' => 'general'),
			'el_multiday_filterrange' => array('std_val' => '1',              'section' => 'general'),
			'el_date_once_per_day'    => array('std_val' => '',               'section' => 'general'),
			'el_html_tags_in_time'    => array('std_val' => '',               'section' => 'general'),
			'el_html_tags_in_loc'     => array('std_val' => '',               'section' => 'general'),

			'el_disable_css_file'     => array('std_val' => '',               'section' => 'frontend'),

			'el_edit_dateformat'      => array('std_val' => '',               'section' => 'admin'),

			'el_enable_feed'          => array('std_val' => '',               'section' => 'feed'),
			'el_feed_name'            => array('std_val' => 'event-list',     'section' => 'feed'),
			'el_feed_description'     => array('std_val' => 'Eventlist Feed', 'section' => 'feed'),
			'el_feed_upcoming_only'   => array('std_val' => '',               'section' => 'feed'),
			'el_head_feed_link'       => array('std_val' => '1',              'section' => 'feed'),
			'el_feed_link_pos'        => array('std_val' => 'bottom',         'section' => 'feed'),
			'el_feed_link_align'      => array('std_val' => 'left',           'section' => 'feed'),
			'el_feed_link_text'       => array('std_val' => 'RSS Feed',       'section' => 'feed'),
			'el_feed_link_img'        => array('std_val' => '1',              'section' => 'feed'),
		);
	}

	public function load_options_helptexts() {
		require_once(EL_PATH.'includes/options_helptexts.php');
		foreach($options_helptexts as $name => $values) {
			$this->options[$name] += $values;
		}
		unset($options_helptexts);
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
