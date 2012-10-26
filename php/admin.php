<?php
//require_once( EL_PATH.'php/options.php' );
require_once( EL_PATH.'php/db.php' );
require_once( EL_PATH.'php/admin_event_table.php' );

// This class handles all available admin pages
class el_admin {
	private static $event_action = false;
	private static $event_action_error = false;

	// show the main admin page as a submenu of "Comments"
	public static function show_main() {
		if ( !current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$action = '';
		// is there POST data an event was edited must be updated
		if( !empty( $_POST ) ) {
			self::$event_action_error = !el_db::update_event( $_POST );
			self::$event_action = isset( $_POST['id'] ) ? 'modified' : 'added';
		}
		// get action
		if( isset( $_GET['action'] ) ) {
			$action = $_GET['action'];
		}
		// if an event should be edited a different page must be displayed
		if( $action === 'edit' ) {
			self::show_edit();
			return;
		}
		// delete events if required
		if( $action === 'delete' && isset( $_GET['id'] ) ) {
			self::$event_action_error = !el_db::delete_events( $_GET['id'] );
			self::$event_action = 'deleted';
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
		$out .= self::show_messages();
		// list event table
		$out .= self::list_events();
		$out .= '</div>';
		echo $out;
	}

	public static function show_new() {
		$out = '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>Add New Event</h2>
				</div>';
		$out .= self::edit_event();
		$out .= '</div>';
		echo $out;
	}

	private static function show_edit() {
		$out = '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>Edit Event</h2>
				</div>';
		$out .= self::edit_event();
		$out .= '</div>';
		echo $out;
	}

	public static function show_settings () {
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
		ob_end_clean();*/
		$out .='
		</form>
		</div>';
		echo $out;
	}

	public static function show_about() {
		$out = '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>About Event List</h2>
				</div>
				<h3>Instructions</h3>
				<p>Add your events <a href="admin.php?page=el_admin_main">here</a>.</p>
				<p>To show the events on your site just place this short code on any Page or Post:</p>
				<pre>[event-list]</pre>';
//				<p>The plugin includes a widget to place your events in a sidebar.</p>
		$out .= '<p>Be sure to also check out the <a href="admin.php?page=el_admin_settings">settings page</a> to get Event List behaving just the way you want.</p>
			</div>';
		echo $out;
	}

	public static function embed_admin_main_scripts() {
		// If edit event is selected switch to embed admin_new
		if( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			self::embed_admin_new_scripts();
		}
		else {
			// Proceed with embedding for admin_main
			wp_enqueue_script( 'eventlist_admin_main_js', EL_URL.'js/admin_main.js' );
			wp_enqueue_style( 'eventlist_admin_main_css', EL_URL.'css/admin_main.css' );
		}
	}

	public static function embed_admin_new_scripts() {
		wp_print_scripts( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'eventlist_admin_new_js', EL_URL.'js/admin_new.js' );
		wp_enqueue_style( 'eventlist_admin_new_css', EL_URL.'css/admin_new.css' );
	}

	private static function list_events() {
		// show calendar navigation
		$out = el_db::html_calendar_nav();
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

	private static function edit_event() {
		$edit = false;
		if( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {
			// existing event
			$event = el_db::get_event( $_GET['id'] );
			if( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
				// editing of an existing event, if not it would be copy of an existing event
				$edit = true;
			}
			$start_date = $event->start_date;
			$end_date = $event->end_date;
		}
		else {
			//new event
			$start_date = date( 'Y-m-d', time()+1*24*60*60 );
			$end_date = $start_date;
		}

		// Add required data for javascript in a hidden field
		$json = json_encode( Array( 'el_url' => EL_URL ) );
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
				<td><input type="text" class="text datepicker form-required" name="start_date" id="start_date" value="'.$start_date.'" /><span id="end_date_area"> - <input type="text" class="text datepicker" name="end_date" id="end_date" value="'.$end_date.'" /></span> <label><input type="checkbox" name="multiday" id="multiday" value="1" /> Multi-Day Event</label></td>
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

	private static function show_messages() {
		$out = '';
		// event added
		if( 'added' === self::$event_action ) {
			if( false === self::$event_action_error ) {
				$out .= '<div id="message" class=updated below-h2"><p>New Event "'.$_POST['title'].'" was added.</p></div>';
			}
			else {
				$out .= '<div id="message" class=error below-h2"><p>Error: New Event "'.$_POST['title'].'" could not be added.</p></div>';
			}
		}
		// event modified
		elseif( 'modified' === self::$event_action ) {
			if( false === self::$event_action_error ) {
				$out .= '<div id="message" class=updated below-h2"><p>Event "'.$_POST['title'].'" (id: '.$_POST['id'].') was modified.</p></div>';
			}
			else {
				$out .= '<div id="message" class=error below-h2"><p>Error: Event "'.$_POST['title'].'" (id: '.$_POST['id'].') could not be modified.</p></div>';
			}
		}
		// event deleted
		elseif( 'deleted' === self::$event_action ) {
			$num_deleted = count( explode( ',', $_GET['id'] ) );
			$plural = '';
			if( $num_deleted > 1 ) {
				$plural = 's';
			}
			if( false === self::$event_action_error ) {
				$out .= '<div id="message" class=updated below-h2"><p>'.$num_deleted.' Event'.$plural.' deleted (id'.$plural.': '.$_GET['id'].').</p></div>';
			}
			else {
				$out .= '<div id="message" class=error below-h2"><p>Error while deleting '.$num_deleted.' Event'.$plural.'.</p></div>';
			}
		}
		return $out;
	}

	// TODO: Function "create_tabs" not required yet, can be removed probably
	private static function create_tabs( $current = 'general' )  {
		$tabs = array( 'general' => 'General settings', 'comment_list' => 'Comment-list settings', 'comment_form' => 'Comment-form settings',
						'comment_form_html' => 'Comment-form html code', 'comment_html' => 'Comment html code' );
		$out = '<h3 class="nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			$out .= "<a class='nav-tab$class' href='?page=cgb_admin_main&tab=$tab'>$name</a>";
		}
		$out .= '</h3>';
		return $out;
	}

	// $desc_pos specifies where the descpription will be displayed.
	// available options:  'right'   ... description will be displayed on the right side of the option (standard value)
	//                     'newline' ... description will be displayed below the option
	private static function show_options( $section, $desc_pos='right' ) {
		$out = '';
		foreach( self::$options as $oname => $o ) {
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
						$out .= cgb_admin::show_checkbox( $oname, self::get( $oname ), $o['caption'] );
						break;
					case 'text':
						$out .= cgb_admin::show_text( $oname, self::get( $oname ) );
						break;
					case 'textarea':
						$out .= cgb_admin::show_textarea( $oname, self::get( $oname ) );
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

	private static function show_checkbox( $name, $value, $caption ) {
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

	private static function show_text( $name, $value ) {
		$out = '
							<input name="'.$name.'" type="text" id="'.$name.'" value="'.$value.'" />';
		return $out;
	}

	private static function show_textarea( $name, $value ) {
		$out = '
							<textarea name="'.$name.'" id="'.$name.'" rows="20" class="large-text code">'.$value.'</textarea>';
		return $out;
	}
}
?>
