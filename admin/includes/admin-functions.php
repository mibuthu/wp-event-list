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

	public function show_combobox($name, $option_array, $selected=null, $class_array=null, $disabled=false) {
		$out = '
							<select id="'.$name.'" name="'.$name.'"'.$this->get_disabled_text($disabled).'>';
		foreach($option_array as $key => $value) {
			$class_text = isset($class_array[$key]) ? 'class="'.$class_array[$key].'" ' : '';
			$selected_text = $selected===$key ? 'selected ' : '';
			$out .= '
								<option '.$class_text.$selected_text.'value="'.$key.'">'.$value.'</option>';
		}
		$out .= '
							</select>';
		return $out;
	}

	public function show_radio($name, $value, $caption, $disabled=false) {
		$out = '
							<fieldset>';
		foreach($caption as $okey => $ocaption) {
			$checked = ($value === $okey) ? 'checked="checked" ' : '';
			$out .= '
								<label title="'.$ocaption.'">
									<input type="radio" '.$checked.'value="'.$okey.'" name="'.$name.'">
									<span>'.$ocaption.'</span>
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
