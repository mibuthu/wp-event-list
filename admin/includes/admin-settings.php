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
			self::$instance = new EL_Admin_Settings();
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
		if(isset($_GET['settings-updated'])) {
			$out .= '<div id="message" class="updated">
				<p><strong>'.__('Settings saved.').'</strong></p>
			</div>';
		}

		// normal output
		$out.= '
				<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>Event List Settings</h2>';
		if(!isset($_GET['tab'])) {
			$_GET['tab'] = 'general';
		}
		$out .= $this->show_tabs($_GET['tab']);
		$out .= '<div id="posttype-page" class="posttypediv">';
		$out .= $this->show_option_tab($_GET['tab']);
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
		$tabs = array('general' => 'General',
		              'admin'   => 'Admin Page Settings',
		              'feed'    => 'Feed Settings');
		$out = '<h3 class="nav-tab-wrapper">';
		foreach($tabs as $tab => $name){
			$class = ($tab == $current) ? ' nav-tab-active' : '';
			$out .= '<a class="nav-tab'.$class.'" href="?page=el_admin_settings&amp;tab='.$tab.'">'.$name.'</a>';
		}
		$out .= '</h3>';
		return $out;
	}

	private function show_option_tab($section) {
		$out = '
			<form method="post" action="options.php">
			';
		ob_start();
		settings_fields('el_'.$_GET['tab']);
		$out .= ob_get_contents();
		ob_end_clean();
		$out .= '
				<div class="el-settings">
				<table class="form-table">';
		foreach($this->options->options as $oname => $o) {
			if($o['section'] == $section) {
				$out .= '
						<tr>
							<th>';
				if($o['label'] != '') {
					$out .= '<label for="'.$oname.'">'.$o['label'].':</label>';
				}
				$out .= '</th>
						<td>';
				switch($o['type']) {
					case 'checkbox':
						$out .= $this->functions->show_checkbox($oname, $this->options->get($oname), $o['caption']);
						break;
					case 'radio':
						$out .= $this->functions->show_radio($oname, $this->options->get($oname), $o['caption']);
						break;
					case 'text':
						$out .= $this->functions->show_text($oname, $this->options->get($oname));
						break;
					case 'textarea':
						$out .= $this->functions->show_textarea($oname, $this->options->get($oname));
						break;
				}
				$out .= '
						</td>
						<td class="description">'.$o['desc'].'</td>
					</tr>';
			}
		}
		$out .= '
			</table>
			</div>';
		ob_start();
		submit_button();
		$out .= ob_get_contents();
		ob_end_clean();
		$out .='
		</form>';
		return $out;
	}

	public function embed_settings_scripts() {
		wp_enqueue_style('eventlist_admin_settings', EL_URL.'admin/css/admin_settings.css');
	}
}
?>
