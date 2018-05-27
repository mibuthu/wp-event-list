<?php
if(!defined('WP_ADMIN')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');
require_once(EL_PATH.'admin/includes/admin-functions.php');
require_once(EL_PATH.'includes/events.php');
// fix for PHP 5.2 (provide function date_create_from_format defined in daterange.php)
if(version_compare(PHP_VERSION, '5.3') < 0) {
	require_once(EL_PATH.'includes/daterange.php');
}

// This class handles all data for the admin new event page
class EL_Admin_Import {
	private static $instance;
	private $options;
	private $events_post_type;
	private $functions;
	private $events;
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
		$this->options = &EL_Options::get_instance();
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		$this->functions = &EL_Admin_Functions::get_instance();
		$this->events = &EL_Events::get_instance();
		$this->example_file_path = EL_URL.'files/events-import-example.csv';
		$this->add_metaboxes();
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
			$import_status = $this->import_events();
			$this->show_import_finished($import_status);
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
				<h3>'.__('Step','event-list').' 1: '.__('Set import file and options','event-list').'</h3>
				<form action="" id="el_import_upload" method="post" enctype="multipart/form-data">
					'.$this->functions->show_option_table('import').'<br />
					<input type="submit" name="button-upload-submit" id="button-upload-submit" class="button" value="'.sprintf(__('Proceed with Step %1$s','event-list'), '2').' &gt;&gt;" />
				</form>
				<br /><br />
				<h3>'.__('Example file','event-list').'</h4>
				<p>'.sprintf(__('You can download an example file %1$shere%2$s (CSV delimiter is a comma!)','event-list'), '<a href="'.$this->example_file_path.'">', '</a>').'</p>
				<p><em>'.__('Note','event-list').':</em> '.__('Do not change the column header and separator line (first two lines), otherwise the import will fail!','event-list').'</p>';
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
			echo __('The uploaded file does not have the required csv extension.','event-list').'</p>';
			return;
		}

		// save settings
		$this->save_import_settings();

		// parse file
		$import_data = $this->parse_import_file($file);

		// show heading
		echo '
			<h3>'.__('Step','event-list').' 2: '.__('Events review and additonal category selection','event-list').'</h3>';

		// show messages
		// failed parsing
		if(is_wp_error($import_data)) {
			echo '
				<div class="el-warning">'.__('Error','event-list').': '.__('This CSV file cannot be imported','event-list').':
					<p>'.$import_data->get_error_message().'</p>
				</div>';
			return;
		}

		// failed events
		$num_event_errors = count(array_filter($import_data, 'is_wp_error'));
		if(!empty($num_event_errors)) {
			if($num_event_errors == count($import_data)) {
				echo '
				<div class="el-warning">'.__('Error','event-list').': '.__('None of the events in this CSV file can be imported','event-list').':';
			}
			else {
				echo '
				<div class="el-warning">'.__('Warning','event-list').': '.sprintf(_n('There is %1$s event which cannot be imported',
				                                                                     'There are %1$s events which cannot be imported',
				                                                                     $num_event_errors,'event-list'), $num_event_errors).':';
			}
			echo '
					<ul class="el-event-errors">';
			foreach($import_data as $event) {
				if(is_wp_error($event)) {
					echo '<li>'.sprintf(__('CSV line %1$s','event-list'), $event->get_error_data()).': '.$event->get_error_message().'</li>';
				}
			}
			echo '</ul>';
			if($num_event_errors == count($import_data)) {
				echo '
				</div>';
				return;
			}
			echo '
					'.__('You can still import all other events listed below.','event-list').'
				</div>';
			$import_data = array_filter($import_data, create_function('$v', 'return !is_wp_error($v)'));
		}

		// missing categories
		$not_available_cats = array();
		foreach($import_data as $event) {
			if(is_wp_error($event)) {
				continue;
			}
			foreach($event['categories'] as $cat) {
				if(!$this->events->cat_exists($cat) && !in_array($cat, $not_available_cats)) {
					$not_available_cats[] = $cat;
				}
			}
		}
		if(!empty($not_available_cats)) {
			echo '
				<div class="el-warning">'.__('Warning','event-list').': '.__('The following category slugs are not available and will be removed from the imported events','event-list').':
					<ul class="el-categories">';
			foreach($not_available_cats as $cat) {
				echo '<li><code>'.$cat.'</code></li>';
			}
			echo '</ul>
					'.__('If you want to keep these categories, please create these Categories first and do the import afterwards.','event-list').'</div>';
		}
		// event form
		echo '
			<form method="POST" action="'.admin_url('edit.php?post_type=el_events&page=el_admin_import').'">';
		wp_nonce_field('autosavenonce', 'autosavenonce', false, false);
		wp_nonce_field('closedpostboxesnonce', 'closedpostboxesnonce', false, false);
		wp_nonce_field('meta-box-order-nonce', 'meta-box-order-nonce', false, false);
		echo '
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">';
		foreach($import_data as $event) {
			$this->show_event($event);
		}
		echo '
					</div>
					<div id="postbox-container-1" class="postbox-container">';
		do_meta_boxes('el-import', 'side', null);
		echo '
					</div>
				</div>
			</div>
			<input type="hidden" name="reviewed_events" id="reviewed_events" value="'.esc_html(json_encode($import_data)).'" />
			</form>';
	}

	private function show_import_finished($import_status) {
		echo '
			<h3>'.__('Step','event-list').' 3: '.__('Import result','event-list').'</h3>';
		if(empty($import_status['errors'])) {
			echo '
				<div class="el-success">'.sprintf(__('Import of %1$s events successful!','event-list'), $import_status['success']).'
				<a href="'.admin_url('edit.php?post_type=el_events').'">'.__('Go back to All Events','event-list').'</a>';
		}
		else {
			echo '
					<div class="el-warning">'.__('Errors during Import','event-list').':';
			if(is_wp_error($import_status['errors'])) {
				echo '
					<p>'.$import_errors->get_error_message().'</p>';
			}
			else {
				echo '
					<ul class="el-event-errors">';
				foreach($import_status['errors'] as $error) {
					echo '<li>'.__('Event from CSV-line','event-list').' '.$error->get_error_data().': '.$error->get_error_message().'</li>';
					}
				}
			echo '</ul>
				</div>';
		}
	}

	private function show_event($event) {
		echo '
				<p>
				<span class="el-event-header">'.__('Title','event-list').':</span> <span class="el-event-data">'.$event['title'].'</span><br />
				<span class="el-event-header">'.__('Start Date','event-list').':</span> <span class="el-event-data">'.$event['startdate'].'</span><br />
				<span class="el-event-header">'.__('End Date','event-list').':</span> <span class="el-event-data">'.$event['enddate'].'</span><br />
				<span class="el-event-header">'.__('Time','event-list').':</span> <span class="el-event-data">'.$event['starttime'].'</span><br />
				<span class="el-event-header">'.__('Location','event-list').':</span> <span class="el-event-data">'.$event['location'].'</span><br />
				<span class="el-event-header">'.__('Content','event-list').':</span> <span class="el-event-data">'.$event['content'].'</span><br />
				<span class="el-event-header">'.__('Category slugs','event-list').':</span> <span class="el-event-data">'.implode(', ', $event['categories']).'</span>
				</p>';
	}

	/**
	 * @return WP_Error
	 */
	private function parse_import_file($file) {
		$delimiter = ',';
		$header = array('title', 'startdate', 'enddate', 'starttime', 'location', 'content', 'category_slugs');
		$separator_line = 'sep=,';

		// list of events to import
		$events = array();

		$file_handle = fopen($file, 'r');
		$event_lines = -1;
		$empty_lines = 0;
		while(!feof($file_handle)) {
			// get line
			$line = fgetcsv($file_handle, 0, $delimiter);
			// prepare line: trim elements and force an array
			$line = is_array($line) ? array_map('trim', $line) : array(trim($line));

			// skip empty lines
			if(!array_filter($line)) {
				$empty_lines += 1;
				continue;
			}
			// check header
			if(0 > $event_lines) {
				// check optional separator line
				if($line[0] === $separator_line) {
					$empty_lines += 1;
					continue;
				}
				// check header line
				elseif($line === $header || $line === array_slice($header,0,-1)) {
					$event_lines += 1;
					continue;
				}
				else {
					return new WP_Error('missing_header', __('Header line is missing or not correct!','event-list').'<br />'
						.sprintf(__('Have a look at the %1$sexample file%2$s to see the correct header line format.','event-list'), '<a href="'.$this->example_file_path.'">', '</a>'));
				}
			}
			$event_lines += 1;
			// check correct number of items in line
			if(6 > count($line) || 7 < count($line)) {
				$events[] = new WP_Error('wrong_number_line_items', sprintf(__('Wrong number of items in line (%1$s items found, 6-7 required)','event-list'), count($line)), $event_lines+$empty_Lines+1);
				continue;
			}
			// check and prepare event data
			$eventdata = array(
				'csv_line'   => $event_lines+$empty_lines+1,
				'title'      => $line[0],
				'startdate'  => $line[1],
				'enddate'    => $line[2],
				'starttime'  => $line[3],
				'location'   => $line[4],
				'content'    => $line[5],
				'categories' => isset($line[6]) ? explode('|', $line[6]) : array(),
			);
			$event = $this->prepare_event($eventdata, $this->options->get('el_import_date_format'));
			// add event
			$events[] = $event;
		}
		//close file
		fclose($file_handle);
		return $events;
	}

	private function prepare_event($event, $date_format=false) {
		// trim all fields
		array_walk($event, create_function('&$v', '$v = is_array($v) ? array_map("trim", $v) : trim($v);'));
		// title
		if(empty($event['title'])) {
			$event = new WP_Error('empty_title', __('Empty event title found','event-list'), $event['csv_line']);
			return $event;
		}
		// startdate
		$event['startdate'] = $this->prepare_date($event['startdate'], $date_format);
		if(false === $event['startdate']) {
			return new WP_Error('wrong_startdate', __('Wrong date format for startdate','event-list'), $event['csv_line']);
		}
		// enddate
		if(empty($event['enddate'])) {
			$event['enddate'] = $event['startdate'];
		}
		else {
			$event['enddate'] = $this->prepare_date($event['enddate'], $date_format);
			if(false === $event['enddate']) {
				return new WP_Error('wrong_enddate', __('Wrong date format for enddate','event-list'), $event['csv_line']);
			}
		}
		// no additional checks for starttime, location, content required
		// categories
		$event['categories'] = array_map('trim', $event['categories']);
		return $event;
	}

	private function prepare_date($date_string, $date_format) {
		$auto_detect = true;
		if(empty($date_format)) {
			$date_format = 'Y-m-d';
			$auto_detect = false;
		}
		// create date from given format
		$date = date_create_from_format($date_format, $date_string);
		if(!$date instanceof DateTime) {
			// try automatic date detection
			if($auto_detect) {
				$date = date_create($date_string);
			}
			if(!$date instanceof DateTime) {
				return false;
			}
		}
		return $date->format('Y-m-d');
	}

	private function save_import_settings() {
		foreach($this->options->options as $oname => $o) {
			// check used post parameters
			$ovalue = isset($_POST[$oname]) ? sanitize_text_field($_POST[$oname]) : '';

			if('import' == $o['section'] && !empty($ovalue)) {
				$this->options->set($oname, $ovalue);
			}
		}
	}

	public function add_metaboxes() {
		add_meta_box('event-publish', __('Import events','event-list'), array(&$this, 'render_publish_metabox'), 'el-import', 'side');
		add_meta_box('event-categories', __('Add additional categories','event-list'), array(&$this, 'render_category_metabox'),'el-import', 'side');
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
		require_once(ABSPATH.'wp-admin/includes/meta-boxes.php');
		$dpost = get_default_post_to_edit('el-events');
		$box = array('args' => array('taxonomy' => $this->events_post_type->taxonomy));
		post_categories_meta_box($dpost, $box);
	}

	private function import_events() {
		// check used post parameters
		$reviewed_events = json_decode(stripslashes($_POST['reviewed_events']), true);
		if(empty($reviewed_events)) {
			return new WP_Error('no_events', __('No events found','event-list'));
		}
		// prepare additional categories
		if($this->events_post_type->event_cat_taxonomy === $this->events_post_type->taxonomy) {
			$additional_cat_ids = isset($_POST['tax_input'][$this->events_post_type->taxonomy]) ? $_POST['tax_input'][$this->events_post_type->taxonomy] : array();
		}
		else {
			$additional_cat_ids = isset($_POST['post_'.$this->events_post_type->taxonomy]) ? $_POST['post_'.$this->events_post_type->taxonomy] : array();
		}
		$additional_cat_ids = is_array($additional_cat_ids) ? array_map('intval', $additional_cat_ids) : array();
		$additional_cat_slugs = array();
		foreach($additional_cat_ids as $cat_id) {
			$cat = $this->events->get_cat_by_id($cat_id);
			if(!empty($cat)) {
				$additional_cat_slugs[] = $cat->slug;
			}
		}
		// prepare events and events categories
		foreach($reviewed_events as &$event_ref) {
			// check event data
			// remove not available categories of import file
			foreach($event_ref['categories'] as $ckey => $cat_slug) {
				if(!$this->events->cat_exists($cat_slug)) {
					unset($event_ref['categories'][$ckey]);
				}
			}
			// add the additionally specified categories to the event
			if(!empty($additional_cat_slugs)) {
				$event_ref['categories'] = array_unique(array_merge($event_ref['categories'], $additional_cat_slugs));
			}
		}
		// save events
		$ret = array('success' => 0, 'errors' => array());
		require_once(EL_PATH.'includes/event.php');
		foreach($reviewed_events as $eventdata) {
			$ed = $this->prepare_event($eventdata);
			if(is_wp_error($ed)) {
				$ret['errors'][] = $ed;
				continue;
			}
			//TODO: return WP_Error instead of false in EL_Event when safing fails
			$event = EL_Event::save($eventdata);
			if(!$event) {
				$ret['errors'][] = new WP_Error('failed_saving', __('Saving of event failed!','event-list'), $event['csv_line']);
				continue;
			}
			$ret['success'] += 1;
		}
		return $ret;
	}

	public function embed_import_scripts() {
		wp_enqueue_style('eventlist_admin_import', EL_URL.'admin/css/admin_import.css');
	}
}
?>
