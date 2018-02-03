<?php
if(!defined('WPINC')) {
	exit;
}

// Class for database access via wordpress functions
class EL_Daterange {
	private static $instance;
	public $date_formats;
	public $daterange_formats;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset( self::$instance)) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {
		$this->init_formats();
	}

	public function init_formats() {
		$this->date_formats = array(
			'year'         => array('regex' => '^((19[7-9]\d)|(2\d{3}))$',
			                        'start' => '%v%-01-01',
			                        'end'   => '%v%-12-31'),
			'month'        => array('regex' => '^((19[7-9]\d)|(2\d{3}))-(0[1-9]|1[012])$',
			                        'start' => '%v%-01',
			                        'end'   => '%v%-31'),
			'day'          => array('regex' => '^((19[7-9]\d)|(2\d{3}))-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01])$',
			                        'start' => '%v%',
			                        'end'   => '%v%'),
			'rel_year'     => array('regex' => '^([+-]?\d+|last|next|previous|this)_year[s]?$',
			                        'start' => '--func--date("Y", strtotime(str_replace("_", " ", "%v%")))."-01-01";',
			                        'end'   => '--func--date("Y", strtotime(str_replace("_", " ", "%v%")))."-12-31";'),
			'rel_month'    => array('regex' => '^([+-]?\d+|last|previous|next|this)month[s]?$',
			                        'start' => '--func--date("Y-m", strtotime(str_replace("_", " ", "%v%")))."-01";',
			                        'end'   => '--func--date("Y-m", strtotime(str_replace("_", " ", "%v%")))."-31";'),
			'rel_week'     => array('regex' => '^([+-]?\d+|last|previous|next|this)_week[s]?$',
			                        'start' => '--func--date("Y-m-d", strtotime(str_replace(array("_","last","previous","next","this"), array(" ","-1","-1","+1","0"), "%v%"))-86400*((date("w")-get_option("start_of_week")+7)%7));',
			                        'end'   => '--func--date("Y-m-d", strtotime(str_replace(array("_","last","previous","next","this"), array(" ","-1","-1","+1","0"), "%v%"))-86400*((date("w")-get_option("start_of_week")+7)%7-6));'),
			                                   // replace special values due to some date calculation problems,
			                                   // then calculate the new date
			                                   // and at last remove calculated days to get first day of the week (acc. start_of_week option), add 6 day for end date (- sign due to - for first day calculation)
			'rel_day'      => array('regex' => '^((([+-]?\d+|last|previous|next|this)_day[s]?)|yesterday|today|tomorrow)$',
			                        'start' => '--func--date("Y-m-d", strtotime(str_replace("_", " ", "%v%")));',
			                        'end'   => '--func--date("Y-m-d", strtotime(str_replace("_", " ", "%v%")));'),
		);
		$this->daterange_formats = array(
			'date_range'   => array('regex' => '.+~.+'),
			'all'          => array('regex' => '^all$',
			                        'start' => '1970-01-01',
			                        'end'   => '2999-12-31'),
			'upcoming'     => array('regex' => '^upcoming$',
			                        'start' => '--func--date("Y-m-d", current_time("timestamp"));',
			                        'end'   => '2999-12-31'),
			'past'         => array('regex' => '^past$',
			                        'start' => '1970-01-01',
			                        'end'   => '--func--date("Y-m-d", current_time("timestamp")-86400);'),  // previous day (86400 seconds = 1*24*60*60 = 1 day
		);
	}

	public function load_formats_helptexts() {
		require_once(EL_PATH.'includes/daterange_helptexts.php');
		foreach($date_formats_helptexts as $name => $values) {
			$this->date_formats[$name] += $values;
		}
		unset($date_formats_helptexts);
		foreach($daterange_formats_helptexts as $name => $values) {
			$this->daterange_formats[$name] += $values;
		}
		unset($daterange_formats_helptexts);
	}

	public function check_date_format($element, $ret_value=null) {
		foreach($this->date_formats as $date_type) {
			if(preg_match('@'.$date_type['regex'].'@', $element)) {
				return $this->get_date_range($element, $date_type, $ret_value);
			}
		}
		return null;
	}

	public function check_daterange_format($element) {
		foreach($this->daterange_formats as $key => $daterange_type) {
			if(preg_match('@'.$daterange_type['regex'].'@', $element)) {
				//check for date_range which requires special handling
				if('date_range' == $key) {
					$sep_pos = strpos($element, "~");
					$startrange = $this->check_date_format(substr($element, 0, $sep_pos), 'start');
					$endrange = $this->check_date_format(substr($element, $sep_pos+1), 'end');
					return array($startrange[0], $endrange[1]);
				}
				return $this->get_date_range($element, $daterange_type);
			}
		}
		return null;
	}

	public function get_date_range($element, &$range_type, $ret_value=null) {
		if('end' != $ret_value) {
			// start date:
			// set range values by replacing %v% in $range_type string with $element
			$range[0] = str_replace('%v%', $element, $range_type['start']);
			// enum function if required
			if(substr($range[0], 0, 8) == '--func--') {  //start
				eval('$range[0] = '.substr($range[0], 8));
			}
		}
		if('start' != $ret_value) {
			// same for end date:
			$range[1] = str_replace('%v%', $element, $range_type['end']);
			if(substr($range[1], 0, 8) == '--func--') {  //end
				eval('$range[1] = '.substr($range[1], 8));
			}
		}
		return $range;
	}
}

/* create date_create_from_format (DateTime::createFromFormat) alternative for PHP 5.2
 */
if(!function_exists('date_create_from_format')) {
	function date_create_from_format($dformat, $dvalue) {
	$schedule = $dvalue;
	$schedule_format = str_replace(array('Y','m','d', 'H', 'i','a'), array('%Y','%m','%d', '%I', '%M', '%p'), $dformat);
	$ugly = strptime($schedule, $schedule_format);
	$ymd = sprintf(
		// This is a format string that takes six total decimal arguments, then left-pads
		// them with zeros to either 4 or 2 characters, as needed
		'%04d-%02d-%02d %02d:%02d:%02d',
		$ugly['tm_year'] + 1900,  // This will be "111", so we need to add 1900.
		$ugly['tm_mon'] + 1,      // This will be the month minus one, so we add one.
		$ugly['tm_mday'],
		$ugly['tm_hour'],
		$ugly['tm_min'],
		$ugly['tm_sec']
	);
	return new DateTime($ymd);
	}
}
?>
