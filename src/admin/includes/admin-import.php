<?php
/**
 * The admin import class
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
 * @phan-file-suppress PhanTypeMismatchArgumentInternal
 * @phan-file-suppress PhanPossiblyNullTypeArgumentInternal
 * @phan-file-suppress PhanPossiblyFalseTypeArgument
 * @phan-file-suppress PhanPossiblyFalseTypeArgumentInternal
 * @phan-file-suppress PhanPossiblyNonClassMethodCall
 * @phan-file-suppress PhanTypePossiblyInvalidDimOffset
 * @phan-file-suppress PhanTypeErrorInInternalCall
 *
 * @package event-list
 */

// cspell:ignore autosavenonce closedpostboxesnonce poststuff sexample shere submitbox submitdelete

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'includes/events_post_type.php';
require_once EL_PATH . 'admin/includes/admin-functions.php';
require_once EL_PATH . 'includes/events.php';
// fix for PHP 5.2 (provide function date_create_from_format defined in daterange.php)
if ( version_compare( PHP_VERSION, '5.3' ) < 0 ) {
	require_once EL_PATH . 'includes/daterange.php';
}

/**
 * This class handles all data for the admin new event page
 */
class EL_Admin_Import {

	private static $instance;

	private $options;

	/**
	 * The event post type
	 *
	 * @var EL_Events_Post_Type
	 */
	private $events_post_type;

	private $functions;

	private $events;

