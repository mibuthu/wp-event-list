<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');
require_once(EL_PATH.'includes/events.php');

// This class handles all data for the admin categories page
class EL_Admin_Category_Sync {
	private static $instance;
	private $options;
	private $events_post_type;
	private $events;

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
		$this->events = EL_Events::get_instance();

		// permission checks
		if(empty(wp_get_referer()) || '1' === $this->options->get('el_use_post_cats')) {
			wp_die(__('Error: You are not allowed to view this page!','event-list'));
		}
		if(!current_user_can('manage_categories')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
	}

	public function show_cat_sync() {
		// determine categories to add and to delete
		$post_cats = get_categories(array('type'=>'post', 'orderby'=>'parent', 'hide_empty'=>0));
		$event_cats = get_terms(array('taxonomy'=>$this->events_post_type->taxonomy, 'orderby'=>'parent', 'hide_empty'=>false));
		$post_cat_slugs = wp_list_pluck($post_cats, 'slug');
		$event_cat_slugs = wp_list_pluck($event_cats, 'slug');
		$cats_to_add = array_diff($post_cat_slugs, $event_cat_slugs);
		$cats_to_del = array_diff($event_cat_slugs, $post_cat_slugs);
		$cat_intersect = array_intersect($post_cat_slugs, $event_cat_slugs);
		// compare intersect elements and determine which categories require an update
		$cats_to_mod = array();
		foreach($cat_intersect as $cat_slug) {
			$post_cat = get_category_by_slug($cat_slug);
			$event_cat = $this->events->get_cat_by_slug($cat_slug);
			if($post_cat->name !== $event_cat->name ||
			   $post_cat->alias_of !== $event_cat->alias_of ||
			   $post_cat->description !== $event_cat->description) {
					$cats_to_mod[] = $cat_slug;
					continue;
			}
			// parent checking
			$post_cat_parent = 0 === $post_cat->parent ? null : get_category($post_cat->parent);
			$event_cat_parent = 0 === $event_cat->parent ? null : $this->events->get_cat_by_id($event_cat->parent);
			/* Add to $cats_to_mod when:
			 *  * one of the category is root (parent isnull) and the other is not
			 *  * post category parent exists (instanceof WP_Term) and parent slug of post and event category are different
			 */
			if((is_null($post_cat_parent) xor is_null($event_cat_parent)) || ($post_cat_parent instanceof WP_Term && $post_cat_parent->slug !== $event_cat_parent->slug)) {
				$cats_to_mod[] = $cat_slug;
			}
		}
		// show form
		echo '
			<style>.el-catlist {list-style:inside}</style>
			<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div>
				<h2>'.__('Event Categories: Synchronise with Post Categories','event-list').'</h2>';
		$this->show_cat_list($cats_to_mod, 'event', __('Categories to modify','event-list'));
		$this->show_cat_list($cats_to_add, 'post', __('Categories to add','event-list'));
		$this->show_cat_list($cats_to_del, 'event', __('Categories to delete (optional)','event-list'));
		$delete_disabled = empty($cats_to_del) ? ' disabled' : '';
		$submit_disabled = empty($cats_to_mod) && empty($cats_to_add) && empty($cats_to_del) ? ' disabled' : '';
		echo '
				<div>
					<h3>'.__('Start synchronisation','event-list').':</h3>
					<form action="" id="el_start_cat_sync" method="post">
						<input type="hidden" name="action" id="action" value="sync">
						<input type="hidden" name="_wp_http_referer" id="_wp_http_referer" value="'.wp_get_referer().'" />
						<input type="hidden" name="cats-to-mod" id="cats-to-mod" value="'.esc_html(json_encode($cats_to_mod)).'" />
						<input type="hidden" name="cats-to-add" id="cats-to-add" value="'.esc_html(json_encode($cats_to_add)).'" />
						<input type="hidden" name="cats-to-del" id="cats-to-del" value="'.esc_html(json_encode($cats_to_del)).'" />
						<div><label for="delete-cats"><input name="delete-cats" type="checkbox" id="delete-cats" value="1"'.$delete_disabled.' />Delete not available post categories</label>
						<p><em>'.__('Attention','event-list').':</em> '.__('If this option is enabled the above listed categories will be deleted and removed from the existing events!','event-list').'</p></div>
						<button type="submit" id="cat-sync-submit" class="button"'.$submit_disabled.'>'.__('Start synchronisation','event-list').'</button>
					</form>
				</div
			</div>';
	}

