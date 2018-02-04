<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/events_post_type.php');
// fix for PHP 5.2 (provide function date_create_from_format defined in daterange.php)
if(version_compare(PHP_VERSION, '5.3') < 0) {
	require_once(EL_PATH.'includes/daterange.php');
}

// Class to manage categories
class EL_Event {
	private $events_post_type;
	public $post;
	public $categories;
	public $title = '';
	public $startdate = '0000-00-00';
	public $enddate = '0000-00-00';
	public $starttime = '';
	public $location = '';
	public $content = '';

	public function __construct($post) {
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		if($post instanceof WP_Post) {
			$this->post = $post;
		}
		else {
			$this->post = get_post($post);
			if(0 === $this->post->ID) {
				die('ERROR: Post not found!');
			}
		}
		$this->load_eventdata();
	}

	private function load_eventdata() {
		$this->title = $this->post->post_title;
		$this->content = $this->post->post_content;
		$postmeta = get_post_meta($this->post->ID);
		foreach(array('startdate', 'enddate', 'starttime', 'location') as $meta) {
			$this->$meta = isset($postmeta[$meta][0]) ? $postmeta[$meta][0] : '';
		}
		$this->categories = get_the_terms($this->post, $this->events_post_type->taxonomy);
		if(!is_array($this->categories)) {
			$this->categories = array();
		}
		return true;
	}

	public static function safe($eventdata) {
		// create new post
		$postdata['post_type'] = 'el_events';
		$postdata['post_status'] = 'publish';
		$postdata['post_title'] = $eventdata['title'];
		$postdata['post_content'] = $eventdata['content'];
		if(isset($eventdata['slug'])) {
			$postdata['post_name'] = $eventdata['slug'];
		}
		if(isset($eventdata['post_date'])) {
			$postdata['post_date'] = $eventdata['post_date'];
		}
		if(isset($eventdata['post_user'])) {
			$postdata['post_user'] = $eventdata['post_user'];
		}

		$pid = wp_insert_post($postdata);
		// set categories
		$cats = self::set_categories($pid, $eventdata['categories']);
		// save postmeta (created event instance)
		if(!empty($pid)) {
			$post = self::safe_postmeta($pid, $eventdata);
			return $post;
		}
		else {
			global $wpdb;
			$wpdb->update($wpdb->posts, array('post_status' => 'pending'), array('ID' => $pid));
		}
		return false;
	}

	/** ************************************************************************************************************
	 * Create or update the event data (all event data except title and content)
	 *
	 * @param   int    $pid        The post id of the event to update.
	 * @param   array  $eventdata  The event data provided in an array where the key is the event field and the
	 *                             value is the corresponding data. The provided data does not have to be
	 *                             sanitized from the user imputs because this is done in the function.
	 * @return  int|bool           event id ... for a successfully created new event
	 *                             true     ... for a successfully modified existing event
	 *                             false    ... if an error occured during the creation or modification an event
	 **************************************************************************************************************/
	public static function safe_postmeta($pid, $eventdata) {
		$instance = new self($pid);
		$errors = array();

		// Sanitize event data (event data will be provided without sanitation of user input)
		$eventdata['startdate'] = empty($eventdata['startdate']) ? '' : preg_replace('/[^0-9\-]/', '', $eventdata['startdate']);
		$eventdata['enddate'] = empty($eventdata['enddate']) ? '' : preg_replace('/[^0-9\-]/', '', $eventdata['enddate']);
		$eventdata['starttime'] = empty($eventdata['starttime']) ? '' : wp_kses_post($eventdata['starttime']);
		$eventdata['location'] = empty($eventdata['location']) ? '' : wp_kses_post($eventdata['location']);

		//startdate
		$instance->startdate = $instance->validate_date($eventdata['startdate']);
		if(empty($instance->startdate)) {
			$errors[] = __('No valid start date provided','event-list');
		}
		//enddate
		$instance->enddate = $instance->validate_date($eventdata['enddate']);
		if(empty($instance->enddate) || new DateTime($instance->enddate) < new DateTime($instance->startdate)) {
			$instance->enddate = $instance->startdate;
		}
		//time
		$instance->starttime = $instance->validate_time($eventdata['starttime']);
		//location
		$instance->location = stripslashes($eventdata['location']);

		// update all data
		foreach(array('startdate', 'enddate', 'starttime', 'location') as $meta) {
			update_post_meta($pid, $meta, $instance->$meta);
		}
		// error handling: set event back to pending, and publish error message
		if(!empty($errors)) {
			//if((isset($_POST['publish']) || isset( $_POST['save'] ) ) && $_POST['post_status'] == 'publish' ) {
			global $wpdb;
			$wpdb->update($wpdb->posts, array('post_status' => 'pending'), array('ID' => $pid));
			add_filter('redirect_post_location', create_function('$location','return add_query_arg("'.implode('<br />', $errors).'", "4", $location);'));
			unset($instance);
			return false;
		}
		return $instance;
	}

	private static function set_categories($pid, $cats) {
		return wp_set_object_terms($pid, $cats, EL_Events_Post_Type::get_instance()->taxonomy);
	}

	public function starttime_i18n() {
		$timestamp = strtotime($this->starttime);
		if($timestamp) {
			return date_i18n(get_option('time_format'), $timestamp);
		}
		return $this->starttime;
	}

