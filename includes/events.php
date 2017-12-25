<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/daterange.php');
require_once(EL_PATH.'includes/event.php');

/**
 * Class to access events
 */
class EL_Events {
	private static $instance;
	private $options;
	private $daterange;
	private $el_category_taxonomy = 'el_eventcategory';

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
		$this->daterange = &EL_Daterange::get_instance();
	}

	public function get($options) {
		// TODO: use WP_Query to get events
		global $wpdb;
		$options = wp_parse_args($options, array('date_filter'=>null, 'cat_filter'=>null, 'num_events'=>0, 'order'=>array('startdate ASC', 'time ASC', 'enddate ASC')));
		$where_string = $this->get_sql_filter_string($options['date_filter'], $options['cat_filter']);
		$sql = 'SELECT ID FROM ('.$this->get_events_sql('ID').') as events WHERE '.$this->get_sql_filter_string($options['date_filter'], $options['cat_filter']).' ORDER BY '.implode(', ', $options['order']);
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
		$options = wp_parse_args($options, array('date_filter'=>null, 'cat_filter'=>null, 'order'=>'asc', 'hierarchical'=>false));
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
		$where = $this->get_sql_filter_string($options['date_filter'], $options['cat_filter']);
		if('desc' != $options['order']) {
			$options['order'] = 'asc';   // standard order is ASC
		}
		$sql = 'SELECT DISTINCT '.$distinct.' AS listitems FROM ('.$this->get_events_sql('ID').' WHERE '.$where.') AS filterlist ORDER BY listitems '.$options['order'];
		$result = wp_list_pluck($wpdb->get_results($sql), 'listitems');
		if('categories' === $type && count($result)) {
			// split result at | chars
			$cats = array();
			foreach($result as $concat_cat) {
				if(!empty($concat_cat)) {
					$cats = array_merge($cats, explode('|', $concat_cat));
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

	private function get_events_sql($posts_fields='*', $postmeta_fields=array('startdate', 'enddate', 'starttime', 'location'), $incl_categories=true) {
		global $wpdb;
		$tposts = $wpdb->prefix.'posts';
		$tpostmeta = $wpdb->prefix.'postmeta';
		$sql = 'SELECT * FROM (SELECT DISTINCT '.implode(', ', (array)$posts_fields);
		foreach((array)$postmeta_fields as $pm) {
			$sql .= ', (SELECT meta_value FROM '.$tpostmeta.' WHERE '.$tpostmeta.'.meta_key = "'.$pm.'" AND '.$tpostmeta.'.post_id = '.$tposts.'.ID) AS '.$pm;
		}
		if($incl_categories) {
			$tterms = $wpdb->prefix.'terms';
			$ttax = $wpdb->prefix.'term_taxonomy';
			$ttermrel = $wpdb->prefix.'term_relationships';
			$sql .= ', (SELECT GROUP_CONCAT('.$tterms.'.slug SEPARATOR "|") FROM '.$tterms.'
			            INNER JOIN '.$ttax.' on '.$tterms.'.term_id = '.$ttax.'.term_id
			            INNER JOIN '.$ttermrel.' wpr on wpr.term_taxonomy_id = '.$ttax.'.term_taxonomy_id
			            WHERE taxonomy= "'.$this->el_category_taxonomy.'" and '.$tposts.'.ID = wpr.object_id
			           ) AS categories';
		}
		$sql .= ' FROM '.$tposts.' WHERE post_type = "el_events" AND post_status = "publish") AS events';
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

	private function convert_events_timeformat($events) {
		foreach($events as $event) {
			$this->convert_event_timeformat($event);
		}
		return $events;
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
		return (get_term_by('slug', $cat_slug, $this->el_category_taxonomy) instanceof WP_Term);
	}

	public function add_category($name, $args) {
		return wp_insert_term($name, $this->el_category_taxonomy, $args);
	}

	public function get_cat_by_id($cat) {
		return get_term_by('id', $cat, $this->el_category_taxonomy);
	}

	public function get_cat_by_slug($cat) {
		return get_term_by('slug', $cat, $this->el_category_taxonomy);
	}
}
?>
