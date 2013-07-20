<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/categories.php');
require_once(EL_PATH.'admin/includes/admin-functions.php');

// This class handles all data for the admin categories page
class EL_Admin_Categories {
	private static $instance;
	private $db;
	private $categories;
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
		$this->functions = &EL_Admin_Functions::get_instance();
	}

	public function show_categories () {
		if(!current_user_can('manage_options')) {
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
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>Event List Categories</h2>
				<div id="posttype-page" class="posttypediv">';
		if('edit' === $action && isset($_GET['id'])) {
			$out .=$this->show_edit_category_form(__('Edit Category'), __('Update Category'), $this->categories->get_category_data($_GET['id']));
		}
		else {
			// show category table
			$out .= $this->show_category_table();
			// show add category form
			$out .= $this->show_edit_category_form(__('Add New Category'), __('Add New Category'));
		}
		$out .= '
				</div>
			</div>';
		echo $out;
	}

	public function embed_categories_scripts() {
		wp_enqueue_script('eventlist_admin_categories_js', EL_URL.'admin/js/admin_categories.js');
	}

	private function check_actions_and_show_messages($action) {
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
							<form id="addtag" method="POST" action="?page=el_admin_categories&amp;tab=category">';
		if(!$is_new_event) {
			$out .= '
				<input type="hidden" name="id" value="'.$cat_data['slug'].'">';
		}
		// Category Name
		$out .= '
				<div class="form-field form-required"><label for="name">Name: </label>';
		$out .= $this->functions->show_text('name', $cat_data['name']);
		$out .= '<p>'.__('The name is how it appears on your site.').'</p></div>';
		// Category Slug
		$out .= '
				<div class="form-field"><label for="name">Slug: </label>';
		$out .= $this->functions->show_text('slug', $cat_data['slug']);
		$out .= '<p>'.__('The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.').'</p></div>';
		// Category Parent
		$out .= '
				<div class="form-field"><label for="parent">Parent: </label>';
		$cat_array = $this->categories->get_cat_array('name', 'asc');
		$option_array = array('' => __('None'));
		$class_array = array();
		foreach($cat_array as $cat) {
			if($cat['slug'] != $cat_data['slug']) {
				$option_array[$cat['slug']] = str_pad($cat['name'], 18*$cat['level'], '&nbsp;', STR_PAD_LEFT);
				$class_array[$cat['slug']] = 'level-'.$cat['level'];
			}
		}
		$selected = isset($cat_data['parent']) ? $cat_data['parent'] : null;
		$out .= $this->functions->show_combobox('parent', $option_array, $selected, $class_array);
		$out .= '<p>'.__('Categories can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.').'</p></div>';
		// Category Description
		$out .= '
				<div class="form-field"><label for="name">Description: </label>';
		$out .= $this->functions->show_textarea('desc', $cat_data['desc']);
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
}
?>
