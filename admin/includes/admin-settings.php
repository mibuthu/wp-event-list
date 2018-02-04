<?php
if(!defined('WP_ADMIN')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'admin/includes/admin-functions.php');

// This class handles all data for the admin settings page
class EL_Admin_Settings {
	private static $instance;
	private $options;
	private $functions;

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
		$this->functions = &EL_Admin_Functions::get_instance();
	}

	public function show_settings () {
		if(!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		// check used get parameters
		$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
		$settings_updated = isset($_GET['settings-updated']) ? sanitize_key($_GET['settings-updated']) : '';

		$out = '';
		// check for changed settings
		if('true' === $settings_updated) {
			// show "settings saved" message
			$out .= '<div id="message" class="updated">
				<p><strong>'.__('Settings saved.').'</strong></p>
			</div>';
			switch($tab) {
				case 'frontend':
					// flush rewrite rules (required if permalink slug was changed)
					flush_rewrite_rules();
					break;
				case 'feed':
					// update feed rewrite status if required
					require_once(EL_PATH.'includes/feed.php');
					EL_Feed::get_instance()->update_feed_rewrite_status();
					break;
				case 'taxonomy':
					// update category count
					require_once(EL_PATH.'admin/includes/event-category_functions.php');
					EL_Event_Category_Functions::get_instance()->update_cat_count();
					break;
			}
		}

		// normal output
		$out.= '
				<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>'.__('Event List Settings','event-list').'</h2>';
		$out .= $this->show_tabs($tab);
		$out .= '<div id="posttype-page" class="posttypediv">';
		$options = array();
		if('taxonomy' === $tab) {
			$options['page'] = admin_url('edit.php?post_type=el_events&page=el_admin_cat_sync&switch_taxonomy=1');
			$options['button_text'] = __('Go to Event Category switching page','event-list');
			$options['button_class'] = __('secondary');
		}
		$out .= $this->functions->show_option_form($tab, $options);
		$out .= '
				</div>
			</div>';
		echo $out;
	}

	private function show_tabs($current = 'category') {
		$tabs = array('general'  => __('General','event-list'),
		              'frontend' => __('Frontend Settings','event-list'),
		              'admin'    => __('Admin Page Settings','event-list'),
		              'feed'     => __('Feed Settings','event-list'),
		              'taxonomy' => __('Category Taxonomy','event-list'));
		$out = '<h3 class="nav-tab-wrapper">';
		foreach($tabs as $tab => $name) {
			$class = ($tab == $current) ? ' nav-tab-active' : '';
			$out .= '<a class="nav-tab'.$class.'" href="'.remove_query_arg('settings-updated', add_query_arg('tab', $tab)).'">'.$name.'</a>';
		}
		$out .= '</h3>';
		return $out;
	}

	public function embed_settings_scripts() {
		wp_enqueue_style('eventlist_admin_settings', EL_URL.'admin/css/admin_settings.css');
	}
}
?>
