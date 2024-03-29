<?php
/**
 * The RSS class
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginNoCommentOnPrivateMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPossiblyNullTypeArgument
 * @phan-file-suppress PhanPossiblyFalseTypeArgument
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 *
 * @package event-list
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';
require_once EL_PATH . 'includes/events.php';

/**
 * This class handles rss feeds
 */
class EL_Rss {

	private static $instance;

	private $options;

	private $events;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->events  = EL_Events::get_instance();
		$this->options = EL_Options::get_instance();
		$this->init();
	}


	public function init() {
		add_feed( $this->options->get( 'el_feed_rss_name' ), array( &$this, 'print_rss' ) );
		add_action( 'wp_head', array( &$this, 'print_head_feed_link' ) );
	}


	public function print_head_feed_link() {
		echo '
<link rel="alternate" type="application/rss+xml" title="' . esc_attr( get_bloginfo_rss( 'name' ) ) . ' &raquo; ' . esc_attr( $this->options->get( 'el_feed_rss_description' ) ) .
		'" href="' . esc_url_raw( $this->feed_url() ) . '" />';
	}


	public function print_rss() {
		// @phan-suppress-next-line PhanTypeVoidArgument  Missing @return in WordPress 5.9 source code for feed_content_type function
		header( 'Content-Type: ' . strval( feed_content_type( 'rss-http' ) ) . '; charset=' . get_option( 'blog_charset' ), true );
		$options = array(
			'date_filter' => $this->options->get( 'el_feed_rss_upcoming_only' ) ? 'upcoming' : null,
			'order'       => array( 'startdate DESC', 'starttime DESC', 'enddate DESC' ),
		);
		$events  = $this->events->get( $options );

		// Print RSS
		echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . '"?>
	<rss version="2.0"
		xmlns:content="http://purl.org/rss/1.0/modules/content/"
		xmlns:wfw="http://wellformedweb.org/CommentAPI/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
		xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	>
		<channel>
			<title>' . esc_html( get_bloginfo_rss( 'name' ) ) . '</title>
			<atom:link href="' . esc_url_raw( apply_filters( 'self_link', get_bloginfo() ) ) . '" rel="self" type="application/rss+xml" />
			<link>' . esc_url_raw( get_bloginfo_rss( 'url' ) ) . '</link>
			<description>' . esc_html( $this->options->get( 'el_feed_rss_description' ) ) . '</description>
			<lastBuildDate>' . esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ) . '</lastBuildDate>
			<language>' . esc_attr( get_option( 'rss_language' ) ) . '</language>
			<sy:updatePeriod>' . esc_attr( apply_filters( 'rss_update_period', 'hourly' ) ) . '</sy:updatePeriod>
			<sy:updateFrequency>' . esc_attr( apply_filters( 'rss_update_frequency', '1' ) ) . '</sy:updateFrequency>
			';
		do_action( 'rss2_head' );
		if ( ! empty( $events ) ) {
			foreach ( $events as $event ) {
				echo '
			<item>
				<title>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No escaping required for format_date
				$this->format_date( $event->startdate, $event->enddate ) . ' - ' . esc_html( $event->title ),
				'</title>
				<pubDate>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No escaping required for this date format
				mysql2date( 'D, d M Y H:i:s +0000', $event->startdate, false ),
				'</pubDate>';
				foreach ( (array) $event->categories as $cat ) {
					echo '
				<category>' . esc_attr( $cat->name ) . '</category>';
				}
				$timetext     = empty( $event->starttime ) ? '' :
					__( 'Time', 'event-list' ) . ': ' . $this->format_date( $event->startdate, $event->enddate ) . ' ' . wp_kses_post( $event->starttime ) . '<br />
					';
				$locationtext = empty( $event->location ) ? '' :
					__( 'Location', 'event-list' ) . ': ' . wp_kses_post( $event->location ) . '<br />
					<br />
					';
				if ( ! empty( $timetext ) && empty( $locationtext ) ) {
					$timetext .= '<br />
					';
				}
				echo '
				<description>
					' . esc_html( $timetext . $locationtext . do_shortcode( $event->content ) ) . '
				</description>';
				echo '
			</item>';
			}
		}
		echo '
		</channel>
	</rss>';
	}


	public function feed_url() {
		if ( get_option( 'permalink_structure' ) ) {
			$feed_link = get_bloginfo( 'url' ) . '/feed/';
		} else {
			$feed_link = get_bloginfo( 'url' ) . '/?feed=';
		}
		return $feed_link . $this->options->get( 'el_feed_rss_name' );
	}


	public function update_rewrite_status() {
		$feeds               = array_keys( (array) get_option( 'rewrite_rules' ), 'index.php?&feed=$matches[1]', true );
		$feed_rewrite_status = 0 < count( preg_grep( '@[(\|]' . $this->options->get( 'el_feed_rss_name' ) . '[\|)]@', $feeds ) );
		if ( '1' === $this->options->get( 'el_feed_enable_rss' ) && ! $feed_rewrite_status ) {
			// add eventlist RSS feed to rewrite rules
			flush_rewrite_rules( false );
		} elseif ( '1' !== $this->options->get( 'el_feed_enable_rss' ) && $feed_rewrite_status ) {
			// remove eventlist RSS feed from rewrite rules
			flush_rewrite_rules( false );
		}
	}


	private function format_date( $startdate, $enddate ) {
		$start_array = explode( '-', $startdate );
		$startdate   = intval( mktime( 0, 0, 0, intval( $start_array[1] ), intval( $start_array[2] ), intval( $start_array[0] ) ) );

		$end_array = explode( '-', $enddate );
		$enddate   = intval( mktime( 0, 0, 0, intval( $end_array[1] ), intval( $end_array[2] ), intval( $end_array[0] ) ) );

		$eventdate = '';

		if ( $startdate === $enddate ) {
			if ( '00' === $start_array[2] ) {
				$startdate  = intval( mktime( 0, 0, 0, intval( $start_array[1] ), 15, intval( $start_array[0] ) ) );
				$eventdate .= gmdate( 'F, Y', $startdate );
				return $eventdate;
			}
			$eventdate .= gmdate( 'M j, Y', $startdate );
			return $eventdate;
		}

		if ( $start_array[0] === $end_array[0] ) {
			if ( $start_array[1] === $end_array[1] ) {
				$eventdate .= gmdate( 'M j', $startdate ) . '-' . gmdate( 'j, Y', $enddate );
				return $eventdate;
			}
			$eventdate .= gmdate( 'M j', $startdate ) . '-' . gmdate( 'M j, Y', $enddate );
			return $eventdate;
		}

		$eventdate .= gmdate( 'M j, Y', $startdate ) . '-' . gmdate( 'M j, Y', $enddate );
		return $eventdate;
	}

}

