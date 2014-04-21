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

			'initial_event_id' => array('val'     => 'all<br />event-id',
			                            'std_val' => 'all',
			                            'desc'    => __('With this attribute you can specify an event from which the event-details are shown initially. The standard is to show the event-list.<br />
			                                             Specify an event-id e.g. "13" to change this behavior. It is still possible to go back to the event-list via the filterbar or url parameters.')),

			'initial_date'     => array('val'     => 'all<br />upcoming<br />year e.g. "2014"',
			                            'std_val' => 'upcoming',
			                            'desc'    => __('This attribute specifies which events are initially shown. The standard is to show the upcoming events.<br />
			                                             Specify a year e.g. "2014" to change this behavior. It is still possible to change the displayed event date range via the filterbar or url parameters.')),

			'initial_cat'      => array('val'     => 'all<br />category slug',
			                            'std_val' => 'all',
			                            'desc'    => __('This attribute specifies the category of which events are initially shown. The standard is to show events of all categories.<br />
			                                             Specify a category slug to change this behavior. It is still possible to change the displayed categories via the filterbar or url parameters.')),
/*
			'date_filter'      => array('val'     => 'all<br />upcoming<br />year e.g. "2014"',
			                            'std_val' => 'all',
			                            'desc'    => 'This attribute specifies the date range of which events are displayed. The standard is "all" to show all events.<br />
			                                          Events defined in date ranges not listed here are also not available in the date selection in the filterbar. It is also not possible to show them with a manual added url parameter<br />
			                                          Specify a year or a list of years separated by a comma "," e.g. "2014,2015,2016".'),
*/
			'cat_filter'       => array('val'     => 'all<br />category slugs',
			                            'std_val' => 'all',
			                            'desc'    => 'This attribute specifies the categories of which events are shown. The standard is "all" or an empty string to show all events.<br />
			                                          Events defined in categories which doesn´t match cat_filter are not shown in the event list. They are also not available if a manual url parameter is added.<br />
			                                          The filter is specified via the given category slug. You can use AND ("&") and OR ("|" or ",") connections to define complex filters. Additionally you can set brackets for nested queries.<br />
			                                          Examples:<br />
			                                          <code>tennis</code> ... Show all events with category "tennis".<br />
			                                          <code>tennis,hockey</code> ... Show all events with category "tennis" or "hockey".<br />
			                                          <code>tennis|(hockey&winter)</code> ... Show all events with category "tennis" and all events where category "hockey" as well as "winter" is selected.<br />
			                                          If you only use OR connections (no AND connection) the category selection in the filterbar will also be filtered according to the given filter.<br />'),

			'num_events'       => array('val'     => 'number',
			                            'std_val' => '0',
			                            'desc'    => 'This attribute specifies how many events should be displayed if upcoming events is selected.<br />
			                                          0 is the standard value which means that all events will be displayed.<br />
			                                          Please not that in the actual version there is no pagination of the events available.'),

			'show_filterbar'   => array('val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'desc'    => 'This attribute specifies if the filterbar should be displayed. The filterbar allows the users to select filters to limit the listed events.<br />
			                                          Choose "false" to always hide and "true" to always show the navigation.<br />
			                                          With "event_list_only" the filterbar is only visible in the event list and with "single_event_only" only for a single event'),

			'filterbar_items'  => array('val'     => 'years_hlist<br />years_dropdown<br />cats_hlist<br />cats_dropdown<br />reset_link',
			                            'std_val' => 'years_hlist',
			                            'desc'    => 'This attribute specifies the available items in the filterbar. This options are only valid if the filterbar is displayed (see show_filterbar attribute).<br /><br />
			                                          Find below an overview of the available filterbar items and their options:<br />
			                                          <small><table class="el-filterbar-table">
			                                              <th class="el-filterbar-item">filterbar item</th><th class="el-filterbar-desc">description</th><th class="el-filterbar-options">item options</th><th class="el-filterbar-values">option values</th><th class="el-filterbar-default">default value</th><th class="el-filterbar-desc2">description</th></thead>
			                                              <tr><td>years</td><td>Show a list of all available years. Additional there are some special entries available (see item options).</td><td>show_all<br />show_upcoming<br />show_past</td><td>true | false<br />true | false<br />true | false</td><td>true<br />true<br />false</td><td>Add an entry to show all events.<br />Add an entry to show all upcoming events.<br />Add an entry to show events in the past.</tr>
			                                              <tr><td>cats</td><td>Show a list of all available categories.</td><td>show_all</td><td>true<br />false</td><td>true</td><td>Add an entry to show events from all categories.</td></tr>
			                                              <tr><td>reset</td><td>Only a link to reset the eventlist filter to standard.</td><td>none</td><td></td><td></td><td></td></tr>
			                                          </table></small>
			                                          Find below an overview of the available filterbar display options:<br />
			                                          <small><table class="el-filterbar-table">
			                                             <th class="el-filterbar-doption">display option</th><th class="el-filterbar-desc3">description</th><th class="el-filterbar-for">available for</th></thead>
			                                             <tr><td>hlist</td><td>"hlist" shows a horizonal list seperated by "|" with a link to each item</td><td>years, cat</td></tr>
			                                             <tr><td>dropdown</td><td>"dropdown" shows a select box where an item can be choosen. After the selection of an item the page is reloaded via javascript to show the filtered events.</td><td>years, cat</td></tr>
			                                             <tr><td>link</td><td>"link" shows a simple link which can be clicked.</td><td>reset</td></tr>
			                                          </table></small>
			                                          <p>Find below some declaration examples with descriptions:</p>
			                                          <code>years_hlist,cats_dropdown</code><br />
			                                          In this example you can see that the filterbar item and the used display option is seperated by "_". You can define several filterbar items seperated by comma (","). The items will be aligned on the left side.
			                                          <p><code>years_dropdown(show_all=false|show_past=true),cats_dropdown;;reset_link</code><br />
			                                          In this example you can see that filterbar options can be added in brackets in format "option_name=value". You can also add multiple options seperated by a pipe ("|").<br />
			                                          The 2 semicolon (";") devides the bar in 3 section. The first section will be displayed left-justified, the second section will be centered and the third section will be right-aligned. So in this example the 2 dropdown will be left-aligned and the reset link will be on the right side.</p>'),

			'show_starttime'   => array('val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'desc'    => 'This attribute specifies if the starttime is displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the starttime.<br />
			                                          With "event_list_only" the starttime is only visible in the event list and with "single_event_only" only for a single event'),

			'show_location'    => array('val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'desc'    => 'This attribute specifies if the location is displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the location.<br />
			                                          With "event_list_only" the location is only visible in the event list and with "single_event_only" only for a single event'),

			'show_cat'         => array('val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'false',
			                            'desc'    => 'This attribute specifies if the categories are displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the category.<br />
			                                          With "event_list_only" the categories are only visible in the event list and with "single_event_only" only for a single event'),

			'show_details'     => array('val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the details are displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the details.<br />
			                                          With "event_list_only" the details are only visible in the event list and with "single_event_only" only for a single event'),

			'details_length'   => array('val'     => 'number',
			                            'std_val' => '0',
			                            'desc'    => 'This attribute specifies if the details should be truncate to the given number of characters in the event list.<br />
			                                          With the standard value 0 the full details are displayed.<br />
			                                          This attribute has no influence if only a single event is shown.'),

			'link_to_event'    => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'event_list_only',
			                            'desc'    => 'This attribute specifies if a link to the single event should be added onto the event name in the event list.<br />
			                                          Choose "false" to never add and "true" to always add the link.<br />
			                                          With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event'),

			'add_feed_link'    => array('val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'false',
			                            'desc'    => 'This attribute specifies if a rss feed link should be added.<br />
			                                          You have to enable the feed in the eventlist settings to make this attribute workable.<br />
			                                          On that page you can also find some settings to modify the output.<br />
			                                          Choose "false" to never add and "true" to always add the link.<br />
			                                          With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event'),
			// Invisible attributes ('visibe' = false): This attributes are required for the widget but will not be listed in the attributes table on the admin info page
			'title_length'     => array('val'     => 'number',
			                            'std_val' => '0',
			                            'hidden'  => true,
			                            'desc'    => 'This attribute specifies if the title should be truncate to the given number of characters in the event list.<br />
			                                          With the standard value 0 the full details are displayed.<br />
			                                          This attribute has no influence if only a single event is shown.'),

			'location_length'  => array( 'val'     => 'number',
			                            'std_val' => '0',
			                            'hidden'  => true,
			                            'desc'    => 'This attribute specifies if the title should be truncate to the given number of characters in the event list.<br />
			                                          With the standard value 0 the full details are displayed.<br />
			                                          This attribute has no influence if only a single event is shown.'),

			'url_to_page'      => array('val'     => 'url',
			                            'std_val' => '',
			                            'hidden'  => true,
			                            'desc'    => 'This attribute specifies that the link should follow the given url.<br />
			                                          The standard is to leave this attribute empty, then the url will be calculated automatically from the actual page or post url.<br />
			                                          This is o.k. for the normal use of the shortcode. This attribute is normally only required for the event-list widget.' ),

			'sc_id_for_url'    => array('val'     => 'number',
			                            'std_val' => '',
			                            'hidden'  => true,
			                            'desc'    => 'This attribute the specifies shortcode id of the used shortcode on the page specified with "url_to_page" attribute.<br />
			                                          The empty standard value is o.k. for the normal use. This attribute is normally only required for the event-list widget.' ),
			// Internal attributes: This parameters will be added by the script and are not available in the shortcode
			//   'sc_id'
			//   'actual_date'
			//   'actual_cat'
		);

		$this->num_sc_loaded = 0;
		$this->single_event = false;
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

	private function html_event_details( &$a ) {
		$event = $this->db->get_event( $a['event_id'] );
		$out = $this->html_filterbar($a);
		$out .= '
			<h2>Event Information:</h2>
			<ul class="single-event-view">';
		$out .= $this->html_event( $event, $a );
		$out .= '</ul>';
		return $out;
	}

	private function html_events( &$a ) {
		// specify to show all events if not upcoming is selected
		if('upcoming' != $a['actual_date']) {
			$a['num_events'] = 0;
		}
		$date_filter = $this->get_date_filter('all', $a['actual_date']);
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
		$out = '
			 	<li class="event">';
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
			$out .= '<div class="event-details">'.$this->db->truncate(do_shortcode($event->details), $a['details_length'], $this->single_event).'</div>';
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
		$actual_date = $a['initial_date'];
		if(isset($_GET['event_id'.$a['sc_id']])) {
			$actual_date = null;
		}
		elseif(isset($_GET['date'.$a['sc_id']])) {
			$actual_date = $_GET['date'.$a['sc_id']];
		}
		return $actual_date;
	}

	private function get_actual_cat(&$a) {
		$actual_cat = $a['initial_cat'];
		if(isset($_GET['event_id'.$a['sc_id']])) {
			$actual_cat = null;
		}
		elseif(isset($_GET['cat'.$a['sc_id']])) {
			$actual_cat = $_GET['cat'.$a['sc_id']];
		}
		return $actual_cat;
	}

	private function get_date_filter($date_filter, $actual_date) {
		// TODO: date_filter not implemented yet
		if('all' == $actual_date) {
			return null;
		}
		else {
			return $actual_date;
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