	private function show_cat_list($cat_slugs, $cat_type, $heading) {
		echo '
				<div>
					<h3>'.$heading.':</h3>';
		if(empty($cat_slugs)) {
			echo '
					<p>'.__('none','event-list').'</p>';
		}
		else {
			echo '
					<ul class="el-catlist">';
			foreach($cat_slugs as $cat_slug) {
				$cat_name = 'event' === $cat_type ? $this->events->get_cat_by_slug($cat_slug)->name : get_category_by_slug($cat_slug)->name;
				echo '
							<li>'.$cat_name.' ('.__('Slug').': '.$cat_slug.')</li>';
			}
			echo '
					</ul>';
		}
		echo '
				</div><br />';
	}

	public function handle_actions() {
		// check used post parameter
		$action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
		$referer = wp_get_referer();
		// action handling
		if('sync' === $action) {
			$args['msgdata'] = $this->sync_post_categories();
			if(empty($args['msgdata']['mod_error']) && empty($args['msgdata']['add_error']) && empty($args['msgdata']['del_error'])) {
				$args['message'] = '21';
			}
			else {
				$args['message'] = '22';
				$args['error'] = 1;
			}
			wp_safe_redirect(add_query_arg($args, $referer));
			exit;
		}
	}

	private function sync_post_categories() {
		// check used post parameters
		$delete_cats = isset($_POST['delete-cats']) ? (bool)intval($_POST['delete-cats']) : false;
		$cats_to_mod = isset($_POST['cats-to-mod']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-mod']), true)) : array();
		$cats_to_add = isset($_POST['cats-to-add']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-add']), true)) : array();
		$cats_to_del = isset($_POST['cats-to-del']) ? array_map('sanitize_key', json_decode(stripslashes($_POST['cats-to-del']), true)) : array();

		$ret = array();

		// modify categories
		foreach($cats_to_mod as $cat_slug) {
			$cat = get_category_by_slug($cat_slug);
			if(empty($cat)) {
				$ret['mod_error'][] = $cat_slug;
				continue;
			}
			$parent_post_cat = get_category($cat->parent);
			$parent_event_cat = $parent_post_cat instanceof WP_Term ? $this->events->get_cat_by_slug($parent_post_cat->slug) : 0;
			$parent_id = $parent_event_cat instanceof WP_Term ? $parent_event_cat->term_id : 0;
			$ret = $this->events->update_category($cat->slug, array(
				'name' => $cat->name,
				'alias_of' => $cat->alias_of,
				'description' => $cat->description,
				'parent' => $parent_id,
				'slug' => $cat->slug
			));
			if($ret instanceof WP_Error) {
				$ret['mod_error'][] = $cat_slug;
			}
			else {
				$ret['mod_ok'][] = $cat_slug;
			}
		}

		// add categories
		foreach($cats_to_add as $cat_slug) {
			$cat = get_category_by_slug($cat_slug);
			if(empty($cat)) {
				$ret['add_error'][] = $cat_slug;
				continue;
			}
			$parent_post_cat = get_category($cat->parent);
			$parent_event_cat = empty($parent) ? null : $this->events->get_cat_by_slug($parent_post_cat->slug);
			$parent_id = empty($parent_event_cat) ? 0 : $parent_event_cat->term_id;
			$add_result = $this->events->add_category($cat->name, array(
				'alias_of' => $cat->alias_of,
				'description' => $cat->description,
				'parent' => $parent_id,
				'slug' => $cat->slug
			));
			if($add_result instanceof WP_Error) {
				$ret['add_error'][] = $cat_slug;
			}
			else {
				$ret['add_ok'][] = $cat_slug;
			}
		}

		// delete categories
		if($delete_cats) {
			foreach($cats_to_del as $cat_slug) {
				$del_result = $this->events->delete_category($cat_slug);
				if($del_result instanceof WP_Error) {
					$ret['del_error'][] = $cat_slug;
				}
				else {
					$ret['del_ok'][] = $cat_slug;
				}
			}
		}
		return $ret;
	}
}
?>
