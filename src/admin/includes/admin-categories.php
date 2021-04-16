<?php
if(!defined('WP_ADMIN')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');

// This class handles all data for the admin categories page
class EL_Admin_Categories {
	private static $instance;
	private $events_post_type;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		add_action('admin_print_scripts', array(&$this, 'embed_categories_scripts'));
		add_action('after-'.$this->events_post_type->taxonomy.'-table', array(&$this, 'sync_cats_button'));
		add_filter('term_updated_messages', array(&$this, 'prepare_syncdone_message'));
		add_filter('removable_query_args', array(&$this, 'remove_message_args'));
	}

	public function embed_categories_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('eventlist_admin_categories_js', EL_URL.'admin/js/admin_categories.js');
	}

	public function sync_cats_button() {
		$url = esc_url(admin_url(add_query_arg(array('page' => 'el_admin_cat_sync', '_wp_http_referer' => $_SERVER['REQUEST_URI']), 'edit.php?post_type=el_events')));
		echo '<button type="button" id="sync-cats" class="button action" onclick="el_show_syncform(\''.$url.'\')" style="margin-top: 3px">'.__('Synchronize with post categories', 'event-list').'</button>';
	}

	public function prepare_syncdone_message($messages) {
		// prepare used get parameters
		$msgdata = isset($_GET['msgdata']) ? $_GET['msgdata'] : array();
		$error = isset($_GET['error']);

		$items['mod_ok'] = __('%1$s categories modified (%2$s)','event-list');
		$items['add_ok'] = __('%1$s categories added (%2$s)','event-list');
		$items['del_ok'] = __('%1$s categories deleted (%2$s)','event-list');
		if($error) {
			$items['mod_error'] = __('%1$s categories not modified (%2$s)','event-list');
			$items['add_error'] = __('%1$s categories not added (%2$s)','event-list');
			$items['del_error'] = __('%1$s categories not deleted (%2$s)','event-list');
		}
		if($error) {
			$msgtext = __('An Error occured during the category sync','event-list').':<br />';
			$msgnum = 22;
		}
		else {
			$msgtext = __('Category sync finished','event-list').':<br />';
			$msgnum = 21;
		}
		$msgtext .= '<ul style="list-style:inside">';
		foreach($items as $name => $text) {
			if(isset($msgdata[$name]) && is_array($msgdata[$name])) {
				$items = array_map('sanitize_key', $msgdata[$name]);
				$msgtext .= $this->show_sync_items($items, $text);
			}
		}
		$msgtext .= '</ul>';
		$messages['_item'][$msgnum] = $msgtext;
		return $messages;
	}

	private function show_sync_items($items, $text) {
		if(!empty($items)) {
			return '
				<li>'.sprintf($text, '<strong>'.count($items).'</strong>', implode(', ', $items)).'</li>';
		}
		return '';
	}

	public function remove_message_args($args) {
		array_push($args, 'msgdata');
		return $args;
	}
}
?>
