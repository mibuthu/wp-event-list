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
	private $max_exec_time;
	private $upgrade_starttime;
	private $resume_version;
	private $upgr_action_status = array();
	private $error = false;
	private $logfile_handle = false;
	public $logfile = '';

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->logfile = EL_PATH.'upgrade.log';
	}

	public function upgrade() {
		// check required get parameters
		$this->resume_version = isset($_GET['resume-el-upgr']) ? str_replace('-', '.', sanitize_title($_GET['resume-el-upgr'])) : false;
		$this->set_max_exec_time();
		$this->upgrade_starttime = time();
		// check upgrade trigger to avoid duplicate updates
		if(empty($this->resume_version) && $this->upgrade_starttime <= get_option('el_upgr_in_progress') + $this->max_exec_time + 5) {
			$this->log('Upgrade is already running', false);
			return false;
		}
		// set upgrade trigger
		$this->update_option('el_upgr_in_progress', $this->upgrade_starttime, false);
		// do upgrade
		if(!$this->init()) {
			return false;
		}
		$this->upgrade_check();
		// delete upgrade action status
		$this->delete_upgr_action_status();
		// delete upgrade trigger
		$this->delete_option('el_upgr_in_progress', false);
		// close logfile
		$this->logfile_close();
		// redirect
		$this->redirect(array('el-upgr-finished'=>($this->error ? 2 : 1)), array('resume-el-upgr'));
	}

	/**
	 * Preparations for the upgrade check
	 */
	private function init() {
		// enable wbdb error logging
		global $wpdb;
		$wpdb->show_errors();
		// init logfile
		$this->logfile_init();
		// get actual plugin version
		$filedata = get_file_data(EL_PATH.'event-list.php', array('version'=>'Version'));
		$this->actual_version = $filedata['version'];
		// check last upgrade version
		$this->last_upgr_version = get_option('el_last_upgr_version');
		// fix for older version < 0.8.0
		if(empty($this->last_upgr_version) && false !== get_option('el_db_version')) {
			$this->last_upgr_version = '0.7.0';
			$this->add_option('el_last_upgr_version', $this->last_upgr_version, false);
			$this->log('Applied fix for versions < 0.8.0', false);
		}
		// return if last_upgr_version is empty (new install --> no upgrade required)
		if(empty($this->last_upgr_version)) {
			$this->add_option('el_last_upgr_version', $this->actual_version, false);
			flush_rewrite_rules();
			$this->log('New install -> no upgrade required', false);
			return false;
		}
		// show upgrade message
		echo 'Event list plugin upgrade in progress &hellip;<br />Please be patience until this process is finished.<br />'.
		flush();
		return true;
	}

	/**
	 * Do the upgrade check and start the required upgrades
	 */
	private function upgrade_check() {
		$this->log('Start upgrade check', false);
		if($this->upgrade_required('0.8.0')) {
			$this->upgrade_to_0_8_0();
		}

		// update last_upgr_version
		$this->update_last_upgr_version();
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

		$version = '0.8.0';
		// Correct events post type
		require_once(EL_PATH.'includes/events_post_type.php');
		$events_post_type = EL_Events_Post_Type::get_instance();
		// set correct taxonomy
		$events_post_type->use_post_categories = false !== get_option('el_sync_cats');
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
			$cats_array = get_option('el_categories');
			if(!empty($cats_array)) {
				$action = 'el_category_upgr_0_8_0';
				if(!$this->is_action_completed($action)) {
					foreach($cats_array as $cat) {
						if($this->is_action_item_completed($action, $cat['slug'])) {
							continue;
						}
						// check if the event category is already available
						if(EL_Events::get_instance()->cat_exists($cat['slug'])) {
							$this->log('Event category "'.$cat['name'].'" is already available, import skipped!');
							$this->complete_action_item($version, $action, $cat['slug']);
							continue;
						}
						// import event category
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
						$this->complete_action_item($version, $action, $cat['slug']);
					}
					$this->complete_action($action);
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
			$action = 'el_events_upgr_0_8_0';
			if(!$this->is_action_completed($action)) {
				foreach($events as $event) {
					if($this->is_action_item_completed($action, $event['id'])) {
						continue;
					}
					// check if the event is already available
					$sql = 'SELECT ID FROM (SELECT * FROM (SELECT DISTINCT ID, post_title, post_date, '.
						'(SELECT meta_value FROM wp_postmeta WHERE wp_postmeta.meta_key = "startdate" AND wp_postmeta.post_id = wp_posts.ID) AS startdate, '.
						'(SELECT meta_value FROM wp_postmeta WHERE wp_postmeta.meta_key = "enddate" AND wp_postmeta.post_id = wp_posts.ID) AS enddate, '.
						'(SELECT meta_value FROM wp_postmeta WHERE wp_postmeta.meta_key = "starttime" AND wp_postmeta.post_id = wp_posts.ID) AS starttime, '.
						'(SELECT meta_value FROM wp_postmeta WHERE wp_postmeta.meta_key = "location" AND wp_postmeta.post_id = wp_posts.ID) AS location '.
						'FROM wp_posts WHERE post_type = "el_events") AS events) AS events '.
						'WHERE ('.
						'post_title="'.wp_kses_post($event['title']).'" AND '.
						'post_date="'.$event['pub_date'].'" AND '.
						'startdate="'.$event['start_date'].'" AND '.
						'enddate="'.$event['end_date'].'" AND '.
						'starttime = "'.wp_kses_post(EL_Event::validate_time($event['time'])).'" AND '.
						'location = "'.wp_kses_post($event['location']).'")';
					$ret = $wpdb->get_row($sql, ARRAY_N);
					if(is_array($ret)) {
						$this->log('Event "'.$event['title'].'" is already available, import skipped!');
						$this->complete_action_item($version, $action, $event['id']);
						continue;
					}
					// import event
					$eventdata['title'] = $event['title'];
					$eventdata['startdate'] = $event['start_date'];
					$eventdata['enddate'] = $event['end_date'];
					$eventdata['starttime'] = $event['time'];
					$eventdata['location'] = $event['location'];
					$eventdata['content'] = $event['details'];
					$eventdata['post_date'] = $event['pub_date'];
					$eventdata['post_user'] = $event['pub_user'];
					$eventdata['categories'] = explode('|', substr($event['categories'], 1, -1));
					$ret = EL_Event::save($eventdata);
					if(empty($ret)) {
						$this->log('Import of event "'.$eventdata['title'].'" failed!', true, true);
					}
					else {
						$this->log('Event "'.$eventdata['title'].'" successfully imported');
					}
					$this->complete_action_item($version, $action, $event['id']);
				}
			$this->complete_action($action);
			}
		}
		else {
			$this->log('No existing events found');
		}

		// Delete obsolete option "el_db_version"
		$this->delete_option('el_db_version');

		// Rename option "el_show_details_text" to "el_content_show_text"
		$this->rename_option('el_show_details_text', 'el_content_show_text');

		// Rename option "el_hide_details_text" to "el_content_hide_text"
		$this->rename_option('el_hide_details_text', 'el_content_hide_text');

		// Rename option "el_sync_cats" to "el_use_post_cats"
		$this->rename_option('el_sync_cats', 'el_use_post_cats');
	}

	private function upgrade_required($version) {
		if(version_compare($this->last_upgr_version, $version) < 0 || $this->resume_version === $version) {
			return true;
		}
		else {
			return false;
		}
	}

	private function set_max_exec_time() {
		$this->max_exec_time = ini_get('max_execution_time');
		if(empty($this->max_exec_time)) {
			$this->max_exec_time = 25;
		}
		$this->log('Maximum script execution time: '.$this->max_exec_time.' seconds', false);
	}

	private function is_action_completed($action) {
		$this->upgr_action_status[$action] = array();
		$status = get_option($action);
		if('completed' === $status) {
			return true;
		}
		if(!empty($status)) {
			$this->upgr_action_status[$action] = explode(',', $status);
		}
		return false;
	}

	private function is_action_item_completed($action, $id) {
		return in_array($id, $this->upgr_action_status[$action]);
	}

	private function complete_action($action) {
		$this->update_option($action, 'completed', false);
		$this->upgr_action_status[$action] = array();
	}

	private function complete_action_item($upgr_version, $action, $id) {
		$this->upgr_action_status[$action][] = $id;
		// save status to db from time to time
		if(0 === count($this->upgr_action_status[$action]) % 25) {
			$this->update_option($action, implode(',', $this->upgr_action_status[$action]), null);
		}
		// if max execution time is nearly reached, save the actual status to db and redirect
		// the upgrade will be resumed after the reload with a new set of execution time
		if($this->max_exec_time - 5 <= time() - $this->upgrade_starttime) {
			$this->update_option($action, implode(',', $this->upgr_action_status[$action]), false);
			$this->log('The maximum execution time is already consumed, script will redirect and continue upgrade afterwards with a new set of time.');
			// close logfile
			$this->logfile_close();
			// redirect
			$this->redirect(array('resume-el-upgr' => $upgr_version));
		}
	}

	private function delete_upgr_action_status() {
		foreach($this->upgr_action_status as $action=>$status) {
			$this->delete_option($action, false);
		}
	}

	private function redirect($args_to_add=array(), $args_to_remove=array()) {
		$url = add_query_arg($args_to_add, remove_query_arg($args_to_remove));
		echo '<meta http-equiv="refresh" content="0; url='.$url.'">';
		die();
	}

	/**
	 * Wrapper for update_option function with additional error checking and log handling
	 * @param string $option  Option name
	 * @param mixed  $value   Option value
	 * @param bool   $msg     Print logging messages?
	 * @return int|false      2..     if option was not available and added successfully
	 *                        1..     if option was updated successfully
	 *                        0..     if option was available but value was already correct
	 *                        false.. on error
	 */
	private function update_option($option, $value, $msg=true) {
		$oldvalue = get_option($option, null);
		// add option, if option does not exist
		if(is_null($oldvalue)) {
			$ret = $this->add_option($option, $value, $msg);
			return $ret ? 2 : false;
		}
		// do nothing, if correct value is already set
		if($value === $oldvalue) {
			$this->log('Update of option "'.$option.'" is not required: correct value "'.$value.'" already set', $msg);
			return 0;
		}
		// update option
		$ret = update_option($option, $value);
		if($ret) {
			$this->log('Updated option "'.$option.'" to value "'.$value.'"', $msg);
		}
		else {
			$this->log('Updating option "'.$option.'" to value "'.$value.'" failed!', $msg, true);
		}
		return $ret;
	}

	/**
	 * Wrapper for add_option function with additional error checking and log handling
	 * @param string $option  Option name
	 * @param mixed  $value   Option value
	 * @param bool   $msg     Print logging messages?
	 * @return int|false      true .. if option was added successfully
	 *                        false.. on error
	 */
	private function add_option($option, $value, $msg=true) {
		if(!is_null(get_option($option, null))) {
			$this->log('Adding option "'.$option.'" with value "'.$value.'" failed: Option already exists!', $msg, true);
			return false;
		}
		$ret = add_option($option, $value);
		if($ret) {
			$this->log('Added option "'.$option.'" with value "'.$value.'"', $msg);
		}
		else {
			$this->log('Adding option "'.$option.'" with value "'.$value.'" failed!', $msg, true);
		}
		return $ret;
	}

	/**
	 * Wrapper for delete_option function with additional error checking and log handling
	 * @param string $option  Option name
	 * @param bool   $msg     Print logging messages?
	 * @return int|null|false 1..     if option was deleted successfully
	 *                        null..  if option is already not set
	 *                        false.. on error
	 */
	private function delete_option($option, $msg=true) {
		global $wpdb;
		if(is_null(get_option($option, null))) {
			$this->log('Deleting option "'.$option.'" is not required: option is not set', $msg);
			return null;
		}
		$ret = delete_option($option);
		if($ret) {
			$this->log('Deleted option "'.$option.'"', $msg);
		}
		else {
			$this->log('Deleting option "'.$option.'" failed!', $msg, true);
		}
		return $ret;
	}

	/**
	 * Rename an option (create new option name with old option name value, then delete old option name)
	 *
	 * @param string $oldname  Old option name
	 * @param string $newname  New option name
	 * @param bool   $msg      Print logging messages?
	 * @return bool            true..  if option renaming was successfully
	 *                         false.. on error
	 */
	private function rename_option($oldname, $newname, $msg=true) {
		$value = get_option($oldname, null);
		if(is_null($value)) {
			$this->log('Renaming of option "'.$oldname.'" to "'.$newname.'" is not required: old option name "'.$oldname.'" is not set (default value is used)', $msg);
			return true;
		}
		$newvalue = get_option($newname, null);
		if(!is_null($newvalue)) {
			// update existing option
			$this->log('New option name "'.$newname.'" is already available', $msg);
			if($value !== $newvalue) {
				$ret = $this->update_option($newname, $value, $msg);
				if(false !== $ret) {
					$this->log('Updated value for existing new option name "'.$newname.'"', $msg);
				}
				else {
					$this->log('Updating value for existing new option name "'.$newname.'" failed!', $msg, true);
				}
			}
			else {
				$this->log('Correct value "'.$value.'"is already set', $msg);
			}
		}
		else {
			// insert new option
			$ret = $this->add_option($newname, $value, false);
			if(false === $ret) {
				$this->log('Renaming of option "'.$oldname.'" failed during adding new option name "'.$newname.'" with the value "'.$value.'"!', $msg, true);
				return false;
			}
		}
		$ret = $this->delete_option($oldname, false);
		if(!empty($ret)) {
			$this->log('Deleted old option name "'.$oldname.'"', $msg);
		}
		else {
			$this->log('Deleting of old option name "'.$oldname.'" failed!', $msg, true);
		}
		return (bool)$ret;
	}

	private function update_last_upgr_version() {
		$ret = $this->update_option('el_last_upgr_version', $this->actual_version);
		if(false === $ret) {
			$this->log('Could not update the "el_last_upgr_version"!', true, true);
		}
		return $ret;
	}

	private function logfile_init() {
		// rename all existing log files and remove files older than 90 days
		if(file_exists($this->logfile) && empty($this->resume_version)) {
			// delete file if it is too old
			if(filemtime($this->logfile) < time() - 30*24*60*60) {
				if(!@unlink($this->logfile)) {
					error_log('"'.$this->logfile.'" cannot be deleted! No upgrade log file will be written!');
					return false;
				}
			}
		}
		// open logfile for writing
		$this->logfile_handle = @fopen($this->logfile, 'a');
		if(empty($this->logfile_handle)) {
			error_log('"'.$this->logfile.'" cannot be opened for writing! No upgrade log file will be written!');
			return false;
		}
		return true;
	}

	private function logfile_close() {
		if(!empty($this->logfile_handle)) {
			fclose($this->logfile_handle);
		}
	}

	/** Log function
	 *  This function prints the error messages to the log file, prepares the text for the admin ui message
	 *  and sets the error flag (required for the admin ui message)
	 *
	 *  @param string    $text   Message text
	 *  @param bool|null $msg    Print error message:
	 *                           null:  don't print message to debug log or upgrade log file
	 *                           false: only print message to debug log
	 *                           true:  print message to log and upgrade log file
	 *  @return null
	 */
	private function log($text, $msg=true, $error=false) {
		$error_text = '';
		if(!is_null($msg)) {
			if($error) {
				$this->error = true;
				$error_text = 'ERROR: ';
			}
			error_log('EL_UPGRADE: '.$error_text.$text);
		}
		if($this->logfile_handle && $msg) {
			$time = date('[Y-m-d H:i:s] ', time());
			fwrite($this->logfile_handle, $time.$error_text.$text.PHP_EOL);
		}
	}
}

/** Function to unregister posttype before WordPress version 4.5
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
