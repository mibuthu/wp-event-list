<?php
/**
 * The filterbar class
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginNoCommentOnPrivateMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPartialTypeMismatchArgumentInternal
 * @phan-file-suppress PhanPossiblyUndeclaredProperty
 * @phan-file-suppress PhanPossiblyFalseTypeArgumentInternal
 *
 * @package event-list
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

require_once EL_PATH . 'includes/events.php';

/**
 * This class handles the navigation and filter bar
 */
class EL_Filterbar {

	private static $instance;

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
		$this->events = &EL_Events::get_instance();
	}


	/**
	 * Main function to show the rendered HTML output
	 *
	 * @param string               $url The url for links
	 * @param array<string,string> $args The filerbar arguments
	 * @return string
	 */
	public function show( $url, &$args ) {
		$this->parse_args( $args );
		$out = '
				<style type="text/css">
					.filterbar { display:table; width:100% }
					.filterbar > div { display:table-cell }
				</style>
				<!--[if lte IE 7]>
				<style>.filterbar > div { float:left }</style>
				<![endif]-->
				<div class="filterbar subsubsub">';
		// prepare filterbar-items
		// split 3 section (left, center, right) seperated by semicolon
		$sections      = array_slice( explode( ';', html_entity_decode( $args['filterbar_items'] ) ), 0, 3 );
		$section_align = array( 'left', 'center', 'right' );
		$num_sections  = count( $sections );
		for ( $i = 0; $i < $num_sections; $i++ ) {
			if ( ! empty( $sections[ $i ] ) ) {
				$out .= '
					<div style="text-align:' . esc_attr( $section_align[ $i ] ) . '">';
				// split items in section seperated by comma
				$items = explode( ',', $sections[ $i ] );
				foreach ( $items as $item ) {
					// search for item options
					$options    = array();
					$item_array = explode( '(', $item );
					if ( count( $item_array ) > 1 ) {
						// options available
						$option_array = explode( '|', substr( $item_array[1], 0, -1 ) );
						foreach ( $option_array as $option_text ) {
							$o                = explode( '=', $option_text );
							$options[ $o[0] ] = $o[1];
						}
					}
					list( $filter_type, $display_type ) = explode( '_', $item_array[0] );
					switch ( $filter_type ) {
						case 'years':
							$out .= $this->show_years( $url, $args, $display_type, $options );
							break;
						case 'daterange':
							$out .= $this->show_daterange( $url, $args, $display_type, $options );
							break;
						case 'cats':
							$out .= $this->show_cats( $url, $args, $display_type, $options );
							break;
						case 'months':
							$out .= $this->show_months( $url, $args, $display_type, $options );
							break;
						case 'reset':
							$out .= $this->show_reset( $url, $args, $options );
					}
				}
				$out .= '
					</div>';
			}
		}
		$out .= '</div>';
		return $out;
	}


	public function show_years( $url, &$args, $type = 'hlist', $options = array() ) {
		$default_args    = array(
			'date_filter'   => array(),
			'cat_filter'    => array(),
			'sc_id_for_url' => false,
			'selected_date' => false,
		);
		$args            = wp_parse_args( $args, $default_args );
		$default_options = array(
			'show_all'      => 'true',
			'show_upcoming' => 'true',
			'show_past'     => 'false',
			'years_order'   => 'asc',
		);
		$options         = wp_parse_args( $options, $default_options );
		// add args['order'] (required in $this->events->get_filter_list options)
		$args['order'] = $options['years_order'];
		// prepare displayed elements
		$elements = array();
		if ( 'true' === $options['show_all'] ) {
			$elements[] = $this->all_element( 'date', $type );
		}
		if ( 'true' === $options['show_upcoming'] ) {
			$elements[] = $this->upcoming_element();
		}
		if ( 'true' === $options['show_past'] ) {
			$elements[] = $this->past_element();
		}
		$event_years = $this->events->get_filter_list( 'years', $args );
		foreach ( $event_years as $entry ) {
			$elements[] = array(
				'slug' => $entry,
				'name' => $entry,
			);
		}
		// display elements
		if ( 'dropdown' === $type ) {
			return $this->show_dropdown( $elements, 'date' . $args['sc_id_for_url'], $args['selected_date'], $args['sc_id_for_url'] );
		} else {
			return $this->show_hlist( $elements, $url, 'date' . $args['sc_id_for_url'], $args['selected_date'] );
		}
	}


	public function show_months( $url, &$args, $type = 'dropdown', $options = array() ) {
		$default_options = array(
			'show_all'      => 'false',
			'show_upcoming' => 'false',
			'show_past'     => 'false',
			'months_order'  => 'asc',
			'date_format'   => 'Y-m',
		);
		$options         = wp_parse_args( $options, $default_options );
		// add args['order'] (required in $this->events->get_filter_list options)
		$args['order'] = $options['months_order'];
		// prepare displayed elements
		$elements = array();
		if ( 'true' === $options['show_all'] ) {
			$elements[] = $this->all_element( 'date', $type );
		}
		if ( 'true' === $options['show_upcoming'] ) {
			$elements[] = $this->upcoming_element();
		}
		if ( 'true' === $options['show_past'] ) {
			$elements[] = $this->past_element();
		}
		$event_months = $this->events->get_filter_list( 'months', $args );
		foreach ( $event_months as $entry ) {
			list($year, $month) = explode( '-', $entry );
			$elements[]         = array(
				'slug' => $entry,
				'name' => gmdate( $options['date_format'], mktime( 0, 0, 0, intval( $month ), 1, intval( $year ) ) ),
			);
		}
		// display elements
		if ( 'hlist' === $type ) {
			return $this->show_hlist( $elements, $url, 'date' . $args['sc_id_for_url'], $args['selected_date'] );
		} else {
			return $this->show_dropdown( $elements, 'date' . $args['sc_id_for_url'], $args['selected_date'], $args['sc_id_for_url'] );
		}
	}


	public function show_daterange( $url, &$args, $type = 'hlist', $options = array() ) {
		// prepare displayed elements
		if ( isset( $options['item_order'] ) ) {
			$items = explode( '&', $options['item_order'] );
		} else {
			$items = array( 'all', 'upcoming', 'past' );
		}
		$elements = array();
		foreach ( $items as $item ) {
			// show all
			switch ( $item ) {
				case 'all':
					$elements[] = $this->all_element( 'date' );
					// Always show short form ... hlist
					break;
				case 'upcoming':
					$elements[] = $this->upcoming_element();
					break;
				case 'past':
					$elements[] = $this->past_element();
			}
		}
		// display elements
		if ( 'dropdown' === $type ) {
			return $this->show_dropdown( $elements, 'date' . $args['sc_id_for_url'], $args['selected_date'], $args['sc_id_for_url'] );
		} else {
			return $this->show_hlist( $elements, $url, 'date' . $args['sc_id_for_url'], $args['selected_date'] );
		}
	}


	public function show_cats( $url, &$args, $type = 'dropdown', $options = array() ) {
		$default_args    = array(
			'date_filter'   => array(),
			'cat_filter'    => array(),
			'sc_id_for_url' => false,
			'selected_cats' => false,
		);
		$args            = wp_parse_args( $args, $default_args );
		$default_options = array(
			'show_all' => 'true',
		);
		$options         = wp_parse_args( $options, $default_options );
		// add arg 'cat_data' to receive all required data
		$args['cat_data']     = 'all';
		$args['hierarchical'] = true;
		// prepare displayed elements
		$elements = array();
		if ( 'true' === $options['show_all'] ) {
			$elements[] = $this->all_element( 'cat', $type );
		}
		// create elements array
		$cat_array = $this->events->get_filter_list( 'categories', $args );
		foreach ( $cat_array as $cat ) {
			$elements[] = array(
				'slug' => $cat->slug,
				'name' => str_repeat( '&nbsp;', 3 * $cat->level ) . $cat->name,
			);
		}
		// display elements
		if ( 'hlist' === $type ) {
			return $this->show_hlist( $elements, $url, 'cat' . $args['sc_id_for_url'], $args['selected_cat'] );
		} else {
			return $this->show_dropdown( $elements, 'cat' . $args['sc_id_for_url'], $args['selected_cat'], $args['sc_id_for_url'] );
		}
	}


	public function show_reset( $url, $args, $options ) {
		$args_to_remove = array(
			'event_id' . $args['sc_id_for_url'],
			'date' . $args['sc_id_for_url'],
			'cat' . $args['sc_id_for_url'],
		);
		if ( ! isset( $options['caption'] ) ) {
			$options['caption'] = __( 'Reset', 'event-list' );
		}
		return $this->show_link( remove_query_arg( $args_to_remove, $url ), $options['caption'], 'link' );
	}


	private function show_hlist( $elements, $url, $name, $selected = null ) {
		$out = '<ul class="hlist">';
		foreach ( $elements as $element ) {
			$out .= '<li>';
			if ( $selected === $element['slug'] ) {
				$out .= '<strong>' . esc_html( $element['name'] ) . '</strong>';
			} else {
				$out .= $this->show_link( add_query_arg( $name, $element['slug'], $url ), $element['name'] );
			}
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}


	private function show_dropdown( $elements, $name, $selected = null, $sc_id = '' ) {
		$onchange = '';
		if ( ! is_admin() ) {
			wp_register_script( 'el_filterbar', EL_URL . 'includes/js/filterbar.js', array(), '1.0', true );
			add_action( 'wp_footer', array( &$this, 'footer_script' ) );
			$onchange = ' onchange="el_redirect(this.name,this.value,' . esc_attr( $sc_id ) . ')"';
		}
		$out = '<select class="dropdown" name="' . esc_attr( $name ) . '"' . $onchange . '>';
		foreach ( $elements as $element ) {
			$out .= '
					<option';
			if ( $element['slug'] === $selected ) {
				$out .= ' selected="selected"';
			}
			$out .= ' value="' . esc_attr( $element['slug'] ) . '">' . esc_html( $element['name'] ) . '</option>';
		}
		$out .= '
				</select>';
		return $out;
	}


	private function show_link( $url, $caption, $class = null ) {
		$class = ( null === $class ) ? '' : ' class="' . $class . '"';
		return '<a href="' . esc_url_raw( $url ) . '"' . $class . '>' . esc_html( $caption ) . '</a>';
	}


	private function all_element( $list_type = 'date', $display_type = 'hlist' ) {
		if ( 'hlist' === $display_type ) {
			$name = __( 'All', 'event-list' );
		} else {
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomainDefault -- Standard WordPress string
			$name = ( 'date' === $list_type ) ? __( 'All Dates', 'event-list' ) : __( 'All Categories' );
		}
		return array(
			'slug' => 'all',
			'name' => $name,
		);
	}


	private function upcoming_element() {
		return array(
			'slug' => 'upcoming',
			'name' => __( 'Upcoming', 'event-list' ),
		);
	}


	private function past_element() {
		return array(
			'slug' => 'past',
			'name' => __( 'Past', 'event-list' ),
		);
	}


	private function parse_args( &$args ) {
		$defaults = array(
			'date'          => null,
			'selected_date' => null,
			'selected_cat'  => null,
			'event_id'      => null,
			'sc_id_for_url' => '',
		);
		$args     = wp_parse_args( $args, $defaults );
		if ( ! empty( $args['event_id'] ) ) {
			$args['selected_date'] = null;
			$args['selected_cat']  = null;
		};
	}


	public function footer_script() {
		wp_print_scripts( 'el_filterbar' );
	}

}

