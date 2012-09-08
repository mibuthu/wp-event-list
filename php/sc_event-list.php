<?php
require_once( EL_PATH.'php/db.php' );

// This class handles the shortcode [event-list]
class sc_event_list {

	// All available attributes
	public static $attr = array(

		'initial_date' => array( 'val'     => 'upcoming<br />year e.g. "2012"',
		                         'std_val' => 'upcoming',
		                         'desc'    => 'This attribute specifies which events are listed after the site is shown. The standard is to show the upcoming events.<br />
		                                       Specify a year e.g. "2012" to change this.' )
	);

	// main function to show the rendered HTML output
	public static function show_html( $atts ) {
		// check attributes
		$std_values = array();
		foreach( self::$attr as $aname => $attribute ) {
			$std_values[$aname] = $attribute['std_val'];
		}
		$a = shortcode_atts( $std_values, $atts );

		if( isset( $_GET['event_id'] ) ) {
			$out = self::html_event_details( $_GET['event_id'] );
		}
		else {
			$out = self::html_events( $a );
		}
		return $out;
	}
	
	private static function html_event_details( $event_id ) {
		$event = el_db::get_event( $event_id );
		$out = el_db::html_calendar_nav();
		$out .= '<ul id="eventlist">';
		$out .= self::html_event( $event );
		$out .= '</ul>';
		return $out;
	}
	
	private static function html_events( $a ) {
		// specify visible events
		if( isset( $_GET['ytd'] ) ) {
			$events = el_db::get_events( $_GET['ytd'] );
		}
		elseif( $a['initial_date'] !== 'upcoming' ) {
			$events = el_db::get_events( $a['initial_date'] );
		}
		else {
			$events = el_db::get_events( 'upcoming' );
		}
		$out = '';
		// TODO: add rss feed
		//		if ($mfgigcal_settings['rss']) {
		//			(get_option('permalink_structure')) ? $feed_link = "/feed/events" : $feed_link = "/?feed=events";
		//			$out .= "<a href=\"$feed_link\" class=\"rss-link\">RSS</a>";
		//		}
		
		// generate output
		$out .= el_db::html_calendar_nav();
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
			$url = '?';
			if( !get_option( 'permalink_structure' ) ) {
				foreach( $_GET as  $k => $v ) {
					if( $k != 'ytd' && $k != 'event_id' ) {
						$url .= $k . "=" . $v . "&";
					}
				}
			}
			
			// set html code
			$out .= '<ul id="eventlist">';
			foreach ($events as $event) {
				$out .= self::html_event( $event, $url );
			}
			$out .= '</ul>';
			return $out;
		}
		return $out;
	}
	
	private static function html_event( $event, $url=false ) {
		$out = '<li class="event">';
		$out .= self::html_fulldate( $event->start_date, $event->end_date );
		$out .= '<div class="info_block"><h3>';
		if( $url ) {
			$out .= '<a href="'.$url.'event_id='.$event->id.'">'.$event->title.'</a>';
		}
		else {
			$out .= $event->title;
		}
		$out .= '</h3>';
		if( $event->time != '' ) {
			$out .= '<span class="time">'.$event->time.'</span>';
		}
		$out .= '<span class="location">'.$event->location.'</span>';
		$out .= '<span class="details">'.$event->details.'</span>';
		$out .= '</div></li>';
		return $out;
	}
	
	private static function html_fulldate( $start_date, $end_date ) {
		$startArray = explode("-", $start_date);
		$start_date = mktime(0,0,0,$startArray[1],$startArray[2],$startArray[0]);
		$endArray = explode("-", $end_date);
		$end_date = mktime(0,0,0,$endArray[1],$endArray[2],$endArray[0]);
		$out = '';
		if( $start_date == $end_date ) {
			// one day event
			$out .= '<div class="date">';
			$out .= '<div class="end-date">';
			$out .= self::html_date( $start_date );
			$out .= '</div>';
		}
		else {
			// multi day event
			$out .= '<div class="date multi-date">';
			$out .= '<div class="start-date">';
			$out .= self::html_date( $start_date );
			$out .= '</div>';
			$out .= '<div class="end-date">';
			$out .= self::html_date( $end_date );
			$out .= '</div>';
		}
		$out .= '</div>';
		return $out;
	}

	private static function html_date( $date ) {
		$out = '<div class="weekday">'.date( 'D', $date ).'</div>';
		$out .= '<div class="day">'.date( 'd', $date ).'</div>';
		$out .= '<div class="month">'.date( 'M', $date ).'</div>';
		$out .= '<div class="year">'.date( 'Y', $date ).'</div>';
		return $out;
	}
}
?>
