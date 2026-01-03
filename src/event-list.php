<?php
/**
 * Plugin Name: Event List
 * Plugin URI: https://wordpress.org/plugins/event-list/
 * Description: Manage your events and show them in a list view on your site.
 * Version: 0.8.8
 * Author: mibuthu
 * Author URI: https://wordpress.org/plugins/event-list/
 * Text Domain: event-list
 * License: GPLv2
 *
 * A plugin for the blogging MySQL/PHP-based WordPress.
 * Copyright 2012-2022 mibuthu
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNUs General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You can view a copy of the HTML version of the GNU General Public
 * License at http://www.gnu.org/copyleft/gpl.html
 *
 * @package event-list
 */

// cspell:ignore mofile

if ( ! defined( 'WPINC' ) ) {
	exit;
}

// General definitions
define( 'EL_URL', plugin_dir_url( __FILE__ ) );
define( 'EL_PATH', plugin_dir_path( __FILE__ ) );

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'includes/events_post_type.php';

/**
 * Main plugin class
 *
 * This is the initial class for loading the plugin.
 */
class Event_List {

	/**
	 * Option instance used for the whole plugin
	 *
	 * @var EL_Options
	 */
	private $options;

	/**
	 * Shortcode instance
	 *
	 * @var SC_Event_List
	 */
	private $shortcode = null;

	/**
	 * Holds the status if the event-list styles are already loaded
	 *
	 * @var bool
	 */
	private $styles_loaded = false;


	/**
	 * Constructor:
	 * Initializes the plugin.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->options = EL_Options::get_instance();

		// ALWAYS:
		// Register translation
		add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ) );
		// Register Events post type
		EL_Events_Post_Type::get_instance();
		// Register shortcodes
		add_shortcode( 'event-list', array( &$this, 'shortcode_event_list' ) );
		// Register widgets
		add_action( 'widgets_init', array( &$this, 'widget_init' ) );
		// Register RSS feed
		add_action( 'init', array( &$this, 'feed_init' ), 10 );
		// Register iCal feed
		add_action( 'init', array( &$this, 'ical_init' ), 10 );

		// Admin page
		if ( is_admin() ) {
			// Init admin page
			require_once EL_PATH . 'admin/admin.php';
			EL_Admin::get_instance();
		} else {
			// Front page
			// Register actions
			add_action( 'wp_print_styles', array( &$this, 'print_styles' ) );
		}
	}


	/**
	 * Load the textdomain
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$el_lang_path = basename( EL_PATH ) . '/languages';
		$domain       = 'event-list';
		if ( '' !== get_option( 'el_mo_lang_dir_first', '' ) ) {
			// this->option->get not available in this early stage
			// Use default WordPress function (language files from language dir wp-content/languages/plugins/ are preferred)
			load_plugin_textdomain( $domain, false, $el_lang_path );
		} else {
			// Use fork of WordPress function load_plugin_textdomain (see wp-includes/l10n.php) to prefer the language files provided within the plugin (wp-content/plugins/event-list/languages/)
			// @phan-suppress-next-line PhanParamTooMany
			$locale = apply_filters( 'plugin_locale', get_user_locale(), $domain );
			$mofile = $domain . '-' . $locale . '.mo';
			load_textdomain( $domain, WP_PLUGIN_DIR . '/' . $el_lang_path . '/' . $mofile );
			load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile );
		}
	}


	/**
	 * Load the shortcode
	 *
	 * @param array<string,string|string[]> $atts The shortcode attributes.
	 * @return string
	 */
	public function shortcode_event_list( $atts ) {
		if ( null === $this->shortcode ) {
			require_once EL_PATH . 'includes/sc_event-list.php';
			$this->shortcode = SC_Event_List::get_instance();
			if ( ! $this->styles_loaded ) {
				// normally styles are loaded with wp_print_styles action in head
				// but if the shortcode is not in post content (e.g. included in a theme) it must be loaded here
				$this->enqueue_styles();
			}
		}
		return $this->shortcode->show_html( $atts );
	}


	/**
	 * Load the feed
	 *
	 * @return void
	 */
	public function feed_init() {
		if ( $this->options->get( 'el_feed_enable_rss' ) ) {
			include_once EL_PATH . 'includes/rss.php';
			EL_Rss::get_instance();
		}
	}


	/**
	 * Load the ical support
	 *
	 * @return void
	 */
	public function ical_init() {
		if ( $this->options->get( 'el_feed_enable_ical' ) ) {
			include_once EL_PATH . 'includes/ical.php';
			EL_ICal::get_instance();
		}
	}


	/**
	 * Load the widget
	 *
	 * @return void
	 */
	public function widget_init() {
		// Widget "event-list"
		require_once EL_PATH . 'includes/widget.php';
		register_widget( 'EL_Widget' );
	}


	/**
	 * Print the event-list styles
	 *
	 * @return void
	 */
	public function print_styles() {
		global $post;
		if ( is_active_widget( false, false, 'event_list_widget' ) || ( is_object( $post ) && strstr( $post->post_content, '[event-list' ) ) ) {
			$this->enqueue_styles();
		}
	}


	/**
	 * Enqueue the event-list styles
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( '' === $this->options->get( 'el_disable_css_file' ) ) {
			wp_register_style( 'event-list', EL_URL . 'includes/css/event-list.css', array(), '1.0' );
			wp_enqueue_style( 'event-list' );
		}
		$this->styles_loaded = true;
	}

}


/**
 * EventList Class instance
 *
 * @var EL_EventList
 */
$event_list = new Event_List();

