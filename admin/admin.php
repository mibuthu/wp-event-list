<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once( EL_PATH.'admin/includes/admin-main.php' );
require_once( EL_PATH.'admin/includes/admin-new.php' );
require_once( EL_PATH.'admin/includes/admin-settings.php' );
require_once( EL_PATH.'admin/includes/admin-about.php' );

// This class handles all available admin pages
class EL_Admin {

	/**
	 * Add and register all admin pages in the admin menu
	 */
	public function register_pages() {
		add_menu_page( 'Event List', 'Event List', 'edit_posts', 'el_admin_main', array( EL_Admin_Main::get_instance(), 'show_main' ) );
		$page = add_submenu_page( 'el_admin_main', 'Events', 'All Events', 'edit_posts', 'el_admin_main', array( EL_Admin_Main::get_instance(), 'show_main' ) );
		add_action( 'admin_print_scripts-'.$page, array( EL_Admin_Main::get_instance(), 'embed_admin_main_scripts' ) );
		$page = add_submenu_page( 'el_admin_main', 'Add New Event', 'Add New', 'edit_posts', 'el_admin_new', array( EL_Admin_New::get_instance(), 'show_new' ) );
		add_action( 'admin_print_scripts-'.$page, array( EL_Admin_New::get_instance(), 'embed_admin_new_scripts' ) );
		$page = add_submenu_page( 'el_admin_main', 'Event List Settings', 'Settings', 'manage_options', 'el_admin_settings', array( EL_Admin_Settings::get_instance(), 'show_settings' ) );
		add_action( 'admin_print_scripts-'.$page, array( EL_Admin_Settings::get_instance(), 'embed_admin_settings_scripts' ) );
		$page = add_submenu_page( 'el_admin_main', 'About Event List', 'About', 'edit_posts', 'el_admin_about', array( EL_Admin_About::get_instance(), 'show_about' ) );
		add_action( 'admin_print_scripts-'.$page, array( EL_Admin_About::get_instance(), 'embed_admin_about_scripts' ) );
	}
}
?>
