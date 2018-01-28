<?php
if(!defined('WP_ADMIN')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');

// This class handles all available admin pages
class EL_Admin {
	private static $instance;
	private $options;
	private $events_post_type;
	private $upgrade_req = false;

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

		// Register actions
		add_action('init', array(&$this, 'plugin_upgrade_check'), 5);
		add_action('admin_notices', array(&$this, 'show_plugin_upgrade_message'));
		add_action('current_screen', array(&$this, 'register_events_post_type_mods'));
		add_action('admin_head', array(&$this, 'add_dashboard_styles'));
		add_action('admin_menu', array(&$this, 'register_pages'));
		add_filter('dashboard_glance_items', array($this, 'add_events_to_glance'));
	}

	public function plugin_upgrade_check() {
		// Upgrade check
		$file_data = get_file_data(EL_PATH.'event-list.php', array('version'=>'Version'));
		$last_upgr_version = get_option('el_last_upgr_version', '');
		if($file_data['version'] != $last_upgr_version) {
			// load upgrade class
			require_once(EL_PATH.'admin/includes/upgrade.php');
			EL_Upgrade::get_instance();
			$this->upgrade_req = true;
		}
	}

	public function show_plugin_upgrade_message() {
		if($this->upgrade_req) {
			$upgr = EL_Upgrade::get_instance();
			$class = $upgr->error ? 'error' : 'updated fade';
			$title = sprintf($upgr->error ? __('Errors during upgrade of plugin %1$s','event-list') : __('Upgrade of plugin %1$s successful','event-list'), '"Event List"');
			$msg = empty($upgr->msg) ? __('no additional information available','event-list') : '<li>'.implode('</li><li>', $upgr->msg).'</li>';
			echo '<div id="message" class="'.$class.'"><p><strong>'.$title.':</strong></p><ul style="list-style:inside">'.$msg.'</ul></div>';
		}
	}

	public function register_events_post_type_mods($current_screen) {
		// load file depending on current screen
		if(current_user_can('edit_posts')) {
			switch($current_screen->id) {
				// Main/all events page
				case 'edit-el_events':
					require_once(EL_PATH.'admin/includes/admin-main.php');
					EL_Admin_Main::get_instance();
					break;
				// New/edit event page
				case 'el_events':
					// Additional required checks (only for add or edit action, and not for e.g. move event to trash)
					$get_action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
					$post_action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
					if('add' === $current_screen->action || 'edit' === $get_action || 'editpost' === $post_action) {
						require_once(EL_PATH.'admin/includes/admin-new.php');
						EL_Admin_New::get_instance();
					}
					break;
				// Event category page
				case 'edit-'.$this->events_post_type->taxonomy:
					if('1' !== $this->options->get('el_use_post_cats')) {
						require_once(EL_PATH.'admin/includes/admin-categories.php');
						EL_Admin_Categories::get_instance();
					}
					break;
			}
		}
	}

	/**
	 * Add and register all admin pages in the admin menu
	 */
	public function register_pages() {
		// Settings subpage
		$page = add_submenu_page('edit.php?post_type=el_events', __('Event List Settings','event-list'), __('Settings','event-list'), 'manage_options', 'el_admin_settings', array(&$this, 'show_settings_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_settings_scripts'));

		// About subpage
		$page = add_submenu_page('edit.php?post_type=el_events', __('About Event List','event-list'), __('About','event-list'), 'edit_posts', 'el_admin_about', array(&$this, 'show_about_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_about_scripts'));

		// Import page (invisible in menu, but callable by import button on admin main page)
		$page = add_submenu_page(null, null, null, 'edit_posts', 'el_admin_import', array(&$this, 'show_import_page'));
		add_action('admin_print_scripts-'.$page, array(&$this, 'embed_import_scripts'));

		// Sync Post Categories page (invisible in menu, but callable by sync button on admin categories page)
		$page = add_submenu_page(null, null, null, 'manage_categories', 'el_admin_cat_sync', array(&$this, 'show_cat_sync_page'));
		add_action('load-'.$page, array(&$this, 'handle_cat_sync_actions'));
	}

	public function add_dashboard_styles() {
		if(current_user_can('edit_posts') && 'dashboard' === get_current_screen()->base) {
			echo '<style>#dashboard_right_now .el-events-count:before {content: "\f508"}</style>';
		}
	}

	public function add_events_to_glance() {
		if(current_user_can('edit_posts')) {
			require_once(EL_PATH.'includes/events.php');
			$num_events = EL_Events::get_instance()->get_num_events();
			$text = sprintf(_n('%s Event','%s Events',$num_events,'event-list'), number_format_i18n($num_events));
			if(current_user_can(get_post_type_object('el_events')->cap->edit_posts)) {
				return array('<a class="el-events-count" href="'.admin_url('edit.php?post_type=el_events').'">'.$text.'</a>');
			}
			else {
				return array($text);
			}

		}
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

	public function show_import_page() {
		require_once(EL_PATH.'admin/includes/admin-import.php');
		EL_Admin_Import::get_instance()->show_import();
	}

	public function embed_import_scripts() {
		require_once(EL_PATH.'admin/includes/admin-import.php');
		EL_Admin_Import::get_instance()->embed_import_scripts();
	}

	public function show_cat_sync_page() {
		require_once(EL_PATH.'admin/includes/admin-category-sync.php');
		EL_Admin_Category_Sync::get_instance()->show_cat_sync();
	}

	public function handle_cat_sync_actions() {
		require_once(EL_PATH.'admin/includes/admin-category-sync.php');
		EL_Admin_Category_Sync::get_instance()->handle_actions();
	}
}
?>
