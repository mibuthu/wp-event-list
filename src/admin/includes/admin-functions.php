<?php
/**
 * The main class for the admin pages
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 *
 * @package event-list
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/options.php';

/**
 * This class handles general functions which can be used on different admin pages
 */
class EL_Admin_Functions {

	private static $instance;

	private $options;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->options = &EL_Options::get_instance();
		$this->options->load_options_helptexts();
	}


	public function show_option_form( $section, $options ) {
		$options = wp_parse_args(
			$options,
			array(
				'page'         => admin_url( 'options.php' ),
				'button_text'  => null,
				'button_class' => 'primary large',
			)
		);
		echo '
		<form method="post" action="' . esc_attr( $options['page'] ) . '">
		';
		settings_fields( 'el_' . $section );
		$this->show_option_table( $section );
		echo get_submit_button( $options['button_text'], $options['button_class'] );
		echo '
		</form>';
	}


	public function show_option_table( $section ) {
		echo '
			<div class="el-settings">
			<table class="form-table">';
		foreach ( $this->options->options as $oname => $o ) {
			if ( $section === $o['section'] ) {
				echo '
					<tr>
						<th>';
				if ( '' !== $o['label'] ) {
					echo '<label for="' . esc_attr( $oname ) . '">' . esc_html( $o['label'] ) . ':</label>';
				}
				echo '</th>
					<td>';
				switch ( $o['type'] ) {
					case 'checkbox':
						$this->show_checkbox( $oname, $this->options->get( $oname ), $o['caption'], isset( $o['disable'] ) );
						break;
					case 'dropdown':
						$this->show_dropdown( $oname, $this->options->get( $oname ), $o['caption'], isset( $o['disable'] ) );
						break;
					case 'radio':
						$this->show_radio( $oname, $this->options->get( $oname ), $o['caption'], isset( $o['disable'] ) );
						break;
					case 'text':
						$this->show_text( $oname, $this->options->get( $oname ), isset( $o['disable'] ) );
						break;
					case 'textarea':
						$this->show_textarea( $oname, $this->options->get( $oname ), isset( $o['disable'] ) );
						break;
					case 'file-upload':
						$this->show_file_upload( $oname, $o['maxsize'], isset( $o['disable'] ) );
				}
				echo '
					</td>
					<td class="description">' . wp_kses_post( $o['desc'] ) . '</td>
				</tr>';
			}
		}
		echo '
		</table>
		</div>';
	}


	public function show_checkbox( $name, $value, $caption, $disabled = false ) {
		echo '
							<label for="' . esc_attr( $name ) . '">
								<input name="' . esc_attr( $name ) . '" type="checkbox" id="' . esc_attr( $name ) . '" value="1"';
		if ( '1' === $value ) {
			echo ' checked="checked"';
		}
		$this->show_disabled_text( $disabled );
		echo ' />
								' . esc_html( $caption ) . '
							</label>';
	}


	public function show_dropdown( $name, $selected, $value_array, $class_array = null, $disabled = false ) {
		echo '
							<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"';
		$this->show_disabled_text( $disabled );
		echo '>';
		foreach ( $value_array as $key => $value ) {
			$class_text    = isset( $class_array[ $key ] ) ? 'class="' . $class_array[ $key ] . '" ' : '';
			$selected_text = $selected === $key ? 'selected ' : '';
			echo '
								<option ' . esc_html( $class_text ) . esc_html( $selected_text ) . 'value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
		}
		echo '
							</select>';
	}


	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Parameter $disable not used (yet)
	public function show_radio( $name, $selected, $value_array, $_disabled = false ) {
		echo '
							<fieldset>';
		foreach ( $value_array as $key => $value ) {
			$checked = ( $selected === $key ) ? 'checked="checked" ' : '';
			echo '
								<label title="' . esc_attr( $value ) . '">
									<input type="radio" ' . esc_html( $checked ) . 'value="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">
									<span>' . esc_html( $value ) . '</span>
								</label>
								<br />';
		}
		echo '
							</fieldset>';
	}


	public function show_text( $name, $value, $disabled = false ) {
		echo '
							<input name="' . esc_attr( $name ) . '" type="text" id="' . esc_attr( $name ) . '" value="' . esc_html( $value ) . '"';
		$this->show_disabled_text( $disabled );
		echo ' />';
	}


	public function show_textarea( $name, $value, $disabled = false ) {
		echo '
							<textarea name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" rows="5" class="large-text code"';
		$this->show_disabled_text( $disabled );
		echo '>' . esc_textarea( $value ) . '</textarea>';
	}


	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Parameter $disable not used (yet)
	public function show_file_upload( $name, $max_size, $_disabled = false ) {
		echo '
							<input name="' . esc_attr( $name ) . '" type="file" maxlength="' . esc_attr( $max_size ) . '">';
	}


	public function show_disabled_text( $disabled = false ) {
		if ( $disabled ) {
			echo ' disabled="disabled"';
		}
	}

}
