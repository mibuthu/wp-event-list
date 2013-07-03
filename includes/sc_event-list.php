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

			'initial_date'    => array( 'val'     => 'upcoming<br />year e.g. "2013"',
			                            'std_val' => 'upcoming',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies which events are initially shown. The standard is to show the upcoming events.<br />
			                                          Specify a year e.g. "2013" to change this behavior.' ),

			'cat_filter'      => array( 'val'     => 'none<br />category slug',
			                            'std_val' => 'none',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies events of which categories are shown. The standard is "none" to show all events.<br />
			                                          Specify a category slug or a list of category slugs separated by a comma "," e.g. "tennis,hockey" to only show events of the specified categories.' ),

			'num_events'      => array( 'val'     => 'number',
			                            'std_val' => '0',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies how many events should be displayed if upcoming events is selected.<br />
			                                          0 is the standard value which means that all events will be displayed.' ),

			'show_nav'        => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the calendar navigation should be displayed.<br />
			                                          Choose "false" to always hide and "true" to always show the navigation.<br />
			                                          With "event_list_only" the navigation is only visible in the event list and with "single_event_only" only for a single event'),

			'show_starttime'  => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the starttime is displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the starttime.<br />
			                                          With "event_list_only" the starttime is only visible in the event list and with "single_event_only" only for a single event'),

			'show_location'   => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the location is displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the location.<br />
			                                          With "event_list_only" the location is only visible in the event list and with "single_event_only" only for a single event'),

			'show_cat'        => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'false',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the categories are displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the category.<br />
			                                          With "event_list_only" the categories are only visible in the event list and with "single_event_only" only for a single event'),

			'show_details'    => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'true',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the details are displayed in the event list.<br />
			                                          Choose "false" to always hide and "true" to always show the details.<br />
			                                          With "event_list_only" the details are only visible in the event list and with "single_event_only" only for a single event'),

			'details_length'  => array( 'val'     => 'number',
			                            'std_val' => '0',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if the details should be truncate to the given number of character in the event list.<br />
			                                          With the standard value 0 the full details are displayed.<br />
			                                          This attribute has no influence if only a single event is shown.'),

			'link_to_event'   => array( 'val'     => 'false<br />true<br />event_list_only<br />single_event_only',
			                            'std_val' => 'event_list_only',
			                            'visible' => true,
			                            'desc'    => 'This attribute specifies if a link to the single event should be added onto the event name in the event list.<br />
			                                          Choose "false" to never add and "true" to always add the link.<br />
			                                          With "event_list_only" the link is only added in the event list and with "single_event_only" only for a single event'),
			// Invisible attributes ('visibe' = false): This attributes are required for the widget but will not be listed in the attributes table on the admin info page
			'title_length'    => array( 'val'     => 'number',
			                            'std_val' => '0',
			                            'visible' => false,
			                            'desc'    => 'This attribute specifies if the title should be truncate to the given number of character in the event list.<br />
			                                          With the standard value 0 the full details are displayed.<br />
			                                          This attribute has no influence if only a single event is shown.'),

			'location_length' => array( 'val'     => 'number',
			                            'std_val' => '0',
			                            'visible' => false,
			                            'desc'    => 'This attribute specifies if the title should be truncate to the given number of character in the event list.<br />
			                                          With the standard value 0 the full details are displayed.<br />
			                                          This attribute has no influence if only a single event is shown.'),

			'url_to_page'     => array( 'val'     => 'url',
			                            'std_val' => '',
			                            'visible' => false,
			                            'desc'    => 'This attribute specifies that the link should follow the given url.<br />
			                                          The standard is to leave this attribute empty, then the url will be calculated automatically from the actual page or post url.<br />
			                                          This is o.k. for the normal use of the shortcode. This attribute is normally only required for the event-list widget.' ),

			'sc_id_for_url'   => array( 'val'     => 'number',
			                            'std_val' => '',
			                            'visible' => false,
			                            'desc'    => 'This attribute the specifies shortcode id of the used shortcode on the page specified with "url_to_page" attribute.<br />
			                                          The empty standard value is o.k. for the normal use. This attribute is normally only required for the event-list widget.' ),
			// Internal attributes: This parameters will be added by the script and are not available in the shortcode
			//   'sc_id'
			//   'ytd'
		);

		$this->num_sc_loaded = 0;
		$this->single_event = false;
	}

	public function get_atts( $only_visible=true ) {
		if( $only_visible ) {
			$atts = null;
			foreach( $this->atts as $aname => $attr ) {
				if( true === $attr['visible'] ) {
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
	public function show_html( $atts ) {
		// change number of shortcodes
		$this->num_sc_loaded++;
		// check shortcode attributes
		$std_values = array();
		foreach( $this->atts as $aname => $attribute ) {
			$std_values[$aname] = $attribute['std_val'];
		}
		$a = shortcode_atts( $std_values, $atts );
		// add internal attributes
		$a['sc_id'] = $this->num_sc_loaded;
		$a['event_id'] = isset( $_GET['event_id_'.$a['sc_id']] ) ? (integer)$_GET['event_id_'.$a['sc_id']] : null;
		$a['ytd'] = $this->get_ytd( $a );
		// fix sc_id_for_url if required
		if( !is_numeric( $a['sc_id_for_url'] ) ) {
			$a['sc_id_for_url'] = $a['sc_id'];
		}

		$out = '
				<div class="event-list">';
		if( is_numeric( $a['event_id'] ) ) {
			// show events details if event_id is set
			$this->single_event = true;
			$out .= $this->html_event_details( $a );
		}
		else {
			// show full event list
			$this->single_event = false;
			$out .= $this->html_events( $a );
		}
		$out .= '
				</div>';
		return $out;
	}

	private function html_event_details( &$a ) {
		$event = $this->db->get_event( $a['event_id'] );
		$out = $this->html_calendar_nav( $a );
		$out .= '
			<h2>Event Information:</h2>
			<ul class="single-event-view">';
		$out .= $this->html_event( $event, $a );
		$out .= '</ul>';
		return $out;
	}

	private function html_events( &$a ) {
		// specify to show all events if not upcoming is selected
		if( is_numeric( $a['ytd'] ) ) {
			$a['num_events'] = 0;
		}
		$cat_filter = 'none' === $a['cat_filter'] ? null : explode( ',', $a['cat_filter'] );
		if( '1' !== $this->options->get( 'el_date_once_per_day' ) ) {
			// normal sort
			$sort_array = array( 'start_date ASC', 'time ASC', 'end_date ASC' );
		}
		else {
			// sort according end_date before start time (required for option el_date_once_per_day)
			$sort_array = array( 'start_date ASC', 'end_date ASC', 'time ASC' );
		}
		$events = $this->db->get_events( $a['ytd'], $a['num_events'], $cat_filter, $sort_array );
		$out = '';
		// TODO: add rss feed
		//		if ($mfgigcal_settings['rss']) {
		//			(get_option('permalink_structure')) ? $feed_link = "/feed/events" : $feed_link = "/?feed=events";
		//			$out .= "<a href=\"$feed_link\" class=\"rss-link\">RSS</a>";
		//		}

		// generate output
		$out .= $this->html_calendar_nav( $a );
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
		return $out;
	}

	private function html_event( &$event, &$a, $single_day_only=false ) {
		static $last_event_startdate, $last_event_enddate;
		$max_length = is_numeric( $a['event_id'] ) ? 0 : 999999;
		$out = '
			 	<li class="event">';
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
		$out .= '"><h3>';

		$title = $this->db->truncate( min( $max_length, $a['title_length'] ), $event->title );
		if( $this->is_visible( $a['link_to_event'] ) ) {
			$out .= '<a href="'.add_query_arg( 'event_id_'.$a['sc_id_for_url'], $event->id, $this->get_url( $a ) ).'">'.$title.'</a>';
		}
		else {
			$out .= $title;
		}
		$out .= '</h3>';
		if( $event->time != '' && $this->is_visible( $a['show_starttime'] ) ) {
			// set time format if a known format is available, else only show the text
			$date_array = date_parse( $event->time );
			if( empty( $date_array['errors']) && is_numeric( $date_array['hour'] ) && is_numeric( $date_array['minute'] ) ) {
				$event->time = mysql2date( get_option( 'time_format' ), $event->time );
			}
			$out .= '<span class="event-time">'.$event->time.'</span>';
		}
		if( $this->is_visible( $a['show_location'] ) ) {
			$out .= '<span class="event-location">'.$this->db->truncate( min( $max_length, $a['location_length'] ), $event->location ).'</span>';
		}
		if( $this->is_visible( $a['show_cat'] ) ) {
			$out .= '<div class="event-cat">'.$this->categories->get_category_string( $event->categories ).'</div>';
		}
		if( $this->is_visible( $a['show_details'] ) ) {
			$out .= '<div class="event-details">'.$this->db->truncate( min( $max_length, $a['details_length'] ), do_shortcode( $event->details ) ).'</div>';
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

	private function html_calendar_nav( &$a ) {
		$out = '';
		if( ! $this->is_visible( $a['show_nav'] ) ) {
			// no calendar navigation required
			return $out;
		}
		$first_year = $this->db->get_event_date( 'first' );
		$last_year = $this->db->get_event_date( 'last' );

		$url = $this->get_url( $a );
		$out .= '<div class="subsubsub">';
		if( is_numeric( $a['ytd'] ) || is_numeric( $a['event_id'] ) ) {
			$out .= '<a href="'.add_query_arg( 'ytd_'.$a['sc_id_for_url'], 'upcoming', $url ).'">Upcoming</a>';
		}
		else {
			$out .= '<strong>Upcoming</strong>';
		}
		for( $year=$last_year; $year>=$first_year; $year-- ) {
			$out .= ' | ';
			if( $year == $a['ytd'] ) {
				$out .= '<strong>'.$year.'</strong>';
			}
			else {
				$out .= '<a href="'.add_query_arg( 'ytd_'.$a['sc_id_for_url'], $year, $url ).'">'.$year.'</a>';
			}
		}
		$out .= '</div><br />';
		return $out;
	}

	private function get_ytd( &$a ) {
		if( isset( $_GET['ytd_'.$a['sc_id']] ) && 'upcoming' === $_GET['ytd_'.$a['sc_id']] ){
			// ytd is 'upcoming'
			$ytd = 'upcoming';
		}
		elseif( isset( $_GET['ytd_'.$a['sc_id']] ) && is_numeric( $_GET['ytd_'.$a['sc_id']] ) ) {
			// ytd is a year
			$ytd = (int)$_GET['ytd_'.$a['sc_id']];
		}
		elseif( isset( $a['initial_date'] ) && is_numeric( $a['initial_date'] ) && !is_numeric( $a['event_id'] ) && !isset( $_GET['link_'.$a['sc_id']] ) ) {
			// initial_date attribute is set
			$ytd = (int)$a['initial_date'];
		}
		else {
			$ytd = 'upcoming';
		}
		return $ytd;
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
				if( 'ytd_'.$a['sc_id'] !== $k && 'event_id_'.$a['sc_id'] !== $k && 'link_'.$a['sc_id'] !== $k ) {
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
