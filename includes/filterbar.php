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
	public function show($url, &$args) {
		$out = '
				<style type="text/css">
					.filterbar { display:table; width:100% }
					.filterbar > div { display:table-cell }
				</style>
				<!--[if lte IE 7]>
				<style>.filterbar > div { float:left }</style>
				<![endif]-->
				<div class="filterbar subsubsub">';
		// prepare filterbar-items
		//split 3 section (left, center, right) seperated by semicolon
		$sections = explode(";", $args['filterbar_items']);
		$section_align = array('left', 'center', 'right');
		for($i=0; $i<sizeof($sections) && $i<3; $i++) {
			if(strlen($sections[$i]) > 0) {
				$out .= '
					<div style="text-align:'.$section_align[$i].'">';
				//split items in section seperated by comma
				$items = explode(",", $sections[$i]);
				foreach($items as $item) {
					//search for item options
					$options = array();
					$item_array = explode("(", $item);
					if(sizeof($item_array) > 1) {
						// options available
						$option_array = explode("|", substr($item_array[1],0,-1));
						foreach($option_array as $option_text) {
							$o = explode("=", $option_text);
							$options[$o[0]] = $o[1];
						}
					}
					$item_array = explode("_", $item_array[0]);
					switch($item_array[0]) {
						case 'years':
							$out .= $this->show_years($url, $args, $item_array[1], 'std', $options);
							break;
						case 'cats':
							$out .= $this->show_cats($url, $args, $item_array[1], 'std', $options);
							break;
						case 'reset':
							$out .= $this->show_reset($url, $args, $options);
					}
				}
				$out .= '
					</div>';
			}
		}
		$out .= '</div>';
		return $out;
	}
/*	TODO: implementation of show_all and show_upcoming
	private function show_all() {
		$elements[] = $this->all_element();
		return $this->show_hlist($elements);
	}

	private function show_upcoming() {
		$elements[] = $this->upcoming_element();
		return $this->show_hlist($elements);
	}
*/
	public function show_years($url, &$args, $type='hlist', $subtype='std', $options=array()) {
		$args = $this->parse_args($args);
		$argname = 'date'.$args['sc_id_for_url'];
		// prepare displayed elements
		$elements = array();
		if(!isset($options['show_all']) || 'true' == $options['show_all']) {   // default is true
			$elements[] = $this->all_element('hlist'==$type ? null : __('Show all dates'));
		}
		if(!isset($options['show_upcoming']) || 'true' == $options['show_upcoming']) {   // default is true
			$elements[] = $this->upcoming_element();
		}
		if(isset($options['show_past']) && 'true' == $options['show_past']) {   // default is false
			$elements[] = $this->past_element();
		}
		$first_year = $this->db->get_event_date('first');
		$last_year = $this->db->get_event_date('last');
		if(isset($options['years_order']) && 'asc' == strtolower($options['years_order'])) {
			for($year=$first_year; $year<=$last_year; $year++) {
				$elements[] = array('slug'=>$year, 'name'=>$year);
			}
		}
		else {
			for($year=$last_year; $year>=$first_year; $year--) {
				$elements[] = array('slug'=>$year, 'name'=>$year);
			}
		}
		// filter elements acc. date_filter (if only OR connections are used)
		if('all' !== $args['date_filter'] && !strpos($args['cat_filter'], '&')) {
			$tmp_filter = str_replace(array(' ', '(', ')'), '', $args['date_filter']);
			$tmp_filter = str_replace(',', '|', $tmp_filter);
			$filter_array = explode('|', $tmp_filter);
			foreach($elements as $id => $element) {
				if(!in_array($element['slug'], $filter_array) && 'all' !== $element['slug'] && 'upcoming' !== $element['slug'] && 'past' !== $element['slug']) {
					unset($elements[$id]);
				}
			}
		}
		// set selection
		if(is_numeric($args['event_id'])) {
			$actual = null;
		}
		elseif('all' === $args['actual_date'] || 'upcoming' === $args['actual_date'] || 'past' === $args['actual_date'] || is_numeric($args['actual_date'])) {
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

	public function show_cats($url, &$args, $type='dropdown', $subtype='std', $options=array()) {
		$args = $this->parse_args($args);
		$argname = 'cat'.$args['sc_id_for_url'];
		// prepare displayed elements
		$cat_array = $this->categories->get_cat_array();
		$elements = array();
		if(!isset($options['show_all']) || 'true' == $options['show_all']) {
			$elements[] = $this->all_element('hlist'==$type ? null : __('View all categories'));
		}
		foreach($cat_array as $cat) {
			$elements[] = array('slug' => $cat['slug'], 'name' => str_pad('', 12*$cat['level'], '&nbsp;', STR_PAD_LEFT).$cat['name']);
		}
		// filter elements acc. cat_filter (if only OR connections are used)
		if('all' !== $args['cat_filter'] && !strpos($args['cat_filter'], '&')) {
			$tmp_filter = str_replace(array(' ', '(', ')'), '', $args['cat_filter']);
			$tmp_filter = str_replace(',', '|', $tmp_filter);
			$filter_array = explode('|', $tmp_filter);
			foreach($elements as $id => $element) {
				if(!in_array($element['slug'], $filter_array) && 'all' !== $element['slug']) {
					unset($elements[$id]);
				}
			}
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

	public function show_reset($url, $args, $options) {
		$args_to_remove = array('event_id'.$args['sc_id_for_url'],
		                        'date'.$args['sc_id_for_url'],
		                        'cat'.$args['sc_id_for_url']);
		if(!isset($options['caption'])) {
			$options['caption'] = 'Reset';
		}
		return $this->show_link(remove_query_arg($args_to_remove, $url), __($options['caption']), 'link');
	}

	private function show_hlist($elements, $url, $name, $actual=null) {
		$out = '<ul class="hlist">';
		foreach($elements as $element) {
			$out .= '<li>';
			if($actual == $element['slug']) {
				$out .= '<strong>'.$element['name'].'</strong>';
			}
			else {
				$out .= $this->show_link(add_query_arg($name, $element['slug'], $url), $element['name']);
			}
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}

	private function show_dropdown($elements, $name, $subtype='std', $actual=null, $sc_id='') {
		$onchange = '';
		if('admin' != $subtype) {
			wp_register_script('el_filterbar', EL_URL.'includes/js/filterbar.js', null, true);
			add_action('wp_footer', array(&$this, 'footer_script'));
			$onchange = ' onchange="eventlist_redirect(this.name,this.value,'.$sc_id.')"';
		}
		$out = '<select class="dropdown" name="'.$name.'"'.$onchange.'>';
		foreach($elements as $element) {
			$out .= '
					<option';
			if($element['slug'] == $actual) {
				$out .= ' selected="selected"';
			}
			$out .= ' value="'.$element['slug'].'">'.esc_html($element['name']).'</option>';
		}
		$out .= '
				</select>';
		return $out;
	}

	private function show_link($url, $caption, $class=null) {
		$class = (null === $class) ? '' : ' class="'.$class.'"';
		return '<a href="'.esc_url($url).'"'.$class.'>'.esc_html($caption).'</a>';
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

	private function past_element() {
		return array('slug' => 'past', 'name' => __('Past'));
	}

	private function parse_args($args) {
		$defaults = array('date' => null, 'event_id' => null, 'sc_id_for_url' => null);
		$args = wp_parse_args($args, $defaults);
		return $args;
	}

	public function footer_script() {
		wp_print_scripts('el_filterbar');
	}
}
?>
