<?php
//require_once( EL_PATH.'php/options.php' );

// Class for database access via wordpress functions
class el_db {
	const VERSION = "0.1";
	const TABLE_NAME = "event_list";

	// UPDATE DB
	public static function update_check() {
		// TODO: added version checking
//		if( el_options::get( 'el_db_version' ) != self::VERSION) {
			$sql = 'CREATE TABLE '.self::table_name().' (
				id int(11) NOT NULL AUTO_INCREMENT,
				pub_user bigint(20) NOT NULL,
				pub_date datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
				start_date date NOT NULL DEFAULT "0000-00-00",
				end_date date DEFAULT NULL,
				time text,
				title text NOT NULL,
				location text,
				details text,
				history text,
				PRIMARY KEY  (id) )
				DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;';

			require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

//			el_options::set( 'el_db_version', self::VERSION );
//		}
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix.self::TABLE_NAME;
	}

	public static function get_events( $date_range='all' ) {
		global $wpdb;

		// set date for data base query
		if( $date_range === 'all' ) {
			// get all events
			$range_start = '0000-01-01';
			$range_end = '9999-12-31';
		}
		elseif( $date_range === 'upcoming' ) {
			// get only events in the future
			$range_start = date( 'Y-m-d' );
			$range_end = '9999-12-31';
		}
		else {
			$range_start = $date_range.'-01-01';
			$range_end = $date_range.'-12-31';
		}
		$sql = 'SELECT * FROM '.self::table_name().' WHERE (end_date >= "'.$range_start.'" AND start_date <= "'.$range_end.'") ORDER BY start_date ASC, time ASC, end_date ASC';
		return $wpdb->get_results( $sql );
	}

	public static function get_event( $id ) {
		global $wpdb;
		$sql = 'SELECT * FROM '.self::table_name().' WHERE id = '.$id.' LIMIT 1';
		return $wpdb->get_row( $sql );
	}

	public static function get_event_date( $event ) {
		global $wpdb;
		if( $event === 'first' ) {
			// first year
			$search_date = 'start_date';
			$sql = 'SELECT DISTINCT '.$search_date.' FROM '.self::table_name().' WHERE '.$search_date.' != "0000-00-00" ORDER BY '.$search_date.' ASC LIMIT 1';
		}
		else {
			// last year
			$search_date = 'end_date';
			$sql = 'SELECT DISTINCT '.$search_date.' FROM '.self::table_name().' WHERE '.$search_date.' != "0000-00-00" ORDER BY '.$search_date.' DESC LIMIT 1';
		}
		$date = $wpdb->get_results($sql, ARRAY_A);
		if( !empty( $date ) ) {
			$date = self::extract_date( $date[0][$search_date],'Y');
		}
		else {
			$date = date("Y");
		}
		return $date;
	}

	public static function update_event( $event_data ) {
		global $wpdb;
		// prepare and validate sqldata
		$sqldata = array();
		//pub_user
		$sqldata['pub_user'] = wp_get_current_user()->ID;
		//pub_date
		$sqldata['pub_date'] = date( "Y-m-d H:i:s" );
		//start_date
		if( !isset( $event_data['start_date']) ) { return false; }
		$start_timestamp = 0;
		$sqldata['start_date'] = self::extract_date( $event_data['start_date'], "Y-m-d", $start_timestamp );
		if( false === $sqldata['start_date'] ) { return false; }
		//end_date
		if( !isset( $event_data['end_date']) ) { return false; }
		if( isset( $event_data['multiday'] ) && "1" === $event_data['multiday'] ) {
			$end_timestamp = 0;
			$sqldata['end_date'] = self::extract_date( $event_data['end_date'], "Y-m-d", $end_timestamp );
			if( false === $sqldata['end_date'] ) { $sqldata['end_date'] = $sqldata['start_date']; }
			elseif( $end_timestamp < $start_timestamp )	 { $sqldata['end_date'] = $sqldata['start_date']; }
		}
		else {
			$sqldata['end_date'] = $sqldata['start_date'];
		}
		//time
		if( !isset( $event_data['time'] ) ) { $sqldata['time'] = ''; }
		else { $sqldata['time'] = $event_data['time']; }
		//title
		if( !isset( $event_data['title'] ) || $event_data['title'] === '' ) { return false; }
		$sqldata['title'] = stripslashes( $event_data['title'] );
		//location
		if( !isset( $event_data['location'] ) ) { $sqldata['location'] = ''; }
		else { $sqldata['location'] = stripslashes ($event_data['location'] ); }
		//details
		if( !isset( $event_data['details'] ) ) { $sqldata['details'] = ''; }
		else { $sqldata['details'] = stripslashes ($event_data['details'] ); }
		//types for sql data
		$sqltypes = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		if( isset( $event_data['id'] ) ) { // update event
			$wpdb->update( self::table_name(), $sqldata, array( 'id' => $event_data['id'] ), $sqltypes );
		}
		else { // new event
			$wpdb->insert( self::table_name(), $sqldata, $sqltypes );
		}
	}

	public static function delete_event( $event_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM '.self::table_name().' WHERE id = "'.$event_id.'"' ) );
	}

	public static function extract_date( $datestring, $ret_format, &$ret_timestamp=NULL, &$ret_datearray=NULL ) {
		$date_array = date_parse( $datestring );
		if( !empty( $date_array['errors']) ) {
			return false;
		}
		if( false === checkdate( $date_array['month'], $date_array['day'], $date_array['year'] ) ) {
			return false;
		}
		$timestamp = mktime( 0, 0, 0, $date_array['month'], $date_array['day'], $date_array['year'] );
		if( isset( $ret_timestamp ) ) {
			$ret_timestamp = $timestamp;
		}
		if( isset( $ret_datearray ) ) {
			$ret_datearray = $date_array;
		}
		return date( $ret_format, $timestamp );
	}

	public static function html_calendar_nav() {
		$first_year = self::get_event_date( 'first' );
		$last_year = self::get_event_date( 'last' );

		if( is_admin() ) {
			$url = "?page=el_admin_main&";
		}
		else if( get_option( 'permalink_structure' ) ) {
			$url = "?";
		}
		else {
			$existing = "?";
			foreach( $_GET as  $k => $v ) {
				if( $k != "ytd" && $k != "event_id" ) $existing .= $k . "=" . $v . "&";
			}
			$url = $existing;
		}

		// Calendar Navigation
		$out = '<div id="eventlist_nav">';
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

		// Title (only if event details are viewed)
		if( isset( $_GET['event_id'] ) ) {
			$out .= '<h2>Event Information:</h2>';
		}
		return $out;
	}
}
?>
