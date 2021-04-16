<?php
if(!defined('WP_ADMIN')) {
	exit;
}

require_once(EL_PATH.'includes/options.php');

// This class handles general functions which can be used on different admin pages
class EL_Admin_Functions {
	private static $instance;
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
		$this->options = &EL_Options::get_instance();
		$this->options->load_options_helptexts();
	}

	public function show_option_form($section, $options) {
		$options = wp_parse_args($options, array('page' => admin_url('options.php'), 'button_text' => null, 'button_class' => 'primary large'));
		$out = '
		<form method="post" action="'.$options['page'].'">
		';
		ob_start();
		settings_fields('el_'.$section);
		$out .= ob_get_contents();
		ob_end_clean();
		$out .= $this->show_option_table($section);
		$out .= get_submit_button($options['button_text'], $options['button_class']);
		$out .='
		</form>';
		return $out;
	}

	public function show_option_table($section) {
		$out = '
			<div class="el-settings">
			<table class="form-table">';
		foreach($this->options->options as $oname => $o) {
			if($o['section'] == $section) {
				$out .= '
					<tr>
						<th>';
				if($o['label'] != '') {
					$out .= '<label for="'.$oname.'">'.$o['label'].':</label>';
				}
				$out .= '</th>
					<td>';
				switch($o['type']) {
					case 'checkbox':
						$out .= $this->show_checkbox($oname, $this->options->get($oname), $o['caption'], isset($o['disable']));
						break;
					case 'dropdown':
						$out .= $this->show_dropdown($oname, $this->options->get($oname), $o['caption'], isset($o['disable']));
						break;
					case 'radio':
						$out .= $this->show_radio($oname, $this->options->get($oname), $o['caption'], isset($o['disable']));
						break;
					case 'text':
						$out .= $this->show_text($oname, $this->options->get($oname), isset($o['disable']));
						break;
					case 'textarea':
						$out .= $this->show_textarea($oname, $this->options->get($oname), isset($o['disable']));
						break;
					case 'file-upload':
						$out .= $this->show_file_upload($oname, $o['maxsize'], isset($o['disable']));
				}
				$out .= '
					</td>
					<td class="description">'.$o['desc'].'</td>
				</tr>';
			}
		}
		$out .= '
		</table>
		</div>';
		return $out;
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

	public function show_file_upload($name, $max_size, $disabled=false) {
		$out = '
							<input name="'.$name.'" type="file" maxlength="'.$max_size.'">';
		return $out;
	}

	public function get_disabled_text($disabled=false) {
		return $disabled ? ' disabled="disabled"' : '';
	}
}
?>
