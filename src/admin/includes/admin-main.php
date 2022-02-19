<?php
/**
 * The main class for the admin pages
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPluginRemoveDebugEcho
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 * @phan-file-suppress PhanTypeMismatchProperty
 *
 * @package event-list
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'includes/events_post_type.php';
require_once EL_PATH . 'includes/filterbar.php';
require_once EL_PATH . 'includes/daterange.php';
require_once EL_PATH . 'includes/event.php';

/**
 * This class handles all data for the admin main page
 */
class EL_Admin_Main {

	private static $instance;

	private $options;

	/**
	 * The event post type
	 *
	 * @var EL_Events_Post_Type
	 */
	private $events_post_type;

	private $filterbar;

	/**
	 * The post_status of the actual view
	 *
	 * @var string
	 */
	private $post_status;

	/**
	 * The default date for the actual view ('upcoming' or 'all')
	 *
	 * @var string
	 */
	private $default_date;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->options          = &EL_Options::get_instance();
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		$this->filterbar        = &EL_Filterbar::get_instance();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->post_status  = isset( $_GET['post_status'] ) ? sanitize_key( $_GET['post_status'] ) : 'publish';
		$this->default_date = 'all';
		add_action( 'manage_posts_custom_column', array( &$this, 'events_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-el_events_columns', array( &$this, 'events_edit_columns' ) );
		add_filter( 'manage_edit-el_events_sortable_columns', array( &$this, 'events_sortable_columns' ) );
		add_filter( 'request', array( &$this, 'sort_events' ) );
		add_filter( 'post_row_actions', array( &$this, 'add_action_row_elements' ), 10, 2 );
		add_filter( 'disable_months_dropdown', '__return_true' );
		add_filter( 'disable_categories_dropdown', '__return_true' );
		add_action( 'restrict_manage_posts', array( &$this, 'add_table_filters' ) );
		add_filter( 'parse_query', array( &$this, 'filter_request' ) );
		add_action( 'load-edit.php', array( &$this, 'set_default_posts_list_mode' ) );
		add_action( 'admin_print_scripts', array( &$this, 'embed_scripts' ) );
		add_action( 'admin_head', array( &$this, 'add_import_button' ) );
	}


