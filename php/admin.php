<?php
//require_once( EL_PATH.'php/options.php' );
require_once( EL_PATH.'php/db.php' );

// This class handles all available admin pages
class el_admin {

	// show the main admin page as a submenu of "Comments"
	public static function show_main() {
		if ( !current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$out ='
			<div class="wrap nosubsub" style="padding-bottom:15px">
			<div id="icon-edit-pages" class="icon32"><br /></div><h2>Event List</h2>
			</div>';
		
		// is there POST data to deal with?
		if ($_POST) {
			el_db::update_event($_POST);
		}
		
		$out .= '<div class="wrap">';

		if( !isset( $_GET['action'] ) ) {
			$_GET['action'] = '';
		}
		switch ( $_GET['action'] ) {
			case "edit" :
				$out .= self::show_edit();
				break;
			case "delete" :
				el_db::delete_event( $_GET['id'] );
				$out .= self::list_events();
				break;
			case "copy" :
				$out .= self::edit_event();
				break;
			default :
				$out .= self::list_events();
		}
		$out .= '</div>';
		echo $out;
	}
	
	public static function show_new() {
		$out = '<div class="wrap">
				<div class="wrap nosubsub" style="padding-bottom:15px">
					<div id="icon-edit-pages" class="icon32"><br /></div><h2>New Event</h2>
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
	
	public static function embed_admin_js() {
		echo '<script type="text/javascript" src="'.EL_URL.'/js/admin.js"></script>';
	}

	private static function list_events() {
		if ( isset( $_GET['ytd'] ) ) {
			$events = el_db::get_events( $_GET['ytd'] );
		}
		else {
			$events = el_db::get_events( 'upcoming' );
		}
		$out = el_db::html_calendar_nav();
		$out .=  '<style type="text/css">
					<!--
					.widefat .event_date { text-align: right; width: 150px; }
					.widefat .event_location { text-align: left; width: 27%; min-width: 200px; }
					.widefat .event_details { min-width: 70px; }
					.widefat .event_buttons { text-align: right; padding: 8px; }
					.widefat .event_title { font-weight: bold; }
					}
					-->
				</style>';
		$out .= '<a href="?page=el_admin_new" class="button-primary" style="float:right;">New Event</a>
			<table class="widefat" style="margin-top:10px;">
				<thead>
				<tr><th class="event_date">Date</th><th class="event_location">Event</th><th class="event_details" colspan="2">Event Details</th></tr>
			</thead>';

		if ( !empty( $events ) ) {	
			foreach ( $events as $event ) {
				$out .= '<tr><td class="event_date">';
				$out .= self::format_date( $event->start_date, $event->end_date).'<br />';
				$out .= $event->time;
				$out .= '</td>
						<td class="event_location"><div class="event_title">'.$event->title.'</div>'.self::truncate( 80, $event->location ).'</td>
						<td class="event_details">'.self::truncate( 100, $event->details ).'</td>
						<td class="event_buttons" style="white-space:nowrap;">
							<a href="?page=el_admin_main&id='.$event->id.'&action=edit" class="button-secondary" title="Edit this event">Edit</a>
							<a href="?page=el_admin_main&id='.$event->id.'&action=copy" class="button-secondary" title="Create a new event based on this event">Duplicate</a>
							<a href="#" onClick="eventlist_deleteEvent('.$event->id.');return false;" class="button-secondary" title="Delete this event">Delete</a>
						</td></tr>';
			}
		}
		else {
			$out .= '<tr>
				<td colspan="10" style="text-align:center;">No events found in this range.</td>
			</tr>';
		}
	
		$out .= "</table>";
		return $out;
	}
	
	private static function edit_event() {
		$copy = false;
		$new = false;
		if( isset( $_GET['id'] ) ) {
			// existing event
			$event = el_db::get_event( $_GET['id'] );
			if ( isset( $_GET['action'] ) && $_GET['action'] == 'copy' ) {
				// copy of existing event
				$start_date = date('Y-m-d');
				$end_date = date('Y-m-d');
				$copy = true;
			}
			else {
				// edit existing event
				$start_date = $event->start_date;
				$end_date = $event->end_date;
			}
		}
		else {
			//new event
			$start_date = date('Y-m-d');
			$end_date = date('Y-m-d');
			$new = true;
		}

		$out = '<form method="POST" action="?page=el_admin_main">';
		if ( !$new && !$copy ) {
			$out .= '<input type="hidden" name="id" value="'.$_GET['id'].'" />';
		}
		$out .= '<table class="form-table">
			<tr>
				<th><label>Start Date (required)</label></th>
				<td><input type="text" class="text datepicker form-required" name="start_date" id="start_date" value="'.$start_date.'" /> <label><input type="checkbox" id="multi" /> Multiple Day Event</label></td>
			</tr>
			<tr id="end_date_row">
				<th><label>End Date</label></th>
				<td><input type="text" class="text datepicker" name="end_date" id="end_date" value="'.$end_date.'" /></td>
			</tr>
			<tr>
				<th><label>Event Title (required)</label></th>
				<td><input type="text" class="text form-required" style="width:350px;" name="title" id="title" value="'.str_replace( '"', '&quot;', isset( $event->title ) ? $event->title : '' ).'" /></td>
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
		$out .= '<p style="margin:2px;"><i>NOTE: In the text editor, use RETURN to start a new paragraph - use SHIFT-RETURN to start a new line.</i></p></td>
			</tr>
			</table>';
		$out .= '<p class="submit"><input type="submit" class="button-primary" name="save" value="Save Event" id="submitbutton"> <a href="?page=el_admin_main" class="button-secondary">Cancel</a></p></form>';
		return $out;
	}

	private static function format_date( $start_date, $end_date ) {
		$start_array = explode("-", $start_date);
		$start_date = mktime(0,0,0,$start_array[1],$start_array[2],$start_array[0]);
		$end_array = explode("-", $end_date);
		$end_date = mktime(0,0,0,$end_array[1],$end_array[2],$end_array[0]);
		$out = '';
	
		if ($start_date == $end_date) {
			if ($start_array[2] == "00") {
				$start_date = mktime(0,0,0,$start_array[1],15,$start_array[0]);
				$out .= '<span style="white-space:nowrap;">' . date("F, Y", $start_date) . "</span>";
				return $out;
			}
			$out .= '<span style="white-space:nowrap;">' . date("M j, Y", $start_date) . "</span>";
			return $out;
		}
	
		if ($start_array[0] == $end_array[0]) {
			if ($start_array[1] == $end_array[1]) {
				$out .= '<span style="white-space:nowrap;">' . date("M j", $start_date) . "-" . date("j, Y", $end_date) . "</span>";
				return $out;
			}
			$out .= '<span style="white-space:nowrap;">' . date("M j", $start_date) . "-" . date("M j, Y", $end_date) . "</span>";
			return $out;
	
		}
	
		$out .= '<span style="white-space:nowrap;">' . date("M j, Y", $start_date) . "-" . date("M j, Y", $end_date) . "</span>";
		return $out;
	}
	
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
	
	// function to truncate and shorten html text
	private static function truncate( $maxLength, $html ) {
		$printedLength = 0;
		$position = 0;
		$tags = array();
	
		$out = '';
	
		while ($printedLength < $maxLength && preg_match('{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position)) {
			list($tag, $tagPosition) = $match[0];
	
			// Print text leading up to the tag.
			$str = substr($html, $position, $tagPosition - $position);
			if ($printedLength + strlen($str) > $maxLength) {
				$out .= substr($str, 0, $maxLength - $printedLength);
				$printedLength = $maxLength;
				break;
			}
	
			$out .= $str;
			$printedLength += strlen($str);
	
			if ($tag[0] == '&') {
				// Handle the entity.
				$out .= $tag;
				$printedLength++;
			}
			else {
				// Handle the tag.
				$tagName = $match[1][0];
				if ($tag[1] == '/')
				{
					// This is a closing tag.
					$openingTag = array_pop($tags);
					assert($openingTag == $tagName); // check that tags are properly nested.
					$out .= $tag;
				}
				else if ($tag[strlen($tag) - 2] == '/') {
					// Self-closing tag.
					$out .= $tag;
				}
				else {
					// Opening tag.
					$out .= $tag;
					$tags[] = $tagName;
				}
			}
	
			// Continue after the tag.
			$position = $tagPosition + strlen($tag);
		}
	
		// Print any remaining text.
		if ($printedLength < $maxLength && $position < strlen($html)) {
			$out .= substr($html, $position, $maxLength - $printedLength);
		}
		if ($maxLength < strlen($html)) {
			$out .= "...";
		}

		// Close any open tags.
		while (!empty($tags)) {
			$out .= "</" . array_pop($tags) . ">";
		}
		return $out;
	}
}
?>