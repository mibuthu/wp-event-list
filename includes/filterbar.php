<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once( EL_PATH.'includes/db.php' );
//require_once( EL_PATH.'includes/options.php' );
require_once( EL_PATH.'includes/categories.php' );

// This class handles the navigation and filter bar
class EL_Filterbar {
	private static $instance;
	private $db;
//	private $options;
	private $categories;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new EL_Filterbar();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
//		$this->options = &EL_Options::get_instance();
		$this->categories = &EL_Categories::get_instance();
	}

	// main function to show the rendered HTML output
	public function show($url, $args) {
		$defaults = array('ytd'=>null, 'event_id'=>null, 'sc_id_for_url'=>null);
		$args = wp_parse_args($args, $defaults);
		$args['sc_id_for_url'] = is_numeric($args['sc_id_for_url']) ? '_'.$args['sc_id_for_url'] : '';
		$out = '<div class="filterbar subsubsub">';
		$out .= $this->show_years($url, $args);
		$out .= $this->show_cats($url, $args);
		$out .= '</div><br />';
		return $out;
	}

	private function show_all() {
		$elements[] = $this->all_element();
		return $this->show_hlist($elements);
	}

	private function show_upcoming() {
		$elements[] = $this->upcoming_element();
		return $this->show_hlist($elements);
	}

	private function show_years($url, $args, $show_all=true, $show_upcoming=true) {
		$ytd = 'ytd'.$args['sc_id_for_url'];
		// prepare displayed elements
		if($show_all) {
			$elements[__('All')] = ('all' != $args['ytd'] && !is_numeric($args['event_id'])) ? add_query_arg($ytd, 'all', $url) : null;
		}
		if($show_upcoming) {
			$elements[__('Upcoming')] = ('upcoming' != $args['ytd'] && !is_numeric($args['event_id'])) ? add_query_arg($ytd, 'upcoming', $url) : null;
		}
		$first_year = $this->db->get_event_date('first');
		$last_year = $this->db->get_event_date('last');
		for($year=$last_year; $year>=$first_year; $year--) {
			$elements[$year] = add_query_arg($ytd, $year, $url);
		}
		// remove link from actual element
		if(is_numeric($args['ytd']) && !is_numeric($args['event_id'])) {
			$elements[$args['ytd']] = null;
		}
		return $this->show_hlist($elements);
	}

	private function show_cats($url, $args) {
		$cat_array = $this->categories->get_cat_array();
		$elements['all'] = __('All');
		foreach($cat_array as $cat) {
			$elements[$cat['slug']] = str_pad('', 12*$cat['level'], '&nbsp;', STR_PAD_LEFT).$cat['name'];
		}
		return $this->show_combobox($elements, 'categories');
	}

	private function show_hlist($elements) {
		$out = '';
		foreach($elements as $name=>$url) {
			if(null === $url) {
				$out .= '<strong>'.$name.'</strong>';
			}
			else {
				$out .= $this->show_url($url, $name);
			}
			$out .= ' | ';
		}
		// remove | at the end
		$out = substr($out, 0, -3);
		return $out;
	}

	private function show_combobox($elements, $selectname) {
		$out = '<select name="'.$selectname.'">';
		foreach($elements as $name=>$url) {
			$out .= '
					<option';
			if(null === $url) {
				$out .= ' selected="selected"';
			}
			$out .= ' value="'.$name.'">'.$url.'</option>';
		}
		$out .= '
				</select>';
		return $out;
	}

	private function show_url($url, $caption) {
		return '<a href="'.$url.'">'.$caption.'</a>';
	}
}
?>
