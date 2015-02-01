<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once( EL_PATH.'includes/db.php' );
require_once( EL_PATH.'includes/categories.php' );

// This class handles the navigation and filter bar
class EL_Filterbar {
	private static $instance;
	private $db;
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
		$this->categories = &EL_Categories::get_instance();
	}

	// main function to show the rendered HTML output
	public function show($url, &$args) {
		$this->parse_args($args);
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
		$sections = explode(";", html_entity_decode($args['filterbar_items']));
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
						case 'daterange':
							$out .= $this->show_daterange($url, $args, $item_array[1], 'std', $options);
							break;
						case 'cats':
							$out .= $this->show_cats($url, $args, $item_array[1], 'std', $options);
							break;
						case 'months':
							$out .= $this->show_months($url, $args, $item_array[1], 'std', $options);
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

	public function show_years($url, &$args, $type='hlist', $subtype='std', $options=array()) {
		$default_options = array (
				'show_all' => 'true',
				'show_upcoming' => 'true',
				'show_past' => 'false',
				'years_order' => 'asc',
		);
		$options = wp_parse_args($options, $default_options);
		// prepare displayed elements
		$elements = array();
		if('true' == $options['show_all']) {
			$elements[] = $this->all_element('date', $type);
		}
		if('true' == $options['show_upcoming']) {
			$elements[] = $this->upcoming_element();
		}
		if('true' == $options['show_past']) {
			$elements[] = $this->past_element();
		}
		$event_years = $this->db->get_distinct_event_data('substr(`start_date`,1,4)', $args['date_filter'],$args['cat_filter'], $options['years_order']);
		foreach($event_years as $entry) {
			$elements[] = array('slug'=>$entry->data, 'name'=>$entry->data);
		}
		// display elements
		if('dropdown' === $type) {
			return $this->show_dropdown($elements, 'date'.$args['sc_id_for_url'], $subtype, $args['actual_date'], $args['sc_id_for_url']);
		}
		else {
			return $this->show_hlist($elements, $url, 'date'.$args['sc_id_for_url'], $args['actual_date']);
		}
	}

	public function show_months($url, &$args, $type='dropdown', $subtype='std', $options=array()) {
		$default_options = array (
				'show_all' => 'false',
				'show_upcoming' => 'false',
				'show_past' => 'false',
				'months_order' => 'asc',
				'date_format' => 'Y-m',
		);
		$options = wp_parse_args($options, $default_options);
		// prepare displayed elements
		$elements = array();
		if('true' == $options['show_all']) {
			$elements[] = $this->all_element('date', $type);
		}
		if('true' == $options['show_upcoming']) {
			$elements[] = $this->upcoming_element();
		}
		if('true' == $options['show_past']) {
			$elements[] = $this->past_element();
		}
		$event_months = $this->db->get_distinct_event_data('substr(`start_date`,1,7)', $args['date_filter'],$args['cat_filter'], $options['months_order']);
		foreach($event_months as $entry) {
			list($year, $month) = explode('-', $entry->data);
			$elements[] = array('slug' => $entry->data, 'name' => date($options['date_format'], mktime(0,0,0,$month,1,$year)));
		}
		// display elements
		if('hlist' === $type) {
			return $this->show_hlist($elements, $url, 'date'.$args['sc_id_for_url'], $args['actual_date']);
		}
		else {
			return $this->show_dropdown($elements, 'date'.$args['sc_id_for_url'], $subtype, $args["actual_date"], $args['sc_id_for_url']);
		}
	}

	public function show_daterange($url, &$args, $type='hlist', $subtype='std', $options) {
		// prepare displayed elements
		if(isset($options['item_order'])) {
			$items = explode('&', $options['item_order']);
		}
		else {
			$items = array('all', 'upcoming', 'past');
		}
		$elements = array();
		foreach($items as $item) {
			// show all
			switch($item) {
				case 'all':
					$elements[] = $this->all_element('date');   // Always show short form ... hlist
					break;
				case 'upcoming':
					$elements[] = $this->upcoming_element();
					break;
				case 'past':
					$elements[] = $this->past_element();
			}
		}
		// display elements
		if('dropdown' === $type) {
			return $this->show_dropdown($elements, 'date'.$args['sc_id_for_url'], $subtype, $args['actual_date'], $args['sc_id_for_url']);
		}
		else {
			return $this->show_hlist($elements, $url, 'date'.$args['sc_id_for_url'], $args['actual_date']);
		}
	}

	public function show_cats($url, &$args, $type='dropdown', $subtype='std', $options=array()) {
		$default_options = array (
				'show_all' => 'true',
		);
		$options = wp_parse_args($options, $default_options);
		// prepare displayed elements
		$elements = array();
		if('true' == $options['show_all']) {
			$elements[] = $this->all_element('cat', $type);
		}
		//prepare required arrays
		$cat_array = $this->categories->get_cat_array();
		$events_cat_strings = $this->db->get_distinct_event_data('`categories`', $args['date_filter'],$args['cat_filter']);
		$events_cat_array = array();
		foreach($events_cat_strings as $cat_string) {
			$events_cat_array = array_merge($events_cat_array, $this->categories->convert_db_string($cat_string->data, 'slug_array'));
		}
		$events_cat_array = array_unique($events_cat_array);
		//create filtered cat_array
		$filtered_cat_array = array();
		$required_cats = array();
		for($i=count($cat_array)-1; 0<=$i; $i--) {   // start from the end to have the childs first and be able to add the parent to the required_cats
			if(in_array($cat_array[$i]['slug'], $events_cat_array) || in_array($cat_array[$i]['slug'], $required_cats)) {
				array_unshift($filtered_cat_array, $cat_array[$i]);   // add the new cat at the beginning (unshift) due to starting at the end in the loop
				if('' != $cat_array[$i]['parent']) {   // the parent is required to show the categories correctly
					$required_cats[] = $cat_array[$i]['parent'];
				}
			}
		}
		//create elements array
		foreach($filtered_cat_array as $cat) {
			$elements[] = array('slug' => $cat['slug'], 'name' => str_pad('', 12*$cat['level'], '&nbsp;', STR_PAD_LEFT).$cat['name']);
		}
		// display elements
		if('hlist' === $type) {
			return $this->show_hlist($elements, $url, 'cat'.$args['sc_id_for_url'], $args['actual_cat']);
		}
		else {
			return $this->show_dropdown($elements, 'cat'.$args['sc_id_for_url'], $subtype, $args['actual_cat'], $args['sc_id_for_url']);
		}
	}

	public function show_reset($url, $args, $options) {
		$args_to_remove = array('event_id'.$args['sc_id_for_url'],
		                        'date'.$args['sc_id_for_url'],
		                        'cat'.$args['sc_id_for_url']);
		if(!isset($options['caption'])) {
			$options['caption'] = 'Reset';
		}
		return $this->show_link(remove_query_arg($args_to_remove, $url), $options['caption'], 'link');
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

	private function all_element($list_type='date', $display_type='hlist') {
		if('hlist' == $display_type) {
			$name = __('All','event-list');
		}
		else {
			$name = ('date' == $list_type) ? __('Show all dates','event-list') :  __('Show all categories','event-list');
		}
		return array('slug' => 'all', 'name' => $name);
	}

	private function upcoming_element() {
		return array('slug' => 'upcoming', 'name' => __('Upcoming','event-list'));
	}

	private function past_element() {
		return array('slug' => 'past', 'name' => __('Past','event-list'));
	}

	private function parse_args(&$args) {
		$defaults = array('date' => null,
		                  'actual_date' => null,
		                  'actual_cat' => null,
		                  'event_id' => null,
		                  'sc_id_for_url' => '',
		);
		$args = wp_parse_args($args, $defaults);
		if(is_numeric($args['event_id'])) {
			$args['actual_date'] = null;
			$args['actual_cat'] = null;
		};
	}

	public function footer_script() {
		wp_print_scripts('el_filterbar');
	}
}
?>
