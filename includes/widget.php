<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event List Widget
*/
class EL_Widget extends WP_Widget {

	private $items;

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
				'event_list_widget', // Base ID
				'Event List', // Name
				array( 'description' => __( 'This widget displays a list of upcoming events. If you want to enable a link to the events or to the event page you have to insert a link to the event-list page or post.', 'text_domain' ), ) // Args
		);

		// define all available items
		$this->items = array(
			'title' =>                array( 'type'          => 'text',
			                                 'std_value'     => __( 'Upcoming events', 'text_domain' ),
			                                 'caption'       => __( 'Title:' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'The title for the widget' ),
			                                 'form_style'    => null,
			                                 'form_width'    => null ),

			'cat_filter' =>           array( 'type'          => 'text',
			                                 'std_value'     => 'all',
			                                 'caption'       => __('Category Filter:'),
			                                 'caption_after' => null,
			                                 'tooltip'       => __('This attribute specifies the categories of which events are shown. The standard is \'all\' or an empty string to show all events. Specify a category slug or a list of category slugs to only show events of the specified categories. See description of the shortcode attribute "cat_filter" for detailed info about all possibilities.'),
			                                 'form_style'    => 'margin:0 0 0.8em 0',
			                                 'form_width'    => null ),

			'num_events' =>           array( 'type'          => 'text',
			                                 'std_value'     => '3',
			                                 'caption'       => __('Number of listed events:'),
			                                 'caption_after' => null,
			                                 'tooltip'       => __('The number of upcoming events to display'),
			                                 'form_style'    => '',
			                                 'form_width'    => 30 ),

			'title_length' =>         array( 'type'          => 'text',
			                                 'std_value'     => '0',
			                                 'caption'       => __( 'Truncate event title to' ),
			                                 'caption_after' => __( 'chars' ),
			                                 'tooltip'       => __( 'This option specifies the number of displayed characters for the event title. Set this value to 0 to view the full title.' ),
			                                 'form_style'    => null,
			                                 'form_width'    => 30 ),

			'show_starttime' =>       array( 'type'          => 'checkbox',
			                                 'std_value'     => 'true',
			                                 'caption'       => __( 'Show event starttime' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'This option defines if the event start time will be displayed.' ),
			                                 'form_style'    => null,
			                                 'form_width'    => null ),

			'show_location' =>        array( 'type'          => 'checkbox',
			                                 'std_value'     => 'false',
			                                 'caption'       => __( 'Show event location' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'This option defines if the event location will be displayed.' ),
			                                 'form_style'    => 'margin:0 0 0.2em 0',
			                                 'form_width'    => null ),

			'location_length' =>      array( 'type'          => 'text',
			                                 'std_value'     => '0',
			                                 'caption'       => __( 'Truncate location to' ),
			                                 'caption_after' => __( 'chars' ),
			                                 'tooltip'       => __( 'If the event location is diplayed this option specifies the number of displayed characters. Set this value to 0 to view the full location.' ),
			                                 'form_style'    => 'margin:0 0 0.6em 0.9em',
			                                 'form_width'    => 30 ),

			'show_details' =>         array( 'type'          => 'checkbox',
			                                 'std_value'     => 'false',
			                                 'caption'       => __( 'Show event details' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'This option defines if the event details will be displayed.' ),
			                                 'form_style'    => 'margin:0 0 0.2em 0',
			                                 'form_width'    => null ),

			'details_length' =>       array( 'type'          => 'text',
			                                 'std_value'     => '0',
			                                 'caption'       => __( 'Truncate details to' ),
			                                 'caption_after' => __( 'characters' ),
			                                 'tooltip'       => __( 'If the event details are diplayed this option specifies the number of diplayed characters. Set this value to 0 to view the full details.' ),
			                                 'form_style'    => 'margin:0 0 0.6em 0.9em',
			                                 'form_width'    => 30 ),

			'url_to_page' =>          array( 'type'          => 'text',
			                                 'std_value'     => '',
			                                 'caption'       => __( 'URL to the linked eventlist page:' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'This options specifies the url to the linked event-list page. This option is required if you want to use one of the options below.' ),
			                                 'form_style'    => 'margin:0 0 0.4em 0',
			                                 'form_width'    => null ),

			'sc_id_for_url' =>        array( 'type'          => 'text',
			                                 'std_value'     => '1',
			                                 'caption'       => __( 'Shortcode ID on linked page:' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'This option specifies the shortcode-id for the event-list on the linked page. Normally the standard value 1 is correct, you only have to change it if you use multiple event-list shortcodes on the linked page.' ),
			                                 'form_style'    => null,
			                                 'form_width'    => 30 ),

			'link_to_event' =>        array( 'type'          => 'checkbox',
			                                 'std_value'     => 'false',
			                                 'caption'       => __( 'Add links to the single events' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'With this option you can add a link to the single event page for every displayed event. You have to specify the url to the page and the shortcode id option if you want to use it.' ),
			                                 'form_style'    => 'margin-left:0.8em',
			                                 'form_width'    => null ),

			'link_to_page' =>         array( 'type'          => 'checkbox',
			                                 'std_value'     => 'false',
			                                 'caption'       => __( 'Add a link to an event page' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'With this option you can add a link to the event-list page below the diplayed events. You have to specify the url to page option if you want to use it.' ),
			                                 'form_style'    => 'margin:0 0 0.2em 0.8em',
			                                 'form_width'    => null ),

			'link_to_page_caption' => array( 'type'          => 'text',
			                                 'std_value'     => __( 'show event-list page', 'text_domain' ),
			                                 'caption'       => __( 'Caption for the link:' ),
			                                 'caption_after' => null,
			                                 'tooltip'       => __( 'This option specifies the text for the link to the event-list page if the approriate option is selected.' ),
			                                 'form_style'    => 'margin:0 0 1em 2.5em',
			                                 'form_width'    => null ),
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$this->prepare_instance($instance);
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		{
			echo $args['before_title'].$title.$args['after_title'];
		}
		$this->upgrade_widget($instance, true);
		$linked_page_is_set = 0 < strlen( $instance['url_to_page'] );
		$linked_page_id_is_set = 0 < (int)$instance['sc_id_for_url'];
		$shortcode = '[event-list show_filterbar=false';
		$shortcode .= ' cat_filter='.$instance['cat_filter'];
		$shortcode .= ' num_events="'.$instance['num_events'].'"';
		$shortcode .= ' title_length='.$instance['title_length'];
		$shortcode .= ' show_starttime='.$instance['show_starttime'];
		$shortcode .= ' show_location='.$instance['show_location'];
		$shortcode .= ' location_length='.$instance['location_length'];
		$shortcode .= ' show_details='.$instance['show_details'];
		$shortcode .= ' details_length='.$instance['details_length'];
		if( $linked_page_is_set && $linked_page_id_is_set ) {
			$shortcode .= ' link_to_event='.$instance['link_to_event'];
			$shortcode .= ' url_to_page="'.$instance['url_to_page'].'"';
			$shortcode .= ' sc_id_for_url='.$instance['sc_id_for_url'];
		}
		else {
			$shortcode .= ' link_to_event=false';
		}
		$shortcode .= ']';
		echo do_shortcode( $shortcode );
		if( 'true' === $instance['link_to_page'] && $linked_page_is_set ) {
			echo '<div style="clear:both"><a title="'.$instance['link_to_page_caption'].'" href="'.$instance[ 'url_to_page'].'">'.$instance['link_to_page_caption'].'</a></div>';
		}
		echo $args['after_widget'];
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		foreach( $this->items as $itemname => $item ) {
			if( 'checkbox' === $item['type'] ) {
				$instance[$itemname] = ( isset( $new_instance[$itemname] ) && 1==$new_instance[$itemname] ) ? 'true' : 'false';
			}
			else { // 'text'
				$instance[$itemname] = strip_tags( $new_instance[$itemname] );
			}
		}
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$this->upgrade_widget($instance);
		$out = '';
		foreach( $this->items as $itemname => $item ) {
			if( ! isset( $instance[$itemname] ) ) {
				$instance[$itemname] = $item['std_value'];
			}
			$style_text = ( null===$item['form_style'] ) ? '' : ' style="'.$item['form_style'].'"';
			if( 'checkbox' === $item['type'] ) {
				$checked_text = ( 'true'===$instance[$itemname] || 1==$instance[$itemname] ) ? 'checked = "checked" ' : '';
				$out .= '
					<p'.$style_text.' title="'.$item['tooltip'].'">
						<label><input class="widefat" id="'.$this->get_field_id( $itemname ).'" name="'.$this->get_field_name( $itemname ).'" type="checkbox" '.$checked_text.'value="1" /> '.$item['caption'].'</label>
					</p>';
			}
			else { // 'text'
				$width_text = ( null === $item['form_width'] ) ? '' : 'style="width:'.$item['form_width'].'px" ';
				$caption_after_text = ( null === $item['caption_after'] ) ? '' : '<label>'.$item['caption_after'].'</label>';
				$out .= '
					<p'.$style_text.' title="'.$item['tooltip'].'">
						<label for="'.$this->get_field_id( $itemname ).'">'.$item['caption'].' </label>
						<input '.$width_text.'class="widefat" id="'.$this->get_field_id( $itemname ).'" name="'.$this->get_field_name( $itemname ).'" type="text" value="'.esc_attr( $instance[$itemname] ).'" />'.$caption_after_text.'
					</p>';
			}
		}
		echo $out;
	}

	/**
	 * Prepare the instance array and add not available items with std_value
	 *
	 * This is required for a plugin upgrades: In existing widgets probably added widget options are not available.
	 *
	 * @param array &$instance Previously saved values from database.
	 */
	private function prepare_instance(&$instance) {
		foreach($this->items as $itemname => $item) {
			if(!isset($instance[$itemname])) {
				$instance[$itemname] = $item['std_value'];
			}
		}
	}

	private function upgrade_widget(&$instance, $on_frontpage=false) {
		// required change of cat_filter in version 0.6.0 (can be removed in 0.7.0)
		if(isset($instance['cat_filter']) && 'none' === $instance['cat_filter']) {
			if($on_frontpage) {
				if(current_user_can('edit_theme_options')) {
					echo '<p style="color:red"><strong>Please visit widget admin page (Appearance -> Widgets) and press "Save" to perform the required widget updates (required due to changes in new plugin version) !</strong></p>';
				}
			}
			else {
				echo '<p style="color:red"><strong>Press "Save" to perform the required widget updates (required due to changes in new plugin version) !</strong></p>';
				$instance['cat_filter'] = 'all';
				$this->update($instance, null);
			}
		}
	}
}
?>
