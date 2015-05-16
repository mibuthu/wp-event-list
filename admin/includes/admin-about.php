<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/daterange.php');

// This class handles all data for the admin about page
class EL_Admin_About {
	private static $instance;
	private $options;
	private $daterange;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->options = EL_Options::get_instance();
		$this->daterange = EL_Daterange::get_instance();
	}

	public function show_about() {
		if(!current_user_can('edit_posts')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		echo '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>'.__('About Event List','event-list').'</h2>
				<h3 class="el-headline">'.__('Help and Instructions','event-list').'</h3>
				<p>'.sprintf(__('You can manage your events %1$shere%2$s','event-list'), '<a href="admin.php?page=el_admin_main">', '</a>').'.</p>
				<p>'.__('To show the events on your site you have 2 possibilities','event-list').':</p>
				<ul class="el-show-event-options"><li>'.sprintf(__('you can place the <strong>shortcode</strong> %1$s on any page or post','event-list'), '<code>[event-list]</code>').'</li>
				<li>'.sprintf(__('you can add the <strong>widget</strong> %1$s in your sidebars','event-list'), '"Event List"').'</li></ul>
				<p>'.__('The displayed events and their style can be modified with the available widget settings and the available attributes for the shortcode.','event-list').'<br />
					'.__('A list of all available shortcode attributes with their description is available below.','event-list').'<br />
					'.__('The available  widget options are described in their tooltip text.','event-list').'<br />
					'.sprintf(__('For the widget it is important to know that you have to insert an URL to the linked event-list page if you enable one of the links options
					(%1$s or %2$s). This is required because the widget didn´t know in which page or post the shortcode was included.','event-list'), '"'.__('Add links to the single events','event-list').'"', '"'.__('Add a link to the Event List page','event-list').'"').'<br />
					'.sprintf(__('Additionally you have to insert the correct Shortcode ID on the linked page. This ID describes which shortcode should be used on the given page or post if you have more than one.
					So the standard value "1" is normally o.k., but if required you can check the ID by looking into the URL of an event link on your linked page or post.
					The ID will be added at the end of the query parameter name (e.g. %1$s).','event-list'), '<i>http://www.your-homepage.com/?page_id=99&amp;event_id<strong>1</strong>=11</i>').'
				</p>
				<p>'.sprintf(__('Be sure to also check the %1$sSettings page%2$s to get Event List behaving just the way you want.','event-list'), '<a href="admin.php?page=el_admin_settings">', '</a>').'</p>
			</div>';
		echo $this->show_atts();
		echo $this->show_filter_syntax();
		echo $this->show_date_syntax();
		echo $this->show_daterange_syntax();
	}

	public function embed_about_scripts() {
		wp_enqueue_style('eventlist_admin_about', EL_URL.'admin/css/admin_about.css');
	}

	private function show_atts() {
		$out = '
			<h3 class="el-headline">'.__('Shortcode Attributes', 'event-list').'</h3>
			<div>
				'.__('You have the possibility to modify the output if you add some of the following attributes to the shortcode.','event-list').'<br />
				'.sprintf(__('You can combine and add as much attributes as you want. E.g. the shortcode including the attributes %1$s and %2$s would looks like this:','event-list'), '"num_events"', '"show_filterbar"').'
				<p><code>[event-list num_events=10 show_filterbar=false]</code></p>
				<p>'.__('Below you can find a list of all supported attributes with their descriptions and available options:','event-list').'</p>';
		$out .= $this->show_atts_table();
		$out .= '
			</div>';
		return $out;
	}

	private function show_atts_table() {
		require_once(EL_PATH.'includes/sc_event-list.php');
		$shortcode = &SC_Event_List::get_instance();
		$shortcode->load_sc_eventlist_helptexts();
		$atts = $shortcode->get_atts();
		$out = '
			<table class="el-atts-table">
				<tr>
					<th class="el-atts-table-name">'.__('Attribute name','event-list').'</th>
					<th class="el-atts-table-options">'.__('Value options','event-list').'</th>
					<th class="el-atts-table-default">'.__('Default value','event-list').'</th>
					<th class="el-atts-table-desc">'.__('Description','event-list').'</th>
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

	private function show_filter_syntax() {
		return '
			<h3 class="el-headline">'.__('Filter Syntax','event-list').'</h3>
			<p>'.__('For date and cat filters you can specify complex filters with the following syntax:','event-list').'</p>
			<p>'.sprintf(__('You can use %1$s and %2$s connections to define complex filters. Additionally you can set brackets %3$s for nested queries.','event-list'), __('AND','event-list').' ( "<strong>&amp;</strong>" )', __('OR','event-list').' ( "<strong>&verbar;</strong>" '.__('or','event-list').' "<strong>&comma;</strong>" )', '( "<strong>(</strong>" '.__('and','event-list').' "<strong>)</strong>" )').'</p>
			'.__('Examples for cat filters:','event-list').'
			<p><code>tennis</code>&hellip; '.sprintf(__('Show all events with category %1$s.','event-list'), '"tennis"').'<br />
			<code>tennis&comma;hockey</code>&hellip; '.sprintf(__('Show all events with category %1$s or %2$s.','event-list'), '"tennis"', '"hockey"').'<br />
			<code>tennis&verbar;(hockey&amp;winter)</code>&hellip; '.sprintf(__('Show all events with category %1$s and all events where category %2$s as well as %3$s is selected.','event-list'), '"tennis"', '"hockey"', '"winter"').'</p>';
	}

	private function show_date_syntax() {
		return '
			<h3 class="el-headline">'.__('Available Date Formats','event-list').'</h3>
			<p>'.__('For date filters you can use the following date formats:','event-list').'</p>
			<ul class="el-formats">
			'.$this->show_formats($this->daterange->date_formats).'
			</ul>';
	}

	private function show_daterange_syntax() {
		return '
			<h3 class="el-headline">'.__('Available Date Range Formats','event-list').'</h3>
			<p>'.__('For date filters you can use the following daterange formats:','event-list').'</p>
			<ul class="el-formats">
			'.$this->show_formats($this->daterange->daterange_formats).'
			</ul>';
	}

	private function show_formats(&$formats_array) {
		$out = '';
		foreach($formats_array as $format) {
			$out .= '
				<li><div class="el-format-entry"><div class="el-format-name">'.$format['name'].':</div><div class="el-format-desc">';
			if(isset($format['value'])) {
				$out .= __('Value','event-list').': <em>'.$format['value'].'</em><br />';
			}
			$out .= $format['desc'].'<br />';
			if(isset($format['examp'])) {
				$out .= __('Example','event-list').': <em>'.$format['examp'].'</em>';
			}
			$out .= '</div></div></li>';
		}
		return $out;
	}
}
?>
