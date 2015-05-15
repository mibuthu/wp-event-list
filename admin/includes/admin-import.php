<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/categories.php');

// This class handles all data for the admin new event page
class EL_Admin_Import {
	private static $instance;
	private $db;
	private $categories;
	private $import_data;
	private $example_file_path;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = & EL_Db::get_instance();
		$this->categories = & EL_Categories::get_instance();
		$this->example_file_path = EL_URL.'/files/events-import-example.csv';
	}

	public function show_import() {
		if(!current_user_can('edit_posts')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		echo '
			<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div>
				<h2>'.__('Import Events','event-list').'</h2>';
		// Review import
		if(isset($_FILES['el_import_file'])) {
			$this->show_import_review();
		}
		// Finish import (add events)
		elseif(isset($_POST['reviewed_events'])) {
			$import_error = $this->import_events();
			$this->show_import_finished($import_error);
		}
		// Import form
		else {
			$this->show_import_form();
		}
		echo '
			</div>';
	}

	private function show_import_form() {
		echo '
				<form action="" id="el_import_upload" method="post" enctype="multipart/form-data">
					<p>'.__('Select a file that contains event data.','event-list').'</p>
					<p><input name="el_import_file" type="file" size="50" maxlength="100000"></p>
					<input type="submit" name="button-upload-submit" id="button-upload-submit" class="button" value="'.__('Import Event Data','event-list').'" />
					<br /><br />
				</form>
				<h3>'.__('Example file','event-list').'</h3>
				<p>'.sprintf(__('You can find an example file %1$shere%2$s (CSV delimiter is a comma!)','event-list'), '<a href="'.$this->example_file_path.'">', '</a>').'<br />
				'.__('Note','event-list').': <em>'.__('Do not change the column header and separator line (first two lines), otherwise the import will fail!','event-list').'</em></p>';
	}

	private function show_import_review() {
		$file = $_FILES['el_import_file']['tmp_name'];
		// check for file existence (upload failed?)
		if(!is_file($file)) {
			echo '<h3>'.__('Sorry, there has been an error.','event-list').'</h3>';
			echo __('The file does not exist, please try again.','event-list').'</p>';
			return;
		}

		// check for file extension (csv) first
		$file_parts = pathinfo($_FILES['el_import_file']['name']);
		if($file_parts['extension'] !== "csv") {
			echo '<h3>'.__('Sorry, there has been an error.','event-list').'</h3>';
			echo __('The file is not a CSV file.','event-list').'</p>';
			return;
		}

		// parse file
		$import_data = $this->parseImportFile($file);

		// parsing failed?
		if(is_wp_error($import_data)) {
			echo '<h3>'.__('Sorry, there has been an error.','event-list').'</h3>';
			echo '<p>' . esc_html($import_data->get_error_message()).'</p>';
			return;
		}

		// TODO: $this->import_data vs. $import_data ?
		$this->import_data = $import_data;
		$serialized = serialize($this->import_data);

		echo '
			<h3>'.__('Please review the events to import and choose categories before importing.','event-list').'</h3>
			<form method="POST" action="?page=el_admin_main&action=import">';
		wp_nonce_field('autosavenonce', 'autosavenonce', false, false);
		wp_nonce_field('closedpostboxesnonce', 'closedpostboxesnonce', false, false);
		wp_nonce_field('meta-box-order-nonce', 'meta-box-order-nonce', false, false);
		echo '
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">';
		foreach($this->import_data as $event) {
			$this->show_event($event);
		}
		echo '
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables ui-sortable">';
		add_meta_box('event-categories', __('Categories'), array(&$this, 'render_category_metabox'),'event-list', 'advanced', 'default', null);
		add_meta_box('event-publish', __('Import','event-list'), array(&$this, 'render_publish_metabox'), 'event-list');
		do_meta_boxes('event-list', 'advanced', null);
		echo '
						</div>
					</div>
				</div>
			</div>
			<input type="hidden" name="reviewed_events" id="reviewed_events" value="'.esc_html($serialized).'" />
			</form>';
	}

	private function show_import_finished($with_error) {
		if(!$with_error) {
			echo '
				<h3>'.__('Import with errors!','event-list').'</h3>
				'.sprintf(__('An error occurred during import! Please send your import file to %1$sthe administrator%2$s for analysis.','event-list'), '<a href="mailto:'.get_option('admin_email').'">', '</a>');
		}
		else {
			echo '
				<h3>'.__('Import successful!','event-list').'</h3>
				<a href="?page=el_admin_main">'.__('Go back to All Events','event-list').'</a>';
		}
	}

	private function show_event($event) {
		echo '
				<p>
				<span style="font-weight: bold;">'.__('Title','event-list').':</span> <span style="font-style: italic;">'.$event['title'].'</span><br />
				<span style="font-weight: bold;">'.__('Start Date','event-list').':</span> <span style="font-style: italic;">'.$event['start_date'].'</span><br />
				<span style="font-weight: bold;">'.__('End Date','event-list').':</span> <span style="font-style: italic;">'.$event['end_date'].'</span><br />
				<span style="font-weight: bold;">'.__('Time','event-list').':</span> <span style="font-style: italic;">'.$event['time'].'</span><br />
				<span style="font-weight: bold;">'.__('Location','event-list').':</span> <span style="font-style: italic;">'.$event['location'].'</span><br />
				<span style="font-weight: bold;">'.__('Details','event-list').':</span> <span style="font-style: italic;">'.$event['details'].'</span>
				</p>';
	}

	/**
	 * @return WP_Error
	 */
	private function parseImportFile($file) {
		$delimiter = ',';
		$header = array('title', 'start date', 'end date', 'time', 'location', 'details');
		$separator = array('sep=,');

		// list of events to import
		$events = array();

		$file_handle = fopen($file, 'r');
		$lineNum = 0;
		while(!feof($file_handle)) {
			$line = fgetcsv($file_handle, 1024);

			// skip empty line
			if(empty($line)) {
				continue;
			}
			if($lineNum === 0) {
				if($line === $separator) {
					continue;
				}
				if($line === $header) {
					$lineNum += 1;
					continue;
				}
				else {
					var_dump($line);
					var_dump($header);
					return new WP_Error('CSV_parse_error', __('There was an error when reading this CSV file.','event-list'));
				}
			}
			$events[] = array(
				'title'      => $line[0],
				'start_date' => $line[1],
				'end_date'   => !empty($line[2]) ? $line[2] : $line[1],
				'time'       => $line[3],
				'location'   => $line[4],
				'details'    => $line[5],
			);
			$lineNum += 1;
		}
		//close file
		fclose($file_handle);
		return $events;
	}

	public function render_publish_metabox() {
		echo '
			<div class="submitbox">
				<div id="delete-action"><a href="?page=el_admin_main" class="submitdelete deletion">'.__('Cancel').'</a></div>
				<div id="publishing-action"><input type="submit" class="button button-primary button-large" name="import" value="'.__('Import','event-list').'" id="import"></div>
				<div class="clear"></div>
			</div>';
	}

	public function render_category_metabox($post, $metabox) {
		echo '
			<div id="taxonomy-category" class="categorydiv">
			<div id="category-all" class="tabs-panel">';
		$cat_array = $this->categories->get_cat_array('name', 'asc');
		if(empty($cat_array)) {
			echo __('No categories available.');
		}
		else {
			echo '
				<ul id="categorychecklist" class="categorychecklist form-no-clear">';
			$level = 0;
			$event_cats = explode('|', substr($metabox['args']['event_cats'], 1, -1));
			foreach($cat_array as $cat) {
				if($cat['level'] > $level) {
					//new sub level
					echo '
						<ul class="children">';
					$level++;
				}
				while($cat['level'] < $level) {
					// finish sub level
					echo '
						</ul>';
					$level--;
				}
				$level = $cat['level'];
				$checked = in_array($cat['slug'], $event_cats) ? 'checked="checked" ' : '';
				echo '
						<li id="'.$cat['slug'].'" class="popular-catergory">
							<label class="selectit">
								<input value="'.$cat['slug'].'" type="checkbox" name="categories[]" id="categories" '.$checked.'/> '.$cat['name'].'
							</label>
						</li>';
			}
			echo '
					</ul>';
		}
		echo '
				</div>
			</div>';
	}

	private function import_events() {
		$reviewed_events = unserialize(stripslashes($_POST['reviewed_events']));
		$categories = isset($_POST['categories']) ? $_POST['categories'] : '';
		if(isset($categories)) {
			foreach($reviewed_events as &$event) {
				$event['categories'] = $categories;
			}
		}
		$returnValues = array();
		foreach($reviewed_events as &$event) {
			// convert date format to be SQL-friendly
			$myDateTime = DateTime::createFromFormat('d.m.Y', $event['start_date']);
			$event['start_date'] = $myDateTime->format('Y-m-d');
			$myDateTime = DateTime::createFromFormat('d.m.Y', $event['end_date']);
			$event['end_date'] = $myDateTime->format('Y-m-d');
			$returnValues[] = $this->db->update_event($event);
		}
		return $returnValues;
	}
}
?>
