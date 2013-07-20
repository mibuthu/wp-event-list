<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once( EL_PATH.'includes/options.php' );

// Class to manage categories
class EL_Categories {
	private static $instance;
	private $options;
	private $db;
	private $cat_array;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new EL_Categories();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->options = &EL_Options::get_instance();
		$this->db = &EL_Db::get_instance();
		$this->initalize_cat_array();
	}

	private function initalize_cat_array() {
		$cat_array = (array) $this->options->get( 'el_categories' );
		$this->cat_array = array();
		foreach( $cat_array as $cat ) {
			// check if "parent" field is available (required due to old version without parent field)
			// this can be removed in a later version
			if(!isset($cat['parent']) || !isset($cat['level'])) {
				$cat['parent'] = '';
				$cat['level'] = 0;
			}
			$this->cat_array[$cat['slug']] = $cat;
		}
	}

	public function add_category( $cat_data ) {
		// check if name was set
		if( !isset( $cat_data['name'] ) || '' == $cat_data['name'] ) {
			return false;
		}
		// check if name already exists
		foreach( $this->cat_array as $cat ) {
			if( $cat['name'] == $cat_data['name'] ) {
				return false;
			}
		}
		// set cat name
		$cat['name'] = trim( $cat_data['name'] );
		// set slug
		// generate slug if no slug was given
		if( !isset( $cat_data['slug'] ) || '' == $cat_data['slug'] ) {
			$cat_data['slug'] = $cat_data['name'];
		}
		// make slug unique
		$cat['slug'] = $slug = sanitize_title( $cat_data['slug'] );
		$num = 1;
		while( isset( $this->cat_array[$cat['slug']] ) ) {
			$num++;
			$cat['slug'] = $slug.'-'.$num;
		}
		// set parent and level
		if(!isset($cat_data['parent'])) {
			$cat_data['parent'] = '';
		}
		$cat['parent'] = $cat_data['parent'];
		if('' == $cat['parent']) {
			$cat['level'] = 0;
		}
		else {
			$cat['level'] = $this->cat_array[$cat_data['parent']]['level'] + 1;
		}
		// set description
		$cat['desc'] = isset( $cat_data['desc'] ) ? trim( $cat_data['desc'] ) : '';
		// add category
		$this->cat_array[$cat['slug']] = $cat;
		return $this->safe_categories();
	}

	public function edit_category( $cat_data, $old_slug ) {
		// check if slug already exists
		if(!isset($this->cat_array[$old_slug])) {
			return false;
		}
		unset($this->cat_array[$old_slug]);
		return $this->add_category($cat_data);
	}

	public function remove_categories( $slugs ) {
		foreach( $slugs as $slug ) {
			unset( $this->cat_array[$slug] );
		}
		return $this->safe_categories();
	}

	private function safe_categories() {
		$cat_array = $this->get_cat_array('slug', true);
		if(!is_array($cat_array) || empty($cat_array)) {
			return false;
		}
		if(!$this->options->set('el_categories', $cat_array)) {
			return false;
		}
		return true;
	}

	public function get_cat_array($sort_key='name', $sort_order='asc') {
		return $this->get_cat_child_array('', $sort_key, $sort_order);
	}

	private function get_cat_child_array($slug, $sort_key, $sort_order) {
		$children = $this->get_children($slug, $sort_key, $sort_order);
		if(empty($children)) {
			return null;
		}
		$ret = array();
		foreach($children as $child) {
			$ret[] = $this->cat_array[$child];
			$grandchilds = $this->get_cat_child_array($child, $sort_key, $sort_order);
			if(is_array($grandchilds)) {
				$ret = array_merge($ret, $grandchilds);
			}
		}
		return $ret;
	}

	private function get_children($slug='', $sort_key='slug', $sort_order='asc') {
		// create array with slugs
		$ret = array();
		foreach($this->cat_array as $cat) {
			if($slug == $cat['parent']) {
				$ret[] = $cat['slug'];
			}
		}
		// sort array
		if('slug' == $sort_key) {
			if('desc' == $sort_order) {
				rsort($ret);
			}
			else {
				sort($ret);
			}
			return $ret;
		}
		else {
			$sort_key_array = array();
			foreach($ret as $cat_slug) {
				$sort_key_array[] = strtolower($this->cat_array[$cat_slug][$sort_key]);
			}
			asort($sort_key_array);
			$ret_sorted = array();
			foreach($sort_key_array as $key => $value) {
				$ret_sorted[] = $ret[$key];
			}
			return $ret_sorted;
		}
	}

	public function get_category_data($slug) {
		return $this->cat_array[$slug];
	}

	public function get_category_string($slug_text) {
		if(2 >= strlen($slug_text)) {
			return '';
		}
		$slug_array = explode('|', substr( $slug_text, 1, -1));
		$name_array = array();
		foreach($slug_array as $slug) {
			$name_array[] = $this->cat_array[$slug]['name'];
		}
		sort($name_array, SORT_STRING);
		return implode(', ', $name_array);
	}
}