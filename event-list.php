<?php
/*
Plugin Name: Event List
Plugin URI: http://wordpress.org/extend/plugins/event-list/
Description: Manage your events and show them in a list view on your site.
Version: 0.4.5
Author: Michael Burtscher
Author URI: http://wordpress.org/extend/plugins/event-list/
License: GPLv2

A plugin for the blogging MySQL/PHP-based WordPress.
Copyright 2012-2013 Michael Burtscher

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

if( !defined( 'ABSPATH' ) ) {
	exit;
}

// GENERAL DEFINITIONS
define( 'EL_URL', plugin_dir_url( __FILE__ ) );
define( 'EL_PATH', plugin_dir_path( __FILE__ ) );


// MAIN PLUGIN CLASS
class Event_List {
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
			require_once( EL_PATH.'admin/admin.php' );
			EL_Admin::get_instance()->init_admin_page();
		}

		// FRONT PAGE:
		else {
			// Register actions
			add_action('wp_print_styles', array( &$this, 'print_styles' ) );
		}
	} // end constructor

	public function shortcode_event_list( $atts ) {
		if( NULL == $this->shortcode ) {
			require_once( EL_PATH.'includes/sc_event-list.php' );
			$this->shortcode = SC_Event_List::get_instance();
		}
		return $this->shortcode->show_html( $atts );
	}

	public function widget_init() {
		// Widget "event-list"
		require_once( EL_PATH.'includes/widget.php' );
		return register_widget( 'EL_Widget' );
	}

	public function print_styles() {
		wp_register_style('event-list_css', EL_URL.'/includes/css/event-list.css');
		wp_enqueue_style( 'event-list_css');
	}
} // end class linkview


// create a class instance
$event_list = new Event_List();
?>