	/**
	 * This method dictates the table's columns and titles. This should returns
	 * an array where the key is the column slug (and class) and the value is
	 * the column's title text.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array<string,string> $columns The columns
	 * @return array<string,string> An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function events_edit_columns( $columns ) {
		return array(
			// Render a checkbox instead of text
			'cb'        => '<input type="checkbox" />',
			'eventdate' => __( 'Event Date', 'event-list' ),
			'title'     => __( 'Title', 'event-list' ),
			'location'  => __( 'Location', 'event-list' ),
			'taxonomy-' . $this->events_post_type->taxonomy => __( 'Categories', 'default' ),
			'author'    => __( 'Author', 'event-list' ),
			'date'      => __( 'Date', 'default' ),
		);
	}


	public function events_custom_columns( $column_name, $pid ) {
		switch ( $column_name ) {
			case 'eventdate':
				$event = new EL_Event( $pid );
				echo $this->format_event_date( $event->startdate, $event->enddate, $event->starttime_i18n() );
				break;
			case 'location':
				$event = new EL_Event( $pid );
				echo $event->location;
				break;
		}
	}


	public function events_sortable_columns( $columns ) {
		$columns['eventdate'] = 'eventdate';
		$columns['location']  = 'location';
		$columns['author']    = 'author';
		return $columns;
	}


	public function sort_events( $args ) {
		$add_args = array();
		// Set default order to 'eventdate' if no custom sorting is set
		if ( empty( $args['orderby'] ) ) {
			$args['orderby'] = 'eventdate';
			$args['order']   = 'desc';
		}
		switch ( $args['orderby'] ) {
			case 'eventdate':
				$add_args = array(
					'meta_key'   => 'startdate',
					'meta_query' => array(
						'relation'  => 'AND',
						'startdate' => array( 'key' => 'startdate' ),
						'starttime' => array( 'key' => 'starttime' ),
						'enddate'   => array( 'key' => 'enddate' ),
					),
					'orderby'    => array(
						'startdate' => $args['order'],
						'starttime' => $args['order'],
						'enddate'   => $args['order'],
					),
				);
				break;
			case 'location':
				$add_args = array(
					'meta_key' => 'location',
				);
				break;
		}
		if ( ! empty( $add_args ) ) {
			$args = array_merge( $args, $add_args );
		}
		return $args;
	}


	public function add_action_row_elements( $actions, $post ) {
		if ( 'trash' !== $this->post_status ) {
			$actions['copy'] = '<a href="' . admin_url( add_query_arg( 'copy', $post->ID, 'post-new.php?post_type=el_events' ) ) .
				'" aria-label="' . sprintf( __( 'Add a copy of %1$s', 'event-list' ), '&#8222;' . $post->post_title . '&#8220;' ) . '">' . __( 'Copy', 'event-list' ) . '</a>';
		}

		return $actions;
	}


	public function add_table_filters() {
		global $cat;

		// date filter
		$date_args = array(
			'selected_date' => isset( $_GET['date'] ) ? sanitize_key( $_GET['date'] ) : $this->default_date,
		);
		echo( $this->filterbar->show_years( admin_url( 'edit.php?post_type=el_events' ), $date_args, 'dropdown', array( 'show_past' => true ) ) );

		// cat filter
		$cat_args = array(
			'selected_cat' => isset( $_GET['cat'] ) ? sanitize_key( $_GET['cat'] ) : 'all',
		);
		echo( $this->filterbar->show_cats( admin_url( 'edit.php?post_type=el_events' ), $cat_args, 'dropdown' ) );
	}


	/**
	 * Filter the request
	 *
	 * @param WP_Query $query The WordPress Query class
	 * @return void
	 * @suppress PhanPluginMixedKeyNoKey
	 */
	public function filter_request( $query ) {
		$selected_date = isset( $_GET['date'] ) ? sanitize_key( $_GET['date'] ) : $this->default_date;
		$meta_query    = array( 'relation' => 'AND' );
		// date filter
		$date_for_startrange = ( '' === $this->options->get( 'el_multiday_filterrange' ) ) ? 'startdate' : 'enddate';
		$date_range          = EL_Daterange::get_instance()->check_daterange_format( $selected_date );
		if ( empty( $date_range ) ) {
			$date_range = EL_Daterange::get_instance()->check_date_format( $selected_date );
		}
		$meta_query[]                    = array(
			'relation' => 'AND',
			array(
				'key'     => $date_for_startrange,
				'value'   => $date_range[0],
				'compare' => '>=',
			),
			array(
				'key'     => 'startdate',
				'value'   => $date_range[1],
				'compare' => '<',
			),
		);
		$query->query_vars['meta_query'] = $meta_query;
		// adaptions for taxonomy filter if a seperate taxonomy is used (no adaptions required if post categories are used)
		if ( ! $this->events_post_type->use_post_categories ) {
			// check used get parameters
			$selected_cat = isset( $_GET['cat'] ) ? sanitize_key( $_GET['cat'] ) : '';
			if ( 'all' === $selected_cat ) {
				$selected_cat = '';
			}
			$query->query_vars['cat']                               = false;
			$query->query_vars[ $this->events_post_type->taxonomy ] = $selected_cat;
		}
	}


	public function set_default_posts_list_mode() {
		// check used get parameters
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';
		$mode      = isset( $_REQUEST['mode'] ) ? sanitize_title( wp_unslash( $_REQUEST['mode'] ) ) : '';

		if ( 'el_events' === $post_type && empty( $_REQUEST['mode'] ) ) {
			$_REQUEST['mode'] = 'excerpt';
		}
	}


	public function embed_scripts() {
		wp_enqueue_style( 'eventlist_admin_main', EL_URL . 'admin/css/admin_main.css', array(), '1.0' );
	}


	public function add_import_button() {
		echo '
			<script>jQuery(document).ready(function($) { items = $("a.page-title-action").length ? $("a.page-title-action") : $("a.add-new-h2"); ' .
			'items.first().after(\'<a href="' . admin_url( 'edit.php?post_type=el_events&page=el_admin_import' ) . '" class="add-new-h2">' . __( 'Import', 'event-list' ) . '</a>\'); });</script>';
	}


	/**
	 * In this function the start date, the end date and time is formated for
	 * the output.
	 *
	 * @param string $startdate The start date of the event
	 * @param string $enddate The end date of the event
	 * @param string $starttime The start time of the event
	 * @return string
	 */
	private function format_event_date( $startdate, $enddate, $starttime ) {
		$out = '<span style="white-space:nowrap;">';
		// start date
		$out .= mysql2date( __( 'Y/m/d', 'default' ), $startdate );
		// end date for multiday event
		if ( $startdate !== $enddate ) {
			$out .= ' -<br />' . mysql2date( __( 'Y/m/d', 'default' ), $enddate );
		}
		// event starttime
		if ( '' !== $starttime ) {
			$out .= '<br />
				<span class="starttime">' . esc_html( $starttime ) . '</span>';
		}
		$out .= '</span>';
		return $out;
	}

}

