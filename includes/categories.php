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
		if( !isset( $cat_data['slug'] ) || '' == $cat_data['slug'] ) {
			$cat_data['slug'] = $cat_data['name'];
		}
		$cat['name'] = trim( $cat_data['name'] );
		$cat['desc'] = isset( $cat_data['desc'] ) ? trim( $cat_data['desc'] ) : '';
		// make slug unique
		$cat['slug'] = $slug = sanitize_title( $cat_data['slug'] );
		$num = 1;
		while( isset( $this->cat_array[$cat['slug']] ) ) {
			$num++;
			$cat['slug'] = $slug.'-'.$num;
		}
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
		if( !sort( $this->cat_array ) ) {
			return false;
		}
		if( !$this->options->set( 'el_categories', $this->cat_array ) ) {
			return false;
		}
		return true;
	}

	public function get_cat_array() {
		return $this->cat_array;
	}

	public function get_category_data($slug) {
		return $this->cat_array[$slug];
	}

	public function get_category_string( $slugs ) {
		if( 2 >= strlen( $slugs ) ) {
			return '';
		}
		$slug_array = explode( '|', substr( $slugs, 1, -1 ) );
		$name_array = array();
		foreach( $slug_array as $slug ) {
			$name_array[] = $this->cat_array[$slug]['name'];
		}
		return implode( ', ', $name_array );
	}
}