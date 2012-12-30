<?php
/**
 * Event List Widget
*/
class event_list_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
				'event_list_widget', // Base ID
				'Event List', // Name
				array( 'description' => __( 'This widget displays a list of upcoming events.', 'text_domain' ), ) // Args
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
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
		{
			echo $before_title . $title . $after_title;
		}
		$out = do_shortcode( '[event-list num_events="'.$instance['num_events'].'" show_nav=0 show_details=0 show_location=0 link_to_event='.$instance['link_to_event'].']' );
		echo $out;
		echo $after_widget;
		extract( $args );
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
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['num_events'] = strip_tags( $new_instance['num_events'] );
		$instance['link_to_event'] = (isset( $new_instance['link_to_event'] ) && 1==$new_instance['link_to_event'] ) ? 1 : 0;
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
		$title =         isset( $instance['title'] )         ? $instance['title'] : __( 'New title', 'text_domain' );
		$num_events =    isset( $instance['num_events'] )    ? $instance['num_events'] : '3';
		$link_to_event = isset( $instance['link_to_event'] ) ? $instance['link_to_event'] : '';
		$link_to_event_checked = 1==$link_to_event ? 'checked = "checked" ' : '';
		$out = '
		<p>
			<label for="'.$this->get_field_id( 'title' ).'">'.__( 'Title:' ).'</label>
			<input class="widefat" id="'.$this->get_field_id( 'title' ).'" name="'.$this->get_field_name( 'title' ).'" type="text" value="'.esc_attr( $title ).'" />
		</p>
		<p>
			<label for="'.$this->get_field_id( 'num_events' ).'">'.__( 'Number of events:' ).'</label>
			<input style="width:30px" class="widefat" id="'.$this->get_field_id( 'num_events' ).'" name="'.$this->get_field_name( 'num_events' ).'" type="text" value="'.esc_attr( $num_events ).'">
		</p>
		<p>
			<label><input class="widefat" id="'.$this->get_field_id( 'link_to_event' ).'" name="'.$this->get_field_name( 'link_to_event' ).'" type="checkbox" '.$link_to_event_checked.'value="1"> '.__( 'Add a link to the event' ).'</input></label>
		</p>';
		echo $out;
	}

}
?>