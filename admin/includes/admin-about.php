<?php
if(!defined('ABSPATH')) {
	exit;
}

// This class handles all data for the admin about page
class EL_Admin_About {
	private static $instance;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin_About();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {

	}

	public function show_about() {
		if(!current_user_can('edit_posts')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$out = '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>About Event List</h2>
				<h3>Help and Instructions</h3>
				<p>You can manage your events <a href="admin.php?page=el_admin_main">here</a>.</p>
				<p>To show the events on your site you have two possibilities:
					<ul class="el-show-event-options"><li>you can place the <strong>shortcode</strong> <code>[event-list]</code> on any page or post</li>
					<li>you can add the <strong>widget</strong> "Event List" in your sidebars</li></ul>
					The displayed events and their style can be modified with the available widget settings and the available attributes for the shortcode.<br />
					A list of all available shortcode attributes with their description is available below.<br />
					The most available options of the widget should be clear by there description.<br />
					It is important to know that you have to insert an URL to the linked event-list page if you enable one of the links options ("Add links to the single events" or "Add a link to an event page").
					This is required because the widget didn´t know in which page or post you have insert the shortcode.<br />
					Additonally you have to insert the correct Shortcode ID on the linked page. This ID describes which shortcode should be used on the given page or post if you have more than one.
					So the standard value "1" is normally o.k., but you can check the ID if you have a look into the URL of an event link on your linked page or post.
					The ID is given behind the "_" (e.g. <i>http://www.your-homepage.com/?page_id=99&event_id_<strong>1</strong>=11</i>).
				</p>
				<p>Be sure to also check the <a href="admin.php?page=el_admin_settings">Settings page</a> to get Event List behaving just the way you want.</p>
			</div>';
		$out .= $this->show_atts();
		echo $out;
	}

	public function embed_about_scripts() {
		wp_enqueue_style('eventlist_admin_about', EL_URL.'admin/css/admin_about.css');
	}

	private function show_atts() {
		$out = '
			<h3 class="el-headline">AShortcode Attributes</h3>
			<div>
				You have the possibility to modify the output if you add some of the following attributes to the shortcode.<br />
				You can combine as much attributes as you want. E.g.the shortcode including the attributes "num_events" and "show_nav" would looks like this:
				<p><code>[event-list num_events=10 show_nav=false]</code></p>
				<p>Below you can find a list of all supported attributes with their descriptions and available options:</p>';
		$out .= $this->show_atts_table();
		$out .= '
			</div>';
		return $out;
	}

	private function show_atts_table() {
		require_once(EL_PATH.'includes/sc_event-list.php');
		$shortcode = &SC_Event_List::get_instance();
		$atts = $shortcode->get_atts();
		$out = '
			<table class="el-atts-table">
				<tr>
					<th class="el-atts-table-name">Attribute name</th>
					<th class="el-atts-table-options">Value options</th>
					<th class="el-atts-table-default">Default value</th>
					<th class="el-atts-table-desc">Description</th>
				</tr>';
		foreach($atts as $aname => $a) {
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
}
?>
