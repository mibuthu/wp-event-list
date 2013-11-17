<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once( EL_PATH.'includes/db.php' );
//require_once( EL_PATH.'includes/options.php' );
//require_once( EL_PATH.'includes/categories.php' );

// This class handles the navigation and filter bar
class EL_Navbar {
	private static $instance;
	private $db;
//	private $options;
//	private $categories;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new EL_Navbar();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
//		$this->options = &EL_Options::get_instance();
//		$this->categories = &EL_Categories::get_instance();
	}

	// main function to show the rendered HTML output
	public function show($url, $args) {
		$defaults = array('ytd'=>null, 'event_id'=>null, 'sc_id_for_url'=>null);
		$args = wp_parse_args($args, $defaults);
		$args['sc_id_for_url'] = is_numeric($args['sc_id_for_url']) ? '_'.$args['sc_id_for_url'] : '';
		$first_year = $this->db->get_event_date('first');
		$last_year = $this->db->get_event_date('last');
		$out = '<div class="navbar subsubsub">';
		if('all' != $args['ytd'] || is_numeric($args['event_id'])) {
			$out .= '<a href="'.add_query_arg('ytd'.$args['sc_id_for_url'], 'all', $url).'">'.__('All').'</a> | ';
		}
		else {
			$out .= '<strong>All</strong> | ';
		}
		if('upcoming' != $args['ytd'] || is_numeric($args['event_id'])) {
			$out .= '<a href="'.add_query_arg('ytd'.$args['sc_id_for_url'], 'upcoming', $url).'">'.__('Upcoming').'</a>';
		}
		else {
			$out .= '<strong>Upcoming</strong>';
		}
		for($year=$last_year; $year>=$first_year; $year--) {
			$out .= ' | ';
			if($year == $args['ytd']) {
				$out .= '<strong>'.$year.'</strong>';
			}
			else {
				$out .= '<a href="'.add_query_arg('ytd'.$args['sc_id_for_url'], $year, $url).'">'.$year.'</a>';
			}
		}
		$out .= '</div><br />';
		return $out;
	}
}
?>
