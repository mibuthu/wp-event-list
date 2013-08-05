<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once( EL_PATH.'includes/options.php' );

// Class for database access via wordpress functions
class EL_Db {
	const VERSION = '0.2';
	const TABLE_NAME = 'event_list';
	private static $instance;
	private $table;
	private $options;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new EL_Db();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix.self::TABLE_NAME;
		$this->options = &EL_Options::get_instance();
	}

	// UPDATE DB
	public function upgrade_check() {
		if( $this->options->get( 'el_db_version' ) != self::VERSION ) {
			$sql = 'CREATE TABLE '.$this->table.' (
				id int(11) NOT NULL AUTO_INCREMENT,
				pub_user bigint(20) NOT NULL,
				pub_date datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
				start_date date NOT NULL DEFAULT "0000-00-00",
				end_date date DEFAULT NULL,
				time text,
				title text NOT NULL,
				location text,
				details text,
				categories text,
				history text,
				PRIMARY KEY  (id) )
				DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;';
			require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			$this->options->set( 'el_db_version', self::VERSION );
		}
	}

	public function get_events( $date_range='all', $num_events=0, $cat_filter=null, $sort_array=array( 'start_date ASC', 'time ASC', 'end_date ASC') ) {
		global $wpdb;

		// set date for data base query
		if( is_numeric( $date_range ) ) {
			// get events of a specific year
			$range_start = $date_range.'-01-01';
			$range_end = $date_range.'-12-31';
		}
		elseif( 'all' === $date_range ) {
			// get all events
			$range_start = '0000-01-01';
			$range_end = '9999-12-31';
		}
		else {  // upcoming
			// get only events in the future
			$range_start = date( 'Y-m-d' );
			$range_end = '9999-12-31';
		}
		// set category filter
		$sql_cat_filter = empty( $cat_filter ) ? '' : ' AND ( categories LIKE "%|'.implode( '|%" OR categories LIKE "%|', $cat_filter ).'|%" )';
		$sql = 'SELECT * FROM '.$this->table.' WHERE end_date >= "'.$range_start.'" AND start_date <= "'.$range_end.'"'.$sql_cat_filter.' ORDER BY '.implode( ', ', $sort_array );
		if( 'upcoming' === $date_range && is_numeric($num_events) && 0 < $num_events ) {
			$sql .= ' LIMIT '.$num_events;
		}
		return $wpdb->get_results( $sql );
	}

	public function get_event( $id ) {
		global $wpdb;
		$sql = 'SELECT * FROM '.$this->table.' WHERE id = '.$id.' LIMIT 1';
		return $wpdb->get_row( $sql );
	}

	public function get_event_date( $event ) {
		global $wpdb;
		if( $event === 'first' ) {
			// first year
			$search_date = 'start_date';
			$sql = 'SELECT DISTINCT '.$search_date.' FROM '.$this->table.' WHERE '.$search_date.' != "0000-00-00" ORDER BY '.$search_date.' ASC LIMIT 1';
		}
		else {
			// last year
			$search_date = 'end_date';
			$sql = 'SELECT DISTINCT '.$search_date.' FROM '.$this->table.' WHERE '.$search_date.' != "0000-00-00" ORDER BY '.$search_date.' DESC LIMIT 1';
		}
		$date = $wpdb->get_results($sql, ARRAY_A);
		if( !empty( $date ) ) {
			$date = $this->extract_date( $date[0][$search_date],'Y');
		}
		else {
			$date = date("Y");
		}
		return $date;
	}

	public function update_event( $event_data, $dateformat=NULL ) {
		global $wpdb;
		// prepare and validate sqldata
		$sqldata = array();
		if(!isset($event_data['id'])) {
			// for new events only:
			//pub_user
			$sqldata['pub_user'] = isset($event_data['id']) ? $event_data['pub_user'] : wp_get_current_user()->ID;
			//pub_date
			$sqldata['pub_date'] = isset($event_data['pub_date']) ? $event_data['pub_date'] : date("Y-m-d H:i:s");
		}
		//start_date
		if( !isset( $event_data['start_date']) ) { return false; }
		$start_timestamp = 0;
		$sqldata['start_date'] = $this->extract_date( $event_data['start_date'], "Y-m-d", $dateformat, $start_timestamp );
		if( false === $sqldata['start_date'] ) { return false; }
		//end_date
		if( !isset( $event_data['end_date']) ) { return false; }
		if( isset( $event_data['multiday'] ) && "1" === $event_data['multiday'] ) {
			$end_timestamp = 0;
			$sqldata['end_date'] = $this->extract_date( $event_data['end_date'], "Y-m-d", $dateformat, $end_timestamp );
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
		//categories
		if( !isset( $event_data['categories'] ) || !is_array( $event_data['categories'] ) || empty( $event_data['categories'] ) ) { $sqldata['categories'] = ''; }
		else { $sqldata['categories'] = '|'.implode( '|', $event_data['categories'] ).'|'; }
		//types for sql data
		$sqltypes = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
		if( isset( $event_data['id'] ) ) { // update event
			$wpdb->update( $this->table, $sqldata, array( 'id' => $event_data['id'] ), $sqltypes );
		}
		else { // new event
			$wpdb->insert( $this->table, $sqldata, $sqltypes );
		}
		return true;
	}

	public function delete_events( $event_ids ) {
		global $wpdb;
		// filter event_ids string to int values only
		$filtered_ids = array_map( 'intval', $event_ids );
		if( count( $event_ids ) != count( $filtered_ids ) )
		{
			// something is wrong with the event_ids array
			return false;
		}
		// sql query
		$num_deleted = (int) $wpdb->query( 'DELETE FROM '.$this->table.' WHERE id IN ('.implode( ',', $filtered_ids ).')' );
		if( $num_deleted == count( $event_ids ) ) {
			return true;
		}
		else {
			return false;
		}
	}

	public function remove_category_in_events($category_slugs) {
		global $wpdb;
		$sql = 'SELECT * FROM '.$this->table.' WHERE categories LIKE "%|'.implode('|%" OR categories LIKE "%|', $category_slugs).'|%"';
		$affected_events = $wpdb->get_results($sql, ARRAY_A);
		foreach($affected_events as $event) {
			// remove category from categorystring
			foreach($category_slugs as $slug) {
				$event['categories'] = str_replace('|'.$slug.'|', '|', $event['categories']);
			}
			if(3 > strlen( $event['categories'])) {
				$event['categories'] = '';
			}
			else {
				$event['categories'] = explode( '|', substr($event['categories'], 1, -1));
			}
			$this->update_event($event);
			}
		return count($affected_events);
	}

	public function change_category_slug_in_events($old_slug, $new_slug) {
		global $wpdb;
		$sql = 'SELECT * FROM '.$this->table.' WHERE categories LIKE "%|'.$old_slug.'|%"';
		$affected_events = $wpdb->get_results($sql, ARRAY_A);
		foreach( $affected_events as $event ) {
			// replace slug in categorystring
			$event['categories'] = str_replace('|'.$old_slug.'|', '|'.$new_slug.'|', $event['categories']);
			$event['categories'] = explode( '|', substr($event['categories'], 1, -1 ) );
			$this->update_event( $event );
		}
		return count( $affected_events );
	}

	public function count_events( $slug ) {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM '.$this->table.' WHERE categories LIKE "%|'.$slug.'|%"';
		return $wpdb->get_var( $sql );
	}

	private function extract_date( $datestring, $ret_format, $dateformat=NULL, &$ret_timestamp=NULL, &$ret_datearray=NULL ) {
		if( NULL === $dateformat ) {
			$date_array = date_parse( $datestring );
		}
		else {
			$date_array = date_parse_from_format( $dateformat, $datestring );
		}
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

	/** ************************************************************************
	 * Function to truncate and shorten text
	 *
	 * @param int $max_length The length to which the text should be shortened
	 * @param string $html The html code which should be shortened
	 ***************************************************************************/
	public function truncate( $max_length, $html ) {
		if( $max_length > 0 && strlen( $html ) > $max_length ) {
			$printedLength = 0;
			$position = 0;
			$tags = array();
			$out = '';
			while ($printedLength < $max_length && preg_match('{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position)) {
				list($tag, $tagPosition) = $match[0];
				// Print text leading up to the tag
				$str = substr($html, $position, $tagPosition - $position);
				if ($printedLength + strlen($str) > $max_length) {
					$out .= substr($str, 0, $max_length - $printedLength);
					$printedLength = $max_length;
					break;
				}
				$out .= $str;
				$printedLength += strlen($str);
				if ($tag[0] == '&') {
					// Handle the entity
					$out .= $tag;
					$printedLength++;
				}
				else {
					// Handle the tag
					$tagName = $match[1][0];
					if ($tag[1] == '/')
					{
						// This is a closing tag
						$openingTag = array_pop($tags);
						assert($openingTag == $tagName); // check that tags are properly nested
						$out .= $tag;
					}
					else if ($tag[strlen($tag) - 2] == '/') {
						// Self-closing tag
						$out .= $tag;
				}
					else {
					// Opening tag
						$out .= $tag;
						$tags[] = $tagName;
					}
				}
				// Continue after the tag
				$position = $tagPosition + strlen($tag);
			}
			// Print any remaining text
			if ($printedLength < $max_length && $position < strlen($html)) {
				$out .= substr($html, $position, $max_length - $printedLength);
			}
			// Print "..." if the html is not complete
			if( strlen( $html) != $position ) {
				$out .= ' ...';
			}
			// Close any open tags.
			while (!empty($tags)) {
				$out .= '</'.array_pop($tags).'>';
			}
			return $out;
		}
		else {
			return $html;
		}
	}
}


// Define "date_parse_from_format" (This is required for php versions < 5.3)
if( !function_exists('date_parse_from_format') ){
	function date_parse_from_format($format, $date) {
		// reverse engineer date formats
		$keys = array(
			'Y' => array('year', '\d{4}'),              // A full numeric representation of a year, 4 digits
			'y' => array('year', '\d{2}'),              // A two digit representation of a year
			'm' => array('month', '\d{2}'),             // Numeric representation of a month, with leading zeros
			'n' => array('month', '\d{1,2}'),           // Numeric representation of a month, without leading zeros
			'M' => array('month', '[A-Z][a-z]{3}'),     // A short textual representation of a month, three letters
			'F' => array('month', '[A-Z][a-z]{2,8}'),   // A full textual representation of a month, such as January or March
			'd' => array('day', '\d{2}'),               // Day of the month, 2 digits with leading zeros
			'j' => array('day', '\d{1,2}'),             // Day of the month without leading zeros
			'D' => array('day', '[A-Z][a-z]{2}'),       // A textual representation of a day, three letters
			'l' => array('day', '[A-Z][a-z]{6,9}'),     // A full textual representation of the day of the week
			'u' => array('hour', '\d{1,6}'),            // Microsecondes
			'h' => array('hour', '\d{2}'),              // 12-hour format of an hour with leading zeros
			'H' => array('hour', '\d{2}'),              // 24-hour format of an hour with leading zeros
			'g' => array('hour', '\d{1,2}'),            // 12-hour format of an hour without leading zeros
			'G' => array('hour', '\d{1,2}'),            // 24-hour format of an hour without leading zeros
			'i' => array('minute', '\d{2}'),            // Minutes with leading zeros
			's' => array('second', '\d{2}')             // Seconds, with leading zeros
		);

		// convert format string to regex
		$regex = '';
		$chars = str_split($format);
		foreach ( $chars AS $n => $char ) {
			$lastChar = isset($chars[$n-1]) ? $chars[$n-1] : '';
			$skipCurrent = '\\' == $lastChar;
			if ( !$skipCurrent && isset($keys[$char]) ) {
				$regex .= '(?P<'.$keys[$char][0].'>'.$keys[$char][1].')';
			}
			else if ( '\\' == $char ) {
				$regex .= $char;
			}
			else {
				$regex .= preg_quote($char);
			}
		}

		// create array
		$dt = array();
		$dt['error_count'] = 0;
		$dt['errors'] = array();
		// now try to match it
		if( preg_match('#^'.$regex.'$#', $date, $dt) ){
			foreach ( $dt AS $k => $v ){
				if ( is_int($k) ){
					unset($dt[$k]);
				}
			}
			if( !checkdate($dt['month'], $dt['day'], $dt['year']) ){
				$dt['error_count'] = 1;
				array_push( $dt['errors'], 'ERROR' );
			}
		}
		else {
			$dt['error_count'] = 1;
			array_push( $dt['errors'], 'ERROR' );
		}
		$dt['fraction'] = '';
		$dt['warning_count'] = 0;
		$dt['warnings'] = array();
		$dt['is_localtime'] = 0;
		$dt['zone_type'] = 0;
		$dt['zone'] = 0;
		$dt['is_dst'] = '';
		return $dt;
	}
}
?>
