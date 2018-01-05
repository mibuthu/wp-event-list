<?php
if(!defined('WP_ADMIN')) {
	exit;
}


// This class handles all data for the admin new event page
function el_upgrade_check() {
	error_log('EVENT LIST PLUGIN: Start upgrade check!');
	global $el_actual_version, $el_last_upgr_version;
	// get actual plugin version
	$el_actual_version = get_plugin_data(EL_PATH.'event-list.php', false, false)['Version'];
	// check last upgrade version
	$last_upgr_version = el_get_option_from_db('el_last_upgrade_version');
	// fix for older version < 0.8.0
	if(empty($last_upgr_version) && count(el_get_option_from_db('el_db_version'))) {
		$last_upgr_version = '0.7.9';
		el_insert_option_in_db('el_last_upgrade_version', $last_upgr_version);
	}

	// return if last_upgr_version is empty (new install --> no upgrade required)
	if(empty($last_upgr_version)) {
		el_insert_option_in_db('el_last_upgrade_version', $el_actual_version);
		return false;
	}
	// create version array
	$el_last_upgr_version = explode('.', $last_upgr_version);

	// do required upgrades

	/** VERSION 0.8.0: change from seperate database to custom post type
	 *   * import existing categories from "categories" option
	 *   * import existing events from "event_list" table
	 *   * delete option "el_db_version"
	 *   * rename option "el_show_details_text" to "el_content_show_text"
	 *   * rename option "el_hide_details_text" to "el_content_hide_text"
	 *   * obsolete db table "event_list" and option "el_categories" will be kept for backup and deleted in a later version
	 **/
	if(el_upgrade_required('0.8.0')) {
		require_once(EL_PATH.'includes/events.php');
		require_once(EL_PATH.'includes/event.php');
		// Manually register event category taxonomy which is not available at this stage by default
		require_once(EL_PATH.'includes/events_post_type.php');
		EL_Events_Post_Type::get_instance()->register_event_category_taxonomy();
		// import categories
		$cats_array = el_get_option_from_db('el_categories');
		if(!empty($cats_array)) {
			foreach($cats_array as $cat) {
				if(!EL_Events::get_instance()->cat_exists($cat['slug'])) {
					$args['slug'] = $cat['slug'];
					$args['description'] = $cat['desc'];
					if(isset($cat['parent'])) {
						$parent = EL_Events::get_instance()->get_cat_by_slug($cat['parent']);
						if(!empty($parent)) {
							$args['parent'] = $parent->term_id;
						}
					}
					error_log('EL_UPGRADE: Import event category "'.$cat['name'].'"!');
					$ret = EL_Events::get_instance()->insert_category($cat['name'], $args);
					if(is_wp_error($ret)) {
						error_log('ERROR: Import failed: '.$ret->get_error_message().'!');
					}
				}
			}
		}
		// import events
		global $wpdb;
		$sql = 'SELECT * FROM '.$wpdb->prefix.'event_list ORDER BY start_date ASC, time ASC, end_date ASC';
		$events = $wpdb->get_results($sql, 'ARRAY_A');
		if(!empty($events)) {
			foreach($events as $event) {
				$eventdata['title'] = $event['title'];
				$eventdata['startdate'] = $event['start_date'];
				$eventdata['enddate'] = $event['end_date'];
				$eventdata['starttime'] = $event['time'];
				$eventdata['location'] = $event['location'];
				$eventdata['content'] = $event['details'];
				$eventdata['post_date'] = $event['pub_date'];
				$eventdata['post_user'] = $event['pub_user'];
				$eventdata['categories'] = explode('|', substr($event['categories'], 1, -1));
				error_log('EL_UPGRADE: Import event "'.$eventdata['title'].'"!');
				$ret = EL_Event::safe($eventdata);
				if(empty($ret)) {
					error_log('ERROR: Import failed!');
				}
			}
		}
		// delete option "el_db_version"
		error_log('EL_UPGRADE: Delete obsolete option "el_db_version"!');
		el_delete_option_in_db('el_db_version');
		// rename option "el_show_details_text" to "el_content_show_text"
		error_log('EL_UPGRADE: Rename option "el_show_details_text" to "el_content_show_text"');
		el_rename_option_in_db('el_show_details_text', 'el_content_show_text');
		// rename option "el_hide_details_text" to "el_content_hide_text"
		error_log('EL_UPGRADE: Rename option "el_hide_details_text" to "el_content_hide_text"');
		el_rename_option_in_db('el_hide_details_text', 'el_content_hide_text');
		// update last_upgr_version option
		el_update_last_upgr_version();
	}

}


function el_upgrade_required($version) {
	global $el_last_upgr_version;
	// create version array
	$vers = explode('.', $version);
	// compare main version
	if($el_last_upgr_version[0] < $vers[0]) {
		return true;
	}
	// compare sub version
	elseif($el_last_upgr_version[0] === $vers[0] && $el_last_upgr_version[1] < $vers[1]) {
		return true;
	}
	// compare revision
	elseif($el_last_upgr_version[0] === $vers[0] && $el_last_upgr_version[1] === $vers[1] && $el_last_upgr_version[2] < $vers[2]) {
		return true;
	}
	else {
		return false;
	}
}


function el_get_option_from_db($option) {
	global $wpdb;
	$sql = 'SELECT option_value FROM `'.$wpdb->prefix.'options` WHERE option_name = "'.$option.'";';
	return maybe_unserialize($wpdb->get_var($sql));
}


function el_update_option_in_db($option, $value) {
	global $wpdb;
	return $wpdb->update(
		$wpdb->prefix.'options',
		array('option_value' => $value),
		array('option_name' => $option),
		'%s'
	);
}

function el_insert_option_in_db($option, $value) {
	global $wpdb;
	if(!empty(el_get_option_from_db($option))) {
		return $wpdb->insert(
			$wpdb->prefix.'options',
			array('option_name' => $option, 'option_value' => $value),
			'%s'
		);
	}
	return false;
}


function el_delete_option_in_db($option) {
	global $wpdb;
	return $wpdb->delete(
		$wpdb->prefix.'options',
		array('option_name' => $option),
		'%s'
	);
}

function el_rename_option_in_db($oldname, $newname) {
	$val = el_get_option_from_db($oldname);
	if(!empty($val)) {
		el_insert_option_in_db($newname, $val);
		el_delete_option_in_db($oldname);
	}
}


function el_update_last_upgr_version() {
	global $el_actual_version;
	$ret = el_update_option_in_db('el_last_upgrade_version', $el_actual_version);
}

?>
