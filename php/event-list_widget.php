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
		echo do_shortcode( '[event-list num_events="'.$instance['num_events'].'" show_nav=0 show_details=0 show_location=0 link_to_event='.$instance['link_to_event'].']' );
		if( 1 == $instance['link_to_page'] ) {
			echo '<div style="clear:both"><a title="'.$instance['link_to_page_caption'].'" href="'.$instance[ 'link_to_page_url'].'">'.$instance['link_to_page_caption'].'</a></div>';
		}
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
		$instance['link_to_page'] = (isset( $new_instance['link_to_page'] ) && 1==$new_instance['link_to_page'] ) ? 1 : 0;
		$instance['link_to_page_url'] = strip_tags( $new_instance['link_to_page_url'] );
		$instance['link_to_page_caption'] = strip_tags( $new_instance['link_to_page_caption'] );
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
		$title =                isset( $instance['title'] )                ? $instance['title']                : __( 'New title', 'text_domain' );
		$num_events =           isset( $instance['num_events'] )           ? $instance['num_events']           : '3';
		$link_to_event =        isset( $instance['link_to_event'] )        ? $instance['link_to_event']        : '1';
		$link_to_page =         isset( $instance['link_to_page'] )         ? $instance['link_to_page']         : '';
		$link_to_page_url =     isset( $instance['link_to_page_url'] )     ? $instance['link_to_page_url']     : '';
		$link_to_page_caption = isset( $instance['link_to_page_caption'] ) ? $instance['link_to_page_caption'] : __( 'show event-list page', 'text_domain' );
		$link_to_event_checked = 1==$link_to_event ? 'checked = "checked" ' : '';
		$link_to_page_checked =  1==$link_to_page  ? 'checked = "checked" ' : '';
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
			<label><input class="widefat" id="'.$this->get_field_id( 'link_to_event' ).'" name="'.$this->get_field_name( 'link_to_event' ).'" type="checkbox" '.$link_to_event_checked.'value="1"> '.__( 'Add links to the single events' ).'</input></label>
		</p>
		<p>
			<label><input class="widefat" id="'.$this->get_field_id( 'link_to_page' ).'" name="'.$this->get_field_name( 'link_to_page' ).'" type="checkbox" '.$link_to_page_checked.'value="1"> '.__( 'Add a link to a page:' ).'</input></label>
			<p>
				<label for="'.$this->get_field_id( 'link_to_page_url' ).'">'.__( 'URL of the linked page:' ).'</label>
				<input class="widefat" id="'.$this->get_field_id( 'link_to_page_url' ).'" name="'.$this->get_field_name( 'link_to_page_url' ).'" type="text" value="'.esc_attr( $link_to_page_url ).'" />
			</p>
			<p>
				<label for="'.$this->get_field_id( 'link_to_page_caption' ).'">'.__( 'Caption for the link:' ).'</label>
				<input class="widefat" id="'.$this->get_field_id( 'link_to_page_caption' ).'" name="'.$this->get_field_name( 'link_to_page_caption' ).'" type="text" value="'.esc_attr( $link_to_page_caption ).'" />
			</p>
		</p>';
		echo $out;
	}

}
?>