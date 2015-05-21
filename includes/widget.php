<?php
if(!defined('ABSPATH')) {
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
				array('description' => __('With this widget a list of upcoming events can be displayed.','event-list')) // Args
		);

		// define all available items
		$this->items = array(
			'title'                => array('std_value' => __('Upcoming events','event-list').':'),
			'cat_filter'           => array('std_value' => 'all'),
			'num_events'           => array('std_value' => '3'),
			'title_length'         => array('std_value' => '0'),
			'show_starttime'       => array('std_value' => 'true'),
			'show_location'        => array('std_value' => 'false'),
			'location_length'      => array('std_value' => '0'),
			'show_details'         => array('std_value' => 'false'),
			'details_length'       => array('std_value' => '0'),
			'url_to_page'          => array('std_value' => ''),
			'sc_id_for_url'        => array('std_value' => '1'),
			'link_to_event'        => array('std_value' => 'false'),
			'link_to_page'         => array('std_value' => 'false'),
			'link_to_page_caption' => array('std_value' => __('show events page','event-list')),
		);

		add_action('admin_init', array(&$this, 'load_widget_items_helptexts'), 2);
	}

	public function load_widget_items_helptexts() {
		require_once(EL_PATH.'includes/widget_helptexts.php');
		foreach($widget_items_helptexts as $name => $values) {
			$this->items[$name] += $values;
		}
		unset($widget_items_helptexts);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		$this->prepare_instance($instance);
		$title = apply_filters('widget_title', $instance['title']);
		echo $args['before_widget'];
		if(!empty($title))
		{
			echo $args['before_title'].$title.$args['after_title'];
		}
		$this->upgrade_widget($instance, true);
		$linked_page_is_set = 0 < strlen($instance['url_to_page']);
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
		if($linked_page_is_set && $linked_page_id_is_set) {
			$shortcode .= ' link_to_event='.$instance['link_to_event'];
			$shortcode .= ' url_to_page="'.$instance['url_to_page'].'"';
			$shortcode .= ' sc_id_for_url='.$instance['sc_id_for_url'];
		}
		else {
			$shortcode .= ' link_to_event=false';
		}
		$shortcode .= ']';
		echo do_shortcode($shortcode);
		if('true' === $instance['link_to_page'] && $linked_page_is_set) {
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
	public function update($new_instance, $old_instance) {
		$instance = array();
		foreach($this->items as $itemname => $item) {
			if('checkbox' === $item['type']) {
				$instance[$itemname] = (isset($new_instance[$itemname]) && 1==$new_instance[$itemname]) ? 'true' : 'false';
			}
			else { // 'text'
				$instance[$itemname] = strip_tags($new_instance[$itemname]);
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
	public function form($instance) {
		$this->upgrade_widget($instance);
		$out = '';
		foreach($this->items as $itemname => $item) {
			if(! isset($instance[$itemname])) {
				$instance[$itemname] = $item['std_value'];
			}
			$style_text = (null===$item['form_style']) ? '' : ' style="'.$item['form_style'].'"';
			if('checkbox' === $item['type']) {
				$checked_text = ('true'===$instance[$itemname] || 1==$instance[$itemname]) ? 'checked = "checked" ' : '';
				$out .= '
					<p'.$style_text.' title="'.$item['tooltip'].'">
						<label><input class="widefat" id="'.$this->get_field_id($itemname).'" name="'.$this->get_field_name($itemname).'" type="checkbox" '.$checked_text.'value="1" /> '.$item['caption'].'</label>
					</p>';
			}
			else { // 'text'
				$width_text = (null === $item['form_width']) ? '' : 'style="width:'.$item['form_width'].'px" ';
				$caption_after_text = (null === $item['caption_after']) ? '' : '<label>'.$item['caption_after'].'</label>';
				$out .= '
					<p'.$style_text.' title="'.$item['tooltip'].'">
						<label for="'.$this->get_field_id($itemname).'">'.$item['caption'].' </label>
						<input '.$width_text.'class="widefat" id="'.$this->get_field_id($itemname).'" name="'.$this->get_field_name($itemname).'" type="text" value="'.esc_attr($instance[$itemname]).'" />'.$caption_after_text.'
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
