<?php
/**
 * The main class for the admin pages
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginNoCommentOnPrivateMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 *
 * @package event-list
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'includes/daterange.php';

/**
 * This class handles all data for the admin about page
 */
class EL_Admin_About {

	private static $instance;

	private $options;

	private $daterange;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->options   = EL_Options::get_instance();
		$this->daterange = EL_Daterange::get_instance();
	}


	public function show_about() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}
		// check used get parameters
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		echo '<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div><h2>' . esc_html__( 'About Event List', 'event-list' ) . '</h2>';
		echo wp_kses_post( $this->show_tabs( $tab ) );
		if ( 'atts' === $tab ) {
			$this->daterange->load_formats_helptexts();
			$this->show_atts();
			$this->show_filter_syntax();
			$this->show_date_syntax();
			$this->show_daterange_syntax();
		} else {
			$this->show_help();
			$this->show_author();
		}
		echo '
			</div>';
	}


	public function embed_about_scripts() {
		wp_enqueue_style( 'eventlist_admin_about', EL_URL . 'admin/css/admin_about.css', array(), '1.0' );
	}


	private function show_tabs( $current = 'general' ) {
		$tabs = array(
			'general' => __( 'General', 'event-list' ),
			'atts'    => __( 'Shortcode Attributes', 'event-list' ),
		);
		$out  = '<h3 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab === $current ) ? ' nav-tab-active' : '';
			$out  .= '<a class="nav-tab' . $class . '" href="' . add_query_arg( 'tab', $tab, add_query_arg( array() ) ) . '">' . $name . '</a>';
		}
		$out .= '</h3>';
		return $out;
	}


	private function show_help() {
		echo '
			<h3 class="el-headline">' . esc_html__( 'Help and Instructions', 'event-list' ) . '</h3>
			<p>' . sprintf( esc_html__( 'You can manage the events %1$shere%2$s', 'event-list' ), '<a href="' . admin_url( 'edit.php?post_type=el_events' ) . '">', '</a>' ) . '.</p>
			<p>' . esc_html__( 'To show the events on your site you have 2 possibilities', 'event-list' ) . ':</p>
			<ul class="el-show-event-options"><li>' . sprintf( wp_kses_post( __( 'you can place the <strong>shortcode</strong> %1$s on any page or post', 'event-list' ) ), '<code>[event-list]</code>' ) . '</li>
			<li>' . sprintf( wp_kses_post( __( 'you can add the <strong>widget</strong> %1$s in your sidebars', 'event-list' ) ), '"Event List"' ) . '</li></ul>
			<p>' . esc_html__( 'The displayed events and their style can be modified with the available widget settings and the available attributes for the shortcode.', 'event-list' ) . '<br />
				' . sprintf(
				esc_html__( 'A list of all available shortcode attributes with their descriptions is available in the %1$s tab.', 'event-list' ),
				'<a href="' . admin_url( 'admin.php?page=el_admin_about&tab=atts' ) . '">' . esc_html__( 'Shortcode Attributes', 'event-list' ) . '</a>'
			) . '<br />
				' . esc_html__( 'The available  widget options are described in their tooltip text.', 'event-list' ) . '<br />
				' . sprintf(
					esc_html__( 'If you enable one of the links options (%1$s or %2$s) in the widget you have to insert an URL to the linked event-list page.', 'event-list' ),
					'"' . esc_html__( 'Add links to the single events', 'event-list' ) . '"',
					'"' . esc_html__( 'Add a link to the Event List page', 'event-list' ) . '"'
				)
				. esc_html__( 'This is required because the widget does not know in which page or post the shortcode was included.', 'event-list' ) . '<br />
				' . esc_html__( 'Additionally you have to insert the correct Shortcode id on the linked page. This id describes which shortcode should be used on the given page or post if you have more than one.', 'event-list' )
				. sprintf(
					esc_html__( 'The default value %1$s is normally o.k. (for pages with 1 shortcode only), but if required you can check the id by looking into the URL of an event link on your linked page or post.', 'event-list' ),
					'[1]'
				)
				. sprintf(
					esc_html__( 'The id is available at the end of the URL parameters (e.g. %1$s).', 'event-list' ),
					'<i>https://www.your-homepage.com/?page_id=99&amp;event_id<strong>1</strong>=11</i>'
				) . '
			</p>
			<p>' . sprintf(
					esc_html__( 'Be sure to also check the %1$s to get the plugin behaving just the way you want.', 'event-list' ),
					'<a href="' . admin_url( 'admin.php?page=el_admin_settings' ) . '">' . esc_html__( 'Settings page', 'event-list' ) . '</a>'
				) . '</p>';
	}


	private function show_author() {
		echo '
			<br />
			<h3>' . esc_html__( 'About the plugin author', 'event-list' ) . '</h3>
			<div class="help-content">
				<p>' . sprintf(
				esc_html__( 'This plugin is developed by %1$s, you can find more information about the plugin on the %2$s.', 'event-list' ),
				'mibuthu',
				'<a href="http://wordpress.org/plugins/event-list" target="_blank" rel="noopener">' . esc_html__( 'WordPress plugin site', 'event-list' ) . '</a>'
			) . '</p>
				<p>' . sprintf(
				esc_html__( 'If you like the plugin please rate it on the %1$s.', 'event-list' ),
				'<a href="http://wordpress.org/support/view/plugin-reviews/event-list" target="_blank" rel="noopener">' . esc_html__( 'WordPress plugin review site', 'event-list' ) . '</a>'
			) . '<br />
				<p>' . esc_html__( 'If you want to support the plugin I would be happy to get a small donation', 'event-list' ) . ':<br />
				<a class="donate" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W54LNZMWF9KW2" target="_blank" rel="noopener"><img src="' .
				esc_url_raw( EL_URL ) . 'admin/images/paypal_btn_donate.gif" alt="PayPal Donation" title="' . sprintf( esc_html__( 'Donate with %1$s', 'event-list' ), 'PayPal' ) . '" border="0"></a>
				<a class="donate" href="https://liberapay.com/mibuthu/donate" target="_blank" rel="noopener"><img src="' .
				esc_url_raw( EL_URL ) . 'admin/images/liberapay-donate.svg" alt="Liberapay Donation" title="' . sprintf( esc_html__( 'Donate with %1$s', 'event-list' ), 'Liberapay' ) . '" border="0"></a>
				<a class="donate" href="https://flattr.com/submit/auto?user_id=mibuthu&url=https%3A%2F%2Fwordpress.org%2Fplugins%2Fevent-list" target="_blank" rel="noopener"><img src="' .
				esc_url_raw( EL_URL ) . 'admin/images/flattr-badge-large.png" alt="Flattr this" title="' . sprintf( esc_html__( 'Donate with %1$s', 'event-list' ), 'Flattr' ) . '" border="0"></a></p>
			</div>';
	}


	private function show_atts() {
		echo '
			<h3 class="el-headline">' . esc_html__( 'Shortcode Attributes', 'event-list' ) . '</h3>
			<div>
				' . esc_html__( 'You have the possibility to modify the output if you add some of the following attributes to the shortcode.', 'event-list' ) . '<br />
				' . sprintf(
					esc_html__( 'You can combine and add as much attributes as you want. E.g. the shortcode including the attributes %1$s and %2$s would looks like this:', 'event-list' ),
					'"num_events"',
					'"show_filterbar"'
				) . '
				<p><code>[event-list num_events=10 show_filterbar=false]</code></p>
				<p>' . esc_html__( 'Below you can find a list of all supported attributes with their descriptions and available options:', 'event-list' ) . '</p>';
		echo wp_kses_post( $this->show_atts_table() );
		echo '
			</div>';
	}


	private function show_atts_table() {
		require_once EL_PATH . 'includes/sc_event-list.php';
		$shortcode = &SC_Event_List::get_instance();
		$shortcode->load_sc_eventlist_helptexts();
		$atts = $shortcode->get_atts();
		$out  = '
			<table class="el-atts-table">
				<tr>
					<th class="el-atts-table-name">' . __( 'Attribute name', 'event-list' ) . '</th>
					<th class="el-atts-table-options">' . __( 'Value options', 'event-list' ) . '</th>
					<th class="el-atts-table-default">' . __( 'Default value', 'event-list' ) . '</th>
					<th class="el-atts-table-desc">' . __( 'Description', 'event-list' ) . '</th>
				</tr>';
		foreach ( $atts as $aname => $a ) {
			$out .= '
				<tr>
					<td>' . $aname . '</td>
					<td>' . implode( '<br />', $a['val'] ) . '</td>
					<td>' . $a['std_val'] . '</td>
					<td>' . $a['desc'] . '</td>
				</tr>';
		}
		$out .= '
			</table>';
		return $out;
	}


	private function show_filter_syntax() {
		echo '
			<h3 class="el-headline">' . esc_html__( 'Filter Syntax', 'event-list' ) . '</h3>
			<p>' . esc_html__( 'For date and cat filters you can specify complex filters with the following syntax:', 'event-list' ) . '</p>
			<p>' . sprintf(
				esc_html__( 'You can use %1$s and %2$s connections to define complex filters. Additionally you can set brackets %3$s for nested queries.', 'event-list' ),
				esc_html__( 'AND', 'event-list' ) . ' ( "<strong>&amp;</strong>" )',
				esc_html__( 'OR', 'event-list' ) . ' ( "<strong>&verbar;</strong>" ' . esc_html__( 'or', 'event-list' ) . ' "<strong>&comma;</strong>" )',
				'( "<strong>(</strong>" ' . esc_html__( 'and', 'event-list' ) . ' "<strong>)</strong>" )'
			) . '</p>
			' . esc_html__( 'Examples for cat filters:', 'event-list' ) . '
			<p><code>tennis</code>&hellip; ' . sprintf( esc_html__( 'Show all events with category %1$s.', 'event-list' ), '"tennis"' ) . '<br />
			<code>tennis&comma;hockey</code>&hellip; ' . sprintf( esc_html__( 'Show all events with category %1$s or %2$s.', 'event-list' ), '"tennis"', '"hockey"' ) . '<br />
			<code>tennis&verbar;(hockey&amp;winter)</code>&hellip; ' .
			sprintf( esc_html__( 'Show all events with category %1$s and all events where category %2$s as well as %3$s is selected.', 'event-list' ), '"tennis"', '"hockey"', '"winter"' ) . '</p>';
	}


	private function show_date_syntax() {
		echo '
			<h3 class="el-headline">' . esc_html__( 'Available Date Formats', 'event-list' ) . '</h3>
			<p>' . esc_html__( 'For date filters you can use the following date formats:', 'event-list' ) . '</p>
			<ul class="el-formats">
			' . wp_kses_post( $this->show_formats( $this->daterange->date_formats ) ) . '
			</ul>';
	}


	private function show_daterange_syntax() {
		echo '
			<h3 class="el-headline">' . esc_html__( 'Available Date Range Formats', 'event-list' ) . '</h3>
			<p>' . esc_html__( 'For date filters you can use the following daterange formats:', 'event-list' ) . '</p>
			<ul class="el-formats">
			' . wp_kses_post( $this->show_formats( $this->daterange->daterange_formats ) ) . '
			</ul>';
	}


	private function show_formats( &$formats_array ) {
		$out = '';
		foreach ( $formats_array as $format ) {
			$out .= '
				<li><div class="el-format-entry"><div class="el-format-name">' . $format['name'] . ':</div><div class="el-format-desc">';
			if ( isset( $format['value'] ) ) {
				$out .= __( 'Value', 'event-list' ) . ': <em>' . $format['value'] . '</em><br />';
			}
			$out .= $format['desc'] . '<br />';
			if ( isset( $format['examp'] ) ) {
				$out .= __( 'Example', 'event-list' ) . ': <em>' . $format['examp'] . '</em>';
			}
			$out .= '</div></div></li>';
		}
		return $out;
	}

}

