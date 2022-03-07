<?php
/**
 * This file displays the admin settings page
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginNoCommentOnPrivateMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 *
 * @package event-list
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'admin/includes/admin-functions.php';

/**
 * This class handles all data for the admin settings page
 */
class EL_Admin_Settings {

	private static $instance;

	private $options;

	private $functions;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->options   = &EL_Options::get_instance();
		$this->functions = &EL_Admin_Functions::get_instance();
	}


	public function show_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}
		// check used get parameters
		$tab              = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$settings_updated = isset( $_GET['settings-updated'] ) ? sanitize_key( $_GET['settings-updated'] ) : '';

		// check for changed settings
		if ( 'true' === $settings_updated ) {
			// show "settings saved" message
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			echo '<div id="message" class="updated"><p><strong>' . esc_html__( 'Settings saved.' ) . '</strong></p></div>';
			switch ( $tab ) {
				case 'frontend':
					// flush rewrite rules (required if permalink slug was changed)
					flush_rewrite_rules();
					break;
				case 'feed':
					// update feed rewrite status if required
					require_once EL_PATH . 'includes/rss.php';
					EL_Rss::get_instance()->update_rewrite_status();
					require_once EL_PATH . 'includes/ical.php';
					EL_ICal::get_instance()->update_ical_rewrite_status();
					break;
				case 'taxonomy':
					// update category count
					require_once EL_PATH . 'admin/includes/event-category_functions.php';
					EL_Event_Category_Functions::get_instance()->update_cat_count();
					break;
			}
		}

		// normal output
		echo '
				<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>' . esc_html__( 'Event List Settings', 'event-list' ) . '</h2>';
		$this->show_tabs( $tab );
		echo '<div id="posttype-page" class="posttypediv">';
		$options = array();
		if ( 'taxonomy' === $tab ) {
			$options['page']         = admin_url( 'edit.php?post_type=el_events&page=el_admin_cat_sync&switch_taxonomy=1' );
			$options['button_text']  = __( 'Go to Event Category switching page', 'event-list' );
			$options['button_class'] = 'secondary';
		}
		$this->functions->show_option_form( $tab, $options );
		echo '
				</div>
			</div>';
	}


	private function show_tabs( $current = 'category' ) {
		$tabs = array(
			'general'  => __( 'General', 'event-list' ),
			'frontend' => __( 'Frontend Settings', 'event-list' ),
			'admin'    => __( 'Admin Page Settings', 'event-list' ),
			'feed'     => __( 'Feed Settings', 'event-list' ),
			'taxonomy' => __( 'Category Taxonomy', 'event-list' ),
		);
		echo '<h3 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab === $current ) ? ' nav-tab-active' : '';
			echo '<a class="nav-tab' . esc_attr( $class ) . '" href="' . esc_url_raw( remove_query_arg( 'settings-updated', add_query_arg( 'tab', $tab ) ) ) . '">' . esc_html( $name ) . '</a>';
		}
		echo '</h3>';
	}


	public function embed_settings_scripts() {
		wp_enqueue_style( 'eventlist_admin_settings', EL_URL . 'admin/css/admin_settings.css', array(), '1.0' );
	}

}

