<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events.php');
require_once(EL_PATH.'includes/event.php');
//require_once(EL_PATH.'includes/categories.php');

// This class handles the shortcode [event-list]
class SC_Event_List {
	private static $instance;
	private $events;
	private $options;
//	private $categories;
	private $atts;
	private $num_sc_loaded;
	private $single_event;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->options = &EL_Options::get_instance();
		$this->events = &EL_Events::get_instance();
//		$this->categories = &EL_Categories::get_instance();

		// All available attributes
		$this->atts = array(
			'initial_event_id' => array('std_val' => 'all'),
			'initial_date'     => array('std_val' => 'upcoming'),
			'initial_cat'      => array('std_val' => 'all'),
			'initial_order'    => array('std_val' => 'date_asc'),
			'date_filter'      => array('std_val' => 'all'),
			'cat_filter'       => array('std_val' => 'all'),
			'num_events'       => array('std_val' => '0'),
			'show_filterbar'   => array('std_val' => 'true'),
			'filterbar_items'  => array('std_val' => 'years_hlist'),
			'title_length'     => array('std_val' => '0'),
			'show_starttime'   => array('std_val' => 'true'),
			'show_location'    => array('std_val' => 'true'),
			'location_length'  => array('std_val' => '0'),
			'show_cat'         => array('std_val' => 'false'),
			'show_content'     => array('std_val' => 'true'),
			'content_length'   => array('std_val' => '0'),
			'collapse_content' => array('std_val' => 'false'),
			'link_to_event'    => array('std_val' => 'event_list_only'),
			'add_feed_link'    => array('std_val' => 'false'),
			'url_to_page'      => array('std_val' => ''),
			'sc_id_for_url'    => array('std_val' => ''),
			// Internal attributes: This parameters will be added by the script and are not available in the shortcode
			//  'sc_id'
			//  'selected_date'
			//  'selected_cat'
			//  'event_id'
		);
		$this->num_sc_loaded = 0;
		$this->single_event = false;
	}

	public function load_sc_eventlist_helptexts() {
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
		// TODO: sanitize all provided shortcode attributes ($atts) before going further
		$a = shortcode_atts($std_values, $atts);
		// add internal attributes
		$a['sc_id'] = $this->num_sc_loaded;
		$a['selected_date'] = $this->get_selected_date($a);
		$a['selected_cat'] = $this->get_selected_cat($a);
		$a['event_id'] = $this->get_event_id($a);

		// set sc_id_for_url if empty
		if(0 === intval($a['sc_id_for_url'])) {
			$a['sc_id_for_url'] = $a['sc_id'];
		}

		// actual output
		$out = '
				<div class="event-list">';
		if(!empty($a['event_id'])) {
			// show events content if event_id is set
			$this->single_event = true;
			$out .= $this->html_event_content($a);
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

	private function html_event_content(&$a) {
		$event = new EL_Event($a['event_id']);
		$out = $this->html_filterbar($a);
		$out .= '
			<h2>'.__('Event Information:','event-list').'</h2>
			<ul class="single-event-view">';
		$single_day_only = ($event->startdate == $event->enddate) ? true : false;
		$out .= $this->html_event($event, $a, $single_day_only);
		$out .= '</ul>';
		return $out;
	}

	private function html_events(&$a) {
		// specify to show all events if not upcoming is selected
		if('upcoming' != $a['selected_date']) {
			$a['num_events'] = 0;
		}
		$options['date_filter'] = $this->get_date_filter($a['date_filter'], $a['selected_date']);
		$options['cat_filter'] = $this->get_cat_filter($a['cat_filter'], $a['selected_cat']);
		$options['num_events'] = $a['num_events'];
		$order = 'date_desc' == $a['initial_order'] ? 'DESC' : 'ASC';
		if('1' !== $this->options->get('el_date_once_per_day')) {
			// normal sort
			$options['order'] = array('startdate '.$order, 'starttime ASC', 'enddate '.$order);
		}
		else {
			// sort according end_date before start time (required for option el_date_once_per_day)
			$options['order'] = array('startdate '.$order, 'enddate '.$order, 'starttime ASC');
		}
		$events = $this->events->get($options);

		// generate output
		$out  = $this->html_feed_link($a, 'top');
		$out .= $this->html_filterbar($a);
		$out .= $this->html_feed_link($a, 'below_nav');
		if(empty($events)) {
			// no events found
			$out .= '<p>'.$this->options->get('el_no_event_text').'</p>';
		}
		else {
			// print available events
			$out .= '
				<ul class="event-list-view">';
			$single_day_only = $this->is_single_day_only($events);
			foreach ($events as $event) {
				$out .= $this->html_event($event, $a, $single_day_only);
			}
			$out .= '</ul>';
		}
		$out .= $this->html_feed_link($a, 'bottom');
		return $out;
	}

	private function html_event(&$event, &$a, $single_day_only=false) {
		static $last_event_startdate=null, $last_event_enddate=null;
		$cat_string = implode(' ', $event->get_category_slugs());
		// add class with each category slug
		$out = '
			 	<li class="event '.$cat_string.'">';
		// event date
		if('1' !== $this->options->get('el_date_once_per_day') || $last_event_startdate !== $event->startdate || $last_event_enddate !== $event->enddate) {
			$out .= $this->html_fulldate($event->startdate, $event->enddate, $single_day_only);
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
		$title = $event->truncate(esc_attr($event->title), $a['title_length'], $this->single_event);
		if($this->is_link_available($a, $event)) {
			$out .= $this->get_event_link($a, $event->post->ID, $title);
		}
		else {
			$out .= $title;
		}
		$out .= '</h3></div>';
		// event starttime
		if('' != $event->starttime && $this->is_visible($a['show_starttime'])) {
			if('' == $this->options->get('el_html_tags_in_time')) {
				$event->starttime = esc_attr($event->starttime_i18n());
			}
			$out .= '<span class="event-time">'.$event->starttime_i18n().'</span>';
		}
		// event location
		if('' != $event->location && $this->is_visible($a['show_location'])) {
			if('' == $this->options->get('el_html_tags_in_loc')) {
				$location =$event->truncate(esc_attr($event->location), $a['location_length'], $this->single_event, false);
			}
			else {
				$location = $event->truncate($event->location, $a['location_length'], $this->single_event);
			}
			$out .= '<span class="event-location">'.$location.'</span>';
		}
		// event categories
		if( $this->is_visible( $a['show_cat'] ) ) {
			$out .= '<div class="event-cat">'.esc_attr($this->categories->convert_db_string($event->categories)).'</div>';
		}
		// event content
		if( $this->is_visible( $a['show_content'] ) ) {
			$out .= $this->get_content($event, $a);
		}
		$out .= '</div>
				</li>';
		$last_event_startdate = $event->startdate;
		$last_event_enddate = $event->enddate;
		return $out;
	}

	private function html_fulldate($startdate, $enddate, $single_day_only=false) {
		$out = '
					';
		if($startdate === $enddate) {
			// one day event
			$out .= '<div class="event-date">';
			if($single_day_only ) {
				$out .= '<div class="startdate">';
			}
			else {
				$out .= '<div class="enddate">';
			}
			$out .= $this->html_date($startdate);
			$out .= '</div>';
		}
		else {
			// multi day event
			$out .= '<div class="event-date multi-date">';
			$out .= '<div class="startdate">';
			$out .= $this->html_date($startdate);
			$out .= '</div>';
			$out .= '<div class="enddate">';
			$out .= $this->html_date($enddate);
			$out .= '</div>';
		}
		$out .= '</div>';
		return $out;
	}

	private function html_date($date) {
		$out = '<div class="event-weekday">'.mysql2date('D', $date).'</div>';
		$out .= '<div class="event-day">'.mysql2date('d', $date).'</div>';
		$out .= '<div class="event-month">'.mysql2date('M', $date).'</div>';
		$out .= '<div class="event-year">'.mysql2date('Y', $date).'</div>';
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

	private function get_selected_date(&$a) {
		// check used get parameters
		$date = isset($_GET['date'.$a['sc_id']]) ? sanitize_key($_GET['date'.$a['sc_id']]) : null;

		if('all' === $date || 'upcoming' === $date) {
			return $date;
		}
		else if(preg_match('/^[0-9]{4}(-[0-9]{2})?(-[0-9]{2})?$/', $date)) {
			return $date;
		}
		return $a['initial_date'];
	}

	private function get_selected_cat(&$a) {
		// check used get parameters
		$cat = isset($_GET['cat'.$a['sc_id']]) ? sanitize_key($_GET['cat'.$a['sc_id']]) : '';

		if(!empty($cat)) {
			return $cat;
		}
		return $a['initial_cat'];
	}

	private function get_event_id(&$a) {
		// check used get parameters
		$event_id = isset($_GET['event_id'.$a['sc_id']]) ? intval($_GET['event_id'.$a['sc_id']]) : 0;

		if(0 < $event_id) {
			return $event_id;
		}
		elseif('all' !== $a['initial_event_id'] && $a['selected_date'] === $a['initial_date'] && $a['selected_cat'] === $a['initial_cat']) {
			return intval($a['initial_event_id']);
		}
		else {
			return 0;
		}
	}

	private function get_date_filter($date_filter, $selected_date) {
		if('all' == $date_filter || '' == $date_filter) {
			if('all' == $selected_date || '' == $selected_date) {
				return null;
			}
			else {
				return $selected_date;
			}
		}
		else {
			// Convert html entities to correct characters, e.g. &amp; to &
			$date_filter = html_entity_decode($date_filter);
			if('all' == $selected_date || '' == $selected_date) {
				return $date_filter;
			}
			else {
				return '('.$date_filter.')&('.$selected_date.')';
			}
		}
	}

	private function get_cat_filter($cat_filter, $selected_cat) {
		if('all' == $cat_filter || '' == $cat_filter) {
			if('all' == $selected_cat || '' == $selected_cat) {
				return null;
			}
			else {
				return $selected_cat;
			}
		}
		else {
			// Convert html entities to correct characters, e.g. &amp; to &
			$cat_filter = html_entity_decode($cat_filter);
			if('all' == $selected_cat || '' == $selected_cat) {
				return $cat_filter;
			}
			else {
				return '('.$cat_filter.')&('.$selected_cat.')';
			}
		}
	}

	private function get_content(&$event, &$a) {
		// check if content is available
		if('' == $event->content) {
			return '';
		}
		$truncate_url = false;
		// check and handle the read more tag if available
		//search fore more-tag (no more tag handling if truncate of content is set)
		if(preg_match('/<!--more(.*?)?-->/', $event->content, $matches)) {
			$part = explode($matches[0], $event->content, 2);
			if(!$this->is_link_available($a, $event) || 0 < $a['content_length'] || $this->single_event) {
				//content with removed more-tag
				$content = $part[0].$part[1];
			}
			else {
				//set more-link text
				if(!empty($matches[1])) {
					$more_link_text = strip_tags(wp_kses_no_null(trim($matches[1])));
				}
				else {
					$more_link_text = __('(more&hellip;)');
				}
				//content with more-link
				$content = apply_filters('the_content_more_link', $part[0].$this->get_event_link($a, $event->post->ID, $more_link_text));
			}
		}
		else {
			//normal content
			$content = $event->content;
			if($this->is_link_available($a, $event)) {
				$truncate_url = $this->get_event_url($a, $event->post->ID);
			}
		}
		// last preparations of content
		$content = $event->truncate(do_shortcode(wpautop($content)), $a['content_length'], $this->single_event, true, $truncate_url);
		// preparations for collapsed content
		if($this->is_visible($a['collapse_content'])) {
			wp_register_script('el_event-list', EL_URL.'includes/js/event-list.js', null, true);
			add_action('wp_footer', array(&$this, 'print_eventlist_script'));
			return '<div class="event-content"><div id="event-content-'.$event->post->ID.'" class="el-hidden">'.$content.
			       '</div><a class="event-content-link" id="event-content-a'.$event->post->ID.'" onclick="el_toggle_content('.$event->post->ID.')" href="javascript:void(0)">'.$this->options->get('el_content_show_text').'</a></div>';
		}
		// return without collapsing
		return '<div class="event-content">'.$content.'</div>';
	}

	private function get_event_link(&$a, $event_id, $title) {
		return '<a href="'.$this->get_event_url($a, $event_id).'">'.$title.'</a>';
	}

	private function get_event_url(&$a, $event_id) {
		return esc_html(add_query_arg('event_id'.$a['sc_id_for_url'], $event_id, $this->get_url($a)));
	}

	private function get_url(&$a) {
		if('' !== $a['url_to_page']) {
			// use given url
			$url = $a['url_to_page'];
		}
		else {
			// use actual page
			$url = get_permalink();
			foreach($_GET as  $k => $v) {
				if('date'.$a['sc_id'] !== $k && 'event_id'.$a['sc_id'] !== $k) {
					$url = add_query_arg($k, $v, $url);
				}
			}
		}
		return $url;
	}

	private function is_single_day_only( &$events ) {
		foreach( $events as $event ) {
			if( $event->startdate !== $event->enddate ) {
				return false;
			}
		}
		return true;
	}

	private function is_visible($attribute_value) {
		switch ($attribute_value) {
			case 'true':
			case '1': // = 'true'
				return true;
			case 'event_list_only':
				if($this->single_event) {
					return false;
				}
				else {
					return true;
				}
			case 'single_event_only':
				if($this->single_event) {
					return true;
				}
				else {
					return false;
				}
			default: // 'false' or 0 or nothing handled by this function
				return false;
		}
	}

	private function is_link_available(&$a, &$event) {
		return $this->is_visible($a['link_to_event']) || ('events_with_content_only' == $a['link_to_event'] && !$this->single_event && !empty($event->content));
	}

	public function print_eventlist_script() {
		// print variables for script
		echo('<script type="text/javascript">el_content_show_text = "'.$this->options->get('el_content_show_text').'"; el_content_hide_text = "'.$this->options->get('el_content_hide_text').'"</script>');
		// print script
		wp_print_scripts('el_event-list');
	}
}
?>
