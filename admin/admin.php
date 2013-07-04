<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once( EL_PATH.'includes/db.php' );
require_once( EL_PATH.'includes/options.php' );
require_once( EL_PATH.'admin/includes/event_table.php' );
require_once( EL_PATH.'admin/includes/admin-settings.php' );
require_once( EL_PATH.'admin/includes/admin-about.php' );

// This class handles all available admin pages
class EL_Admin {
	private $db;
	private $options;
	private $event_action = false;
	private $event_action_error = false;

	public function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->options = &EL_Options::get_instance();
		$this->event_action = null;
		$this->event_action_error = null;
	}

	/**
	 * Add and register all admin pages in the admin menu
	 */
	public function register_pages() {
		add_menu_page( 'Event List', 'Event List', 'edit_posts', 'el_admin_main', array( &$this, 'show_main' ) );
		$page = add_submenu_page( 'el_admin_main', 'Events', 'All Events', 'edit_posts', 'el_admin_main', array( &$this, 'show_main' ) );
		add_action( 'admin_print_scripts-'.$page, array( &$this, 'embed_admin_main_scripts' ) );
		$page = add_submenu_page( 'el_admin_main', 'Add New Event', 'Add New', 'edit_posts', 'el_admin_new', array( &$this, 'show_new' ) );
		add_action( 'admin_print_scripts-'.$page, array( &$this, 'embed_admin_new_scripts' ) );
		$page = add_submenu_page( 'el_admin_main', 'Event List Settings', 'Settings', 'manage_options', 'el_admin_settings', array( EL_Admin_Settings::get_instance(), 'show_settings' ) );
		add_action( 'admin_print_scripts-'.$page, array( EL_Admin_Settings::get_instance(), 'embed_admin_settings_scripts' ) );
		$page = add_submenu_page( 'el_admin_main', 'About Event List', 'About', 'edit_posts', 'el_admin_about', array( EL_Admin_About::get_instance(), 'show_about' ) );
		add_action( 'admin_print_scripts-'.$page, array( EL_Admin_About::get_instance(), 'embed_admin_about_scripts' ) );
	}

	// show the main admin page
	public function show_main() {
		if ( !current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$action = '';
		// is there POST data an event was edited must be updated
		if( !empty( $_POST ) ) {
			$this->event_action_error = !$this->db->update_event( $_POST, __( 'Y/m/d' ) );
			$this->event_action = isset( $_POST['id'] ) ? 'modified' : 'added';
		}
		// get action
		if( isset( $_GET['action'] ) ) {
			$action = $_GET['action'];
		}
		// if an event should be edited a different page must be displayed
		if( $action === 'edit' ) {
			$this->show_edit();
			return;
		}
		// delete events if required
		if( $action === 'delete' && isset( $_GET['id'] ) ) {
			$this->event_action_error = !$this->db->delete_events( explode(',', $_GET['id'] ) );
			$this->event_action = 'deleted';
		}
		// automatically set order of table to date, if no manual sorting is set
		if( !isset( $_GET['orderby'] ) ) {
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

	public function show_new() {
		$out = '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>Add New Event</h2>';
		$out .= $this->edit_event();
		$out .= '</div>';
		echo $out;
	}

	private function show_edit() {
		$out = '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>Edit Event</h2>';
		$out .= $this->edit_event();
		$out .= '</div>';
		echo $out;
	}

	public function embed_admin_main_scripts() {
		// If edit event is selected switch to embed admin_new
		if( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$this->embed_admin_new_scripts();
		}
		else {
			// Proceed with embedding for admin_main
			wp_enqueue_script( 'eventlist_admin_main_js', EL_URL.'admin/js/admin_main.js' );
			wp_enqueue_style( 'eventlist_admin_main', EL_URL.'admin/css/admin_main.css' );
		}
	}

	public function embed_admin_new_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'link' );
		wp_enqueue_script( 'eventlist_admin_new_js', EL_URL.'admin/js/admin_new.js' );
		wp_enqueue_style( 'eventlist_admin_new', EL_URL.'admin/css/admin_new.css' );
	}

	private function list_events() {
		// show calendar navigation
		$out = $this->show_calendar_nav();
		// set date range of events being displayed
		$date_range = 'upcoming';
		if( isset( $_GET['ytd'] ) && is_numeric( $_GET['ytd'] ) ) {
			$date_range = $_GET['ytd'];
		}
		// show event table
		// the form is required for bulk actions, the page field is required for plugins to ensure that the form posts back to the current page
		$out .= '<form id="event-filter" method="get">
				<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		// show table
		$table = new EL_Event_Table();
		$table->prepare_items( $date_range );
		ob_start();
			$table->display();
			$out .= ob_get_contents();
		ob_end_clean();
		$out .= '</form>';
		return $out;
	}

	private function edit_event() {
		$edit = false;
		if( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {
			// existing event
			$event = $this->db->get_event( $_GET['id'] );
			if( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
				// editing of an existing event, if not it would be copy of an existing event
				$edit = true;
			}
			$start_date = strtotime( $event->start_date );
			$end_date = strtotime( $event->end_date );
		}
		else {
			//new event
			$start_date = time()+1*24*60*60;
			$end_date = $start_date;
		}

		// Add required data for javascript in a hidden field
		$json = json_encode( array( 'el_url'         => EL_URL,
		                            'el_date_format' => $this->datepicker_format( __( 'Y/m/d' ) ) ) );
		$out = '
				<form method="POST" action="?page=el_admin_main">';
		$out .= "
				<input type='hidden' id='json_for_js' value='".$json."' />"; // single quote required for value due to json layout
		// TODO: saving changed metabox status and order is not working yet
		$out .= wp_nonce_field('autosavenonce', 'autosavenonce', false, false );
		$out .= wp_nonce_field('closedpostboxesnonce', 'closedpostboxesnonce', false, false );
		$out .= wp_nonce_field('meta-box-order-nonce', 'meta-box-order-nonce', false, false );
		$out .= '
				<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">';
		if( true === $edit ) {
			$out .= '<input type="hidden" name="id" value="'.$_GET['id'].'" />';
		}
		$out .= '<table class="form-table">
			<tr>
				<th><label>Event Title (required)</label></th>
				<td><input type="text" class="text form-required" name="title" id="title" value="'.str_replace( '"', '&quot;', isset( $event->title ) ? $event->title : '' ).'" /></td>
			</tr>
			<tr>
				<th><label>Event Date (required)</label></th>
				<td><input type="text" class="text datepicker form-required" name="start_date" id="start_date" value="'.date_i18n( __( 'Y/m/d' ), $start_date ).'" />
						<span id="end_date_area"> - <input type="text" class="text datepicker" name="end_date" id="end_date" value="'.date_i18n( __( 'Y/m/d' ), $end_date ).'" /></span>
						<label><input type="checkbox" name="multiday" id="multiday" value="1" /> Multi-Day Event</label></td>
			</tr>
			<tr>
				<th><label>Event Time</label></th>
				<td><input type="text" class="text" name="time" id="time" value="'.str_replace( '"', '&quot;', isset( $event->time ) ? $event->time : '' ).'" /></td>
			</tr>
			<tr>
				<th><label>Event Location</label></th>
				<td><input type="text" class="text" name="location" id="location" value="'.str_replace( '"', '&quot;', isset( $event->location ) ? $event->location : '' ).'" /></td>
			</tr>
			<tr>
				<th><label>Event Details</label></th>
				<td>';
		$editor_settings = array( 'media_buttons' => true,
		                          'wpautop' => false,
		                          'textarea_rows' => 20 );
		ob_start();
			wp_editor( isset( $event->details ) ? $event->details : '', 'details', $editor_settings);
			$out .= ob_get_contents();
		ob_end_clean();
		$out .= '<p class="note">NOTE: In the text editor, use RETURN to start a new paragraph - use SHIFT-RETURN to start a new line.</p></td>
			</tr>
			</table>';
		$out .= '
				</div>
				<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box-sortables ui-sortable">';
		add_meta_box( 'event-publish', __( 'Publish' ), array( &$this, 'render_publish_metabox' ), 'event-list' );
		$metabox_args = isset( $event->categories ) ? array( 'event_cats' => $event->categories ) : null;
		add_meta_box( 'event-categories', __( 'Categories' ), array( &$this, 'render_category_metabox' ), 'event-list', 'advanced', 'default', $metabox_args );
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

	private function show_calendar_nav() {
		$first_year = $this->db->get_event_date( 'first' );
		$last_year = $this->db->get_event_date( 'last' );

		// Calendar Navigation
		if( true === is_admin() ) {
			$url = "?page=el_admin_main";
			$out = '<ul class="subsubsub">';
			if( isset( $_GET['ytd'] ) || isset( $_GET['event_id'] ) ) {
				$out .= '<li class="upcoming"><a href="'.$url.'">Upcoming</a></li>';
			}
			else {
				$out .= '<li class="upcoming"><a class="current" href="'.$url.'">Upcoming</a></li>';
			}
			for( $year=$last_year; $year>=$first_year; $year-- ) {
				$out .= ' | ';
				if( isset( $_GET['ytd'] ) && $year == $_GET['ytd'] ) {
					$out .= '<li class="year"><a class="current" href="'.$url.'ytd='.$year.'">'.$year.'</a></li>';
				}
				else {
					$out .= '<li class="year"><a href="'.$url.'&amp;ytd='.$year.'">'.$year.'</a></li>';
				}
			}
			$out .= '</ul><br />';
		}
		return $out;
	}

	private function show_messages() {
		$out = '';
		// event added
		if( 'added' === $this->event_action ) {
			if( false === $this->event_action_error ) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>New Event "'.stripslashes( $_POST['title'] ).'" was added.</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error: New Event "'.stripslashes( $_POST['title'] ).'" could not be added.</strong></p></div>';
			}
		}
		// event modified
		elseif( 'modified' === $this->event_action ) {
			if( false === $this->event_action_error ) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>Event "'.stripslashes( $_POST['title'] ).'" (id: '.$_POST['id'].') was modified.</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error: Event "'.stripslashes( $_POST['title'] ).'" (id: '.$_POST['id'].') could not be modified.</strong></p></div>';
			}
		}
		// event deleted
		elseif( 'deleted' === $this->event_action ) {
			$num_deleted = count( explode( ',', $_GET['id'] ) );
			$plural = '';
			if( $num_deleted > 1 ) {
				$plural = 's';
			}
			if( false === $this->event_action_error ) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>'.$num_deleted.' Event'.$plural.' deleted (id'.$plural.': '.$_GET['id'].').</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error while deleting '.$num_deleted.' Event'.$plural.'.</strong></p></div>';
			}
		}
		return $out;
	}

	public function render_publish_metabox() {
		$out = '<div class="submitbox">
				<div id="delete-action"><a href="?page=el_admin_main" class="submitdelete deletion">'.__( 'Cancel' ).'</a></div>
				<div id="publishing-action"><input type="submit" class="button button-primary button-large" name="publish" value="'.__( 'Publish' ).'" id="publish"></div>
				<div class="clear"></div>
			</div>';
		echo $out;
	}

	public function render_category_metabox( $post, $metabox ) {
		$out = '
				<div id="taxonomy-category" class="categorydiv">
				<div id="category-all" class="tabs-panel">';
		$cat_array = (array) $this->options->get( 'el_categories' );
		if( empty( $cat_array ) ) {
			$out .= __( 'No categories available.' );
		}
		else {
			$out .= '
					<ul id="categorychecklist" class="categorychecklist form-no-clear">';
			$event_cats = explode( '|', substr($metabox['args']['event_cats'], 1, -1 ) );
			foreach( $cat_array as $cat ) {
				$checked = in_array( $cat['slug'], $event_cats ) ? 'checked="checked" ' : '';
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
					<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js">'.__( '+ Add New Category' ).'</a></h4>
					<p id="category-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="newcategory">'.__( 'Category Name' ).'</label>
						<input type="text" name="newcategory" id="newcategory" class="form-required form-input-tip" value="" aria-required="true"/>
						<input type="button" id="category-add-submit" class="button category-add-submit" value="'.__( 'Add Category' ).'" />
					</p>
				</div>*/
		$out .= '
				<div id="category-manager">
					<a id="category-manage-link" href="?page=el_admin_settings&amp;tab=category">'.__( 'Goto Category Settings' ).'</a>
				</div>
				</div>';
		echo $out;
	}

	/**
	 * Convert a date format to a jQuery UI DatePicker format
	 *
	 * @param string $format a date format
	 * @return string
	 */
	private function datepicker_format( $format ) {
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
