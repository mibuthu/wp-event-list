<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events.php');

// This class handles iCal feed
class EL_iCal {
	private static $instances = array();
	private $options;
	private $events;
	private $cat_filter;

	public static function &get_instance( $cat_filter = 'all') {
		// Create class instance if required
		if ( ! isset( self::$instances[ $cat_filter ] ) ) {
			self::$instances[ $cat_filter ] = new self( $cat_filter );
		}

		// Return class instance
		return self::$instances[ $cat_filter ];
	}

	private function __construct( $cat_filter ) {
		$this->options = EL_Options::get_instance();
		$this->events = EL_Events::get_instance();
		$this->cat_filter = $cat_filter;
		$this->init();
	}

	public function init() {
		// register feed properly with WordPress
        add_feed($this->get_feed_name(), array(&$this, 'print_eventlist_ical'));
    }

	public function print_eventlist_ical() {
		header('Content-Type: text/calendar; charset='.get_option('blog_charset'), true);
        $options = array(
			'date_filter' => $this->options->get('el_ical_upcoming_only') ? 'upcoming' : null,
            'order' => array('startdate DESC', 'starttime DESC', 'enddate DESC'),
            'cat_filter' => $this->cat_filter
        );

		$events = $this->events->get($options);

        // Print feeds
        $eol = "\r\n";
		echo
        'BEGIN:VCALENDAR'.$eol.
        'VERSION:2.0'.$eol.
        'PRODID:-//'.get_bloginfo('name').'//NONSGML v1.0//EN'.$eol.
		'CALSCALE:GREGORIAN'.$eol.
		'UID:'.md5(uniqid(mt_rand(), true)).'@'.get_bloginfo('name').$eol;

        if(!empty($events)) {
            foreach ($events as $event) {
                echo
                    'BEGIN:VEVENT'.$eol.
					'UID:'.md5(uniqid(mt_rand(), true)).'@'.get_bloginfo('name').$eol.
                    'DTSTART;TZID=Europe/Berlin:'.mysql2date('Ymd\T', $event->startdate, false).mysql2date('His', $event->starttime, false).$eol.
					'DTEND;TZID=Europe/Berlin:'.mysql2date('Ymd\T', $event->enddate, false).mysql2date('His', $event->endtime, false).$eol.
					'DTSTAMP:'.date("Ymd\THis\Z").$eol.
                    'LOCATION:'.$event->location.$eol.
                    'SUMMARY:'.$this->sanitize_feed_text($event->title).$eol;
                    if(!empty($event->content)) {
                        echo
                        'DESCRIPTION:'.$this->sanitize_feed_text(str_replace(array("\r", "\n"), ' ', $event->content)).$eol;
                    }
                    echo
                    'END:VEVENT';
                    if ($event == end(array_keys($events))) {
                    } else {
                        echo $eol;
                    }
            }
        }
		echo 'END:VCALENDAR';

    }

	public function update_ical_rewrite_status() {
		$feeds = array_keys((array)get_option('rewrite_rules'), 'index.php?&feed=$matches[1]');
		$feed_rewrite_status = 0 < count(preg_grep('@[(\|]'.$this->get_feed_name().'[\|)]@', $feeds));
		// if iCal is enabled but rewrite rules do not exist already, flush rewrite rules
		if('1' == $this->options->get('el_enable_ical') && !$feed_rewrite_status) {
			// result: add eventlist ical to rewrite rules
			flush_rewrite_rules(false);
		}
		// if iCal is disabled but rewrite rules do exist already, flush rewrite rules also
		elseif('1' != $this->options->get('el_enable_ical') && $feed_rewrite_status) {
			// result: remove eventlist ical from rewrite rules
			flush_rewrite_rules(false);
		}
    }

    private function sanitize_feed_text($text) {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}

	private function get_feed_name() {
		$cat = isset($this->cat_filter) && $this->cat_filter !== "" ? $this->cat_filter . "-" : "";
		return $cat . $this->options->get('el_ical_name');
	}

    public function ical_feed_url() {
		if(get_option('permalink_structure')) {
			$feed_link = get_bloginfo('url').'/feed/';
		}
		else {
			$feed_link = get_bloginfo('url').'/?feed=';
		}
		return $feed_link . $this->get_feed_name();
	}
}
?>
