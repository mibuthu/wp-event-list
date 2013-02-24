<?php
//require_once( EL_PATH.'php/options.php' );
require_once( EL_PATH.'php/db.php' );
require_once( EL_PATH.'php/sc_event-list.php' );
require_once( EL_PATH.'php/admin_event_table.php' );

// This class handles all available admin pages
class el_admin {
	private $db;
	private $options;
	private $shortcode;
	private $dateformat;
	private $event_action = false;
	private $event_action_error = false;

	public function __construct() {
		$this->db = el_db::get_instance();
		//$this->options = &el_options::get_instance();
		$this->shortcode = &sc_event_list::get_instance();
		$this->dateformat = __( 'Y/m/d' ); // similar date format than in list tables (e.g. post, pages, media)
		// $this->dateformat = 'd/m/Y'; // for debugging only
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
		//add_submenu_page( 'el_admin_main', 'Event List Settings', 'Settings', 'manage_options', 'el_admin_settings', array( &$this, 'show_settings' ) );
		$page = add_submenu_page( 'el_admin_main', 'About Event List', 'About', 'edit_posts', 'el_admin_about', array( &$this, 'show_about' ) );
		add_action( 'admin_print_scripts-'.$page, array( &$this, 'embed_admin_about_scripts' ) );
	}

