<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');

/**
* This class handles the creation of a custom post type for events
* and the required modifications and additions.
*/
class EL_Events_Post_Type {
	private static $instance;
	public $post_cat_taxonomy = 'category';
	public $event_cat_taxonomy = 'el_eventcategory';
	public $taxonomy;
	public $use_post_categories;

	/**
	 * Get the singleton instance of the class.
	 *
	 * @return  class instance reference
	 */
	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	/**
	 * Constructor which handles all required class preparations
	 *
	 * @return  null
	 */
	private function __construct() {
		// Register actions and filters
		add_action('init', array(&$this, 'init'), 2);
	}

	public function init() {
		$this->use_post_categories = ('1' === EL_Options::get_instance()->get('el_use_post_cats'));
		$this->taxonomy = $this->use_post_categories ? $this->post_cat_taxonomy : $this->event_cat_taxonomy;
		// Register actions and filters during init phase
		add_action('init', array(&$this, 'register_event_post_type'), 3);
		if(!$this->use_post_categories) {
			add_action('init', array(&$this, 'register_event_category_taxonomy'), 4);
		}
	}

	/**
	 * Register the events post type to handle the events.
	 *
	 * @return  null
	 */
	public function register_event_post_type() {
		$labels = array(
			'name' => __('Events','event-list'),
			'singular_name' => __('Event','event-list'),
			'add_new' => __('Add New','event-list'),
			'add_new_item' => __('Add New Event','event-list'),
			'edit_item' => __('Edit Event','event-list'),
			'new_item' => __('New Event','event-list'),
			'view_item' => __('View Event','event-list'),
			'view_items' => __('View Events','event-list'),
			'search_items' => __('Search Events','event-list'),
			'not_found' =>  __('No events found','event-list'),
			'not_found_in_trash' => __('No events found in Trash','event-list'),
			'parent_item_colon' => '',
			'all_items' => __('All Events','event-list'),
			'archives' => __('Event Archives','event-list'),
			'attributes' => __('Event Attributes','event-list'),
			'insert_into_item' => __('Insert into event','event-list'),
			'uploaded_to_this_item' => __('Uploaded to this event','event-list'),
			'menu_name' => __('Event List','event-list'),
			'filter_items_list' => __('Filter events list','event-list'),
			'items_list_navigation' => __('Events list navigation','event-list'),
			'items_list' => __('Events list','event-list'),
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'hierarchical' => false,
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'show_in_rest' => false,
			'menu_position' => 23,
			'menu_icon' => 'dashicons-calendar-alt',
			'capability_type' => 'post',
			'supports'=> array('title', 'editor', 'revisions', 'autor', 'thumbnail'),
			'register_meta_box_cb' => null,
			'taxonomies' => $this->use_post_categories ? array($this->post_cat_taxonomy) : array(),
			'has_archive' => true,
			'rewrite' => array('slug' => EL_Options::get_instance()->get('el_permalink_slug')),
			'query_var' => true,
			'can_export' => true,
			'delete_with_user' => false,
			'_builtin' => false,
		);
		register_post_type('el_events', $args);
	}

	/**
	 * Register the event category taxonomy for handling event categories.
	 *
	 * @return
	 */
	public function register_event_category_taxonomy() {
		$labels = array(
			'name' => _x('Categories', 'taxonomy general name'),
			'singular_name' => _x('Category', 'taxonomy singular name'),
			'search_items' =>  __('Search Categories'),
			'popular_items' => __('Popular Categories'),
			'all_items' => __('All Categories'),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __('Edit Category'),
			'update_item' => __('Update Category'),
			'add_new_item' => __('Add New Category'),
			'new_item_name' => __('New Category Name'),
			'separate_items_with_commas' => __('Separate categories with commas'),
			'add_or_remove_items' => __('Add or remove categories'),
			'choose_from_most_used' => __('Choose from the most used categories'),
		);
		$args = array(
			'label' => __('Event Category'),
			'labels' => $labels,
			'description' => __('Event category handling'),
			'public' => true,
			'publicly_queryable' => true,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_rest' => false,
			'show_in_tag_cloud' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'capabilities' => array('manage_terms' => 'manage_categories', 'edit_terms' => 'manage_categories', 'delete_terms' => 'manage_categories', 'assign_terms' => 'edit_posts'),
			'rewrite' => array('slug' => 'event-category'),
			'query_var' => true,
		);
		register_taxonomy($this->event_cat_taxonomy, 'el_events', $args);
	}
}
?>
