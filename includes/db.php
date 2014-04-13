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

	public function get_events($date_filter=null, $cat_filter=null, $num_events=0, $sort_array=array('start_date ASC', 'time ASC', 'end_date ASC')) {
		global $wpdb;
		$where_string = $this->get_sql_filter_string($date_filter, $cat_filter);
		$sql = 'SELECT * FROM '.$this->table.' WHERE '.$where_string.' ORDER BY '.implode(', ', $sort_array);
		if('upcoming' === $date_filter && is_numeric($num_events) && 0 < $num_events) {
			$sql .= ' LIMIT '.$num_events;
		}
		return $wpdb->get_results($sql);
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
			$datestring = substr($date[0][$search_date], 0, 4);
		}
		else {
			$datestring = date('Y', current_time('timestamp'));
		}
		return $datestring;
	}

	public function get_num_events() {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM '.$this->table;
		return $wpdb->get_var($sql);
	}

	public function update_event($event_data) {
		global $wpdb;
		// prepare and validate sqldata
		$sqldata = array();
		if(!isset($event_data['id'])) {
			// for new events only:
			//pub_user
			$sqldata['pub_user'] = isset($event_data['id']) ? $event_data['pub_user'] : wp_get_current_user()->ID;
			//pub_date
			$sqldata['pub_date'] = isset($event_data['pub_date']) ? $event_data['pub_date'] : date("Y-m-d H:i:s", current_time('timestamp'));
		}
		//start_date
		if(!isset( $event_data['start_date'])) { return false; }
		$sqldata['start_date'] = $this->validate_sql_date($event_data['start_date']);
		if(false === $sqldata['start_date']) { return false; }
		//end_date
		if(isset($event_data['multiday']) && "1" === $event_data['multiday']) {
			if(!isset($event_data['end_date'])) { $sqldata['end_date'] = $sqldata['start_date']; }
			$sqldata['end_date'] = $this->validate_sql_date($event_data['end_date']);
			if(false === $sqldata['end_date']) { $sqldata['end_date'] = $sqldata['start_date']; }
			elseif(new DateTime($sqldata['end_date']) < new DateTime($sqldata['start_date'])) { $sqldata['end_date'] = $sqldata['start_date']; }
		}
		else {
			$sqldata['end_date'] = $sqldata['start_date'];
		}
		//time
		if( !isset( $event_data['time'] ) ) { $sqldata['time'] = ''; }
		else { $sqldata['time'] = stripslashes($event_data['time']); }
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
		if(isset( $event_data['id'] ) ) { // update event
			return $wpdb->update($this->table, $sqldata, array('id' => $event_data['id']), $sqltypes);
		}
		else { // new event
			$wpdb->insert($this->table, $sqldata, $sqltypes);
			return $wpdb->insert_id;
		}
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

	private function validate_sql_date($datestring) {
		$d = date_create_from_format('Y-m-d', $datestring);
		if($d && $d->format('Y-m-d') == $datestring) {
			return $datestring;
		}
		return false;
	}

	private function get_sql_filter_string($date_filter=null, $cat_filter=null) {
		$sql_filter_string = '';
		// date filter
		$date_filter=str_replace(' ','',$date_filter);
		if(null != $date_filter && 'all' != $date_filter && '' != $date_filter) {
			if(is_numeric($date_filter)) {
				// get events of a specific year
				$range_start = $date_filter.'-01-01';
				$range_end = $date_filter.'-12-31';
			}
			elseif('past' === $date_filter) {
				// get only events in the past
				$range_start = '0000-01-01';
				$range_end = date('Y-m-d', current_time('timestamp')-86400); // previous day (86400 seconds = 1*24*60*60 = 1 day))
			}
			else {  // upcoming
				// get only events from today and in the future
				$range_start = date('Y-m-d', current_time('timestamp'));
				$range_end = '9999-12-31';
			}
			$sql_filter_string .= '(end_date >= "'.$range_start.'" AND start_date <= "'.$range_end.'")';
		}

		// cat_filter
		$cat_filter=str_replace(' ', '', $cat_filter);
		if(null != $cat_filter && 'all' != $cat_filter && '' != $cat_filter) {
			if('' != $sql_filter_string) {
				$sql_filter_string .= ' AND ';
			}
			$sql_filter_string .= '(';
			$delimiters = array('&' => ' AND ',
			                    '|' => ' OR ',
			                    ',' => ' OR ',
			                    '(' => '(',
			                    ')' => ')');
			$delimiter_keys = array_keys($delimiters);
			$tmp_element = '';
			$len_cat_filter = strlen($cat_filter);
			for($i=0; $i<$len_cat_filter; $i++) {
				if(in_array($cat_filter[$i], $delimiter_keys)) {
					if('' !== $tmp_element) {
						$sql_filter_string .= 'categories LIKE "%|'.$tmp_element.'|%"';
						$tmp_element = '';
					}
					$sql_filter_string .= $delimiters[$cat_filter[$i]];
				}
				else {
					$tmp_element .= $cat_filter[$i];
				}
			}
			if('' !== $tmp_element) {
				$sql_filter_string .= 'categories LIKE "%|'.$tmp_element.'|%"';
			}
			$sql_filter_string .= ')';
		}

		// no filter
		if('' == $sql_filter_string) {
			$sql_filter_string = '1';   // in SQL "WHERE 1" is used to show all events
		}

		return $sql_filter_string;
	}

	/** ************************************************************************************************************
	 * Function to truncate and shorten text
	 *
	 * @param string $html The html code which should be shortened
	 * @param int $length The length to which the text should be shortened
	 * @param bool skip If this value is true the truncate will be skipped (nothing will be done)
	 * @param bool perserve_tags Specifies if html tags should be preserved or if only the text should be shortened
	 ***************************************************************************************************************/
	public function truncate($html, $length, $skip=false, $preserve_tags=true) {
		if(0 >= $length || strlen($html) <= $length || $skip) {
			// do nothing
			return $html;
		}
		elseif(!$preserve_tags) {
			// only shorten text
			return substr($html, 0, $length);
		}
		else {
			// truncate with preserving html tags
			$printedLength = 0;
			$position = 0;
			$tags = array();
			$out = '';
			while($printedLength < $length && preg_match('{</?([a-z]+\d?)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position)) {
				list($tag, $tagPosition) = $match[0];
				// Print text leading up to the tag
				$str = substr($html, $position, $tagPosition - $position);
				if($printedLength + strlen($str) > $length) {
					$out .= substr($str, 0, $length - $printedLength);
					$printedLength = $length;
					break;
				}
				$out .= $str;
				$printedLength += strlen($str);
				if($tag[0] == '&') {
					// Handle the entity
					$out .= $tag;
					$printedLength++;
				}
				else {
					// Handle the tag
					$tagName = $match[1][0];
					if($tag[1] == '/') {
						// This is a closing tag
						$openingTag = array_pop($tags);
						assert($openingTag == $tagName); // check that tags are properly nested
						$out .= $tag;
					}
					else if($tag[strlen($tag) - 2] == '/') {
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
			if($printedLength < $length && $position < strlen($html)) {
				$out .= substr($html, $position, $length - $printedLength);
			}
			// Print "..." if the html is not complete
			if(strlen($html) != $position) {
				$out .= ' &hellip;';
			}
			// Close any open tags.
			while(!empty($tags)) {
				$out .= '</'.array_pop($tags).'>';
			}
			return $out;
		}
	}
}

/* create date_create_from_format (DateTime::createFromFormat) alternative for PHP 5.2
 *
 * This function is only a small implementation of this function with reduced functionality to handle sql dates (format: 2014-01-31)
 */
if(!function_exists("date_create_from_format")) {
	function date_create_from_format($dformat, $dvalue) {
		$d = new DateTime($dvalue);
		return $d;
	}
}
?>
