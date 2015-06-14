<?php
if(!defined('ABSPATH')) {
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
		$out = '';
		if(!isset($_GET['tab'])) {
			$_GET['tab'] = 'general';
		}
		// check for changed settings
		if(isset($_GET['settings-updated'])) {
			// show "settings saved" message
			$out .= '<div id="message" class="updated">
				<p><strong>'.__('Settings saved.','event-list').'</strong></p>
			</div>';
			// check feed rewrite status and update it if required
			if('feed' == $_GET['tab']) {
				require_once(EL_PATH.'includes/feed.php');
				EL_Feed::get_instance()->update_feed_rewrite_status();
			}
		}

		// normal output
		$out.= '
				<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>'.__('Event List Settings','event-list').'</h2>';
		$out .= $this->show_tabs($_GET['tab']);
		$out .= '<div id="posttype-page" class="posttypediv">';
		$out .= $this->functions->show_option_form($_GET['tab']);
		$out .= '
				</div>
			</div>';
		echo $out;
	}
/*
	public function embed_settings_scripts() {
		wp_enqueue_script('eventlist_admin_settings_js', EL_URL.'admin/js/admin_settings.js');
	}
*/
	private function show_tabs($current = 'category') {
		$tabs = array('general'  => __('General','event-list'),
		              'frontend' => __('Frontend Settings','event-list'),
		              'admin'    => __('Admin Page Settings','event-list'),
		              'feed'     => __('Feed Settings','event-list'));
		$out = '<h3 class="nav-tab-wrapper">';
		foreach($tabs as $tab => $name){
			$class = ($tab == $current) ? ' nav-tab-active' : '';
			$out .= '<a class="nav-tab'.$class.'" href="?page=el_admin_settings&amp;tab='.$tab.'">'.$name.'</a>';
		}
		$out .= '</h3>';
		return $out;
	}

	public function embed_settings_scripts() {
		wp_enqueue_style('eventlist_admin_settings', EL_URL.'admin/css/admin_settings.css');
	}
}
?>
