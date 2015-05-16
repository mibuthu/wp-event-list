<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/categories.php');

// This class handles all data for the admin new event page
class EL_Admin_New {
	private static $instance;
	private $db;
	private $options;
	private $categories;
	private $is_new;
	private $is_duplicate;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin_New();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->options = &EL_Options::get_instance();
		$this->categories = &EL_Categories::get_instance();
		$this->is_new = !(isset($_GET['action']) && ('edit' === $_GET['action'] || 'added' === $_GET['action'] || 'modified' === $_GET['action']));
		$this->is_duplicate = $this->is_new && isset($_GET['id']) && is_numeric($_GET['id']);
	}

	public function show_new() {
		if(!current_user_can('edit_posts')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$out = '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>'.__('Add New Event','event-list').'</h2>';
		if($this->is_duplicate) {
			$out .= '<span style="color:silver">('.sprintf(__('Duplicate of event id:%d','event-list'), $_GET['id']).')</span>';
		}
		$out .= $this->edit_event();
		$out .= '</div>';
		echo $out;
	}

	public function embed_new_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('link');
		wp_enqueue_script('eventlist_admin_new_js', EL_URL.'admin/js/admin_new.js');
		wp_enqueue_style('eventlist_admin_new', EL_URL.'admin/css/admin_new.css');
	}

	public function edit_event() {
		$dateformat = $this->get_event_dateformat();
		if($this->is_new && !$this->is_duplicate) {
			// set next day as date
			$start_date = current_time('timestamp')+86400; // next day (86400 seconds = 1*24*60*60 = 1 day);
			$end_date = $start_date;
		}
		else {
			// set event data and existing date
			$event = $this->db->get_event($_GET['id']);
			$start_date = strtotime($event->start_date);
			$end_date = strtotime($event->end_date);
		}
		// Add required data for javascript in a hidden field
		$json = json_encode(array('el_url'         => EL_URL,
		                          'el_date_format' => $this->datepicker_format($dateformat)));
		$out = '
				<form method="POST" action="'.add_query_arg('noheader', 'true', '?page=el_admin_main').'">';
		$out .= "
				<input type='hidden' id='json_for_js' value='".$json."' />"; // single quote required for value due to json layout
		// TODO: saving changed metabox status and order is not working yet
		$out .= wp_nonce_field('autosavenonce', 'autosavenonce', false, false);
		$out .= wp_nonce_field('closedpostboxesnonce', 'closedpostboxesnonce', false, false);
		$out .= wp_nonce_field('meta-box-order-nonce', 'meta-box-order-nonce', false, false);
		$out .= '
				<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">';
		if($this->is_new) {
			$out .= '
					<input type="hidden" name="action" value="new" />';
		}
		else {
			$out .= '
					<input type="hidden" name="action" value="edited" />
					<input type="hidden" name="id" value="'.$_GET['id'].'" />';
		}
		$out .= '
					<table class="form-table">
					<tr>
						<th><label>'.__('Title','event-list').' ('.__('required','event-list').')</label></th>
						<td><input type="text" class="text form-required" name="title" id="title" value="'.str_replace('"', '&quot;', isset($event->title) ? $event->title : '').'" /></td>
					</tr>
					<tr>
						<th><label>'.__('Date','event-list').' ('.__('required','event-list').')</label></th>
						<td><input type="text" class="text datepicker form-required" name="start_date" id="start_date" value="'.date('Y-m-d', $start_date).'" />
							<span id="end_date_area"> - <input type="text" class="text datepicker" name="end_date" id="end_date" value="'.date('Y-m-d', $end_date).'" /></span>
							<label><input type="checkbox" name="multiday" id="multiday" value="1" /> '.__('Multi-Day Event','event-list').'</label>
							<input type="hidden" id="sql_start_date" name="sql_start_date" value="" />
							<input type="hidden" id="sql_end_date" name="sql_end_date" value="" />
						</td>
					</tr>
					<tr>
						<th><label>'.__('Time','event-list').'</label></th>
						<td><input type="text" class="text" name="time" id="time" value="'.str_replace('"', '&quot;', isset($event->time) ? $event->time : '').'" /></td>
					</tr>
					<tr>
						<th><label>'.__('Location','event-list').'</label></th>
						<td><input type="text" class="text" name="location" id="location" value="'.str_replace('"', '&quot;', isset($event->location) ? $event->location : '').'" /></td>
					</tr>
					<tr>
						<th><label>'.__('Details','event-list').'</label></th>
						<td>';
		$editor_settings = array('drag_drop_upload' => true,
		                         'textarea_rows' => 20);
		ob_start();
			wp_editor(isset($event->details) ? $event->details : '', 'details', $editor_settings);
			$out .= ob_get_contents();
		ob_end_clean();
		$out .= '
						<p class="note">NOTE: In the text editor, use RETURN to start a new paragraph - use SHIFT-RETURN to start a new line.</p></td>
					</tr>
					</table>';
		$out .= '
				</div>
				<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box-sortables ui-sortable">';
		add_meta_box('event-publish', __('Publish','event-list'), array(&$this, 'render_publish_metabox'), 'event-list');
		$metabox_args = isset($event->categories) ? array('event_cats' => $event->categories) : null;
		add_meta_box('event-categories', __('Categories','event-list'), array(&$this, 'render_category_metabox'), 'event-list', 'advanced', 'default', $metabox_args);
		ob_start();
			do_meta_boxes('event-list', 'advanced', null);
			$out .= ob_get_contents();
		ob_end_clean();
		$out .= '
				</div>
				</div>
				</div>
				</div>
				</form>';
		return $out;
	}

	public function render_publish_metabox() {
		$button_text = $this->is_new ? __('Publish','event-list') : __('Update','event-list');
		$out = '<div class="submitbox">
				<div id="delete-action"><a href="?page=el_admin_main" class="submitdelete deletion">'.__('Cancel','event-list').'</a></div>
				<div id="publishing-action"><input type="submit" class="button button-primary button-large" name="publish" value="'.$button_text.'" id="publish"></div>
				<div class="clear"></div>
			</div>';
		echo $out;
	}

	public function render_category_metabox($post, $metabox) {
		$out = '
				<div id="taxonomy-category" class="categorydiv">
				<div id="category-all" class="tabs-panel">';
		$cat_array = $this->categories->get_cat_array('name', 'asc');
		if(empty($cat_array)) {
			$out .= __('No categories available.','event-list');
		}
		else {
			$out .= '
					<ul id="categorychecklist" class="categorychecklist form-no-clear">';
			$level = 0;
			$event_cats = explode('|', substr($metabox['args']['event_cats'], 1, -1));
			foreach($cat_array as $cat) {
				if($cat['level'] > $level) {
					//new sub level
					$out .= '
						<ul class="children">';
					$level++;
				}
				while($cat['level'] < $level) {
					// finish sub level
					$out .= '
						</ul>';
					$level--;
				}
				$level = $cat['level'];
				$checked = in_array($cat['slug'], $event_cats) ? 'checked="checked" ' : '';
				$out .= '
						<li id="'.$cat['slug'].'" class="popular-catergory">
							<label class="selectit">
								<input value="'.$cat['slug'].'" type="checkbox" name="categories[]" id="categories" '.$checked.'/> '.$cat['name'].'
							</label>
						</li>';
			}
			$out .= '
					</ul>';
		}

		$out .= '
				</div>';
		// TODO: Adding new categories in edit event form
		/*		<div id="category-adder" class="wp-hidden-children">
					<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js">'.__('+ Add New Category','event-list').'</a></h4>
					<p id="category-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="newcategory">'.__('Category Name','event-list').'</label>
						<input type="text" name="newcategory" id="newcategory" class="form-required form-input-tip" value="" aria-required="true"/>
						<input type="button" id="category-add-submit" class="button category-add-submit" value="'.__('Add Category','event-list').'" />
					</p>
				</div>*/
		$out .= '
				<div id="category-manager">
					<a id="category-manage-link" href="?page=el_admin_categories">'.__('Goto Category Settings','event-list').'</a>
				</div>
				</div>';
		echo $out;
	}

	private function get_event_dateformat() {
		if('' == $this->options->get('el_edit_dateformat')) {
			return __('Y/m/d');
		}
		else {
			return $this->options->get('el_edit_dateformat');
		}
	}

	/**
	 * Convert a date format to a jQuery UI DatePicker format
	 *
	 * @param string $format a date format
	 * @return string
	 */
	private function datepicker_format($format) {
		$chars = array(
				// Day
				'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
				// Month
				'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M',
				// Year
				'Y' => 'yy', 'y' => 'y',
		);
		return strtr((string)$format, $chars);
	}
}
?>
