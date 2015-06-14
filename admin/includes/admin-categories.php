<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/categories.php');
require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'admin/includes/admin-functions.php');

// This class handles all data for the admin categories page
class EL_Admin_Categories {
	private static $instance;
	private $db;
	private $categories;
	private $options;
	private $functions;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin_Categories();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->categories = &EL_Categories::get_instance();
		$this->options = &EL_Options::get_instance();
		$this->functions = &EL_Admin_Functions::get_instance();
	}

	public function show_categories () {
		if(!current_user_can('manage_categories')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$out = '';

		// get action
		$action = '';
		if(isset($_GET['action'])) {
			$action = $_GET['action'];
		}
		$out .= $this->check_actions_and_show_messages($action);

		// normal output
		$out.= '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>'.__('Event List Categories','event-list').'</h2>
				<div id="posttype-page" class="posttypediv">';
		if('edit' === $action && isset($_GET['id'])) {
			$out .=$this->show_edit_category_form(__('Edit Category','event-list'), __('Update Category','event-list'), $this->categories->get_category_data($_GET['id']));
		}
		else {
			// show category table
			$out .= $this->show_category_table();
			// show add category form
			$out .= $this->show_edit_category_form(__('Add New Category','event-list'), __('Add New Category','event-list'));
			// show cat sync option form
			$out .= $this->show_cat_sync_form();
		}
		$out .= '
				</div>
			</div>';
		echo $out;
	}

	public function embed_categories_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('eventlist_admin_categories_js', EL_URL.'admin/js/admin_categories.js');
	}

	private function check_actions_and_show_messages($action) {
		$is_disabled = '1' == $this->options->get('el_sync_cats');
		$out = '';
		if('delete' === $action && isset($_GET['slug'])) {
			if(!$is_disabled) {
				// delete categories
				$slug_array = explode(', ', $_GET['slug']);
				$num_affected_events = $this->db->remove_category_in_events($slug_array);
				if($this->categories->remove_categories($slug_array, false)) {
					$out .= '<div id="message" class="updated">
						<p><strong>'.sprintf(__('Category "%s" deleted.','event-list'), $_GET['slug']);
					if($num_affected_events > 0) {
						$out .= '<br />'.sprintf(__('This Category was also removed from %d events.','event-list'), $num_affected_events);
					}
					$out .= '</strong></p>
					</div>';
				}
				else {
					$out .= '<div id="message" class="error below-h2"><p><strong>'.sprintf(__('Error while deleting category "%s"','event-list'), $_GET['slug']).'.</strong></p></div>';
				}
			}
		}
		else if('setcatsync' === $action) {
			$el_sync_cats = isset($_POST['el_sync_cats']) ? '1' : '';
			$this->options->set('el_sync_cats', $el_sync_cats);
			$is_disabled = '1' == $this->options->get('el_sync_cats');
			if($is_disabled) {
				$this->categories->sync_with_post_cats();
				$out .= '<div id="message" class="updated"><p><strong>'.__('Sync with post categories enabled.','event-list').'</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="updated"><p><strong>'.__('Sync with post categories disabled.','event-list').'</strong></p></div>';
			}
		}
		else if('manualcatsync' === $action) {
			if(!$is_disabled) {
				$this->categories->sync_with_post_cats();
				$out .= '<div id="message" class="updated"><p><strong>'.__('Manual sync with post categories sucessfully finished.','event-list').'</strong></p></div>';
			}
		}
		else if('editcat' === $action && !empty($_POST)) {
			if(!$is_disabled) {
				if(!isset($_POST['id'])) {
					// add new category
					if($this->categories->add_category($_POST)) {
						$out .= '<div id="message" class="updated below-h2"><p><strong>'.sprintf(__('New Category "%s" was added','event-list'), $_POST['name']).'.</strong></p></div>';
					}
					else {
						$out .= '<div id="message" class="error below-h2"><p><strong>'.sprintf(__('Error: New Category "%s" could not be added','event-list'), $_POST['name']).'.</strong></p></div>';
					}
				}
				else {
					// edit category
					if($this->categories->edit_category($_POST, $_POST['id'])) {
						$out .= '<div id="message" class="updated below-h2"><p><strong>'.sprintf(__('Category "%s" was modified','event-list'), $_POST['id']).'.</strong></p></div>';
					}
					else {
						$out .= '<div id="message" class="error below-h2"><p><strong>'.sprintf(__('Error: Category "%s" could not be modified','event-list'), $_POST['id']).'.</strong></p></div>';
					}
				}
			}
		}
		// add message if forms are disabled
		if($is_disabled) {
			$out .= '<div id="message" class="updated"><p>'.__('Categories are automatically synced with the post categories.<br />
			                                                    Because of this all options to add new categories or editing existing categories are disabled.<br />
			                                                    If you want to manually edit the categories you have to disable this option.','event-list').'</p></div>';
		}
		return $out;
	}

	private function show_edit_category_form($title, $button_text, $cat_data=null) {
		$is_disabled = '1' == $this->options->get('el_sync_cats');
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
							<form id="addtag" method="POST" action="?page=el_admin_categories&amp;action=editcat">';
		if(!$is_new_event) {
			$out .= '
				<input type="hidden" name="id" value="'.$cat_data['slug'].'">';
		}
		// Category Name
		$out .= '
				<div class="form-field form-required"><label for="name">'.__('Name').': </label>';
		$out .= $this->functions->show_text('name', $cat_data['name'], $is_disabled);
		$out .= '<p>'.__('The name is how it appears on your site.','event-list').'</p></div>';
		// Category Slug
		$out .= '
				<div class="form-field"><label for="name">'.__('Slug').': </label>';
		$out .= $this->functions->show_text('slug', $cat_data['slug'], $is_disabled);
		$out .= '<p>'.__('The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.','event-list').'</p></div>';
		// Category Parent
		$out .= '
				<div class="form-field"><label for="parent">'.__('Parent').': </label>';
		$cat_array = $this->categories->get_cat_array('name', 'asc', $cat_data['slug']);
		$value_array = array('' => __('None'));
		$class_array = array();
		foreach($cat_array as $cat) {
			$value_array[$cat['slug']] = str_pad('', 12*$cat['level'], '&nbsp;', STR_PAD_LEFT).$cat['name'];
			$class_array[$cat['slug']] = 'level-'.$cat['level'];
		}
		$selected = isset($cat_data['parent']) ? $cat_data['parent'] : null;
		$out .= $this->functions->show_dropdown('parent', $selected, $value_array, $class_array, $is_disabled);
		$out .= '<p>'.__('Categories can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.','event-list').'</p></div>';
		// Category Description
		$out .= '
				<div class="form-field"><label for="name">'.__('Description','event-list').': </label>';
		$out .= $this->functions->show_textarea('desc', $cat_data['desc'], $is_disabled);
		$out .= '</div>
				<p class="submit"><input type="submit" class="button-primary" value="'.$button_text.'" id="submitbutton"'.$this->functions->get_disabled_text($is_disabled).'></p>';
		$out .= '
							</form>
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
		$is_disabled = '1' == $this->options->get('el_sync_cats');
		require_once(EL_PATH.'admin/includes/category_table.php');
		$category_table = new EL_Category_Table($is_disabled);
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

	private function show_cat_sync_form() {
		$sync_option = &$this->options->options['el_sync_cats'];
		$sync_option_value = $this->options->get('el_sync_cats');
		$out = '
					<div id="col-left">
						<div class="col-wrap">
							<div class="form-wrap">
							<h3>'.$sync_option['label'].'</h3>
							<form id="catsync" method="POST" action="?page=el_admin_categories&amp;action=setcatsync">';
		// Checkbox
		$out .= $this->functions->show_checkbox('el_sync_cats', $sync_option_value, $sync_option['caption'].' <input type="submit" class="button-primary" value="'.__('Apply','event-list').'" id="catsyncsubmitbutton">');
		$out .= '<br />'.$sync_option['desc'];
		$out .= '
							</form>
						</div>';
		// Manual sync button
		$disabled_text = $this->functions->get_disabled_text('1' == $sync_option_value);
		$out .= '<br />
						<div>
							<form id="manualcatsync" method="POST" action="?page=el_admin_categories&amp;action=manualcatsync">
								<input type="submit" class="button-secondary" value="'.__('Do a manual sync with post categories','event-list').'" id="manualcatsyncbutton"'.$disabled_text.'>
							</form>
						</div>';
		$out .= '
					</div>';
		return $out;
	}
}
?>
