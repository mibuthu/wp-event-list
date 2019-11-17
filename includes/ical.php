<?php
if(!defined('WPINC')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');
require_once(EL_PATH.'includes/events.php');

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
		$this->events = EL_Events::get_instance();
		$this->options = EL_Options::get_instance();
		$this->init();
    }
    
	public function init() {
        add_feed($this->options->get('el_ical_name'), array(&$this, 'print_eventlist_ical'));
    }
    
	public function print_eventlist_ical() {
		header('Content-Type: text/calendar; charset='.get_option('blog_charset'), true);   
        $options = array(
			'date_filter' => $this->options->get('el_ical_upcoming_only') ? 'upcoming' : null,
            'order' => array('startdate DESC', 'starttime DESC', 'enddate DESC'),
            //'cat_filter' => $this->get_cat_filter($a['cat_filter'], $a['selected_cat']);
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

    private function sanitize_feed_text($text) {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
    
    public function ical_feed_url() {
		if(get_option('permalink_structure')) {
			$feed_link = get_bloginfo('url').'/feed/';
		}
		else {
			$feed_link = get_bloginfo('url').'/?feed=';
		}
		return $feed_link.$this->options->get('el_ical_name');
	}
}
?>