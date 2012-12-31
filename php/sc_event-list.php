<?php
require_once( EL_PATH.'php/db.php' );

// This class handles the shortcode [event-list]
class sc_event_list {
	private static $instance;
	private $db;
	private $options;
	private $atts;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new sc_event_list();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = el_db::get_instance();
		//$this->options = &lv_options::get_instance();

		// All available attributes
		$this->atts = array(

			'initial_date'  => array( 'val'     => 'upcoming<br />year e.g. "2012"',
			                          'std_val' => 'upcoming',
			                          'desc'    => 'This attribute specifies which events are initially shown. The standard is to show the upcoming events.<br />
			                                        Specify a year e.g. "2012" to change this behavior.' ),

			'num_events'    => array( 'val'     => 'number',
			                          'std_val' => '0',
			                          'desc'    => 'This attribute specifies how many events should be displayed if upcoming events is selected.<br />
			                                        0 is the standard value which means that all events will be displayed.' ),

			'show_nav'      => array( 'val'     => '0..false<br />1..true',
			                          'std_val' => '1',
			                          'desc'    => 'This attribute specifies if the calendar navigation should be displayed.'),

			'show_details'  => array( 'val'     => '0..false<br />1..true',
			                          'std_val' => '1',
			                          'desc'    => 'This attribute specifies if the details are displayed in the event list.'),

			'show_location' => array( 'val'     => '0..false<br />1..true',
			                          'std_val' => '1',
			                          'desc'    => 'This attribute specifies if the location is displayed in the event list.'),

			'link_to_event' => array( 'val'     => '0..false<br />1..true',
			                          'std_val' => '1',
			                          'desc'    => 'This attribute specifies if a link to the single event should be added onto the event name in the event list.')
		);
	}

	public function get_atts() {
		return $this->atts;
	}

	// main function to show the rendered HTML output
	public function show_html( $atts ) {
		// check attributes
		$std_values = array();
		foreach( $this->atts as $aname => $attribute ) {
			$std_values[$aname] = $attribute['std_val'];
		}
		$a = shortcode_atts( $std_values, $atts );

		if( isset( $_GET['event_id'] ) ) {
			$out = $this->html_event_details( $_GET['event_id'] );
		}
		else {
			$out = $this->html_events( $a );
		}
		return $out;
	}

	private function html_event_details( $event_id ) {
		$event = $this->db->get_event( $event_id );
		$out = $this->html_calendar_nav();
		$out .= '
			<h2>Event Information:</h2>
			<ul class="event-list">';
		$out .= $this->html_event( $event );
		$out .= '</ul>';
		return $out;
	}

	private function html_events( $a ) {
		// specify visible events
		if( isset( $_GET['ytd'] ) ) {
			$events = $this->db->get_events( $_GET['ytd'] );
		}
		elseif( 'upcoming' !== $a['initial_date'] ) {
			$events = $this->db->get_events( $a['initial_date'] );
		}
		else {
			$events = $this->db->get_events( 'upcoming', $a['num_events'] );
		}
		$out = '';
		// TODO: add rss feed
		//		if ($mfgigcal_settings['rss']) {
		//			(get_option('permalink_structure')) ? $feed_link = "/feed/events" : $feed_link = "/?feed=events";
		//			$out .= "<a href=\"$feed_link\" class=\"rss-link\">RSS</a>";
		//		}

		// generate output
		if( 0 != $a['show_nav'] ) {
			$out .= $this->html_calendar_nav();
		}
		// TODO: Setting missing
		if( empty( $events ) /*&& $mfgigcal_settings['no-events'] == "text"*/ ) {
			$out .= "<p>" . 'no event' /*$mfgigcal_settings['message'] */. "</p>";
			return $out;
		}
		/*		else if (empty($events)) {
		 $this_year = date("Y");
		// show the current year
		$sql = "SELECT * FROM $mfgigcal_table WHERE (end_date >= '$this_year-01-01' AND start_date <= '$this_year-12-31') ORDER BY start_date ASC";
		$events = $wpdb->get_results($sql);
		if (empty($events)) {
		$out .= "<p>" . $mfgigcal_settings['message'] . "</p>";
		return $out;
		}
		}
		*/
		else {
			$url = get_permalink();
			if( !get_option( 'permalink_structure' ) ) {
				foreach( $_GET as  $k => $v ) {
					if( 'ytd' !== $k && 'event_id' !== $k ) {
						$url .= $k.'='.$v.'&amp;';
					}
				}
			}

			// set html code
			$out .= '
				<ul class="event-list">';
			$single_day_only = $this->is_single_day_only( $events );
			foreach ($events as $event) {
				$out .= $this->html_event( $event, $a, $url, $single_day_only );
			}
			$out .= '</ul>';
			return $out;
		}
		return $out;
	}

	private function html_event( $event, $a=null, $url=null, $single_day_only=false ) {
		$out = '
			 	<li class="event">';
		$out .= $this->html_fulldate( $event->start_date, $event->end_date, $single_day_only );
		$out .= '
					<div class="event-info';
		if( $single_day_only ) {
			$out .= ' single-day';
		}
		else {
			$out .= ' multi-day';
		}
		$out .= '"><h3>';
		if( null !== $url && ( null !== $a && 0 != $a['link_to_event'] ) ) {
			$out .= '<a href="'.$url.'event_id='.$event->id.'">'.$event->title.'</a>';
		}
		else {
			$out .= $event->title;
		}
		$out .= '</h3>';
		if( $event->time != '' ) {
			$out .= '<span class="event-time">'.mysql2date( get_option( 'time_format' ), $event->time ).'</span>';
		}
		if( null === $a || 0 != $a['show_location'] ) {
			$out .= '<span class="event-location">'.$event->location.'</span>';
		}
		if( null === $a || 0 != $a['show_details'] ) {
			$out .= '<div class="event-details">'.$event->details.'</div>';
		}
		$out .= '</div>
				</li>';
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

	private function html_calendar_nav() {
		$first_year = $this->db->get_event_date( 'first' );
		$last_year = $this->db->get_event_date( 'last' );

		$url = get_permalink();
		if( !get_option( 'permalink_structure' ) ) {
			foreach( $_GET as  $k => $v ) {
				if( 'ytd' !== $k && 'event_id' !== $k ) {
					$url .= $k.'='.$v.'&amp;';
				}
			}
		}
		else {
			$url .= '?';
		}
		$out = '<div class="subsubsub">';
		if( isset( $_GET['ytd'] ) || isset( $_GET['event_id'] ) ) {
			$out .= '<a href="'.$url.'">Upcoming</a>';
		}
		else {
			$out .= '<strong>Upcoming</strong>';
		}
		for( $year=$last_year; $year>=$first_year; $year-- ) {
			$out .= ' | ';
			if( isset( $_GET['ytd'] ) && $year == $_GET['ytd'] ) {
				$out .= '<strong>'.$year.'</strong>';
			}
			else {
				$out .= '<a href="'.$url.'ytd='.$year.'">'.$year.'</a>';
			}
		}
		$out .= '</div><br />';
		return $out;
	}

	private function is_single_day_only( &$events ) {
		foreach( $events as $event ) {
			if( $event->start_date !== $event->end_date ) {
				return false;
			}
		}
		return true;
	}
}
?>
