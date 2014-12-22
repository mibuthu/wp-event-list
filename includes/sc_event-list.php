<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once( EL_PATH.'includes/db.php' );
require_once( EL_PATH.'includes/options.php' );
require_once( EL_PATH.'includes/categories.php' );

// This class handles the shortcode [event-list]
class SC_Event_List {
	private static $instance;
	private $db;
	private $options;
	private $categories;
	private $atts;
	private $num_sc_loaded;
	private $single_event;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new SC_Event_List();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->options = &EL_Options::get_instance();
		$this->categories = &EL_Categories::get_instance();

		// All available attributes
		$this->atts = array(
			'initial_event_id' => array('std_val' => 'all'),
			'initial_date'     => array('std_val' => 'upcoming'),
			'initial_cat'      => array('std_val' => 'all'),
			'date_filter'      => array('std_val' => 'all'),
			'cat_filter'       => array('std_val' => 'all'),
			'num_events'       => array('std_val' => '0'),
			'show_filterbar'   => array('std_val' => 'true'),
			'filterbar_items'  => array('std_val' => 'years_hlist'),
			'show_starttime'   => array('std_val' => 'true'),
			'show_location'    => array('std_val' => 'true'),
			'show_cat'         => array('std_val' => 'false'),
			'show_details'     => array('std_val' => 'true'),
			'details_length'   => array('std_val' => '0'),
			'link_to_event'    => array('std_val' => 'event_list_only'),
			'add_feed_link'    => array('std_val' => 'false'),
			'title_length'     => array('std_val' => '0'),
			'location_length'  => array('std_val' => '0'),
			'url_to_page'      => array('std_val' => ''),
			'sc_id_for_url'    => array('std_val' => ''),
			// Internal attributes: This parameters will be added by the script and are not available in the shortcode
			//  'sc_id'
			//  'actual_date'
			//  'actual_cat'
		);

