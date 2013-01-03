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
				array( 'description' => __( 'This widget displays a list of upcoming events. If you want to enable a link to the events or to the event page you have to insert a link to the event-list page or post.', 'text_domain' ), ) // Args
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
		echo do_shortcode( '[event-list num_events="'.$instance['num_events'].'" '
		                              .'show_nav=0 '
		                              .'show_details=0 '
		                              .'show_location='.$instance['show_location'].' '
		                              .'link_to_event='.$instance['link_to_event'].' '
		                              .'url_to_page="'.$instance['url_to_page'].'" '
		                              .'sc_id_for_url="'.$instance['sc_id_for_url'].'"]' );
		if( 1 == $instance['link_to_page'] ) {
			echo '<div style="clear:both"><a title="'.$instance['link_to_page_caption'].'" href="'.$instance[ 'url_to_page'].'">'.$instance['link_to_page_caption'].'</a></div>';
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
		$instance['show_location'] =  (isset( $new_instance['show_location'] ) && 1==$new_instance['show_location'] ) ? 1 : 0;
		$instance['url_to_page'] = strip_tags( $new_instance['url_to_page'] );
		$instance['sc_id_for_url'] = strip_tags( $new_instance['sc_id_for_url'] );
		$instance['link_to_event'] = (isset( $new_instance['link_to_event'] ) && 1==$new_instance['link_to_event'] ) ? 1 : 0;
		$instance['link_to_page'] = (isset( $new_instance['link_to_page'] ) && 1==$new_instance['link_to_page'] ) ? 1 : 0;
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
		$show_location =        isset( $instance['show_location'] )        ? $instance['show_location']        : '';
		$url_to_page =          isset( $instance['url_to_page'] )          ? $instance['url_to_page']          : '';
		$sc_id_for_url =        isset( $instance['sc_id_for_url'] )        ? $instance['sc_id_for_url']        : '1';
		$link_to_event =        isset( $instance['link_to_event'] )        ? $instance['link_to_event']        : '';
		$link_to_page =         isset( $instance['link_to_page'] )         ? $instance['link_to_page']         : '';
		$link_to_page_caption = isset( $instance['link_to_page_caption'] ) ? $instance['link_to_page_caption'] : __( 'show event-list page', 'text_domain' );
		$show_location_checked = 1==$show_location ? 'checked = "checked" ' : '';
		$link_to_event_checked = 1==$link_to_event ? 'checked = "checked" ' : '';
		$link_to_page_checked =  1==$link_to_page  ? 'checked = "checked" ' : '';
		$out = '
		<p>
			<label for="'.$this->get_field_id( 'title' ).'">'.__( 'Title:' ).'</label>
			<input class="widefat" id="'.$this->get_field_id( 'title' ).'" name="'.$this->get_field_name( 'title' ).'" type="text" value="'.esc_attr( $title ).'" />
		</p>
		<p>
			<label for="'.$this->get_field_id( 'num_events' ).'">'.__( 'Number of upcoming events:' ).'</label>
			<input style="width:30px" class="widefat" id="'.$this->get_field_id( 'num_events' ).'" name="'.$this->get_field_name( 'num_events' ).'" type="text" value="'.esc_attr( $num_events ).'" />
		</p>
		<p>
			<label><input class="widefat" id="'.$this->get_field_id( 'show_location' ).'" name="'.$this->get_field_name( 'show_location' ).'" type="checkbox" '.$show_location_checked.'value="1" /> '.__( 'Show location' ).'</label>
		</p>
		<p style="margin:0 0 0.4em 0">
			<label for="'.$this->get_field_id( 'link_to_page_url' ).'">'.__( 'URL to the linked eventlist page:' ).'</label>
			<input class="widefat" id="'.$this->get_field_id( 'url_to_page' ).'" name="'.$this->get_field_name( 'url_to_page' ).'" type="text" value="'.esc_attr( $url_to_page ).'" />
		</p>
		<p>
			<label for="'.$this->get_field_id( 'sc_id_for_url' ).'">'.__( 'Shortcode ID on linked page:' ).'</label>
			<input style="width:30px;" class="widefat" id="'.$this->get_field_id( 'sc_id_for_url' ).'" name="'.$this->get_field_name( 'sc_id_for_url' ).'" type="text" value="'.esc_attr( $sc_id_for_url ).'" />
		</p>
		<p style="margin-left:0.8em">
			<label><input class="widefat" id="'.$this->get_field_id( 'link_to_event' ).'" name="'.$this->get_field_name( 'link_to_event' ).'" type="checkbox" '.$link_to_event_checked.'value="1" /> '.__( 'Add links to the single events' ).'</label>
		</p>
		<p style="margin:0 0 0.2em 0.8em">
			<label><input class="widefat" id="'.$this->get_field_id( 'link_to_page' ).'" name="'.$this->get_field_name( 'link_to_page' ).'" type="checkbox" '.$link_to_page_checked.'value="1" /> '.__( 'Add a link to an event page' ).'</label>
		</p>
		<p style="margin:0 0 1em 2.5em">
			<label for="'.$this->get_field_id( 'link_to_page_caption' ).'">'.__( 'Caption for the link:' ).'</label>
			<input class="widefat" id="'.$this->get_field_id( 'link_to_page_caption' ).'" name="'.$this->get_field_name( 'link_to_page_caption' ).'" type="text" value="'.esc_attr( $link_to_page_caption ).'" />
		</p>';
		echo $out;
	}

}
?>