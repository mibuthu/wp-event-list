<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once( EL_PATH.'includes/options.php' );

// This class handles all available admin pages
class EL_Admin {
	private static $instance;
	private $options;

	private function __construct() {
		$this->options = &EL_Options::get_instance();
	}

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin();
		}
		// Return class instance
		return self::$instance;
	}

	public function init_admin_page() {
		// Register actions
		add_action('admin_menu', array(&$this, 'register_pages'));
		add_action('plugins_loaded', array(&$this, 'db_upgrade_check'));
		add_action('right_now_content_table_end', array(&$this, 'add_events_to_right_now'));

		// Register syncing if required
		if(1 == $this->options->get('el_sync_cats')) {
			add_action('create_category', array(&$this, 'action_add_category'));
			add_action('edit_category', array(&$this, 'action_edit_category'));
			add_action('delete_category', array(&$this, 'action_delete_category'));
		}
	}

	/**
	 * Add and register all admin pages in the admin menu
	 */
	public function register_pages() {
		// Main Menu page
		add_menu_page('Event List', 'Event List', 'edit_posts', 'el_admin_main', array(&$this, 'show_main_page'));

		// All Events subpage
		$page = add_submenu_page('el_admin_main', 'Events', 'All Events', 'edit_posts', 'el_admin_main', array(&$this, 'show_main_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_main_scripts'));

		// New Event subpage
		$page = add_submenu_page('el_admin_main', 'Add New Event', 'Add New', 'edit_posts', 'el_admin_new', array(&$this, 'show_new_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_new_scripts'));

		// Categories subpage
		$page = add_submenu_page('el_admin_main', 'Event List Categories', 'Categories', 'manage_options', 'el_admin_categories', array(&$this, 'show_categories_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_categories_scripts'));

		// Settings subpage
		$page = add_submenu_page('el_admin_main', 'Event List Settings', 'Settings', 'manage_options', 'el_admin_settings', array(&$this, 'show_settings_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_settings_scripts'));

		// About subpage
		$page = add_submenu_page('el_admin_main', 'About Event List', 'About', 'edit_posts', 'el_admin_about', array(&$this, 'show_about_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_about_scripts'));
	}

	public function db_upgrade_check() {
		require_once(EL_PATH.'includes/db.php');
		EL_Db::get_instance()->upgrade_check();
	}

	public function add_events_to_right_now() {
		require_once(EL_PATH.'includes/db.php');
		$num_events = EL_Db::get_instance()->get_num_events();
		$event_link = 'admin.php?page=el_admin_main';
		$out = '
			<tr>
				<td class="first b b-events"><a href="'.$event_link.'">'.$num_events.'</a></td>
				<td class="t events"><a href="'.$event_link.'">'.__('Events').'</a></td>
			</tr>';
		echo $out;
	}

	public function show_main_page() {
		require_once(EL_PATH.'admin/includes/admin-main.php');
		EL_Admin_Main::get_instance()->show_main();
	}

	public function embed_main_scripts() {
		require_once(EL_PATH.'admin/includes/admin-main.php');
		EL_Admin_Main::get_instance()->embed_main_scripts();
	}

	public function show_new_page() {
		require_once(EL_PATH.'admin/includes/admin-new.php');
		EL_Admin_New::get_instance()->show_new();
	}

	public function embed_new_scripts() {
		require_once(EL_PATH.'admin/includes/admin-new.php');
		EL_Admin_New::get_instance()->embed_new_scripts();
	}

	public function show_categories_page() {
		require_once(EL_PATH.'admin/includes/admin-categories.php');
		EL_Admin_Categories::get_instance()->show_categories();
	}

	public function embed_categories_scripts() {
		require_once(EL_PATH.'admin/includes/admin-categories.php');
		EL_Admin_Categories::get_instance()->embed_categories_scripts();
	}

	public function show_settings_page() {
		require_once(EL_PATH.'admin/includes/admin-settings.php');
		EL_Admin_Settings::get_instance()->show_settings();
	}

	public function embed_settings_scripts() {
		require_once(EL_PATH.'admin/includes/admin-settings.php');
		EL_Admin_Settings::get_instance()->embed_settings_scripts();
	}

	public function show_about_page() {
		require_once(EL_PATH.'admin/includes/admin-about.php');
		EL_Admin_About::get_instance()->show_about();
	}

	public function embed_about_scripts() {
		require_once(EL_PATH.'admin/includes/admin-about.php');
		EL_Admin_About::get_instance()->embed_about_scripts();
	}

	public function action_add_category($cat_id) {
		require_once(EL_PATH.'includes/categories.php');
		EL_Categories::get_instance()->add_post_category($cat_id);
	}

	public function action_edit_category($cat_id) {
		require_once(EL_PATH.'includes/categories.php');
		EL_Categories::get_instance()->edit_post_category($cat_id);
	}

	public function action_delete_category($cat_id) {
		require_once(EL_PATH.'includes/categories.php');
		EL_Categories::get_instance()->delete_post_category($cat_id);
	}
}
?>
