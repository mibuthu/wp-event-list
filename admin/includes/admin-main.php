<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'admin/includes/event_table.php');
require_once(EL_PATH.'includes/filterbar.php');

// This class handles all data for the admin main page
class EL_Admin_Main {
	private static $instance;
	private $db;
	private $filterbar;
	private $event_table;
	private $event_action = false;
	private $event_action_error = false;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin_Main();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->filterbar = &EL_Filterbar::get_instance();
		$this->event_action = null;
		$this->event_action_error = null;
	}

	// show the main admin page
	public function show_main() {
		if(!current_user_can('edit_posts')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$this->event_table = new EL_Event_Table();
		// is there POST data an event was edited must be updated
		if(!empty($_POST)) {
			$this->event_action_error = !$this->db->update_event($_POST, __('Y/m/d'));
			$this->event_action = isset($_POST['id']) ? 'modified' : 'added';
		}
		// get action
		$action = $this->event_table->current_action();
		// TODO: add check_admin_referer to improve security (see /wp-admin/edit.php)
		// if an event should be edited a different page must be displayed
		if('edit' === $action) {
			$this->show_edit();
			return;
		}
		// delete events if required
		if('delete' === $action && isset($_GET['id'])) {
			$this->event_action_error = !$this->db->delete_events(explode(',', $_GET['id']));
			$this->event_action = 'deleted';
		}
		// automatically set order of table to date, if no manual sorting is set
		if(!isset($_GET['orderby'])) {
			$_GET['orderby'] = 'date';
			$_GET['order'] = 'asc';
		}

		// headline for the normal page
		$out ='
			<div class="wrap">
			<div id="icon-edit-pages" class="icon32"><br /></div><h2>Events <a href="?page=el_admin_new" class="add-new-h2">Add New</a></h2>';
		// added messages if required
		$out .= $this->show_messages();
		// list event table
		$out .= $this->list_events();
		$out .= '</div>';
		echo $out;
	}

	private function show_edit() {
		$out = '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>Edit Event</h2>';
		require_once(EL_PATH.'admin/includes/admin-new.php');
		$out .= EL_Admin_New::get_instance()->edit_event();
		$out .= '</div>';
		echo $out;
	}

	public function embed_main_scripts() {
		// If edit event is selected switch to embed admin_new
		if(isset($_GET['action']) && 'edit' === $_GET['action']) {
			require_once(EL_PATH.'admin/includes/admin-new.php');
			EL_Admin_New::get_instance()->embed_new_scripts();
		}
		else {
			// Proceed with embedding for admin_main
			wp_enqueue_script('eventlist_admin_main_js', EL_URL.'admin/js/admin_main.js');
			wp_enqueue_style('eventlist_admin_main', EL_URL.'admin/css/admin_main.css');
		}
	}

	private function list_events() {
		// set filter options
		if((isset($_GET['ytd']) && 'all' == $_GET['ytd']) ||
		   (isset($_GET['ytd']) && is_numeric($_GET['ytd']))) {
			$date_range = $_GET['ytd'];
		}
		else {
			$date_range = 'upcoming';
		}
		$cat_filter = isset($_GET['cat']) ? array($_GET['cat']) : null;

		// show filterbar
		$out = '';
//		$out = $this->filterbar->show('?page=el_admin_main', $_GET);
		// show event table
		// the form is required for bulk actions, the page field is required for plugins to ensure that the form posts back to the current page
		$out .= '<form id="event-filter" method="get">
				<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		// show table
		$this->event_table->prepare_items($date_range, $cat_filter);
		ob_start();
			$this->event_table->display();
			$out .= ob_get_contents();
		ob_end_clean();
		$out .= '</form>';
		return $out;
	}

	private function show_messages() {
		$out = '';
		// event added
		if('added' === $this->event_action) {
			if(false === $this->event_action_error) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>New Event "'.stripslashes($_POST['title']).'" was added.</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error: New Event "'.stripslashes($_POST['title']).'" could not be added.</strong></p></div>';
			}
		}
		// event modified
		elseif('modified' === $this->event_action) {
			if(false === $this->event_action_error) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>Event "'.stripslashes($_POST['title']).'" (id: '.$_POST['id'].') was modified.</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error: Event "'.stripslashes($_POST['title']).'" (id: '.$_POST['id'].') could not be modified.</strong></p></div>';
			}
		}
		// event deleted
		elseif('deleted' === $this->event_action) {
			$num_deleted = count(explode(',', $_GET['id']));
			$plural = '';
			if($num_deleted > 1) {
				$plural = 's';
			}
			if(false === $this->event_action_error) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>'.$num_deleted.' Event'.$plural.' deleted (id'.$plural.': '.$_GET['id'].').</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error while deleting '.$num_deleted.' Event'.$plural.'.</strong></p></div>';
			}
		}
		return $out;
	}
}
?>
