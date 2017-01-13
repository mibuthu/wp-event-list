<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/daterange.php');

// Class for database access via wordpress functions
class EL_Db {
	const VERSION = '0.2';
	const TABLE_NAME = 'event_list';
	private static $instance;
	private $table;
	private $options;
	private $daterange;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix.self::TABLE_NAME;
		$this->options = &EL_Options::get_instance();
		$this->daterange = &EL_Daterange::get_instance();
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

	public function get_distinct_event_data($search_string, $date_filter, $cat_filter, $order='asc') {
		global $wpdb;
		$where_string = $this->get_sql_filter_string($date_filter, $cat_filter);
		if('desc' != $order) {
			$order = 'asc';   // standard order is ASC
		}
		$sql = 'SELECT DISTINCT '.$search_string.' as data FROM '.$this->table.' WHERE '.$where_string.' order by data '.$order;
		return $wpdb->get_results($sql);
	}

	public function get_num_events() {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM '.$this->table;
		return $wpdb->get_var($sql);
	}

	public function update_event($event_data, $check_multiday=false) {
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
		if(!$check_multiday || (isset($event_data['multiday']) && "1" === $event_data['multiday'])) {
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
			$this->update_event($event);
		}
		return count($affected_events);
	}

	public function count_events( $slug ) {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM '.$this->table.' WHERE categories LIKE "%|'.$slug.'|%"';
		return $wpdb->get_var( $sql );
	}

	private function validate_sql_date($datestring) {
		$d = date_create_from_format('Y-m-d', $datestring);
		if($d && $d->format('Y-m-d') == $datestring
		      && 1970 <= $d->format('Y')
		      && 2999 >= $d->format('Y')) {
			return $datestring;
		}
		return false;
	}

	private function get_sql_filter_string($date_filter=null, $cat_filter=null) {
		$sql_filter_string = '';
		// date filter
		$date_filter=str_replace(' ','',$date_filter);
		if(null != $date_filter && 'all' != $date_filter && '' != $date_filter) {
			$sql_filter_string .= $this->filter_walker($date_filter, 'sql_date_filter');
		}
		// cat_filter
		$cat_filter=str_replace(' ', '', $cat_filter);
		if(null != $cat_filter && 'all' != $cat_filter && '' != $cat_filter) {
			if('' != $sql_filter_string) {
				$sql_filter_string .= ' AND ';
			}
			$sql_filter_string .= $this->filter_walker($cat_filter, 'sql_cat_filter');
		}
		// no filter
		if('' == $sql_filter_string) {
			$sql_filter_string = '1';   // in SQL "WHERE 1" is used to show all events
		}
		return $sql_filter_string;
	}

	private function filter_walker(&$filter_text, $callback) {
		$delimiters = array('&' => ' AND ',
		                    '|' => ' OR ',
		                    ',' => ' OR ',
		                    '(' => '(',
		                    ')' => ')');
		$delimiter_keys = array_keys($delimiters);
		$element = '';
		$filter_length = strlen($filter_text);
		$filter_sql = '(';
		for($i=0; $i<$filter_length; $i++) {
			if(in_array($filter_text[$i], $delimiter_keys)) {
				if('' !== $element) {
					$filter_sql .= call_user_func(array($this, $callback), $element);
					$element = '';
				}
				$filter_sql .= $delimiters[$filter_text[$i]];
			}
			else {
				$element .= $filter_text[$i];
			}
		}
		if('' !== $element) {
			$filter_sql .= call_user_func(array($this, $callback), $element);
		}
		return $filter_sql.')';
	}

	private function sql_date_filter($element) {
		$range = $this->daterange->check_date_format($element);
		if(null === $range) {
			$range = $this->daterange->check_daterange_format($element);
		}
		if(null === $range) {
			//set to standard (upcoming)
			$range = $this->daterange->get_date_range($element, $this->options->daterange_formats['upcoming']);
		}
		$date_for_startrange = ('' == $this->options->get('el_multiday_filterrange')) ? 'start_date' : 'end_date';
		return '('.$date_for_startrange.' >= "'.$range[0].'" AND start_date <= "'.$range[1].'")';
	}

