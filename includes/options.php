<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

// This class handles all available options
class EL_Options {

	private static $instance;
	public $group;
	public $options;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new EL_Options();
			self::$instance->init();
		}
		// Return class instance
		return self::$instance;
	}

	public function __construct() {
		$this->group = 'event-list';

		$this->options = array(
			'el_db_version'        => array( 'section' => 'system',
			                                 'type'    => 'text',
			                                 'std_val' => '',
			                                 'label'   => '',
			                                 'caption' => '',
			                                 'desc'    => 'Database version' ),

			'el_categories'        => array( 'section' => 'categories',
			                                 'type'    => 'category',
			                                 'std_val' => null,
			                                 'label'   => 'Event Categories',
			                                 'caption' => '',
			                                 'desc'    => 'This option specifies all event category data.' ),

			'el_no_event_text'     => array( 'section' => 'general',
			                                 'type'    => 'text',
			                                 'std_val' => 'no event',
			                                 'label'   => 'Text for no events',
			                                 'caption' => '',
			                                 'desc'    => 'This option defines the text which is displayed if no events are available for the selected view.' ),

			'el_date_once_per_day' => array( 'section' => 'general',
			                                 'type'    => 'checkbox',
			                                 'std_val' => '',
			                                 'label'   => 'Date display',
			                                 'caption' => 'Show date only once per day',
			                                 'desc'    => 'With this option you can display the date only once per day if multiple events are available on the same day.<br />
			                                               If this option is enabled the events are ordered in a different way (end date before start time) to allow using the same date for as much events as possible.' ),
		);
	}

	public function init() {
		add_action( 'admin_init', array( &$this, 'register' ) );
	}

	public function register() {
		foreach( $this->options as $oname => $o ) {
			register_setting( 'el_'.$o['section'], $oname );
		}
	}

	public function set( $name, $value ) {
		if( isset( $this->options[$name] ) ) {
			return update_option( $name, $value );
		}
		else {
			return false;
		}
	}

	public function get( $name ) {
		if( isset( $this->options[$name] ) ) {
			return get_option( $name, $this->options[$name]['std_val'] );
		}
		else {
			return null;
		}
	}
}
?>