		if(is_admin()) {
			$this->load_sc_eventlist_helptexts();
		}
		$this->num_sc_loaded = 0;
		$this->single_event = false;
	}

	private function load_sc_eventlist_helptexts() {
		require_once(EL_PATH.'includes/sc_event-list_helptexts.php');
		foreach($sc_eventlist_helptexts as $name => $values) {
			$this->atts[$name] = array_merge($this->atts[$name], $values);
		}
		unset($sc_eventlist_helptexts);
	}

	public function get_atts($only_visible=true) {
		if($only_visible) {
			$atts = null;
			foreach($this->atts as $aname => $attr) {
				if(!isset($attr['hidden']) || true !== $attr['hidden'] ) {
					$atts[$aname] = $attr;
				}
			}
			return $atts;
		}
		else {
			return $this->atts;
		}
	}

	// main function to show the rendered HTML output
	public function show_html($atts) {
		// change number of shortcodes
		$this->num_sc_loaded++;
		// check shortcode attributes
		$std_values = array();
		foreach($this->atts as $aname => $attribute) {
			$std_values[$aname] = $attribute['std_val'];
		}
		$a = shortcode_atts($std_values, $atts);
		// add internal attributes
		$a['sc_id'] = $this->num_sc_loaded;
		$a['actual_date'] = $this->get_actual_date($a);
		$a['actual_cat'] = $this->get_actual_cat($a);
		if(isset($_GET['event_id'.$a['sc_id']])) {
			$a['event_id'] = (int)$_GET['event_id'.$a['sc_id']];
		}
		elseif('all' != $a['initial_event_id'] && !isset($_GET['date'.$a['sc_id']]) && !isset($_GET['cat'.$a['sc_id']])) {
			$a['event_id'] = (int)$a['initial_event_id'];
		}
		else {
			$a['event_id'] = null;
		}
		// fix sc_id_for_url if required
		if(!is_numeric($a['sc_id_for_url'])) {
			$a['sc_id_for_url'] = $a['sc_id'];
		}

		$out = '
				<div class="event-list">';
		if(is_numeric($a['event_id'])) {
			// show events details if event_id is set
			$this->single_event = true;
			$out .= $this->html_event_details($a);
		}
		else {
			// show full event list
			$this->single_event = false;
			$out .= $this->html_events($a);
		}
		$out .= '
				</div>';
		return $out;
	}

	private function html_event_details(&$a) {
		$event = $this->db->get_event($a['event_id']);
		$out = $this->html_filterbar($a);
		$out .= '
			<h2>'.__('Event Information:','eventlist').'</h2>
			<ul class="single-event-view">';
		$single_day_only = ($event->start_date == $event->end_date) ? true : false;
		$out .= $this->html_event($event, $a, $single_day_only);
		$out .= '</ul>';
		return $out;
	}

	private function html_events( &$a ) {
		// specify to show all events if not upcoming is selected
		if('upcoming' != $a['actual_date']) {
			$a['num_events'] = 0;
		}
		$date_filter = $this->get_date_filter($a['date_filter'], $a['actual_date']);
		$cat_filter = $this->get_cat_filter($a['cat_filter'], $a['actual_cat']);
		if( '1' !== $this->options->get( 'el_date_once_per_day' ) ) {
			// normal sort
			$sort_array = array( 'start_date ASC', 'time ASC', 'end_date ASC' );
		}
		else {
			// sort according end_date before start time (required for option el_date_once_per_day)
			$sort_array = array( 'start_date ASC', 'end_date ASC', 'time ASC' );
		}
		$events = $this->db->get_events($date_filter, $cat_filter, $a['num_events'], $sort_array);

		// generate output
		$out = '';
		$out .= $this->html_feed_link($a, 'top');
		$out .= $this->html_filterbar($a);
		$out .= $this->html_feed_link($a, 'below_nav');
		if( empty( $events ) ) {
			// no events found
			$out .= '<p>'.$this->options->get( 'el_no_event_text' ).'</p>';
		}
		else {
			// print available events
			$out .= '
				<ul class="event-list-view">';
			$single_day_only = $this->is_single_day_only( $events );
			foreach ($events as $event) {
				$out .= $this->html_event( $event, $a, $single_day_only );
			}
			$out .= '</ul>';
		}
		$out .= $this->html_feed_link($a, 'bottom');
		return $out;
	}

	private function html_event( &$event, &$a, $single_day_only=false ) {
		static $last_event_startdate=null, $last_event_enddate=null;
		$cat_string = $this->categories->get_category_string($event->categories, 'slug', ' ');
		// add class with each category slug
		$out = '
			 	<li class="event '.$cat_string.'">';
		// event date
		if( '1' !== $this->options->get( 'el_date_once_per_day' ) || $last_event_startdate !== $event->start_date || $last_event_enddate !== $event->end_date ) {
			$out .= $this->html_fulldate( $event->start_date, $event->end_date, $single_day_only );
		}
		$out .= '
					<div class="event-info';
		if( $single_day_only ) {
			$out .= ' single-day';
		}
		else {
			$out .= ' multi-day';
		}
		$out .= '">';
		// event title
		$out .= '<div class="event-title"><h3>';
		$title = esc_attr($this->db->truncate($event->title, $a['title_length'], $this->single_event));
		if( $this->is_visible( $a['link_to_event'] ) ) {
			$out .= '<a href="'.esc_html(add_query_arg('event_id'.$a['sc_id_for_url'], $event->id, $this->get_url($a))).'">'.$title.'</a>';
		}
		else {
			$out .= $title;
		}
		$out .= '</h3></div>';
		// event time
		if('' != $event->time && $this->is_visible($a['show_starttime'])) {
			// set time format if a known format is available, else only show the text
			$date_array = date_parse($event->time);
			$time = $event->time;
			if(empty($date_array['errors']) && is_numeric($date_array['hour']) && is_numeric($date_array['minute'])) {
				$time = mysql2date(get_option('time_format'), $event->time);
			}
			if('' == $this->options->get('el_html_tags_in_time')) {
				$time = esc_attr($time);
			}
			$out .= '<span class="event-time">'.$time.'</span>';
		}
		// event location
		if('' != $event->location && $this->is_visible($a['show_location'])) {
			if('' == $this->options->get('el_html_tags_in_loc')) {
				$location = esc_attr($this->db->truncate($event->location, $a['location_length'], $this->single_event, false));
			}
			else {
				$location = $this->db->truncate($event->location, $a['location_length'], $this->single_event);
			}
			$out .= '<span class="event-location">'.$location.'</span>';
		}
		if( $this->is_visible( $a['show_cat'] ) ) {
			$out .= '<div class="event-cat">'.esc_attr($this->categories->get_category_string($event->categories)).'</div>';
		}
		if( $this->is_visible( $a['show_details'] ) ) {
			$out .= '<div class="event-details">'.$this->db->truncate(do_shortcode(wpautop($event->details)), $a['details_length'], $this->single_event).'</div>';
		}
		$out .= '</div>
				</li>';
		$last_event_startdate = $event->start_date;
		$last_event_enddate = $event->end_date;
		return $out;
	}

	private function html_fulldate( $start_date, $end_date, $single_day_only=false ) {
		$out = '
					';
		if( $start_date === $end_date ) {
			// one day event
			$out .= '<div class="event-date">';
			if( $single_day_only ) {
				$out .= '<div class="start-date">';
			}
			else {
				$out .= '<div class="end-date">';
			}
			$out .= $this->html_date( $start_date );
			$out .= '</div>';
		}
		else {
			// multi day event
			$out .= '<div class="event-date multi-date">';
			$out .= '<div class="start-date">';
			$out .= $this->html_date( $start_date );
			$out .= '</div>';
			$out .= '<div class="end-date">';
			$out .= $this->html_date( $end_date );
			$out .= '</div>';
		}
		$out .= '</div>';
		return $out;
	}

	private function html_date( $date ) {
		$out = '<div class="event-weekday">'.mysql2date( 'D', $date ).'</div>';
		$out .= '<div class="event-day">'.mysql2date( 'd', $date ).'</div>';
		$out .= '<div class="event-month">'.mysql2date( 'M', $date ).'</div>';
		$out .= '<div class="event-year">'.mysql2date( 'Y', $date ).'</div>';
		return $out;
	}

	private function html_filterbar(&$a) {
		if(!$this->is_visible($a['show_filterbar'])) {
			return '';
		}
		require_once( EL_PATH.'includes/filterbar.php');
		$filterbar = EL_Filterbar::get_instance();
		return $filterbar->show($this->get_url($a), $a);
	}

	private function html_feed_link(&$a, $pos) {
		$out = '';
		if($this->options->get('el_enable_feed') && 'true' === $a['add_feed_link'] && $pos === $this->options->get('el_feed_link_pos')) {
			// prepare url
			require_once( EL_PATH.'includes/feed.php' );
			$feed_link = EL_Feed::get_instance()->eventlist_feed_url();
			// prepare align
			$align = $this->options->get('el_feed_link_align');
			if('left' !== $align && 'center' !== $align && 'right' !== $align) {
				$align = 'left';
			}
			// prepare image
			$image = '';
			if('' !== $this->options->get('el_feed_link_img')) {
				$image = '<img src="'.includes_url('images/rss.png').'" alt="rss" />';
			}
			// prepare text
			$text = $image.esc_attr($this->options->get('el_feed_link_text'));
			// create html
			$out .= '<div class="feed" style="text-align:'.$align.'">
						<a href="'.$feed_link.'">'.$text.'</a>
					</div>';
		}
		return $out;
	}

	private function get_actual_date(&$a) {
		if(isset($_GET['event_id'.$a['sc_id']])) {
			return null;
		}
		elseif(isset($_GET['date'.$a['sc_id']])) {
			return $_GET['date'.$a['sc_id']];
		}
		return $a['initial_date'];
	}

	private function get_actual_cat(&$a) {
		if(isset($_GET['event_id'.$a['sc_id']])) {
			return null;
		}
		elseif(isset($_GET['cat'.$a['sc_id']])) {
			return $_GET['cat'.$a['sc_id']];
		}
		return $a['initial_cat'];
	}

	private function get_date_filter($date_filter, $actual_date) {
		if('all' == $date_filter || '' == $date_filter) {
			if('all' == $actual_date || '' == $actual_date) {
				return null;
			}
			else {
				return $actual_date;
			}
		}
		else {
			// Convert html entities to correct characters, e.g. &amp; to &
			$date_filter = html_entity_decode($date_filter);
			if('all' == $actual_date || '' == $actual_date) {
				return $date_filter;
			}
			else {
				return '('.$date_filter.')&('.$actual_date.')';
			}
		}
	}

	private function get_cat_filter($cat_filter, $actual_cat) {
		if('all' == $cat_filter || '' == $cat_filter) {
			if('all' == $actual_cat || '' == $actual_cat) {
				return null;
			}
			else {
				return $actual_cat;
			}
		}
		else {
			// Convert html entities to correct characters, e.g. &amp; to &
			$cat_filter = html_entity_decode($cat_filter);
			if('all' == $actual_cat || '' == $actual_cat) {
				return $cat_filter;
			}
			else {
				return '('.$cat_filter.')&('.$actual_cat.')';
			}
		}
	}

	private function get_url( &$a ) {
		if( '' !== $a['url_to_page'] ) {
			// use given url
			$url = $a['url_to_page'];
		}
		else {
			// use actual page
			$url = get_permalink();
			foreach( $_GET as  $k => $v ) {
				if('date'.$a['sc_id'] !== $k && 'event_id'.$a['sc_id'] !== $k) {
					$url = add_query_arg( $k, $v, $url );
				}
			}
		}
		return $url;
	}

	private function is_single_day_only( &$events ) {
		foreach( $events as $event ) {
			if( $event->start_date !== $event->end_date ) {
				return false;
			}
		}
		return true;
	}

	private function is_visible( $attribute_value ) {
		switch ($attribute_value) {
			case 'false':
				return false;
			case '0': // = 'false'
				return false;
			case 'event_list_only':
				if( $this->single_event ) {
					return false;
				}
				else {
					return true;
				}
			case 'single_event_only':
				if( $this->single_event ) {
					return true;
				}
				else {
					return false;
				}
			default: // 'true' or 1
				return true;
		}
	}
}
?>