	// show the main admin page
	public function show_main() {
		if ( !current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$action = '';
		// is there POST data an event was edited must be updated
		if( !empty( $_POST ) ) {
			$this->event_action_error = !$this->db->update_event( $_POST, $this->dateformat );
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
			$this->event_action_error = !$this->db->delete_events( $_GET['id'] );
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
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>Add New Event</h2>
				</div>';
		$out .= $this->edit_event();
		$out .= '</div>';
		echo $out;
	}

	private function show_edit() {
		$out = '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>Edit Event</h2>
				</div>';
		$out .= $this->edit_event();
		$out .= '</div>';
		echo $out;
	}
/*
	public function show_settings () {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		$out = '';
		if( isset( $_GET['settings-updated'] ) ) {
			$out .= '<div id="message" class="updated">
				<p><strong>'.__( 'Settings saved.' ).'</strong></p>
			</div>';
		}
		$out.= '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>Event List Settings</h2>
				</div>
				<form method="post" action="options.php">
					Not available yet';
		// TODO: Add settings to settings page
//		$out .= settings_fields( 'mfgigcal_settings' );
//		$out .= do_settings_sections('mfgigcal');
//		$out .= '<input name="Submit" type="submit" value="'.esc_attr__( 'Save Changes' ).'" />
//			</form>
//		</div>';
		/*
		<h3>Comment Guestbook Settings</h3>';
		if( !isset( $_GET['tab'] ) ) {
			$_GET['tab'] = 'general';
		}
		$out .= cgb_admin::create_tabs( $_GET['tab'] );
		$out .= '<div id="posttype-page" class="posttypediv">';
		$out .= '
						<form method="post" action="options.php">
						';
		ob_start();
		settings_fields( 'cgb_'.$_GET['tab'] );
		$out .= ob_get_contents();
		ob_end_clean();
		$out .= '
						<div style="padding:0 10px">';
		switch( $_GET['tab'] ) {
			case 'comment_list' :
				$out .= '
							<table class="form-table">';
				$out .= cgb_admin::show_options( 'comment_list' );
				$out .= '
								</table>';
				break;
			default : // 'general'
				$out .= '
							<table class="form-table">';
				$out .= cgb_admin::show_options( 'general' );
				$out .= '
								</table>';
				break;
		}
		$out .=
				'</div>';
		ob_start();
		submit_button();
		$out .= ob_get_contents();
		ob_end_clean();
		$out .='
		</form>
		</div>';
		echo $out;
	}
*/
	public function show_about() {
		$out = '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>About Event List</h2>
				</div>
				<h3>Help and Instructions</h3>
				<p>You can manage your events <a href="admin.php?page=el_admin_main">here</a>.</p>
				<p>To show the events on your site you have two possibilities:
					<ul class="el-show-event-options"><li>you can place the <strong>shortcode</strong> <code>[event-list]</code> on any page or post</li>
					<li>you can add the <strong>widget</strong> "Event List" in your sidebars</li></ul>
					The displayed events and their style can be modified with the available widget settings and the available attributes for the shortcode.<br />
					A list of all available shortcode attributes with their description is listed below.<br />
					The most available options of the widget should be clear by there description.<br />
					It is important to know that you have to insert an URL to the linked event-list page if you enable one of the links options ("Add links to the single events" or "Add a link to an event page").
					This is required because the widget didn´t know in which page or post you have insert the shortcode.<br />
					Additonally you have to insert the correct Shortcode ID on the linked page. This ID describes which shortcode should be used on the given page or post if you have more than one.
					So the standard value "1" is normally o.k., but you can check the ID if you have a look into the URL of an event link on your linked page or post.
					The ID is given behind the "_" (e.g. <i>http://www.your-homepage.com/?page_id=99&event_id_<strong>1</strong>=11</i>).
				</p>
				<p>Be sure to also check the <a href="admin.php?page=el_admin_settings">settings page</a> to get Event List behaving just the way you want.</p>
			</div>';
		$out .= $this->html_atts();
		echo $out;
	}

	public function embed_admin_main_scripts() {
		// If edit event is selected switch to embed admin_new
		if( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$this->embed_admin_new_scripts();
		}
		else {
			// Proceed with embedding for admin_main
			wp_enqueue_script( 'eventlist_admin_main_js', EL_URL.'js/admin_main.js' );
			wp_enqueue_style( 'eventlist_admin_main', EL_URL.'css/admin_main.css' );
		}
	}

	public function embed_admin_new_scripts() {
		wp_print_scripts( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'eventlist_admin_new_js', EL_URL.'js/admin_new.js' );
		wp_enqueue_style( 'eventlist_admin_new', EL_URL.'css/admin_new.css' );
	}

	public function embed_admin_about_scripts() {
		wp_enqueue_style( 'eventlist_admin_about', EL_URL.'css/admin_about.css' );
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
		$table = new Admin_Event_Table();
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
		                            'el_date_format' => $this->datepicker_format( $this->dateformat ) ) );
		$out = "<input type='hidden' id='json_for_js' value='".$json."' />";
		$out .= '<form method="POST" action="?page=el_admin_main">';
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
				<td><input type="text" class="text datepicker form-required" name="start_date" id="start_date" value="'.date_i18n( $this->dateformat, $start_date ).'" />
						<span id="end_date_area"> - <input type="text" class="text datepicker" name="end_date" id="end_date" value="'.date_i18n( $this->dateformat, $end_date ).'" /></span>
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
		                          'tinymce' => array( 'height' => '400',
		                                              'force_br_newlines' => false,
		                                              'force_p_newlines' => true,
		                                              'convert_newlines_to_brs' => false ),
		                          'quicktags' => true );
		ob_start();
			wp_editor( isset( $event->details ) ? $event->details : '', 'details', $editor_settings);
			$out .= ob_get_contents();
		ob_end_clean();
		$out .= '<p class="note">NOTE: In the text editor, use RETURN to start a new paragraph - use SHIFT-RETURN to start a new line.</p></td>
			</tr>
			</table>';
		$out .= '<p class="submit"><input type="submit" class="button-primary" name="publish" value="Publish" id="submitbutton"> <a href="?page=el_admin_main" class="button-secondary">Cancel</a></p></form>';
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
				$out .= '<div id="message" class="updated below-h2"><p><strong>New Event "'.$_POST['title'].'" was added.</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error: New Event "'.$_POST['title'].'" could not be added.</strong></p></div>';
			}
		}
		// event modified
		elseif( 'modified' === $this->event_action ) {
			if( false === $this->event_action_error ) {
				$out .= '<div id="message" class="updated below-h2"><p><strong>Event "'.$_POST['title'].'" (id: '.$_POST['id'].') was modified.</strong></p></div>';
			}
			else {
				$out .= '<div id="message" class="error below-h2"><p><strong>Error: Event "'.$_POST['title'].'" (id: '.$_POST['id'].') could not be modified.</strong></p></div>';
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

	// TODO: Function "create_tabs" not required yet, can be removed probably
	private function create_tabs( $current = 'general' )  {
		$tabs = array( 'general' => 'General settings', 'comment_list' => 'Comment-list settings', 'comment_form' => 'Comment-form settings',
						'comment_form_html' => 'Comment-form html code', 'comment_html' => 'Comment html code' );
		$out = '<h3 class="nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			$out .= "<a class='nav-tab$class' href='?page=cgb_admin_main&amp;tab=$tab'>$name</a>";
		}
		$out .= '</h3>';
		return $out;
	}

	// $desc_pos specifies where the descpription will be displayed.
	// available options:  'right'   ... description will be displayed on the right side of the option (standard value)
	//                     'newline' ... description will be displayed below the option
	private function show_options( $section, $desc_pos='right' ) {
		$out = '';
		foreach( $this->options as $oname => $o ) {
			if( $o['section'] == $section ) {
				$out .= '
						<tr valign="top">
							<th scope="row">';
				if( $o['label'] != '' ) {
					$out .= '<label for="'.$oname.'">'.$o['label'].':</label>';
				}
				$out .= '</th>
						<td>';
				switch( $o['type'] ) {
					case 'checkbox':
						$out .= cgb_admin::show_checkbox( $oname, $this->get( $oname ), $o['caption'] );
						break;
					case 'text':
						$out .= cgb_admin::show_text( $oname, $this->get( $oname ) );
						break;
					case 'textarea':
						$out .= cgb_admin::show_textarea( $oname, $this->get( $oname ) );
						break;
				}
				$out .= '
						</td>';
				if( $desc_pos == 'newline' ) {
					$out .= '
					</tr>
					<tr>
						<td></td>';
				}
				$out .= '
						<td class="description">'.$o['desc'].'</td>
					</tr>';
				if( $desc_pos == 'newline' ) {
					$out .= '
						<tr><td></td></tr>';
				}
			}
		}
		return $out;
	}

	private function show_checkbox( $name, $value, $caption ) {
		$out = '
							<label for="'.$name.'">
								<input name="'.$name.'" type="checkbox" id="'.$name.'" value="1"';
		if( $value == 1 ) {
			$out .= ' checked="checked"';
		}
		$out .= ' />
								'.$caption.'
							</label>';
		return $out;
	}

	private function show_text( $name, $value ) {
		$out = '
							<input name="'.$name.'" type="text" id="'.$name.'" value="'.$value.'" />';
		return $out;
	}

	private function show_textarea( $name, $value ) {
		$out = '
							<textarea name="'.$name.'" id="'.$name.'" rows="20" class="large-text code">'.$value.'</textarea>';
		return $out;
	}

	private function html_atts() {
		$out = '
			<h3 class="el-headline">Available Shortcode Attributes</h3>
			<div>
				You have the possibility to modify the output if you add some of the following attributes to the shortcode.<br />
				You can combine as much attributes as you want. E.g.the shortcode including the attributes "num_events" and "show_nav" would looks like this:
				<p><code>[event-list num_events=10 show_nav=0]</code></p>
				<p>Below you can find a list of all supported attributes with their descriptions and available options:</p>';
		$out .= $this->html_atts_table();
		$out .= '
			</div>';
		return $out;
	}

	private function html_atts_table() {
		$out = '
			<table class="el-atts-table">
				<tr>
					<th class="el-atts-table-name">Attribute name</th>
					<th class="el-atts-table-options">Value options</th>
					<th class="el-atts-table-default">Default value</th>
					<th class="el-atts-table-desc">Description</th>
				</tr>';
		$atts = $this->shortcode->get_atts();
		foreach( $atts as $aname => $a ) {
			$out .= '
				<tr>
					<td>'.$aname.'</td>
					<td>'.$a['val'].'</td>
					<td>'.$a['std_val'].'</td>
					<td>'.$a['desc'].'</td>
				</tr>';
		}
		$out .= '
			</table>';
		return $out;
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
