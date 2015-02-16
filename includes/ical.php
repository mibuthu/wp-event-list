<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/options.php');

// This class handles iCal feed
class EL_iCal {

	private static $instance;
	private $db;
	private $options;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->db = EL_Db::get_instance();
		$this->options = EL_Options::get_instance();
		$this->init();
	}

	public function init() {
		add_feed($this->options->get('el_ical_name'), array(&$this, 'print_eventlist_ical'));
	}

	public function print_eventlist_ical() {
		header('Content-Type: text/calendar; charset='.get_option('blog_charset'), true);
		$events = $this->db->get_events($this->options->get('el_ical_upcoming_only') ? 'upcoming' : null);
	
		// Print feeds
		echo
'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//'.get_bloginfo('name').'//NONSGML v1.0//EN
X-WR-CALNAME:'.get_bloginfo('name').'
X-ORIGINAL-URL:'.get_bloginfo('url').'
X-WR-CALDESC:Events for '.get_bloginfo('name').'
CALSCALE:GREGORIAN\
METHOD:PUBLISH';

		if(!empty($events)) {
			foreach ($events as $event) {
				$start_date=$event->start_date . ' ' . $event->time;
				$end_date=$event->end_date . ' 12:00';
				
				if($event->start_date==$event->end_date)
				{
					$end_date=$start_date;
				}
				
				
				echo '
BEGIN:VEVENT
UID:'.$event->id.'
DTSTART:'.mysql2date('Ymd\THis\Z', $start_date, false).'
DTEND:'.mysql2date('Ymd\THis\Z', $end_date, false).'
LOCATION:'.$event->location.'
SUMMARY:'.$event->title.'
END:VEVENT';
			}
		}
		echo '
END:VCALENDAR';
	}

	public function update_ical_rewrite_status() {
		$feeds = array_keys((array)get_option('rewrite_rules'), 'index.php?&feed=$matches[1]');
		$feed_rewrite_status = (0 < count(preg_grep('@[(\|]'.$this->options->get('el_ical_name').'[\|)]@', $feeds))) ? true : false;
		if('1' == $this->options->get('el_enable_ical') && !$feed_rewrite_status) {
			// add eventlist ical to rewrite rules
			flush_rewrite_rules(false);
		}
		elseif('1' != $this->options->get('el_enable_ical') && $feed_rewrite_status) {
			// remove eventlist ical from rewrite rules
			flush_rewrite_rules(false);
		}
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