	private $example_file_path;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->options           = &EL_Options::get_instance();
		$this->events_post_type  = &EL_Events_Post_Type::get_instance();
		$this->functions         = &EL_Admin_Functions::get_instance();
		$this->events            = &EL_Events::get_instance();
		$this->example_file_path = EL_URL . 'files/events-import-example.csv';
		$this->add_metaboxes();
	}


	public function show_import() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '
			<div class="wrap">
				<div id="icon-edit-pages" class="icon32"><br /></div>
				<h2>' . esc_html__( 'Import Events', 'event-list' ) . '</h2>';
		if ( isset( $_FILES['el_import_file'] ) ) {
			// Review import
			$this->show_import_review();
		} elseif ( isset( $_POST['reviewed_events'] ) ) {
			// Finish import (add events)
			$import_status = $this->import_events();
			$this->show_import_finished( $import_status );
		} else {
			// Import form
			$this->show_import_form();
		}
		echo '
			</div>';
	}


	private function show_import_form() {
		echo '
				<h3>' . esc_html__( 'Step', 'event-list' ) . ' 1: ' . esc_html__( 'Set import file and options', 'event-list' ) . '</h3>
				<form action="" id="el_import_upload" method="post" enctype="multipart/form-data">';
		$this->functions->show_option_table( 'import' );
		echo '<br />
					<input type="submit" name="button-upload-submit" id="button-upload-submit" class="button" value="' .
					sprintf( esc_html__( 'Proceed with Step %1$s', 'event-list' ), '2' ) . ' &gt;&gt;" />
				</form>
				<br /><br />
				<h3>' . esc_html__( 'Example file', 'event-list' ) . '</h4>
				<p>' . sprintf(
					esc_html__( 'You can download an example file %1$shere%2$s (CSV delimiter is a comma!)', 'event-list' ),
					'<a href="' . esc_url_raw( $this->example_file_path ) . '">',
					'</a>'
				) . '</p>
				<p><em>' . esc_html__( 'Note', 'event-list' ) . ':</em> ' .
					esc_html__( 'Do not change the column header and separator line (first two lines), otherwise the import will fail!', 'event-list' ) . '</p>';
	}


	private function show_import_review() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with the realpath function
		$file_path = isset( $_FILES['el_import_file']['tmp_name'] ) ? realpath( wp_unslash( $_FILES['el_import_file']['tmp_name'] ) ) : '';
		// check for file existence (upload failed?)
		if ( ! is_file( $file_path ) ) {
			echo '<h3>' . esc_html__( 'Sorry, there has been an error.', 'event-list' ) . '</h3>';
			echo esc_html__( 'The file does not exist, please try again.', 'event-list' ) . '</p>';
			return;
		}

		// check for file extension (csv) first
		$file_name = isset( $_FILES['el_import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['el_import_file']['name'] ) ) : '';
		if ( 'csv' !== pathinfo( $file_name, PATHINFO_EXTENSION ) ) {
			echo '<h3>' . esc_html__( 'Sorry, there has been an error.', 'event-list' ) . '</h3>';
			echo esc_html__( 'The uploaded file does not have the required csv extension.', 'event-list' ) . '</p>';
			return;
		}

		// save settings
		$this->save_import_settings();

		// parse file
		$import_data = $this->parse_import_file( $file_path );

		// show heading
		echo '
			<h3>' . esc_html__( 'Step', 'event-list' ) . ' 2: ' . esc_html__( 'Events review and additional category selection', 'event-list' ) . '</h3>';

		// show messages
		// failed parsing
		if ( is_wp_error( $import_data ) ) {
			echo '
				<div class="el-warning">' . esc_html__( 'Error', 'event-list' ) . ': ' . esc_html__( 'This CSV file cannot be imported', 'event-list' ) . ':
					<p>' . esc_html( $import_data->get_error_message() ) . '</p>
				</div>';
			return;
		}

		// failed events
		$num_event_errors = count( array_filter( $import_data, 'is_wp_error' ) );
		if ( ! empty( $num_event_errors ) ) {
			if ( count( $import_data ) === $num_event_errors ) {
				echo '
				<div class="el-warning">' . esc_html__( 'Error', 'event-list' ) . ': ' . esc_html__( 'None of the events in this CSV file can be imported', 'event-list' ) . ':';
			} else {
				echo '
				<div class="el-warning">' . esc_html__( 'Warning', 'event-list' ) . ': ' . esc_html(
					sprintf(
						_n(
							'There is %1$s event which cannot be imported',
							'There are %1$s events which cannot be imported',
							$num_event_errors,
							'event-list'
						),
						$num_event_errors
					)
				) . ':';
			}
			echo '
					<ul class="el-event-errors">';
			foreach ( (array) $import_data as $event ) {
				if ( is_wp_error( $event ) ) {
					echo '<li>' . esc_html( sprintf( __( 'CSV line %1$s', 'event-list' ), $event->get_error_data() ) ) . ': ' . esc_html( $event->get_error_message() ) . '</li>';
				}
			}
			echo '</ul>';
			if ( count( $import_data ) === $num_event_errors ) {
				echo '
				</div>';
				return;
			}
			echo '
					' . esc_html__( 'You can still import all other events listed below.', 'event-list' ) . '
				</div>';
			$import_data = array_filter( $import_data, array( $this, 'is_no_wp_error' ) );
		}

		// missing categories
		$not_available_cats = array();
		foreach ( (array) $import_data as $event ) {
			if ( is_wp_error( $event ) ) {
				continue;
			}
			foreach ( $event['categories'] as $cat ) {
				if ( ! $this->events->cat_exists( $cat ) && ! in_array( $cat, $not_available_cats, true ) ) {
					$not_available_cats[] = $cat;
				}
			}
		}
		if ( ! empty( $not_available_cats ) ) {
			echo '
				<div class="el-warning">' . esc_html__( 'Warning', 'event-list' ) . ': ' .
					esc_html__( 'The following category slugs are not available and will be removed from the imported events', 'event-list' ) . ':
					<ul class="el-categories">';
			foreach ( $not_available_cats as $cat ) {
				echo '<li><code>' . esc_html( $cat ) . '</code></li>';
			}
			echo '</ul>
					' . esc_html__( 'If you want to keep these categories, please create these Categories first and do the import afterwards.', 'event-list' ) . '</div>';
		}
		// event form
		echo '
			<form method="POST" action="' . admin_url( 'edit.php?post_type=el_events&page=el_admin_import' ) . '">';
		wp_nonce_field( 'autosavenonce', 'autosavenonce', false, false );
		wp_nonce_field( 'closedpostboxesnonce', 'closedpostboxesnonce', false, false );
		wp_nonce_field( 'meta-box-order-nonce', 'meta-box-order-nonce', false, false );
		echo '
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">';
		foreach ( (array) $import_data as $event ) {
			$this->show_event( $event );
		}
		echo '
					</div>
					<div id="postbox-container-1" class="postbox-container">';
		do_meta_boxes( 'el-import', 'side', null );
		echo '
					</div>
				</div>
			</div>
			<input type="hidden" name="reviewed_events" id="reviewed_events" value="' . esc_html( wp_json_encode( $import_data ) ) . '" />
			</form>';
	}


	private function is_no_wp_error( $value ) {
		return ! is_wp_error( $value );
	}


	private function show_import_finished( $import_status ) {
		echo '
			<h3>' . esc_html__( 'Step', 'event-list' ) . ' 3: ' . esc_html__( 'Import result', 'event-list' ) . '</h3>';
		if ( empty( $import_status['errors'] ) ) {
			echo '
				<div class="el-success">' . esc_html( sprintf( __( 'Import of %1$s events successful!', 'event-list' ), $import_status['success'] ) ) . '<br />
				<a href="' . admin_url( 'edit.php?post_type=el_events' ) . '">' . esc_html__( 'Go back to All Events', 'event-list' ) . '</a>';
		} else {
			echo '
					<div class="el-warning">' . esc_html__( 'Errors during Import', 'event-list' ) . ':';
			if ( is_wp_error( $import_status['errors'] ) ) {
				echo '
					<p>' . esc_html( $import_status['errors']->get_error_message() ) . '</p>';
			} else {
				echo '
					<ul class="el-event-errors">';
				foreach ( $import_status['errors'] as $error ) {
					echo '<li>' . esc_html__( 'Event from CSV-line', 'event-list' ) . ' ' . esc_html( $error->get_error_data() ) . ': ' . esc_html( $error->get_error_message() ) . '</li>';
				}
			}
			echo '</ul>
				</div>';
		}
	}


	private function show_event( $event ) {
		echo '
				<p>
				<span class="el-event-header">' . esc_html__( 'Title', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( $event['title'] ) . '</span><br />
				<span class="el-event-header">' . esc_html__( 'Start Date', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( $event['startdate'] ) . '</span><br />
				<span class="el-event-header">' . esc_html__( 'End Date', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( $event['enddate'] ) . '</span><br />
				<span class="el-event-header">' . esc_html__( 'Time', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( $event['starttime'] ) . '</span><br />
				<span class="el-event-header">' . esc_html__( 'Location', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( $event['location'] ) . '</span><br />
				<span class="el-event-header">' . esc_html__( 'Content', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( $event['content'] ) . '</span><br />
				<span class="el-event-header">' . esc_html__( 'Category slugs', 'event-list' ) . ':</span> <span class="el-event-data">' . esc_html( implode( ', ', $event['categories'] ) ) . '</span>
				</p>';
	}


	/**
	 * Parse the file to import
	 *
	 * @param string $file_path The file path
	 * @return EL_Event[] | WP_Error | WP_Error[]
	 */
	private function parse_import_file( $file_path ) {
		$delimiter      = ',';
		$header         = array( 'title', 'startdate', 'enddate', 'starttime', 'location', 'content', 'category_slugs' );
		$separator_line = 'sep=,';

		// list of events to import
		$events = array();
		// TODO: Use WP_Filesystem instead of direct PHP filesystem calls
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Using WP_Filesystem not implemented yet
		$file_handle = fopen( $file_path, 'r' );
		$event_lines = -1;
		$empty_lines = 0;
		while ( ! feof( $file_handle ) ) {
			// get line
			$line = fgetcsv( $file_handle, 0, $delimiter );
			// prepare line: trim elements and force an array
			$line = is_array( $line ) ? array_map( 'trim', $line ) : array( trim( $line ) );

			// skip empty lines
			if ( ! array_filter( $line ) ) {
				++$empty_lines;
				continue;
			}
			// check header
			if ( 0 > $event_lines ) {
				if ( $line[0] === $separator_line ) {
					// check optional separator line
					++$empty_lines;
					continue;
				} elseif ( $header === $line || array_slice( $header, 0, -1 ) === $line ) {
					// check header line
					++$event_lines;
					continue;
				} else {
					return new WP_Error(
						'missing_header',
						__( 'Header line is missing or not correct!', 'event-list' ) . '<br />'
						. sprintf( __( 'Have a look at the %1$sexample file%2$s to see the correct header line format.', 'event-list' ), '<a href="' . $this->example_file_path . '">', '</a>' )
					);
				}
			}
			++$event_lines;
			// check correct number of items in line
			if ( 6 > count( $line ) || 7 < count( $line ) ) {
				$events[] = new WP_Error(
					'wrong_number_line_items',
					sprintf( __( 'Wrong number of items in line (%1$s items found, 6-7 required)', 'event-list' ), count( $line ) ),
					$event_lines + $empty_lines + 1
				);
				continue;
			}
			// check and prepare event data
			$eventdata = array(
				'csv_line'   => $event_lines + $empty_lines + 1,
				'title'      => $line[0],
				'startdate'  => $line[1],
				'enddate'    => $line[2],
				'starttime'  => $line[3],
				'location'   => $line[4],
				'content'    => $line[5],
				'categories' => isset( $line[6] ) ? explode( '|', $line[6] ) : array(),
			);
			$event     = $this->prepare_event( $eventdata, $this->options->get( 'el_import_date_format' ) );
			// add event
			$events[] = $event;
		}
		// close file
		// TODO: Use WP_Filesystem instead of direct PHP filesystem calls
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Using WP_Filesystem not implemented yet
		fclose( $file_handle );
		return $events;
	}


	private function trim_event_fields( &$value ) {
		if ( is_array( $value ) ) {
			$value = array_map( 'trim', $value );
		} else {
			$value = trim( $value );
		}
	}


	private function save_import_settings() {
		foreach ( $this->options->options as $oname => $o ) {
			// check used post parameters
			$option_value = isset( $_POST[ $oname ] ) ? sanitize_text_field( wp_unslash( $_POST[ $oname ] ) ) : '';

			if ( 'import' === $o['section'] && ! empty( $option_value ) ) {
				$this->options->set( $oname, $option_value );
			}
		}
	}


	public function add_metaboxes() {
		add_meta_box( 'event-publish', __( 'Import events', 'event-list' ), array( &$this, 'render_publish_metabox' ), 'el-import', 'side' );
		add_meta_box( 'event-categories', __( 'Add additional categories', 'event-list' ), array( &$this, 'render_category_metabox' ), 'el-import', 'side' );
	}


	public function render_publish_metabox() {
		// phpcs:disable WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
		echo '
			<div class="submitbox">
				<div id="delete-action"><a href="?page=el_admin_main" class="submitdelete deletion">' . esc_html__( 'Cancel' ) . '</a></div>
				<div id="publishing-action"><input type="submit" class="button button-primary button-large" name="import" value="' . esc_html__( 'Import' ) . '" id="import"></div>
				<div class="clear"></div>
			</div>';
		// phpcs:enable
	}


	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Parameter $post not used
	public function render_category_metabox( $_post, $metabox ) {
		// @phan-suppress-next-line PhanMissingRequireFile  This file is available in WordPress
		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
		$default_post = get_default_post_to_edit( 'el-events' );
		$box          = array( 'args' => array( 'taxonomy' => $this->events_post_type->taxonomy ) );
		post_categories_meta_box( $default_post, $box );
	}


	private function import_events() {
		// check used post parameters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The eventdata is sanitized in prepare_event() function
		$reviewed_events = isset( $_POST['reviewed_events'] ) ? json_decode( wp_unslash( $_POST['reviewed_events'] ), true ) : array();
		if ( empty( $reviewed_events ) ) {
			return new WP_Error( 'no_events', __( 'No events found', 'event-list' ) );
		}
		// prepare additional categories
		if ( $this->events_post_type->event_cat_taxonomy === $this->events_post_type->taxonomy ) {
			$additional_cat_ids = isset( $_POST['tax_input'][ $this->events_post_type->taxonomy ] ) ?
				array_map( 'intval', (array) wp_unslash( $_POST['tax_input'][ $this->events_post_type->taxonomy ] ) ) :
				array();
		} else {
			$additional_cat_ids = isset( $_POST[ 'post_' . $this->events_post_type->taxonomy ] ) ?
				array_map( 'intval', (array) wp_unslash( $_POST[ 'post_' . $this->events_post_type->taxonomy ] ) ) :
				array();
		}
		$additional_cats = array();
		foreach ( $additional_cat_ids as $cat_id ) {
			$cat = $this->events->get_cat_by_id( $cat_id );
			if ( ! empty( $cat ) ) {
				$additional_cats[] = $cat->slug;
			}
		}
		// save events
		require_once EL_PATH . 'includes/event.php';
		$ret = array(
			'success' => 0,
			'errors'  => array(),
		);
		foreach ( $reviewed_events as $eventdata_raw ) {
			// prepare the event categories
			$cats = array();
			foreach ( $eventdata_raw['categories'] as $cat_slug ) {
				$cat_slug = sanitize_title( $cat_slug );
				if ( $this->events->cat_exists( $cat_slug ) ) {
					$cats[] = $cat_slug;
				}
			}
			// add the additionally specified categories
			if ( ! empty( $additional_cats ) ) {
				$cats = array_unique( array_merge( $cats, $additional_cats ) );
			}
			$eventdata_raw['categories'] = $cats;

			$eventdata = $this->prepare_event( $eventdata_raw );
			if ( is_wp_error( $eventdata ) ) {
				$ret['errors'][] = $eventdata;
				continue;
			}
			// TODO: return WP_Error instead of false in EL_Event when saving fails
			$event = EL_Event::save( $eventdata );
			if ( ! $event instanceof EL_Event ) {
				$ret['errors'][] = new WP_Error( 'failed_saving', __( 'Saving of event failed!', 'event-list' ), $eventdata['csv-line'] );
				continue;
			}
			$ret['success'] += 1;
		}
		return $ret;
	}


	private function prepare_event( $eventdata_raw, $date_format = false ) {
		// trim all fields
		array_walk( $eventdata_raw, array( $this, 'trim_event_fields' ) );
		$eventdata = array();
		// prepare csv_line
		$eventdata['csv-line'] = isset( $eventdata_raw['csv-line'] ) ? strval( $eventdata_raw['csv-line'] ) : '';
		// title
		$eventdata['title'] = isset( $eventdata_raw['title'] ) ? sanitize_text_field( $eventdata_raw['title'] ) : '';
		if ( empty( $eventdata['title'] ) ) {
			return new WP_Error( 'empty_title', __( 'Empty event title found', 'event-list' ), $eventdata['csv-line'] );
		}
		// startdate
		$eventdata['startdate'] = isset( $eventdata_raw['startdate'] ) ? $this->prepare_date( $eventdata_raw['startdate'], $date_format ) : '';
		if ( empty( $eventdata['startdate'] ) ) {
			return new WP_Error( 'wrong_startdate', __( 'Wrong date format for startdate', 'event-list' ), $eventdata['csv-line'] );
		}
		// enddate
		if ( empty( $eventdata_raw['enddate'] ) ) {
			$eventdata['enddate'] = $eventdata['startdate'];
		} else {
			$eventdata['enddate'] = $this->prepare_date( $eventdata_raw['enddate'], $date_format );
			if ( empty( $eventdata['enddate'] ) ) {
				return new WP_Error( 'wrong_enddate', __( 'Wrong date format for enddate', 'event-list' ), $eventdata['csv-line'] );
			}
		}
		// starttime
		$eventdata['starttime'] = isset( $eventdata_raw['starttime'] ) ? wp_kses_post( $eventdata_raw['starttime'] ) : '';
		// location
		$eventdata['location'] = isset( $eventdata_raw['location'] ) ? wp_kses_post( $eventdata_raw['location'] ) : '';
		// content
		$eventdata['content'] = isset( $eventdata_raw['content'] ) ? wp_kses_post( $eventdata_raw['content'] ) : '';
		// categories
		$eventdata['categories'] = array_map( 'sanitize_title', $eventdata_raw['categories'] );
		return $eventdata;
	}


	private function prepare_date( $date_string, $date_format ) {
		$auto_detect = true;
		if ( empty( $date_format ) ) {
			$date_format = 'Y-m-d';
			$auto_detect = false;
		}
		// create date from given format
		// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.date_create_from_formatFound -- A custom function is created in daterange.php if it does not exist
		$date = date_create_from_format( $date_format, sanitize_text_field( wp_unslash( $date_string ) ) );
		if ( ! $date instanceof DateTime ) {
			// try automatic date detection
			if ( $auto_detect ) {
				$date = date_create( $date_string );
			}
			if ( ! $date instanceof DateTime ) {
				return '';
			}
		}
		return $date->format( 'Y-m-d' );
	}


	public function embed_import_scripts() {
		wp_enqueue_style( 'eventlist_admin_import', EL_URL . 'admin/css/admin_import.css', array(), '1.0' );
	}

}
