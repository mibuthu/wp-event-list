<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/categories.php');

// This class handles all data for the admin settings page
class EL_Admin_Settings {
	private static $instance;
	private $db;
	private $options;
	private $categories;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin_Settings();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->options = &EL_Options::get_instance();
		$this->categories = &EL_Categories::get_instance();
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

		// get action
		$action = '';
		if(isset($_GET['action'])) {
			$action = $_GET['action'];
		}
		$out .= $this->check_for_actions_and_show_messages($action);

		// normal output
		$out.= '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>Event List Settings</h2>';
		if(!isset($_GET['tab'])) {
			$_GET['tab'] = 'category';
		}
		$out .= $this->show_tabs($_GET['tab']);
		$out .= '<div id="posttype-page" class="posttypediv">';
		if('category' === $_GET['tab']) {
			$out .= $this->show_category_tab($action);
		}
		else {
			$out .= $this->show_option_tab($_GET['tab']);
		}
		$out .= '
				</div>
			</div>';
		echo $out;
	}

	public function embed_settings_scripts() {
		wp_enqueue_script('eventlist_admin_settings_js', EL_URL.'admin/js/admin_settings.js');
	}

	private function show_tabs($current = 'category') {
		$tabs = array('category' => 'Categories', 'general' => 'General');
		$out = '<h3 class="nav-tab-wrapper">';
		foreach($tabs as $tab => $name){
			$class = ($tab == $current) ? ' nav-tab-active' : '';
			$out .= '<a class="nav-tab'.$class.'" href="?page=el_admin_settings&amp;tab='.$tab.'">'.$name.'</a>';
		}
		$out .= '</h3>';
		return $out;
	}

	private function show_category_tab($action) {
		$out = '';
		if('edit' === $action && isset($_GET['id'])) {
			$out .=$this->show_edit_category_form(__('Edit Category'), __('Update Category'), $this->categories->get_category_data($_GET['id']));
		}
		else {
			// show category table
			$out .= $this->show_category_table();
			// show add category form
			$out .= $this->show_edit_category_form(__('Add New Category'), __('Add New Category'));
		}
		return $out;
	}

	private function check_for_actions_and_show_messages($action) {
		$out = '';
		if('delete' === $action && isset($_GET['slug'])) {
			// delete categories
			$slug_array = explode(', ', $_GET['slug']);
			$num_affected_events = $this->db->remove_category_in_events($slug_array);
			require_once(EL_PATH.'admin/includes/category_table.php');
			if($this->categories->remove_categories($slug_array)) {
				$out .= '<div id="message" class="updated">
					<p><strong>'.sprintf(__('Category "%s" deleted.'), $_GET['slug']);
				if($num_affected_events > 0) {
					$out .= '<br />'.sprintf(__('This Category was also removed from %d events.'), $num_affected_events);
				}
				$out .= '</strong></p>
				</div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error while deleting category "'.$_GET['slug'].'".</strong></p></div>';
			}
		}
		else if(!empty($_POST)) {
			if(!isset($_POST['id'])) {
				// add new category
				if($this->categories->add_category($_POST)) {
					$out .= '<div id="message" class="updated below-h2"><p><strong>New Category "'.$_POST['name'].'" was added.</strong></p></div>';
				}
				else {
					$out .= '<div id="message" class="error below-h2"><p><strong>Error: New Category "'.$_POST['name'].'" could not be added.</strong></p></div>';
				}
			}
			else {
				// edit category
				if($this->categories->edit_category($_POST, $_POST['id'])) {
					$this->db->change_category_slug_in_events($_POST['id'], $_POST['slug']);
					$out .= '<div id="message" class="updated below-h2"><p><strong>Category "'.$_POST['id'].'" was modified.</strong></p></div>';
				}
				else {
					$out .= '<div id="message" class="error below-h2"><p><strong>Error: Category "'.$_POST['id'].'" could not be modified.</strong></p></div>';
				}
			}
		}
		return $out;
	}

	private function show_edit_category_form($title, $button_text, $cat_data=null) {
		$is_new_event = (null == $cat_data);
		if($is_new_event) {
			$cat_data['name'] = '';
			$cat_data['slug'] = '';
			$cat_data['desc'] = '';
		}
		$out = '
					<div id="col-left">
						<div class="col-wrap">
							<div class="form-wrap">
							<h3>'.$title.'</h3>
							<form id="addtag" method="POST" action="?page=el_admin_settings&amp;tab=category">';
		if(!$is_new_event) {
			$out .= '
				<input type="hidden" name="id" value="'.$cat_data['slug'].'">';
		}
		// Category Name
		$out .= '
				<div class="form-field form-required"><label for="name">Name: </label>';
		$out .= $this->show_text('name', $cat_data['name']);
		$out .= '<p>'.__('The name is how it appears on your site.').'</p></div>';
		// Category Slug
		$out .= '
				<div class="form-field"><label for="name">Slug: </label>';
		$out .= $this->show_text('slug', $cat_data['slug']);
		$out .= '<p>'.__('The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.').'</p></div>';
		// Category Description
		$out .= '
				<div class="form-field"><label for="name">Description: </label>';
		$out .= $this->show_textarea('desc', $cat_data['desc']);
		$out .= '</div>
				<p class="submit"><input type="submit" class="button-primary" name="add_cat" value="'.$button_text.'" id="submitbutton"></p>';
		$out .= '
							</form>
							</div>
						</div>
					</div>
				</div>';
		return $out;
	}

	private function show_category_table() {
		$out = '
				<div id="col-container">
					<div id="col-right">
						<div class="col-wrap">
							<form id="category-filter" method="get">
								<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		require_once(EL_PATH.'admin/includes/category_table.php');
		$category_table = new EL_Category_Table();
		$category_table->prepare_items();
		ob_start();
		$category_table->display();
		$out .= ob_get_contents();
		ob_end_clean();
		$out .= '
							</form>
						</div>
					</div>';
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
				<div style="padding:0 10px">
				<table class="form-table">';
		foreach($this->options->options as $oname => $o) {
			if($o['section'] == $section) {
				$out .= '
						<tr style="vertical-align:top;">
							<th>';
				if($o['label'] != '') {
					$out .= '<label for="'.$oname.'">'.$o['label'].':</label>';
				}
				$out .= '</th>
						<td>';
				switch($o['type']) {
					case 'checkbox':
						$out .= $this->show_checkbox($oname, $this->options->get($oname), $o['caption']);
						break;
					case 'radio':
						$out .= $this->show_radio($oname, $this->options->get($oname), $o['caption']);
						break;
					case 'text':
						$out .= $this->show_text($oname, $this->options->get($oname));
						break;
					case 'textarea':
						$out .= $this->show_textarea($oname, $this->options->get($oname));
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

	private function show_checkbox($name, $value, $caption) {
		$out = '
							<label for="'.$name.'">
								<input name="'.$name.'" type="checkbox" id="'.$name.'" value="1"';
		if($value == 1) {
			$out .= ' checked="checked"';
		}
		$out .= ' />
								'.$caption.'
							</label>';
		return $out;
	}

	private function show_radio($name, $value, $caption) {
		$out = '
							<fieldset>';
		foreach($caption as $okey => $ocaption) {
			$checked = ($value === $okey) ? 'checked="checked" ' : '';
			$out .= '
								<label title="'.$ocaption.'">
									<input type="radio" '.$checked.'value="'.$okey.'" name="'.$name.'">
									<span>'.$ocaption.'</span>
								</label>
								<br />';
		}
		$out .= '
							</fieldset>';
		return $out;
	}

	private function show_text($name, $value) {
		$out = '
							<input name="'.$name.'" type="text" id="'.$name.'" value="'.$value.'" />';
		return $out;
	}

	private function show_textarea($name, $value) {
		$out = '
							<textarea name="'.$name.'" id="'.$name.'" rows="5" class="large-text code">'.$value.'</textarea>';
		return $out;
	}
}
?>
