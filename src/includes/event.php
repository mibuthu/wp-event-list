<?php
/**
 * The Event class
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPublicProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginNoCommentOnPrivateMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 * @phan-file-suppress PhanPartialTypeMismatchArgumentInternal
 * @phan-file-suppress PhanTypeMismatchProperty
 * @phan-file-suppress PhanPossiblyUndeclaredProperty
 * @phan-file-suppress PhanPluginDuplicateConditionalNullCoalescing
 * @phan-file-suppress PhanPossiblyFalseTypeArgumentInternal
 * @phan-file-suppress PhanTypeArraySuspiciousNullable
 *
 * @package event-list
 */

// TODO: Fix phpcs warnings to remove the disabled checks
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable PHPCompatibility.ParameterValues.NewIconvMbstringCharsetDefault.NotSet

if ( ! defined( 'WPINC' ) ) {
	exit;
}

require_once EL_PATH . 'includes/events_post_type.php';
// fix for PHP 5.2 (provide function date_create_from_format defined in daterange.php)
if ( version_compare( PHP_VERSION, '5.3' ) < 0 ) {
	require_once EL_PATH . 'includes/daterange.php';
}

/**
 * The Event class
 */
class EL_Event {

	/**
	 * The event post type
	 *
	 * @var EL_Events_Post_Type
	 */
	private $events_post_type;

	public $post;

	public $categories;

	public $title = '';

	public $startdate = '0000-00-00';

	public $enddate = '0000-00-00';

	public $starttime = '';

	public $location = '';

	public $excerpt = '';

	public $content = '';


	public function __construct( $post ) {
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		if ( $post instanceof WP_Post ) {
			$this->post = $post;
		} else {
			$this->post = get_post( $post );
			if ( null === $this->post ) {
				return;
			}
		}
		$this->load_eventdata();
	}


	private function load_eventdata() {
		$this->title   = $this->post->post_title;
		$this->excerpt = $this->post->post_excerpt;
		$this->content = $this->post->post_content;
		$postmeta      = get_post_meta( $this->post->ID );
		foreach ( array( 'startdate', 'enddate', 'starttime', 'location' ) as $meta ) {
			$this->$meta = isset( $postmeta[ $meta ][0] ) ? $postmeta[ $meta ][0] : '';
		}
		$this->categories = get_the_terms( $this->post, $this->events_post_type->taxonomy );
		if ( ! is_array( $this->categories ) ) {
			$this->categories = array();
		}
		return true;
	}


