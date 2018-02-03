<?php
if(!defined('WP_ADMIN')) {
	exit;
}

/**
 * This class handles required upgrades for new plugin versions
 */
class EL_Upgrade {
	private static $instance;
	private $actual_version;
	private $last_upgr_version;
	public $error = false;
	public $msg = array();

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->init();
		$this->upgrade_check();
	}

	/**
	 * Preparations for the upgrade check
	 */
	private function init() {
		// get actual plugin version
		$filedata = get_file_data(EL_PATH.'event-list.php', array('version'=>'Version'));
		$this->actual_version = $filedata['version'];
		// check last upgrade version
		$this->last_upgr_version = $this->get_db_option('el_last_upgr_version');
		// fix for older version < 0.8.0
		if(empty($this->last_upgr_version) && (bool)$this->get_db_option('el_db_version')) {
			$this->last_upgr_version = '0.7.0';
			$this->insert_db_option('el_last_upgr_version', $this->last_upgr_version, false);
			$this->log('Applied fix for versions < 0.8.0');
		}
		// return if last_upgr_version is empty (new install --> no upgrade required)
		if(empty($this->last_upgr_version)) {
			$this->insert_db_option('el_last_upgr_version', $this->actual_version);
			$this->log('New install -> no upgrade required');
			return false;
		}
		// create the version array
		$this->last_upgr_version = explode('.', $this->last_upgr_version);
	}

	/**
	 * Do the upgrade check and start the required upgrades
	 */
	private function upgrade_check() {
		error_log('EL_UPGRADE: Start upgrade check!');
		$upgrade_done = false;
		if($this->upgrade_required('0.8.0')) {
			$this->upgrade_to_0_8_0();
			$upgrade_done = true;
		}

		// update last_upgr_version
		if($upgrade_done) {
			$this->update_last_upgr_version();
		}
	}


	/** Upgrade to VERSION 0.8.0: change from seperate database to custom post type
	 *   * import existing categories from "categories" option
	 *   * import existing events from "event_list" table
	 *   * delete option "el_db_version"
	 *   * rename option "el_show_details_text" to "el_content_show_text"
	 *   * rename option "el_hide_details_text" to "el_content_hide_text"
	 *   * rename option "el_sync_cats" to "el_use_post_cats"
	 *   * obsolete db table "event_list" and option "el_categories" will be kept for backup, they will be deleted in a later version
	 **/
	private function upgrade_to_0_8_0() {
		require_once(EL_PATH.'includes/events.php');
		require_once(EL_PATH.'includes/event.php');

		// Correct events post type
		require_once(EL_PATH.'includes/events_post_type.php');
		$events_post_type = EL_Events_Post_Type::get_instance();
		// set correct taxonomy
		$events_post_type->use_post_categories = (bool)$this->get_db_option('el_sync_cats');
		$events_post_type->taxonomy = $events_post_type->use_post_categories ? $events_post_type->post_cat_taxonomy : $events_post_type->event_cat_taxonomy;
		// re-register events post type with correct taxonomy
		unregister_post_type('el_events');
		$events_post_type->register_event_post_type();
		// register event_cateogry taxonomy if required
		if(!$events_post_type->use_post_categories) {
			$events_post_type->register_event_category_taxonomy();
		}
		$this->log('Set event category taxonomy to "'.implode(', ', get_object_taxonomies('el_events')).'" (according existing option "el_sync_cats" = "'.($events_post_type->use_post_categories ? 'true' : 'false').'")');

		// Import existing categories
		if(!$events_post_type->use_post_categories) {
			$cats_array = $this->get_db_option('el_categories');
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
						$ret = EL_Events::get_instance()->insert_category($cat['name'], $args);
						if(is_wp_error($ret)) {
							$this->log('Import of event category "'.$cat['name'].'" failed: '.$ret->get_error_message(), true, true);
						}
						else {
							$this->log('Event category "'.$cat['name'].'" successfully imported');
						}
					}
				}
			}
			else {
				$this->log('No existing event categories found');
			}
		}
		else {
			$this->log('"el_sync_cats is enabled: Syncing event categories is not required -> Post categories will be used');
		}

		// Import existing events
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
				$ret = EL_Event::safe($eventdata);
				if(empty($ret)) {
					$this->log('Import of event "'.$eventdata['title'].'" failed!', true, true);
				}
				else {
					$this->log('Event "'.$eventdata['title'].'" successfully imported');
				}
			}
		}
		else {
			$this->log('No existing events found');
		}

		// Delete obsolete option "el_db_version"
		$this->delete_db_option('el_db_version');

		// Rename option "el_show_details_text" to "el_content_show_text"
		$this->rename_db_option('el_show_details_text', 'el_content_show_text');

		// Rename option "el_hide_details_text" to "el_content_hide_text"
		$this->rename_db_option('el_hide_details_text', 'el_content_hide_text');

		// Rename option "el_sync_cats" to "el_use_post_cats"
		$this->rename_db_option('el_sync_cats', 'el_use_post_cats');
	}


	private function upgrade_required($version) {
		// create version array
		$vers = explode('.', $version);
		// compare main version
		if($this->last_upgr_version[0] < $vers[0]) {
			return true;
		}
		// compare sub version
		elseif($this->last_upgr_version[0] === $vers[0] && $this->last_upgr_version[1] < $vers[1]) {
			return true;
		}
		// compare revision
		elseif($this->last_upgr_version[0] === $vers[0] && $this->last_upgr_version[1] === $vers[1] && $this->last_upgr_version[2] < $vers[2]) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Get a WordPress option directly from database with $wpdb
	 * @param string $option  Option name
	 * @return mixed|null     Value of given option name or null if option is not available
	 */
	private function get_db_option($option) {
		global $wpdb;
		$sql = 'SELECT `option_value` FROM `'.$wpdb->prefix.'options` WHERE `option_name` = "'.$option.'";';
		// use get_row instead of get_var to differenciate between not available and empty value
		$ret = $wpdb->get_row($sql, ARRAY_N);
		if(is_array($ret)) {
			return maybe_unserialize($ret[0]);
		}
		return null;
	}

	/**
	 * Update a WordPress option directly in the database with $wpdb
	 * @param string $option  Option name
	 * @param mixed  $value   Option value
	 * @param bool   $msg     Print logging messages?
	 * @return int|false      1..     if option was updated successfully
	 *                        0..     if option was available but value was already correct
	 *                        false.. on error
	 */
	private function update_db_option($option, $value, $msg=true) {
		global $wpdb;
		$ret = $wpdb->update(
			$wpdb->options,
			array('option_value' => $value),
			array('option_name' => $option),
			'%s'
		);
		if(0 < $ret) {
			$this->log('Updated option "'.$option.'" to value "'.$value.'"', $msg);
		}
		elseif(0 === $ret) {
			$this->log('Update of option "'.$option.'" is not required -> correct value "'.$value.'" is already set', $msg);
		}
		else {  // false === $ret
			$this->log('Updating option "'.$option.'" to value "'.$value.'" failed!', $msg, true);
		}
		return $ret;
	}

	/**
	 * Insert a WordPress option directly in the database with $wpdb
	 * @param string $option  Option name
	 * @param mixed  $value   Option value
	 * @param bool   $msg     Print logging messages?
	 * @return int|false      1..     if option was added successfully
	 *                        false.. on error
	 */
	private function insert_db_option($option, $value, $msg=true) {
		global $wpdb;
		if(is_null($this->get_db_option($option))) {
			$ret = $wpdb->insert(
				$wpdb->options,
				array('option_name' => $option, 'option_value' => $value),
				'%s'
			);
		}
		else {
			$this->log('Adding option "'.$option.'" with value "'.$value.'" failed! Option is already set.', $msg, true);
			return false;
		}
		if(false !== $ret) {
			$this->log('Added option "'.$option.'" with value "'.$value.'"', $msg);
		}
		else {
			$this->log('Adding option "'.$option.'" with value "'.$value.'" failed!', $msg, true);
		}
		return $ret;
	}

	/**
	 * Delete a WordPress option directly in the database with $wpdb
	 * @param string $option  Option name
	 * @param bool   $msg     Print logging messages?
	 * @return int|false      1..     if option was deleted successfully
	 *                        false.. on error
	 */
	private function delete_db_option($option, $msg=true) {
		global $wpdb;
		$ret = $wpdb->delete(
			$wpdb->options,
			array('option_name' => $option),
			'%s'
		);
		if(!empty($ret)) {
			$this->log('Deleted option "'.$option.'"', $msg);
		}
		else {
			$this->log('Deleting option "'.$option.'" failed!', $msg, true);
		}
		return $ret;
	}

	/**
	 * Rename a WordPress option directly in the database with $wpdb
	 *
	 * @param string $oldname  Old option name
	 * @param string $newname  New option name
	 * @param bool   $msg      Print logging messages?
	 * @return bool            true..  if option renaming was successfully
	 *                         false.. on error
	 */
	private function rename_db_option($oldname, $newname, $msg=true) {
		$value = $this->get_db_option($oldname);
		if(is_null($value)) {
			$this->log('Renaming of option "'.$oldname.'" to "'.$newname.'" is not required: old option name "'.$oldname.'" is not set -> use the default value', $msg);
			return true;
		}
		$newvalue = $this->get_db_option($newname);
		if(!is_null($newvalue)) {
			// update existing option
			$this->log('New option name "'.$newname.'" is already available');
			if($value !== $newvalue) {
				$ret = $this->update_db_option($newname, $value, $msg);
				if(false !== $ret) {
					$this->log('Updated value for existing new option name "'.$newname.'"', $msg);
				}
				else {
					$this->log('Updating value for existing new option name "'.$newname.'" failed!', $msg, true);
				}
			}
		}
		else {
			// insert new option
			$ret = $this->insert_db_option($newname, $value, false);
			if(false === $ret) {
				$this->log('Renaming of option "'.$oldname.'" failed during adding new option name "'.$newname.'" with the value "'.$value.'"!', $msg, true);
				return false;
			}
		}
		$ret = $this->delete_db_option($oldname, false);
		if(!empty($ret)) {
			$this->log('Renamed option "'.$oldname.'" to "'.$newname.'"', $msg);
		}
		else {
			$this->log('Renaming to "'.$newname.'" failed during deleting old option name "'.$oldname.'"!', $msg, true);
		}
		return (bool)$ret;
	}

	private function update_last_upgr_version() {
		$ret = $this->update_db_option('el_last_upgr_version', $this->actual_version);
		if(false === $ret) {
			$this->log('Could not update the "el_last_upgr_version"!', true, true);
		}
		return $ret;
	}

	private function log($text, $msg=true, $error=false) {
		if($msg) {
			$error_text = $error ? 'ERROR: ' : '';
			error_log('EL_UPGRADE: '.$error_text.$text);
			$this->msg[] = $error_text.$text;
		}
		if($error) {
			$this->error = true;
		}
	}
}

/** Function to unregister posttype before version 4.5
 **/
if(!function_exists('unregister_post_type')) {
	function unregister_post_type( $post_type ) {
		 global $wp_post_types;
		 if(isset($wp_post_types[$post_type])) {
			unset($wp_post_types[$post_type]);
			return true;
		 }
		 return false;
	}
}
?>
