<?php
/*
Plugin Name: Event List
Plugin URI: http://wordpress.org/extend/plugins/event-list/
Description: Manage your events and show them in a list view on your site.
Version: 0.8.1
Author: mibuthu
Author URI: http://wordpress.org/extend/plugins/event-list/
Text Domain: event-list
License: GPLv2

A plugin for the blogging MySQL/PHP-based WordPress.
Copyright 2012-2018 mibuthu

This program is free software; you can redistribute it and/or
modify it under the terms of the GNUs General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You can view a copy of the HTML version of the GNU General Public
License at http://www.gnu.org/copyleft/gpl.html
*/

if(!defined('WPINC')) {
	exit;
}

// GENERAL DEFINITIONS
define('EL_URL', plugin_dir_url(__FILE__));
define('EL_PATH', plugin_dir_path(__FILE__));

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events_post_type.php');

// MAIN PLUGIN CLASS
class Event_List {
	private $options;
	private $shortcode = null;
	private $styles_loaded = false;

	/**
	 * Constructor:
	 * Initializes the plugin.
	 */
	public function __construct() {
		$this->options = EL_Options::get_instance();

		// ALWAYS:
		// Register translation
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		// Register Events post type
		EL_Events_Post_Type::get_instance();
		// Register shortcodes
		add_shortcode('event-list', array(&$this, 'shortcode_event_list'));
		// Register widgets
		add_action('widgets_init', array(&$this, 'widget_init'));
		// Register RSS feed
		add_action('init', array(&$this, 'feed_init'), 10);

		// ADMIN PAGE:
		if(is_admin()) {
			// Init admin page
			require_once(EL_PATH.'admin/admin.php');
			EL_Admin::get_instance();
		}

		// FRONT PAGE:
		else {
			// Register actions
			add_action('wp_print_styles', array(&$this, 'print_styles'));
		}
	} // end constructor

	public function load_textdomain() {
		$el_lang_path = basename(EL_PATH).'/languages';
		$domain = 'event-list';
		if('' !== get_option('el_mo_lang_dir_first', '')) { // this->option->get not available in this early stage
			// use default wordpress function (language files from language dir wp-content/languages/plugins/ are preferred)
			load_plugin_textdomain($domain, false, $el_lang_path);
		}
		else {
			// use fork of wordpress function load_plugin_textdomain (see wp-includes/l10n.php) to prefer language files included in plugin (wp-content/plugins/event-list/languages/) and additionally from language dir
			$locale = apply_filters('plugin_locale', is_callable('get_user_locale') ? get_user_locale() : get_locale(), $domain);
			$mofile = $domain.'-'.$locale.'.mo';
			load_textdomain($domain, WP_PLUGIN_DIR.'/'.$el_lang_path.'/'.$mofile);
			load_textdomain($domain, WP_LANG_DIR.'/plugins/'.$mofile);
		}
	}

	public function shortcode_event_list($atts) {
		if(null == $this->shortcode) {
			require_once(EL_PATH.'includes/sc_event-list.php');
			$this->shortcode = SC_Event_List::get_instance();
			if(!$this->styles_loaded) {
				// normally styles are loaded with wp_print_styles action in head
				// but if the shortcode is not in post content (e.g. included in a theme) it must be loaded here
				$this->enqueue_styles();
			}
		}
		return $this->shortcode->show_html($atts);
	}

	public function feed_init() {
		if($this->options->get('el_enable_feed')) {
			include_once(EL_PATH.'includes/feed.php');
			EL_Feed::get_instance();
		}
	}

	public function widget_init() {
		// Widget "event-list"
		require_once(EL_PATH.'includes/widget.php');
		return register_widget('EL_Widget');
	}

	public function print_styles() {
		global $post;
		if(is_active_widget(null, null, 'event_list_widget') || (is_object($post) && strstr($post->post_content, '[event-list'))) {
			$this->enqueue_styles();
		}
	}

	public function enqueue_styles() {
		if('' == $this->options->get('el_disable_css_file')) {
			wp_register_style('event-list', EL_URL.'includes/css/event-list.css');
			wp_enqueue_style('event-list');
		}
		$this->styles_loaded = true;
	}
} // end class linkview


// create a class instance
$event_list = new Event_List();
?>
