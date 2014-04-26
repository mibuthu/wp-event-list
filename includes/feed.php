<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/options.php');

// This class handles rss feeds
class EL_Feed {

	private static $instance;
	private $db;
	private $options;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
			self::$instance->init();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = EL_Db::get_instance();
		$this->options = EL_Options::get_instance();
	}

	public function init() {
		add_action('init', array(&$this, 'add_eventlist_feed'));
		if($this->options->get('el_head_feed_link')) {
			add_action('wp_head', array(&$this, 'print_head_feed_link'));
		}
	}

	public function print_head_feed_link() {
		echo '<link rel="alternate" type="application/rss+xml" title="'.get_bloginfo_rss('name').' &raquo; Eventlist Feed" href="'.$this->eventlist_feed_url().'" />';
	}

	public function print_eventlist_feed() {
		header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
		$events = $this->db->get_events($this->options->get('el_feed_upcoming_only') ? 'upcoming' : null);

		// Print feeds
		echo
'<?xml version="1.0" encoding="'.get_option('blog_charset').'"?>
	<rss version="2.0"
		xmlns:content="http://purl.org/rss/1.0/modules/content/"
		xmlns:wfw="http://wellformedweb.org/CommentAPI/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
		xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	>
		<channel>
			<title>'.get_bloginfo_rss('name').'</title>
			<atom:link href="'.apply_filters('self_link', get_bloginfo()).'" rel="self" type="application/rss+xml" />
			<link>'.get_bloginfo_rss('url').'</link>
			<description>'.__('Eventlist').'</description>
			<lastBuildDate>'.mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false).'</lastBuildDate>
			<language>'.get_option('rss_language').'</language>
			<sy:updatePeriod>'.apply_filters('rss_update_period', 'hourly').'</sy:updatePeriod>
			<sy:updateFrequency>'.apply_filters('rss_update_frequency', '1').'</sy:updateFrequency>
			'; do_action('rss2_head');
		if(!empty($events)) {
			foreach ($events as $event) {
				echo '
			<item>
				<title>'.esc_attr($this->format_date($event->start_date, $event->end_date).' - '.$event->title).'</title>
				<pubDate>'.mysql2date('D, d M Y H:i:s +0000', $event->start_date, false).'</pubDate>
				<description>'.esc_attr($this->format_date($event->start_date, $event->end_date).' '.
						('' != $event->time ? $event->time : '').('' != $event->location ? ' - '.$event->location : '')).'</description>
				'.('' != $event->details ?
						'<content:encoded><![CDATA['.esc_attr($this->format_date($event->start_date, $event->end_date).' '.
						('' != $event->time ? $event->time : '').('' != $event->location ? ' - '.$event->location : '')).
						$event->details.']]></content:encoded>' : '').'
			</item>';
			}
		}
		echo '
		</channel>
	</rss>';
	}

	public function add_eventlist_feed() {
		global $wp_rewrite;
		add_feed('eventlist', array(&$this, 'print_eventlist_feed'));
		add_action('generate_rewrite_rules', array(&$this, 'eventlist_feed_rewrite'));
		$wp_rewrite->flush_rules();
	}

	public function eventlist_feed_rewrite() {
		global $wp_rewrite;
		$feed_rules = array('feed/(.+)' => 'index.php?feed='.$wp_rewrite->preg_index(1));
		$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
	}

	public function eventlist_feed_url() {
		if(get_option('permalink_structure')) {
			$feed_link = get_bloginfo('url').'/feed/eventlist';
		}
		else {
			$feed_link = get_bloginfo('url').'/?feed=eventlist';
		}
		return $feed_link;
	}

	private function format_date($start_date, $end_date) {
		$startArray = explode("-", $start_date);
		$start_date = mktime(0,0,0,$startArray[1],$startArray[2],$startArray[0]);

		$endArray = explode("-", $end_date);
		$end_date = mktime(0,0,0,$endArray[1],$endArray[2],$endArray[0]);

		$event_date = '';

		if ($start_date == $end_date) {
			if ($startArray[2] == "00") {
				$start_date = mktime(0,0,0,$startArray[1],15,$startArray[0]);
				$event_date .= date("F, Y", $start_date);
				return $event_date;
			}
			$event_date .= date("M j, Y", $start_date);
			return $event_date;
		}

		if ($startArray[0] == $endArray[0]) {
			if ($startArray[1] == $endArray[1]) {
				$event_date .= date("M j", $start_date) . "-" . date("j, Y", $end_date);
				return $event_date;
			}
			$event_date .= date("M j", $start_date) . "-" . date("M j, Y", $end_date);
			return $event_date;

		}

		$event_date .= date("M j, Y", $start_date) . "-" . date("M j, Y", $end_date);
		return $event_date;
	}
}
?>
