<?php
if(!defined('WP_ADMIN')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');
require_once(EL_PATH.'includes/events.php');
require_once(EL_PATH.'admin/includes/event-category_functions.php');

// This class handles all data for the admin categories page
class EL_Admin_Category_Sync {
	private static $instance;
	private $options;
	private $events_post_type;
	private $events;
	private $event_category_functions;
	private $switch_taxonomy;
	private $use_post_cats;

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
		$this->events = &EL_Events::get_instance();
		$this->event_category_functions = &EL_Event_Category_Functions::get_instance();

		// check used post values
		$this->switch_taxonomy = isset($_GET['switch_taxonomy']) ? (bool)intval($_GET['switch_taxonomy']) : false;
		$this->use_post_cats = ('1' === $this->options->get('el_use_post_cats'));

		// permission checks
		if(!current_user_can('manage_categories') || ($this->switch_taxonomy && !current_user_can('manage_options'))) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		if(!(bool)wp_get_referer() || (!$this->switch_taxonomy && $this->use_post_cats)) {
			wp_die(__('Error: You are not allowed to view this page!','event-list'));
		}
	}

	public function show_cat_sync() {
		// define action
		if(!$this->switch_taxonomy) {
			$action = 'sync';
		}
		elseif($this->use_post_cats) {
			$action = 'switch-to-event';
		}
		else {
			$action = 'switch-to-post';
		}
		// defining the used texts depending on switch_taxonomy value
		if('switch-to-event' === $action) {
			$main_title = __('Affected Categories when switching to seperate Event Categories','event-list');
			$button_text = __('Switch option to seperate Event Categories','event-list');
			$description = __('If you proceed, all post categories will be copied and all events will be re-assigned to this new categories.','event-list').'<br />'.
			               __('Afterwards the event categories are independent of the post categories.','event-list');
		}
		elseif('switch-to-post' === $action) {
			$main_title = __('Affected Categories when switching to use Post Categories for events','event-list');
			$button_text = __('Switch option to use Post Categories for events','event-list');
			$description = __('Take a detailed look at the affected categories above before you proceed! All seperate event categories will be deleted, this cannot be undone!','event-list');
		}
		else {  // 'sync' === action
			$main_title = __('Event Categories: Synchronise with Post Categories','event-list');
			$button_text = __('Start synchronisation','event-list');
			$description = __('If this option is enabled the above listed categories will be deleted and removed from the existing events!','event-list');
		}
		// show form
		echo '
			<style>.el-catlist {list-style:inside}</style>
			<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div>
				<h2>'.$main_title.'</h2>
				<div>
					<form action="" id="el_start_cat_sync" method="post">';
		$this->show_hidden('action', $action);
		$this->show_hidden('_wp_http_referer', wp_get_referer());
		if('sync' === $action) {
			// determine categories to modify, add, delete
			$affected_cats = $this->event_category_functions->get_sync_affected_cats('to_event_cats');
			$this->show_cat_list($affected_cats['to_mod'], 'event', 'cats-to-mod', __('Categories to modify','event-list'));
			$this->show_cat_list($affected_cats['to_add'], 'post',  'cats-to-add', __('Categories to add','event-list'));
			$this->show_cat_list($affected_cats['to_del'], 'event', 'cats-to-del', __('Categories to delete (optional)','event-list'));
			$this->show_checkbox('delete-cats', __('Delete not available post categories','event-list'), empty($affected_cats['to_del']));
		}
		elseif('switch-to-post' === $action) {
			$affected_cats = $this->event_category_functions->get_sync_affected_cats('to_post_cats', array('to_add', 'to_mod'));
			$this->show_cat_list($affected_cats['to_mod'], 'event', 'cats-to-mod', __('Categories with differences','event-list'));
			$this->show_cat_list($affected_cats['to_add'], 'event', 'cats-to-add', __('Categories to add (optional)','event-list'));
			$this->show_checkbox('add-cats', __('Add not available post categories','event-list'), empty($affected_cats['to_add']));
		}
		echo '
						<p style="margin: 3.5em 0 2.5em">'.$description.'</p>';
		$submit_disabled = ('sync' === $action && empty($affected_cats['to_mod']) && empty($affected_cats['to_add']) && empty($affected_cats['to_del'])) ? ' disabled' : '';
		echo '
						<button type="submit" id="cat-sync-submit" class="button button-primary"'.$submit_disabled.'>'.$button_text.'</button>
					</form>
				</div
			</div>';
	}

	private function show_cat_list($cat_slugs, $cat_type, $input_id, $heading) {
		echo '<br />
				<div>
					<h3>'.$heading.':</h3>';
		if(empty($cat_slugs)) {
			echo '
					<p>'.__('none','event-list').'</p>';
		}
		else {
			echo '
					<ul class="el-catlist">';
			foreach($cat_slugs as $cat_slug) {
				$cat_name = 'event' === $cat_type ? $this->events->get_cat_by_slug($cat_slug)->name : get_category_by_slug($cat_slug)->name;
				echo '
							<li>'.$cat_name.' ('.__('Slug').': '.$cat_slug.')</li>';
			}
			echo '
					</ul>';
		}
		echo '
				</div>';
		$this->show_hidden($input_id, esc_html(json_encode($cat_slugs)));
	}

	private function show_hidden($id, $value) {
		echo '
			<input type="hidden" name="'.$id.'" id="'.$id.'" value="'.$value.'">';
	}

	private function show_checkbox($id, $text, $disabled=false) {
		$disabled_text = $disabled ? ' disabled' : '';
		echo '
			<label for="'.$id.'"><input name="'.$id.'" type="checkbox" id="'.$id.'" value="1"'.$disabled_text.' />'.$text.'</label>';
	}

	public function handle_actions() {
		// check used post parameter
		$action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
		// action handling
		switch($action) {

			case 'sync':
				// check used post parameters
				$delete_cats = isset($_POST['delete-cats']) ? (bool)intval($_POST['delete-cats']) : false;
				$affected_cats['to_mod'] = isset($_POST['cats-to-mod']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-mod']), true)) : array();
				$affected_cats['to_add'] = isset($_POST['cats-to-add']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-add']), true)) : array();
				if($delete_cats) {
					$affected_cats['to_del'] = isset($_POST['cats-to-del']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-del']), true)) : array();
				}
				else {
					$affected_cats['to_del'] = array();
				}
				// do actual sync
				$args['msgdata'] = $this->event_category_functions->sync_categories('to_event_cats', $affected_cats);
				if(empty($args['msgdata']['mod_error']) && empty($args['msgdata']['add_error']) && empty($args['msgdata']['del_error'])) {
					$args['message'] = '21';
				}
				else {
					$args['message'] = '22';
					$args['error'] = 1;
				}
				wp_safe_redirect(add_query_arg($args, wp_get_referer()));
				exit;

			case 'switch-to-event':
				$affected_cats = $this->event_category_functions->get_sync_affected_cats('to_event_cats', array('to_add'));
				$args['msgdata'] = $this->event_category_functions->sync_categories('to_event_cats', $affected_cats);
				$args['msgdata'] = $this->event_category_functions->switch_event_taxonomy('to_event_cats');
				$args['settings-updated'] = 'true';
				wp_safe_redirect(add_query_arg($args, wp_get_referer()));
				exit;

			case 'switch-to-post':
				// check used post parameters
				$add_cats = isset($_POST['add-cats']) ? (bool)intval($_POST['add-cats']) : false;
				if($add_cats) {
					$affected_cats['to_add'] = isset($_POST['cats-to-add']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-add']), true)) : array();
					$this->event_category_functions->sync_categories('to_post_cats', $affected_cats);
				}
				$args['msgdata'] = $this->event_category_functions->switch_event_taxonomy('to_post_cats');
				$this->event_category_functions->delete_all_event_cats();
				$args['settings-updated'] = 'true';
				wp_safe_redirect(add_query_arg($args, wp_get_referer()));
				exit;
		}


	}
}
?>
