<?php
/**
 * The options class
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPublicProperty
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 *
 * @package event-list
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

/**
 * This class handles all available options
 */
class EL_Options {

	private static $instance;

	public $options;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		add_action( 'init', array( &$this, 'init_options' ), 1 );
		add_action( 'admin_init', array( &$this, 'register_options' ) );
	}


	public function init_options() {
		$this->options = array(
			'el_last_upgrade_version'    => array(
				'section' => 'system',
				'std_val' => '',
			),

			'el_import_file'             => array(
				'section' => 'import',
				'std_val' => '',
			),
			'el_import_date_format'      => array(
				'section' => 'import',
				'std_val' => 'Y-m-d',
			),

			'el_no_event_text'           => array(
				'section' => 'general',
				'std_val' => 'no event',
			),
			'el_multiday_filterrange'    => array(
				'section' => 'general',
				'std_val' => '1',
			),
			'el_date_once_per_day'       => array(
				'section' => 'general',
				'std_val' => '',
			),
			'el_html_tags_in_time'       => array(
				'section' => 'general',
				'std_val' => '',
			),
			'el_html_tags_in_loc'        => array(
				'section' => 'general',
				'std_val' => '',
			),
			// default value must be set also in load_textdomain function in Event-List class
			'el_mo_lang_dir_first'       => array(
				'section' => 'general',
				'std_val' => '',
			),
			'el_permalink_slug'          => array(
				'section' => 'frontend',
				'std_val' => __( 'events', 'event-list' ),
			),
			'el_content_show_text'       => array(
				'section' => 'frontend',
				'std_val' => __( 'Show content', 'event-list' ),
			),
			'el_content_hide_text'       => array(
				'section' => 'frontend',
				'std_val' => __( 'Hide content', 'event-list' ),
			),
			'el_disable_css_file'        => array(
				'section' => 'frontend',
				'std_val' => '',
			),

			'el_edit_dateformat'         => array(
				'section' => 'admin',
				'std_val' => '',
			),

			'el_feed_enable_rss'         => array(
				'section' => 'feed',
				'std_val' => '',
			),
			'el_feed_enable_ical'        => array(
				'section' => 'feed',
				'std_val' => '',
			),
			'el_feed_link_pos'           => array(
				'section' => 'feed',
				'std_val' => 'bottom',
			),
			'el_feed_link_align'         => array(
				'section' => 'feed',
				'std_val' => 'left',
			),
			'el_feed_rss_name'           => array(
				'section' => 'feed',
				'std_val' => 'event-list',
			),
			'el_feed_rss_description'    => array(
				'section' => 'feed',
				'std_val' => 'Eventlist Feed',
			),
			'el_feed_rss_upcoming_only'  => array(
				'section' => 'feed',
				'std_val' => '',
			),
			'el_feed_rss_link_text'      => array(
				'section' => 'feed',
				'std_val' => 'RSS',
			),
			'el_feed_ical_name'          => array(
				'section' => 'feed',
				'std_val' => 'event-list.ics',
			),
			'el_feed_ical_upcoming_only' => array(
				'section' => 'feed',
				'std_val' => '',
			),
			'el_feed_ical_link_text'     => array(
				'section' => 'feed',
				'std_val' => 'iCal',
			),

			'el_use_post_cats'           => array(
				'section' => 'taxonomy',
				'std_val' => '',
			),
		);
	}


	public function load_options_helptexts() {
		require_once EL_PATH . 'includes/options_helptexts.php';
		// @phan-suppress-next-line PhanUndeclaredVariable
		foreach ( (array) $options_helptexts as $name => $values ) {
			$this->options[ $name ] += $values;
		}
		unset( $options_helptexts );
	}


	public function register_options() {
		foreach ( $this->options as $oname => $o ) {
			register_setting( 'el_' . $o['section'], $oname, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
		}
	}


	public function set( $name, $value ) {
		if ( isset( $this->options[ $name ] ) ) {
			return update_option( $name, $value );
		} else {
			return false;
		}
	}


	public function get( $name ) {
		if ( isset( $this->options[ $name ] ) ) {
			return get_option( $name, $this->options[ $name ]['std_val'] );
		} else {
			return null;
		}
	}


	/**
	 * Sanitize an option value before safing the value to the database
	 *
	 * @param string $value The value to sanitize
	 * @return string
	 */
	public function sanitize( $value ) {
		$value = esc_textarea( sanitize_text_field( $value ) );
		return $value;
	}

}
