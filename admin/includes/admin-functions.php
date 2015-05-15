<?php
if(!defined('ABSPATH')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');

// This class handles general functions which can be used on different admin pages
class EL_Admin_Functions {
	private static $instance;

	public static function &get_instance() {
		// Create class instance if required
		if(!isset(self::$instance)) {
			self::$instance = new EL_Admin_Functions();
		}
		// Return class instance
		return self::$instance;
	}

	private function __construct() {

	}

	public function show_checkbox($name, $value, $caption, $disabled=false) {
		$out = '
							<label for="'.$name.'">
								<input name="'.$name.'" type="checkbox" id="'.$name.'" value="1"';
		if($value == 1) {
			$out .= ' checked="checked"';
		}
		$out .= $this->get_disabled_text($disabled).' />
								'.$caption.'
							</label>';
		return $out;
	}

	public function show_dropdown($name, $selected, $value_array, $class_array=null, $disabled=false) {
		$out = '
							<select id="'.$name.'" name="'.$name.'"'.$this->get_disabled_text($disabled).'>';
		foreach($value_array as $key => $value) {
			$class_text = isset($class_array[$key]) ? 'class="'.$class_array[$key].'" ' : '';
			$selected_text = $selected===$key ? 'selected ' : '';
			$out .= '
								<option '.$class_text.$selected_text.'value="'.$key.'">'.$value.'</option>';
		}
		$out .= '
							</select>';
		return $out;
	}

	public function show_radio($name, $selected, $value_array, $disabled=false) {
		$out = '
							<fieldset>';
		foreach($value_array as $key => $value) {
			$checked = ($selected === $key) ? 'checked="checked" ' : '';
			$out .= '
								<label title="'.$value.'">
									<input type="radio" '.$checked.'value="'.$key.'" name="'.$name.'">
									<span>'.$value.'</span>
								</label>
								<br />';
		}
		$out .= '
							</fieldset>';
		return $out;
	}

	public function show_text($name, $value, $disabled=false) {
		$out = '
							<input name="'.$name.'" type="text" id="'.$name.'" value="'.$value.'"'.$this->get_disabled_text($disabled).' />';
		return $out;
	}

	public function show_textarea($name, $value, $disabled=false) {
		$out = '
							<textarea name="'.$name.'" id="'.$name.'" rows="5" class="large-text code"'.$this->get_disabled_text($disabled).'>'.$value.'</textarea>';
		return $out;
	}

	public function get_disabled_text($disabled=false) {
		return $disabled ? ' disabled="disabled"' : '';
	}
}
?>
