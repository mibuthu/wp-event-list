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
		$args = $this->parse_args($args);
		// prepare displayed elements
		if($show_all) {
			$elements[] = $this->all_element();
		}
		if($show_upcoming) {
			$elements[] = $this->upcoming_element();
		}
		$first_year = $this->db->get_event_date('first');
		$last_year = $this->db->get_event_date('last');
		for($year=$last_year; $year>=$first_year; $year--) {
			$elements[] = array('slug'=>$year, 'name'=>$year);
		}
		// set selected year
		if(is_numeric($args['event_id'])) {
			$actual = null;
		}
		elseif('all' === $args['ytd']) {
			$actual = 'all';
		}
		elseif('upcoming' === $args['ytd']) {
			$actual = 'upcoming';
		}
		elseif(is_numeric($args['ytd'])) {
			$actual = $args['ytd'];
		}
		else {
			$actual = null;
		}
		return $this->show_hlist($elements, $url, 'ytd'.$args['sc_id_for_url'], $actual);
	}

	private function show_cats($url, $args) {
		$args = $this->parse_args($args);
		$cat_array = $this->categories->get_cat_array();
		$elements[] = $this->all_element();
		foreach($cat_array as $cat) {
			$elements[] = array('slug' => $cat['slug'], 'name' => str_pad('', 12*$cat['level'], '&nbsp;', STR_PAD_LEFT).$cat['name']);
		}
		return $this->show_combobox($elements, 'categories');
	}

	private function show_hlist($elements, $url, $query_arg_name, $actual=null) {
		$out = '';
		foreach($elements as $element) {
			if($actual == $element['slug']) {
				$out .= '<strong>'.$element['name'].'</strong>';
			}
			else {
				$out .= $this->show_url(add_query_arg($query_arg_name, $element['slug'], $url), $element['name']);
			}
			$out .= ' | ';
		}
		// remove | at the end
		$out = substr($out, 0, -3);
		return $out;
	}

	private function show_combobox($elements, $selectname, $actual=null) {
		$out = '<select name="'.$selectname.'">';
		foreach($elements as $element) {
			$out .= '
					<option';
			if($element['slug'] === $actual) {
				$out .= ' selected="selected"';
			}
			$out .= ' value="'.$element['slug'].'">'.$element['name'].'</option>';
		}
		$out .= '
				</select>';
		return $out;
	}

	private function all_element() {
		return array('slug'=>'all', 'name'=>__('All'));
	}

	private function upcoming_element() {
		return array('slug' => 'upcoming', 'name' => __('Upcoming'));
	}

	private function show_url($url, $caption) {
		return '<a href="'.$url.'">'.$caption.'</a>';
	}

	private function parse_args($args) {
		$defaults = array('ytd' => null, 'event_id' => null, 'sc_id_for_url' => null);
		$args = wp_parse_args($args, $defaults);
		$args['sc_id_for_url'] = is_numeric($args['sc_id_for_url']) ? '_'.$args['sc_id_for_url'] : '';
		return $args;
	}
}
?>