	private function sql_cat_filter ($element) {
		return 'categories LIKE "%|'.$element.'|%"';
	}

	/** ************************************************************************************************************
	 * Truncate HTML, close opened tags
	 *
	 * @param string $html          The html code which should be shortened.
	 * @param int    $length        The length (number of characters) to which the text will be shortened.
	 *                              With [0] the full text will be returned. With [auto] also the complete text
	 *                              will be used, but a wrapper div will be added which shortens the text to 1 full
	 *                              line via css.
	 * @param bool   $skip          If this value is true the truncate will be skipped (nothing will be done)
	 * @param bool   $perserve_tags Specifies if html tags should be preserved or if only the text should be
	 *                              shortened.
	 * @param string $link          If an url is given a link to the given url will be added for the ellipsis at
	 *                              the end of the truncated text.
	 ***************************************************************************************************************/
	public function truncate($html, $length, $skip=false, $preserve_tags=true, $link=false) {
		mb_internal_encoding("UTF-8");
		if('auto' == $length) {
			// add wrapper div with css styles for css truncate and return
			return '<div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">'.$html.'</div>';
		}
		if(0 >= $length || mb_strlen($html) <= $length || $skip) {
			// do nothing
			return $html;
		}
		elseif(!$preserve_tags) {
			// only shorten text
			return mb_substr($html, 0, $length);
		}
		else {
			// truncate with preserving html tags
			$truncated = false;
			$printedLength = 0;
			$position = 0;
			$tags = array();
			$out = '';
			while($printedLength < $length && $this->mb_preg_match('{</?([a-z]+\d?)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position)) {
				list($tag, $tagPosition) = $match[0];
				// Print text leading up to the tag
				$str = mb_substr($html, $position, $tagPosition - $position);
				if($printedLength + mb_strlen($str) > $length) {
					$out .= mb_substr($str, 0, $length - $printedLength);
					$printedLength = $length;
					$truncated = true;
					break;
				}
				$out .= $str;
				$printedLength += mb_strlen($str);
				if('&' == $tag[0]) {
					// Handle the entity
					$out .= $tag;
					$printedLength++;
				}
				else {
					// Handle the tag
					$tagName = $match[1][0];
					if($this->mb_preg_match('{^<[\b]}', $tag)) {
						// This is a closing tag
						$openingTag = array_pop($tags);
						$out .= $tag;
					}
					else if($this->mb_preg_match('{/\s?>$}', $tag)) {
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
				$position = $tagPosition + mb_strlen($tag);
			}
			// Print any remaining text
			if($printedLength < $length && $position < mb_strlen($html)) {
				$out .= mb_substr($html, $position, $length - $printedLength);
			}
			// Print ellipsis ("...") if the html was truncated
			if($truncated) {
				if($link) {
					$out .= ' <a href="'.$link.'">&hellip;</a>';
				}
				else {
					$out .= ' &hellip;';
				}
			}
			// Close any open tags.
			while(!empty($tags)) {
				$out .= '</'.array_pop($tags).'>';
			}
			return $out;
		}
	}

	private function mb_preg_match($ps_pattern, $ps_subject, &$pa_matches=null, $pn_flags=0, $pn_offset=0, $ps_encoding=null) {
		// WARNING! - All this function does is to correct offsets, nothing else:
		//(code is independent of PREG_PATTER_ORDER / PREG_SET_ORDER)
		if(is_null($ps_encoding)) {
			$ps_encoding = mb_internal_encoding();
		}
		$pn_offset = strlen(mb_substr($ps_subject, 0, $pn_offset, $ps_encoding));
		$out = preg_match($ps_pattern, $ps_subject, $pa_matches, $pn_flags, $pn_offset);
		if($out && ($pn_flags & PREG_OFFSET_CAPTURE))
			foreach($pa_matches as &$ha_match) {
				$ha_match[1] = mb_strlen(substr($ps_subject, 0, $ha_match[1]), $ps_encoding);
			}
		return $out;
	}
}
?>