	private function validate_date($datestring) {
		$d = date_create_from_format('Y-m-d', $datestring);
		if($d && $d->format('Y-m-d') == $datestring
		      && 1970 <= $d->format('Y')
		      && 2999 >= $d->format('Y')) {
			return $datestring;
		}
		return false;
	}

	private function validate_time($timestring) {
		// Try to extract a correct time from the provided text
		$timestamp = strtotime(stripslashes($timestring));
		// Return a standard time format if the conversion was successful
		if($timestamp) {
			return date('H:i:s', $timestamp);
		}
		// Else return the given text
		return $timestring;
	}

	public function get_category_ids() {
		return $this->get_category_fields('term_id');
	}

	public function get_category_slugs() {
		return $this->get_category_fields('slug');
	}

	public function get_category_names() {
		return $this->get_category_fields('name');
	}

	private function get_category_fields($field) {
		//error_log('categories: '.print_r($this->categories, true));
		$list = wp_list_pluck($this->categories, $field);
		if(!is_array($list)) {
			$list = array();
		}
		//error_log('cat_fields: '.print_r($list, true));
		return $list;
	}

	/** ************************************************************************************************************
	 * Truncate HTML, close opened tags
	 *
	 * @param string $html          The html code which should be shortened.
	 * @param int    $length        The length (number of characters) to which the text will be shortened.
	 *                              With [0] the full text will be returned. With [auto] also the complete text
	 *                              will be used, but a wrapper div will be added which shortens the text to 1 full
	 *                              line via css.
	 * @param bool   $skip          If this value is true the truncate will be skipped (nothing will be done)
	 * @param bool   $perserve_tags Specifies if html tags should be preserved or if only the text should be
	 *                              shortened.
	 * @param string $link          If an url is given a link to the given url will be added for the ellipsis at
	 *                              the end of the truncated text.
	 ***************************************************************************************************************/
	public function truncate($html, $length, $skip=false, $preserve_tags=true, $link=false) {
		mb_internal_encoding("UTF-8");
		if('auto' == $length) {
			// add wrapper div with css styles for css truncate and return
			return '<div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">'.$html.'</div>';
		}
		elseif(empty($length) || mb_strlen($html) <= $length || $skip) {
			// do nothing
			return $html;
		}
		elseif(!$preserve_tags) {
			// only shorten the text
			return mb_substr($html, 0, $length);
		}
		else {
			// truncate with preserving html tags
			$truncated = false;
			$printedLength = 0;
			$position = 0;
			$tags = array();
			$out = '';
			while($printedLength < $length && $this->mb_preg_match('{</?([a-z]+\d?)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position)) {
				list($tag, $tagPosition) = $match[0];
				// Print text leading up to the tag
				$str = mb_substr($html, $position, $tagPosition - $position);
				if($printedLength + mb_strlen($str) > $length) {
					$out .= mb_substr($str, 0, $length - $printedLength);
					$printedLength = $length;
					$truncated = true;
					break;
				}
				$out .= $str;
				$printedLength += mb_strlen($str);
				if('&' == $tag[0]) {
					// Handle the entity
					$out .= $tag;
					$printedLength++;
				}
				else {
					// Handle the tag
					$tagName = $match[1][0];
					if($this->mb_preg_match('{^</}', $tag)) {
						// This is a closing tag
						$openingTag = array_pop($tags);
						if($openingTag != $tagName) {
							// Not properly nested tag found: trigger a warning and add the not matching opening tag again
							trigger_error('Not properly nested tag found (last opening tag: '.$openingTag.', closing tag: '.$tagName.')', E_USER_NOTICE);
							$tags[] = $openingTag;
						}
						else {
							$out .= $tag;
						}
					}
					else if($this->mb_preg_match('{/\s*>$}', $tag)) {
						// Self-closing tag
						$out .= $tag;
					}
					else {
						// Opening tag
						$out .= $tag;
						$tags[] = $tagName;
					}
				}
				// Continue after the tag
				$position = $tagPosition + mb_strlen($tag);
			}
			// Print any remaining text
			if($printedLength < $length && $position < mb_strlen($html)) {
				$out .= mb_substr($html, $position, $length - $printedLength);
			}
			// Print ellipsis ("...") if the html was truncated
			if($truncated) {
				if($link) {
					$out .= ' <a href="'.$link.'">&hellip;</a>';
				}
				else {
					$out .= ' &hellip;';
				}
			}
			// Close any open tags.
			while(!empty($tags)) {
				$out .= '</'.array_pop($tags).'>';
			}
			return $out;
		}
	}

	private function mb_preg_match($ps_pattern, $ps_subject, &$pa_matches=null, $pn_flags=0, $pn_offset=0, $ps_encoding=null) {
		// WARNING! - All this function does is to correct offsets, nothing else:
		//(code is independent of PREG_PATTER_ORDER / PREG_SET_ORDER)
		if(is_null($ps_encoding)) {
			$ps_encoding = mb_internal_encoding();
		}
		$pn_offset = strlen(mb_substr($ps_subject, 0, $pn_offset, $ps_encoding));
		$out = preg_match($ps_pattern, $ps_subject, $pa_matches, $pn_flags, $pn_offset);
		if($out && ($pn_flags & PREG_OFFSET_CAPTURE))
			foreach($pa_matches as &$ha_match) {
				$ha_match[1] = mb_strlen(substr($ps_subject, 0, $ha_match[1]), $ps_encoding);
			}
		return $out;
	}
}
