<?php
/**
 * The admin new class
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
 * @phan-file-suppress PhanPartialTypeMismatchArgumentInternal
 * @phan-file-suppress PhanPossiblyNullTypeArgument
 * @phan-file-suppress PhanPossiblyUndeclaredVariable
 * @phan-file-suppress PhanPossiblyFalseTypeArgument
 * @phan-file-suppress PhanPossiblyFalseTypeArgumentInternal
 *
 * @package event-list
 */

// TODO: Fix phpcs warnings to remove the disabled checks
// phpcs:disable WordPress.DateTime.CurrentTimeTimestamp.Requested

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'includes/event.php';

/**
 * This class handles all data for the admin new event page
 */
class EL_Admin_New {

	private static $instance;

	private $options;

	private $is_new;

	private $copy_event = null;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		// check used get parameters
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$copy   = isset( $_GET['copy'] ) ? intval( $_GET['copy'] ) : 0;
		if ( ! empty( $copy ) ) {
			$this->copy_event = new EL_Event( $copy );
			add_filter( 'get_object_terms', array( &$this, 'set_copied_categories' ) );
		}

		$this->options = &EL_Options::get_instance();
		$this->is_new  = 'edit' !== $action;

		add_action( 'add_meta_boxes', array( &$this, 'add_eventdata_metabox' ) );
		add_action( 'edit_form_top', array( &$this, 'form_top_content' ) );
		add_action( 'edit_form_after_title', array( &$this, 'form_after_title_content' ) );
		add_action( 'admin_print_scripts', array( &$this, 'embed_scripts' ) );
		add_action( 'save_post_el_events', array( &$this, 'save_eventdata' ), 10, 3 );
		add_filter( 'enter_title_here', array( &$this, 'change_default_title' ) );
		add_filter( 'post_updated_messages', array( &$this, 'updated_messages' ) );
	}


	public function add_eventdata_metabox( $post_type ) {
		add_meta_box(
			'el_event_edit_meta',
			__( 'Event data', 'event-list' ),
			array( &$this, 'render_eventdata_metabox' ),
			$post_type,
			'primary',
			'high'
		);
	}


	public function render_eventdata_metabox() {
		global $post;
		if ( $this->is_new && empty( $this->copy_event ) ) {
			// set next day as date
			// next day (86400 seconds = 1*24*60*60 = 1 day);
			$startdate = current_time( 'timestamp' ) + 86400;
			$enddate   = $startdate;
			$starttime = '';
			$location  = '';
		} else {
			// set existing eventdata
			$event     = $this->is_new ? $this->copy_event : new EL_Event( $post );
			$startdate = strtotime( $event->startdate );
			$enddate   = strtotime( $event->enddate );
			$starttime = esc_html( $event->starttime );
			$location  = esc_html( $event->location );
		}
		// Add required data for javascript in a hidden field
		$json = wp_json_encode(
			array(
				'el_date_format'   => $this->datepicker_format( $this->get_event_dateformat() ),
				'el_start_of_week' => get_option( 'start_of_week' ),
				'el_copy_url'      => $this->is_new ? '' : admin_url( add_query_arg( array( 'copy' => $post->ID ), 'post-new.php?post_type=el_events' ) ),
				'el_copy_text'     => $this->is_new ? '' : __(
					'Add Copy',
					'event-list'
				),
			)
		);
		// HTML output (single quotes required for json value due to json layout)
		echo '
				<input type="hidden" id="json_for_js" value=\'' . esc_js( $json ) . '\' />
					<label class="event-option">' . esc_html__( 'Date', 'event-list' ) . ' (' . esc_html__( 'required', 'event-list' ) . '):</label>
					<div class="event-data"><span class="date-wrapper"><input type="text" class="text form-required" name="startdate" id="startdate" value="' .
					esc_html( gmdate( 'Y-m-d', $startdate ) ) . '" /><i class="dashicons dashicons-calendar-alt"></i></span>
						<span id="enddate-area"> - <span class="date-wrapper"><input type="text" class="text" name="enddate" id="enddate" value="' .
						esc_html( gmdate( 'Y-m-d', $enddate ) ) . '" /><i class="dashicons dashicons-calendar-alt"></i></span></span>
						<label class="el-inline-checkbox"><input type="checkbox" name="multiday" id="multiday" value="1" /> ' . esc_html__( 'Multi-Day Event', 'event-list' ) . '</label>
						<input type="hidden" id="startdate-iso" name="startdate-iso" value="" />
						<input type="hidden" id="enddate-iso" name="enddate-iso" value="" />
					</div>
					<label class="event-option">' . esc_html__( 'Time', 'event-list' ) . ':</label>
					<div class="event-data"><input type="text" class="text" name="starttime" id="starttime" value="' . esc_html( $starttime ) . '" /></div>
					<label class="event-option">' . esc_html__( 'Location', 'event-list' ) . ':</label>
					<div class="event-data"><input type="text" class="text" name="location" id="location" value="' . esc_html( $location ) . '" /></div>';
	}


	public function form_top_content() {
		// set post values if an event gets copied
		if ( ! empty( $this->copy_event ) ) {
			global $post;
			$post->post_title   = $this->copy_event->title;
			$post->post_content = $this->copy_event->content;
		}
		// show label for event title
		echo '
			<label class="event-option">' . esc_html__( 'Event Title', 'event-list' ) . ':</label>';
	}


	public function form_after_title_content() {
		global $post, $wp_meta_boxes;
		// create "primary" metabox container, show all "primary" metaboxes in that container and unset the "primary" metaboxes afterwards
		echo '
			<div id="postbox-container-0" class="postbox-container">';
		do_meta_boxes( get_current_screen(), 'primary', $post );
		echo '
			</div>';
		// TODO: Check if get_post_type with argument 'post' is possible and if unsetting the primary metabox is required at all
		// @phan-suppress-next-line PhanTypeMismatchArgument
		unset( $wp_meta_boxes[ get_post_type( 'post' ) ]['primary'] );
		// show label for event content
		echo '
			<label class="event-option">' . esc_html__( 'Event Content', 'event-list' ) . ':</label>';
	}


	public function embed_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'eventlist_admin_new_js', EL_URL . 'admin/js/admin_new.js', array(), '1.0', true );
		wp_localize_jquery_ui_datepicker();
		wp_enqueue_style( 'eventlist_admin_new', EL_URL . 'admin/css/admin_new.css', array(), '1.0' );
		// add the jquery-ui style "smooth" (see https://jqueryui.com/download/) (required for the xwp datepicker skin)
		wp_enqueue_style( 'eventlist_jqueryui', EL_URL . 'admin/css/jquery-ui.min.css', array(), '1.0' );
		// add the xwp datepicker skin (see https://github.com/xwp/wp-jquery-ui-datepicker-skins)
		wp_enqueue_style( 'eventlist_datepicker', EL_URL . 'admin/css/jquery-ui-datepicker.css', array(), '1.0' );
	}


	/**
	 * Safe the event data
	 *
	 * @param int     $pid Post ID
	 * @param WP_Post $post Post Object
	 * @param bool    $update Whether this is an existing post being updated
	 * @return bool|int
	 */
	public function save_eventdata( $pid, $post, $update ) {
		// don't do on autosave or when new posts are first created
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'auto-draft' === $post->post_status ) {
			return $pid;
		}
		$eventdata                  = array();
		$eventdata['startdate']     = isset( $_POST['startdate'] ) ? sanitize_text_field( wp_unslash( $_POST['startdate'] ) ) : '';
		$eventdata['startdate-iso'] = isset( $_POST['startdate-iso'] ) ? sanitize_key( $_POST['startdate-iso'] ) : '';
		$eventdata['enddate']       = isset( $_POST['enddate'] ) ? sanitize_text_field( wp_unslash( $_POST['enddate'] ) ) : '';
		$eventdata['enddate-iso']   = isset( $_POST['enddate-iso'] ) ? sanitize_key( $_POST['enddate-iso'] ) : '';
		$eventdata['starttime']     = isset( $_POST['starttime'] ) ? wp_kses_post( wp_unslash( $_POST['starttime'] ) ) : '';
		$eventdata['location']      = isset( $_POST['location'] ) ? wp_kses_post( wp_unslash( $_POST['location'] ) ) : '';
		$eventdata['multiday']      = isset( $_POST['multiday'] ) ? sanitize_key( $_POST['multiday'] ) : '';
		// provide iso start- and end-date
		if ( ! empty( $eventdata['startdate-iso'] ) ) {
			$eventdata['startdate'] = $eventdata['startdate-iso'];
		}
		if ( ! empty( $eventdata['enddate-iso'] ) ) {
			$eventdata['enddate'] = $eventdata['enddate-iso'];
		}
		// set end_date to start_date if multiday is not selected
		if ( empty( $eventdata['multiday'] ) ) {
			$eventdata['enddate'] = $eventdata['startdate'];
		}
		return (bool) EL_Event::save_postmeta( $pid, $eventdata );
	}


	private function get_event_dateformat() {
		if ( '' === $this->options->get( 'el_edit_dateformat' ) ) {
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			return __( 'Y/m/d' );
		} else {
			return $this->options->get( 'el_edit_dateformat' );
		}
	}


	public function change_default_title( $title ) {
		// Delete default title in text field (not required due to additional lable above the title field)
		return '';
	}


	public function updated_messages( $messages ) {
		// check used get parameters
		$revision = isset( $_GET['revision'] ) ? intval( $_GET['revision'] ) : null;

		global $post, $post_ID;
		$messages['el_events'] = array(
			// Unused. Messages start at index 1.
			0  => '',
			1  => __( 'Event updated.', 'event-list' ) . ' <a href="' . esc_url_raw( get_permalink( $post_ID ) ) . '">' . __( 'View event', 'event-list' ) . '</a>',
			// Custom field updated is not required (no custom fields)
			2  => '',
			// Custom field deleted is not required (no custom fields)
			3  => '',
			4  => __( 'Event updated.', 'event-list' ),
			5  => is_null( $revision ) ? false : sprintf( __( 'Event restored to revision from %1$s', 'event-list' ), wp_post_revision_title( $revision, false ) ),
			6  => __( 'Event published.', 'event-list' ) . ' <a href="' . esc_url_raw( get_permalink( $post_ID ) ) . '">' . __( 'View event', 'event-list' ) . '</a>',
			7  => __( 'Event saved.', 'event-list' ),
			8  => __( 'Event submitted.', 'event-list' ) . ' <a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">' .
				__( 'Preview event', 'event-list' ) . '</a>',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			9  => sprintf( __( 'Event scheduled for: %1$s>', 'event-list' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) . '</strong>' ) .
				' <a target="_blank" href="' . esc_url_raw( get_permalink( $post_ID ) ) . '">' . __( 'Preview event', 'event-list' ) . '</a>',
			10 => __( 'Event draft updated.', 'event-list' ) . ' <a target="_blank" href="' . esc_url_raw( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">' .
				__( 'Preview event', 'event-list' ) . '</a>',
		);
		return $messages;
	}


	public function set_copied_categories( $categories ) {
		if ( empty( $categories ) ) {
			$categories = array_merge( $categories, $this->copy_event->get_category_ids() );
		}
		return $categories;
	}


	/**
	 * Convert a date format to a jQuery UI DatePicker format
	 *
	 * @param string $format a date format
	 * @return string
	 */
	private function datepicker_format( $format ) {
		return str_replace(
			array(
				// Day
				'd',
				'j',
				'l',
				'z',
				// Month
				'F',
				'M',
				'n',
				'm',
				// Year
				'Y',
				'y',
			),
			array(
				// Day
				'dd',
				'd',
				'DD',
				'o',
				// Month
				'MM',
				'M',
				'm',
				'mm',
				// Year
				'yy',
				'y',
			),
			$format
		);
	}

}

