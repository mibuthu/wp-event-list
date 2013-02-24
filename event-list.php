<?php
/*
Plugin Name: Event List
Plugin URI: http://wordpress.org/extend/plugins/event-list/
Description: Manage your events and show them in a list view on your site.
Version: 0.3.2
Author: Michael Burtscher
Author URI: http://wordpress.org/extend/plugins/event-list/
License: GPLv2

A plugin for the blogging MySQL/PHP-based WordPress.
Copyright 2012 Michael Burtscher

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

// GENERAL DEFINITIONS
define( 'EL_URL', plugin_dir_url( __FILE__ ) );
define( 'EL_PATH', plugin_dir_path( __FILE__ ) );


// MAIN PLUGIN CLASS
class event_list {
	private $shortcode;

	/**
	 * Constructor:
	 * Initializes the plugin.
	 */
	public function __construct() {
		$this->shortcode = NULL;

		// ALWAYS:
		// Register shortcodes
		add_shortcode( 'event-list', array( &$this, 'shortcode_event_list' ) );
		// Register widgets
		add_action( 'widgets_init', array( &$this, 'widget_init' ) );

		// ADMIN PAGE:
		if ( is_admin() ) {
			// Include required php-files and initialize required objects
			require_once( 'php/admin.php' );
			$admin = new el_admin();
			// Register actions
			add_action( 'admin_menu', array( &$admin, 'register_pages' ) );
			add_action( 'plugins_loaded', array( &$this, 'db_upgrade_check' ) );
		}

		// FRONT PAGE:
		else {
			// Register actions
			add_action('wp_print_styles', array( &$this, 'print_styles' ) );
		}
	} // end constructor

	public function shortcode_event_list( $atts ) {
		if( NULL == $this->shortcode ) {
			require_once( 'php/sc_event-list.php' );
			$this->shortcode = sc_event_list::get_instance();
		}
		return $this->shortcode->show_html( $atts );
	}

	public function widget_init() {
		// Widget "event-list"
		require_once( 'php/event-list_widget.php' );
		return register_widget( 'event_list_widget' );
	}

	public function print_styles() {
		wp_register_style('event-list_css', EL_URL.'css/event-list.css');
		wp_enqueue_style( 'event-list_css');
	}

	public function db_upgrade_check() {
		require_once( 'php/db.php' );
		$db = el_db::get_instance();
		$db->upgrade_check();
	}
} // end class linkview


// create a class instance
$event_list = new event_list();
?>
