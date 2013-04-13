<?php

// This class handles all available options
class el_options {

	private static $instance;
	public $group;
	public $options;

	public static function &get_instance() {
		// Create class instance if required
		if( !isset( self::$instance ) ) {
			self::$instance = new el_options();
			self::$instance->init();
		}
		// Return class instance
		return self::$instance;
	}

	public function __construct() {
		$this->group = 'event-list';

		$this->options = array(

			// TODO: DB-Version Check must be integrated
			'el_db_version' => array( 'section' => 'system',
			                          'type'    => 'text',
			                          'std_val' => '',
			                          'label'   => '',
			                          'caption' => '',
			                          'desc'    => 'Database version' )
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

	/*
	 public function set( $name, $value ) {
		if( isset( $this->options[$name] ) ) {
			return update_option( $name, $value );
		}
		else {
			return false;
		}
	}
	*/

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
