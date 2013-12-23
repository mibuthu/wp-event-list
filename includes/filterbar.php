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
		$out .= $this->show_years($url, $args, 'dropdown');
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

	public function show_years($url, $args, $type='hlist', $subtype='std', $show_all=true, $show_upcoming=true) {
		$args = $this->parse_args($args);
		$argname = 'ytd'.$args['sc_id_for_url'];
		// prepare displayed elements
		if($show_all) {
			$elements[] = $this->all_element('hlist'==$type ? null : __('Show all dates'));
		}
		if($show_upcoming) {
			$elements[] = $this->upcoming_element();
		}
		$first_year = $this->db->get_event_date('first');
		$last_year = $this->db->get_event_date('last');
		for($year=$last_year; $year>=$first_year; $year--) {
			$elements[] = array('slug'=>$year, 'name'=>$year);
		}
		// set selection
		if(is_numeric($args['event_id'])) {
			$actual = null;
		}
		elseif('all' === $args['actual_date']) {
			$actual = 'all';
		}
		elseif('upcoming' === $args['actual_date']) {
			$actual = 'upcoming';
		}
		elseif(is_numeric($args['actual_date'])) {
			$actual = $args['actual_date'];
		}
		else {
			$actual = null;
		}
		if('dropdown' === $type) {
			return $this->show_dropdown($elements, $argname, $subtype, $actual, $args['sc_id_for_url']);
		}
		else {
			return $this->show_hlist($elements, $url, $argname, $actual);
		}
	}

	public function show_cats($url, $args, $type='dropdown', $subtype='std') {
		$args = $this->parse_args($args);
		$argname = 'cat'.$args['sc_id_for_url'];
		// prepare displayed elements
		$cat_array = $this->categories->get_cat_array();
		$elements[] = $this->all_element('hlist'==$type ? null : __('View all categories'));
		foreach($cat_array as $cat) {
			$elements[] = array('slug' => $cat['slug'], 'name' => str_pad('', 12*$cat['level'], '&nbsp;', STR_PAD_LEFT).$cat['name']);
		}
		// set selection
		$actual = isset($args['actual_cat']) ? $args['actual_cat'] : null;
		if('hlist' === $type) {
			return $this->show_hlist($elements, $url, $argname, $actual);
		}
		else {
			return $this->show_dropdown($elements, $argname, $subtype, $actual, $args['sc_id_for_url']);
		}
	}

	private function show_hlist($elements, $url, $name, $actual=null) {
		$out = '';
		foreach($elements as $element) {
			if($actual == $element['slug']) {
				$out .= '<strong>'.$element['name'].'</strong>';
			}
			else {
				$out .= $this->show_url(add_query_arg($name, $element['slug'], $url), $element['name']);
			}
			$out .= ' | ';
		}
		// remove | at the end
		$out = substr($out, 0, -3);
		return $out;
	}

	private function show_dropdown($elements, $name, $subtype='std', $actual=null, $sc_id='') {
		$onchange = '';
		if('admin' != $subtype) {
			wp_register_script('el_filterbar', EL_URL.'includes/js/filterbar.js', null, true);
			add_action('wp_footer', array(&$this, 'footer_script'));
			$onchange = ' onchange="eventlist_redirect(this.name,this.value,'.$sc_id.')"';
		}
		$out = '<select name="'.$name.'"'.$onchange.'>';
		foreach($elements as $element) {
			$out .= '
					<option';
			if($element['slug'] == $actual) {
				$out .= ' selected="selected"';
			}
			$out .= ' value="'.$element['slug'].'">'.$element['name'].'</option>';
		}
		$out .= '
				</select>';
		return $out;
	}

	private function all_element($name=null) {
		if(null == $name) {
			$name = __('All');
		}
		return array('slug' => 'all', 'name' => $name);
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
		return $args;
	}

	public function footer_script() {
		wp_print_scripts('el_filterbar');
	}
}
?>
