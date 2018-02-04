<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');
require_once(EL_PATH.'includes/daterange.php');
require_once(EL_PATH.'includes/event.php');

/**
 * Class to access events
 */
class EL_Events {
	private static $instance;
	private $options;
	private $events_post_type;
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
		$this->options = &EL_Options::get_instance();
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		$this->daterange = &EL_Daterange::get_instance();
	}

	public function get($options=array()) {
		global $wpdb;
		$options = wp_parse_args($options, array('date_filter'=>null, 'cat_filter'=>null, 'num_events'=>0, 'order'=>array('startdate ASC', 'starttime ASC', 'enddate ASC'), 'status'=>'publish'));
		$event_sql = $this->get_events_sql($options);
		$filter_sql = $this->get_sql_filter_string($options['date_filter'], $options['cat_filter']);
		$sql = 'SELECT ID FROM ('.$event_sql.') AS events WHERE '.$filter_sql.' ORDER BY '.implode(', ', $options['order']);
		if('upcoming' === $options['date_filter'] && is_numeric($options['num_events']) && 0 < $options['num_events']) {
			$sql .= ' LIMIT '.$options['num_events'];
		}
		$result = $wpdb->get_results($sql, 'ARRAY_N');
		$events = array();
		foreach($result as $row) {
			$events[] = new EL_Event($row[0]);
		}
		return $events;
	}

	public function get_filter_list($type, $options) {
		global $wpdb;
		$options = wp_parse_args($options, array('date_filter'=>null, 'cat_filter'=>null, 'order'=>'asc', 'hierarchical'=>false, 'status'=>'publish'));
		switch($type) {
			case 'years':
				$distinct = 'SUBSTR(`startdate`,1,4)';
				break;
			case 'months':
				$distinct = 'SUBSTR(`startdate`,1,7)';
				break;
			case 'categories':
				$distinct = '`categories`';
				break;
			default:
				die('ERROR: Unknown filterlist type!');
		}
		$event_sql = $this->get_events_sql($options);
		$where = $this->get_sql_filter_string($options['date_filter'], $options['cat_filter']);
		if('desc' != $options['order']) {
			$options['order'] = 'asc';   // standard order is ASC
		}
		$sql = 'SELECT DISTINCT '.$distinct.' AS listitems FROM ('.$event_sql.' WHERE '.$where.') AS filterlist ORDER BY listitems '.strtoupper($options['order']);
		$result = wp_list_pluck($wpdb->get_results($sql), 'listitems');
		if('categories' === $type && count($result)) {
			// split result at | chars
			$cats = array();
			foreach($result as $concat_cat) {
				if(!empty($concat_cat)) {
					$cats = array_merge($cats, explode('|', substr($concat_cat, 1, -1)));
				}
			}
			$result = array_unique($cats);
			sort($result);
		}
		// handling of the hierarchical category structure
		if('categories' === $type && $options['hierarchical']) {
			// create terms object array
			$terms = array();
			foreach($result as $cat) {
				$terms[] = $this->get_cat_by_slug($cat);
			}
			/*
			* Separate elements into two buckets: top level and children elements.
			* Children_elements is two dimensional array, eg.
			* Children_elements[10][] contains all sub-elements whose parent is 10.
			*/
			$toplevel_elements = array();
			$children_elements  = array();
			foreach($terms as $t) {
				if(empty($t->parent)) {
					$toplevel_elements[] = $t;
				}
				else {
					$children_elements[$t->parent][] = $t;
				}
			}
			// create return array
			$result = array();
			foreach($toplevel_elements as $e) {
				$this->add_term_to_list($e, 0, $children_elements, $result);
			}
			// handle the children_elements of which the corresponding toplevel element is not included
			foreach($children_elements as $eid => &$e) {
				// continue if parent is available in children_elements -> the elements will be handled there
				if(isset($children_elements[$this->get_cat_by_id($eid)->parent])) {
					continue;
				}
				foreach($e as &$op) {
					$this->add_term_to_list($op, 0, $children_elements, $result);
				}
			}
		}
		return $result;
	}

	private function add_term_to_list(&$element, $level, &$children, &$list) {
		// Add level to object and add object to list
		$element->level = $level;
		$list[] = $element;
		// Handle children of element
		if(isset($children[$element->term_id])) {
			foreach($children[$element->term_id] as &$c) {
				$this->add_term_to_list($c, $level+1, $children, $list);
			}
			unset($children[$element->term_id]);
		}
	}

	private function get_events_sql($options) {
		global $wpdb;
		$options = wp_parse_args($options, array('posts_fields'=>'ID', 'postmeta_fields'=>array('startdate', 'enddate', 'starttime', 'location'), 'incl_categories'=>true, 'status'=>'publish'));
		$sql = 'SELECT * FROM (SELECT DISTINCT '.implode(', ', (array)$options['posts_fields']);
		foreach((array)$options['postmeta_fields'] as $pm) {
			$sql .= ', (SELECT meta_value FROM '.$wpdb->postmeta.' WHERE '.$wpdb->postmeta.'.meta_key = "'.$pm.'" AND '.$wpdb->postmeta.'.post_id = '.$wpdb->posts.'.ID) AS '.$pm;
		}
		if($options['incl_categories']) {
			$sql .= ', (CONCAT("|", (SELECT GROUP_CONCAT('.$wpdb->terms.'.slug SEPARATOR "|") FROM '.$wpdb->terms
			       .' INNER JOIN '.$wpdb->term_taxonomy.' ON '.$wpdb->terms.'.term_id = '.$wpdb->term_taxonomy.'.term_id'
			       .' INNER JOIN '.$wpdb->term_relationships.' wpr ON wpr.term_taxonomy_id = '.$wpdb->term_taxonomy.'.term_taxonomy_id'
			       .' WHERE taxonomy= "'.$this->events_post_type->taxonomy.'" AND '.$wpdb->posts.'.ID = wpr.object_id'
			       .'), "|")) AS categories';
		}
		$status_sql = empty($options['status']) ? '' : ' AND post_status = "'.$options['status'].'"';
		$sql .= ' FROM '.$wpdb->posts.' WHERE post_type = "el_events"'.$status_sql.') AS events';
		return $sql;
	}

	public function get_num_events($status='publish') {
		$count = wp_count_posts('el_events');
		// return special case 'all'
		if('all' === $status) {
			return $count->publish + $count->future + $count->draft + $count->pending + $count->private;
		}
		if(in_array($status, array('publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit'))) {
			return $count->$status;
		}
		return false;
	}

	public function delete_events($id_array) {
		global $wpdb;
		// sanitize to int values only
		$id_array = array_map('intval', $id_array);
		if(in_array(0, $id_array)) {
			// something is wrong with the event_ids array
			return false;
		}
		// sql query
		$num_deleted = intval($wpdb->query('DELETE FROM '.$this->table.' WHERE id IN ('.implode(',', $id_array).')'));
		return $num_deleted == count($id_array);
	}

	public function count_events( $slug ) {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM '.$this->table.' WHERE categories LIKE "%|'.$slug.'|%"';
		return $wpdb->get_var( $sql );
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
		$date_for_startrange = ('' == $this->options->get('el_multiday_filterrange')) ? 'startdate' : 'enddate';
		return '('.$date_for_startrange.' >= "'.$range[0].'" AND startdate <= "'.$range[1].'")';
	}

	private function sql_cat_filter ($element) {
		return 'categories LIKE "%|'.$element.'|%"';
	}

	public function cat_exists($cat_slug) {
		return (get_term_by('slug', $cat_slug, $this->events_post_type->taxonomy) instanceof WP_Term);
	}

	public function insert_category($name, $args) {
		return wp_insert_term($name, $this->events_post_type->taxonomy, $args);
	}

	public function update_category($slug, $args) {
		return wp_update_term($this->get_cat_by_slug($slug)->term_id, $this->events_post_type->taxonomy, $args);
	}

	public function delete_category($slug) {
		return wp_delete_term($this->get_cat_by_slug($slug)->term_id, $this->events_post_type->taxonomy);
	}

	public function get_cat_by_id($cat) {
		return get_term_by('id', $cat, $this->events_post_type->taxonomy);
	}

	public function get_cat_by_slug($cat) {
		return get_term_by('slug', $cat, $this->events_post_type->taxonomy);
	}
}
?>