	public static function save( $eventdata ) {
		// create new post
		$postdata = array(
			'post_type'    => 'el_events',
			'post_status'  => 'publish',
			'post_title'   => $eventdata['title'],
			'post_content' => $eventdata['content'],
		);
		if ( isset( $eventdata['excerpt'] ) ) {
			$postdata['post_excerpt'] = $eventdata['excerpt'];
		}
		if ( isset( $eventdata['slug'] ) ) {
			$postdata['post_name'] = $eventdata['slug'];
		}
		if ( isset( $eventdata['post_date'] ) ) {
			$postdata['post_date'] = $eventdata['post_date'];
		}
		if ( isset( $eventdata['post_user'] ) ) {
			$postdata['post_user'] = $eventdata['post_user'];
		}

		$pid = wp_insert_post( $postdata );
		// set categories
		$cats = self::set_categories( $pid, $eventdata['categories'] );
		// save postmeta (created event instance)
		if ( ! empty( $pid ) ) {
			$post = self::save_postmeta( $pid, $eventdata );
			return $post;
		} else {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $pid ) );
		}
		return false;
	}


	/**
	 * Create or update the event data (all event data except title and content)
	 *
	 * @param  int                  $pid       The post id of the event to update.
	 * @param  array<string,string> $eventdata The event data provided in an array where the key is the event field and the value is the corresponding data.
	 *                                         The provided data does not have to be sanitized from the user imputs because this is done in the function.
	 * @return self|false
	 */
	public static function save_postmeta( $pid, $eventdata ) {
		global $el_event_errors;
		$instance = new self( $pid );

		// Sanitize event data (event data will be provided without sanitation of user input)
		$eventdata['startdate'] = empty( $eventdata['startdate'] ) ? '' : preg_replace( '/[^0-9\-]/', '', $eventdata['startdate'] );
		$eventdata['enddate']   = empty( $eventdata['enddate'] ) ? '' : preg_replace( '/[^0-9\-]/', '', $eventdata['enddate'] );
		$eventdata['starttime'] = empty( $eventdata['starttime'] ) ? '' : wp_kses_post( $eventdata['starttime'] );
		$eventdata['location']  = empty( $eventdata['location'] ) ? '' : wp_kses_post( $eventdata['location'] );

		// startdate
		$instance->startdate = $instance->validate_date( $eventdata['startdate'] );
		if ( empty( $instance->startdate ) ) {
			$el_event_errors[] = __( 'No valid start date provided', 'event-list' );
		}
		// enddate
		$instance->enddate = $instance->validate_date( $eventdata['enddate'] );
		// @phan-suppress-next-line PhanPluginComparisonObjectOrdering  Comparing DateTime instances is allowed
		if ( empty( $instance->enddate ) || new DateTime( $instance->enddate ) < new DateTime( $instance->startdate ) ) {
			$instance->enddate = $instance->startdate;
		}
		// time
		$instance->starttime = $instance->validate_time( $eventdata['starttime'] );
		// location
		$instance->location = stripslashes( $eventdata['location'] );

		// update all data
		foreach ( array( 'startdate', 'enddate', 'starttime', 'location' ) as $meta ) {
			update_post_meta( $pid, $meta, $instance->$meta );
		}
		// error handling: set event back to pending, and publish error message
		if ( ! empty( $el_event_errors ) ) {
			// TODO: Check the comment: "if((isset($_POST['publish']) || isset( $_POST['save'] ) ) && $_POST['post_status'] == 'publish' )"
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $pid ) );
			add_filter( 'redirect_post_location', array( &$instance, 'save_metadata_redirect_post_location_filter' ) );
			unset( $instance );
			return false;
		}
		return $instance;
	}


	private function save_metadata_redirect_post_location_filter( $location ) {
		global $el_event_errors;
		$location = add_query_arg( "'.implode('<br />', $el_event_errors).'", '4', $location );
		unset( $el_event_errors );
		return $location;
	}


	private static function set_categories( $pid, $cats ) {
		return wp_set_object_terms( $pid, $cats, EL_Events_Post_Type::get_instance()->taxonomy );
	}


	public function starttime_i18n() {
		$timestamp = strtotime( $this->starttime );
		if ( $timestamp ) {
			return date_i18n( get_option( 'time_format' ), $timestamp );
		}
		return $this->starttime;
	}


	/**
	 * Validate a given date string
	 *
	 * @param string $datestring The date string to validate
	 * @return false|string
	 *
	 * @suppress PhanDeprecatedFunctionInternal
	 */
	private function validate_date( $datestring ) {
		// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.date_create_from_formatFound
		$d = date_create_from_format( 'Y-m-d', $datestring );
		if ( $d && $d->format( 'Y-m-d' ) === $datestring
				&& 1970 <= $d->format( 'Y' )
				&& 2999 >= $d->format( 'Y' ) ) {
			return $datestring;
		}
		return false;
	}


	public static function validate_time( $timestring ) {
		// Try to extract a correct time from the provided text
		$timestamp = strtotime( stripslashes( $timestring ) );
		// Return a standard time format if the conversion was successful
		if ( $timestamp ) {
			return gmdate( 'H:i:s', $timestamp );
		}
		// Else return the given text
		return $timestring;
	}


	public function get_category_ids() {
		return $this->get_category_fields( 'term_id' );
	}


	public function get_category_slugs() {
		return $this->get_category_fields( 'slug' );
	}


	public function get_category_names() {
		return $this->get_category_fields( 'name' );
	}


	private function get_category_fields( $field ) {
		$list = wp_list_pluck( $this->categories, $field );
		if ( ! is_array( $list ) ) {
			$list = array();
		}
		return $list;
	}


	/**
	 * Truncate HTML, close opened tags
	 *
	 * @param string       $html          The html code which should be shortened.
	 * @param int          $length        The length (number of characters) to which the text will be shortened.
	 *                                    With [0] the full text will be returned. With [auto] also the complete text will be used,
	 *                                    but a wrapper div will be added which shortens the text to 1 full line via css.
	 * @param bool         $skip          If this value is true the truncate will be skipped (nothing will be done).
	 * @param bool         $preserve_tags Specifies if html tags should be preserved or if only the text should be shortened.
	 * @param false|string $link          If an url is given a link to the given url will be added for the ellipsis at the end of the truncated text.
	 */
	public function truncate( $html, $length, $skip = false, $preserve_tags = true, $link = false ) {
		mb_internal_encoding( 'UTF-8' );
		if ( 'auto' === $length ) {
			// add wrapper div with css styles for css truncate and return
			return '<div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">' . $html . '</div>';
		} elseif ( empty( $length ) || mb_strlen( $html ) <= $length || $skip ) {
			// do nothing
			return $html;
		} elseif ( ! $preserve_tags ) {
			// only shorten the text
			return mb_substr( $html, 0, $length );
		} else {
			// truncate with preserving html tags
			$truncated      = false;
			$printed_length = 0;
			$position       = 0;
			$tags           = array();
			$out            = '';
			while ( $printed_length < $length && $this->mb_preg_match( '{</?([a-z]+\d?)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position ) ) {
				list($tag, $tag_position) = $match[0];
				// Print text leading up to the tag
				$str = mb_substr( $html, $position, $tag_position - $position );
				if ( $printed_length + mb_strlen( $str ) > $length ) {
					$out           .= mb_substr( $str, 0, $length - $printed_length );
					$printed_length = $length;
					$truncated      = true;
					break;
				}
				$out            .= $str;
				$printed_length += mb_strlen( $str );
				if ( '&' === $tag[0] ) {
					// Handle the entity
					$out .= $tag;
					$printed_length++;
				} else {
					// Handle the tag
					$tag_name = $match[1][0];
					if ( $this->mb_preg_match( '{^</}', $tag ) ) {
						// This is a closing tag
						$opening_tag = array_pop( $tags );
						if ( $opening_tag !== $tag_name ) {
							// Not properly nested tag found: trigger a warning and add the not matching opening tag again
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
							trigger_error( 'Not properly nested tag found (last opening tag: ' . $opening_tag . ', closing tag: ' . $tag_name . ')', E_USER_NOTICE );
							$tags[] = $opening_tag;
						} else {
							$out .= $tag;
						}
					} elseif ( $this->mb_preg_match( '{/\s*>$}', $tag ) ) {
						// Self-closing tag
						$out .= $tag;
					} else {
						// Opening tag
						$out   .= $tag;
						$tags[] = $tag_name;
					}
				}
				// Continue after the tag
				$position = $tag_position + mb_strlen( $tag );
			}
			// Print any remaining text
			if ( $printed_length < $length && $position < mb_strlen( $html ) ) {
				$out .= mb_substr( $html, $position, $length - $printed_length );
			}
			// Print ellipsis ("...") if the html was truncated.
			if ( $truncated ) {
				if ( is_string( $link ) ) {
					$out .= ' <a href="' . $link . '"> [' . __( 'read more', 'event-list' ) . '&hellip;]</a>';
				} else {
					$out .= ' [' . __( 'read more', 'event-list' ) . '&hellip;]';
				}
			}
			// Close any open tags.
			while ( ! empty( $tags ) ) {
				$out .= '</' . array_pop( $tags ) . '>';
			}
			return $out;
		}
	}


	private function mb_preg_match( $ps_pattern, $ps_subject, &$pa_matches = null, $pn_flags = 0, $pn_offset = 0, $ps_encoding = null ) {
		// WARNING! - All this function does is to correct offsets, nothing else:
		// (code is independent of PREG_PATTER_ORDER / PREG_SET_ORDER)
		if ( is_null( $ps_encoding ) ) {
			$ps_encoding = mb_internal_encoding();
		}
		$pn_offset = strlen( mb_substr( $ps_subject, 0, $pn_offset, $ps_encoding ) );
		$out       = preg_match( $ps_pattern, $ps_subject, $pa_matches, $pn_flags, $pn_offset );
		if ( $out && ( $pn_flags & PREG_OFFSET_CAPTURE ) ) {
			foreach ( $pa_matches as &$ha_match ) {
				$ha_match[1] = mb_strlen( substr( $ps_subject, 0, $ha_match[1] ), $ps_encoding );
			}
		}
		return $out;
	}

}
