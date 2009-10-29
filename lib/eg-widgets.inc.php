<?php
/*
Package Name: EG-Widgets
Plugin URI:
Description:  Abstract class to create and manage widget
Version: 1.1.0
Author: Emmanuel GEORJON
Author URI: http://www.emmanuelgeorjon.com/
*/

/*  Copyright 2009  Emmanuel GEORJON  (email : blog@georjon.eu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 *  Constant MULTI_WIDGET_MAX_NUMBER
 *
 * Maximum number of widgets allowed
 */
define('MULTI_WIDGET_MAX_NUMBER', 10);

if (!class_exists('EG_Widget_110')) {

	/**
	  *  EG_Widget - Class
	  *
	  * {@internal Missing Long Description}
	  *
	  * @package EG-Widgets
	  *
	  */
	class EG_Widget_110 {

		var $id;
		var $class_name;
		var $name;
		var $description;

		var $textdomain;
		var $load_textdomain;
		var $corefile;
		var $cache_expiration;

		var $options_entry;
		var $default_values;
		var $fields;
		var $display_conditions;

		var $multi_widget   = FALSE;
		var $current_number = 1;

		var $language_list = array(
			'fr_FR' => 'Fran&ccedil;ais',
			'en_US' => 'English',
			'es_ES' => 'Espa&ntilde;ol',
			'de_DE' => 'Deutsch',
			'it_IT' => 'Italiano'
		);

		/**
		 * EG_Widget() - Constructor
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
		 * @param $id				string		widget_id
		 * @param $title			string		widget title (appears of the left side of widget page)
		 * @param $description		string		description (appears of the left side of widget page)
		 * @param $class_name		string		style to use in the HTML code of the widget
		 * @param $multi_widget		string		TRUE or FALSE multi or mono widget

		 */
		function EG_Widget_110($id, $name, $description, $class_name, $multi_widget=FALSE) {

			$this->__construct($id, $name, $description, $class_name, $multi_widget);
		}

		/**
		 * __construct - Constructor
		 *
		 *
		 * @package EG-Widgets
		 *
		 * See EG_Widget()  documentation
		 */
		function __construct($id, $name, $description, $class_name, $multi_widget=FALSE) {

			// Initialize parameters
			$this->id           = $id;
			$this->name         = $name;
			$this->class_name   = $class_name;
			$this->description  = $description;
			$this->multi_widget = $multi_widget;

			$this->options_entry = 'widget_'.str_replace('widget_', '', $id);

			// Get previously saved options
			$this->options = get_option($this->options_entry);
			if ($this->options !== FALSE)
				$this->current_number = sizeof($this->options)-1;  // we don't keep line [_multiwidget]
			else
				$this->current_number = 1;

		} // End of constructor

		/**
		 * __destruct - Destructor
		 *
		 * @param none
		 */
		function __destruct() {

		} // End of __destruct

		/**
		 * set_options
		 *
		 * Define options
		 *
		 * @package EG-Widgets
		 *
		 * @param	string	$textdomain			textdomain for i18n features,
		 * @param	string	$plugin_corefile	path and name of plugin core file,
		 * @param	integer	$cache_expiration	duration of cache (seconds),
		 * @return	none
		 */
		function set_options($textdomain, $plugin_corefile, $cache_expiration=0 ) {
			$this->textdomain 		= $textdomain;
			$this->corefile	  		= $plugin_corefile;
			$this->cache_expiration = $cache_expiration;
		} // End of set_options

		/**
		 * set_form
		 *
		 * Define forms and default values
		 *
		 * @package EG-Widgets
		 *
		 * @param	array	$fields					fields for the control form,
		 * @param	array	$default_values			default values,
		 * @param	boolean	$display_conditions		TRUE or FALSE to add display conditions
		 * @return	none
		 */
		function set_form($fields, $default_values, $display_conditions=FALSE ) {
			$this->fields             = $fields;
			$this->default_values     = default_values;
			$this->display_conditions = $display_conditions;

			// If display_condition=TRUE => add fields for conditions of display
			if ($display_conditions) {

				$this->fields['separator'] = array(
					'type'    => 'separator'
				);
				$this->fields['display_conditions'] = array(
					'type'    => 'comment',
					'label'	  => 'Display Widget when'
				);
				$this->fields['show_when'] = array(
						'type'    => 'select',
						'label'   => 'Show widget on pages',
						'list'  => array( 'all'			=> 'All',
										  'home'		=> 'Home',
										  'categories'	=> 'Categories',
										  'posts'		=> 'Posts',
										  'tags'		=> 'Tags')
					);
				$this->fields['show_id'] = array(
						'type'    => 'ftext',
						'label'   => 'Show widget, for'
					);
				$this->fields['show_lang'] = array(
						'type'    => 'select',
						'label'   => 'Show widget, when',
						'list'    => array_merge( array('all' => ' '), $this->language_list)
					);
				$this->fields['hide_lang'] = array(
						'type'    => 'select',
						'label'   => 'Hide widget, when',
						'list'    => array_merge( array('none' => ' '), $this->language_list)
				);

				$this->default_values = array_merge($default_values, array(
										'show_when' => 'all',
										'show_id' 	=> '',
										'show_lang' => '',
										'hide_lang' => ''));
			}

		} // End of set_form

		/**
		 * load
		 *
		 * Load widget (add all required hooks)
		 *
		 * @package EG-Widgets
		 *
		 * @param	boolean	$load_textdomain		Load textdomain or not
		 * @return	none
		 */
		function load($load_textdomain=FALSE) {
			$this->load_textdomain = $load_textdomain;
			register_shutdown_function(array(&$this, '__destruct'));
			add_action('init', array(&$this, 'init'));
			add_action('widgets_init', array(&$this, 'register'));
		}

		/**
		 * init
		 *
		 * Init action
		 *
		 * @package EG-Widgets
		 *
		 * @param	none
		 * @return	none
		 */
		function init() {
			global $wp_version;

			if ($this->textdomain && $this->load_textdomain && function_exists('load_plugin_textdomain')) {
				if (version_compare($wp_version, '2.6', '<')) {
					// for WP < 2.6
					load_plugin_textdomain( $this->textdomain, str_replace(ABSPATH,'', $this->corefile).'/lang');
				} else {
					// for WP >= 2.6
					load_plugin_textdomain( $this->textdomain, FALSE , basename(dirname($this->corefile)).'/lang');
				}
			}
		} // End of init

		/**
		 * register - Register the widget
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
		 * @param none
		 */
		function register() {

			if (! $this->multi_widget) {
				$widget_ops = array('classname' => $this->class_name, 'description' => __($this->description, $this->textdomain) );
				wp_register_sidebar_widget($this->id.'-1', __($this->name, $this->textdomain), array(&$this, 'display'), $widget_ops);
				wp_register_widget_control($this->id.'-1', __($this->name, $this->textdomain), array(&$this, 'control') );

				$this->install_update_options();
			}
			else {
				$class = array('classname' => $this->class_name);
				for ($i = 1; $i <= MULTI_WIDGET_MAX_NUMBER; $i++) {
					$id   = $this->id.'-'.$i;
					$name = __($this->name, $this->textdomain).' '.$i;
					if ($i <= $this->current_number)
						$this->install_update_options($i);
					else
						unset($this->options[$i]);

					wp_register_sidebar_widget($id, $name, $i <= $this->current_number ? array(&$this, 'display') : /* unregister */ '', $class, $i);
					wp_register_widget_control($id, $name, $i <= $this->current_number ? array(&$this, 'control') : /* unregister */ '', array() /* $dims */, $i);
				}
				add_action('sidebar_admin_setup', array( $this, 'setup'));
				add_action('sidebar_admin_page', array( $this, 'page'));
			}
		}

		/**
		 * action - Display widget (the widget itself)
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
		 * @param	$args	array		before_widget, after_widget, before_title, after_title ...
		 * @param	$number	int			id of the widget (in case of multi-widget only)
		 */
		function display($args, $number = -1) {
			/* function to be surcharged  */
		}

		/**
		 * control - Display and manage the widget control panel
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function control($number = -1) {

			if ($number < 0 ) $number = 1;
			$submit_button_label = $this->id.'-'.$number.'-submit';

			// if user click on the submit button ?
			if ( isset($_POST[$submit_button_label]) ) {
				// Get and update values
				$this->update($number);
			}
			// Display form
			echo $this->form($number);
		}

		/**
		 * generate_select_form - Generate HTML <select> <option> ...</option></select> code
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
		 * @param	$id		string	id/name of the field
		 * @param	$values	array		list of values
		 * @param	$default	array		default value
		 */
		function generate_select_form($id, $values, $default) {
			$select_string = '<select id="'.$id.'" name="'.$id.'">';
			foreach( $values as $key => $value) {
				if (trim($value) == '') $value = '';
				else $value = __($value, $this->textdomain);
				if ($key == $default) $string = 'selected'; else $string = '';
				$select_string .= '<option '.$string.' value="'.$key.'">'.$value.'</option>';
			}
			$select_string .= '</select>';
			return ($select_string);
		}

		/**
		 * form - Display the widget control panel form
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function form($number = -1) {

			if ($number < 0) $number = 1;
			$default_values = wp_parse_args( $this->options[$number], $this->default_values );
			$form = '';
			foreach ($this->fields as $field_name => $field_value) {

				$item_name = $this->id.'-'.$number.'-'.$field_name;
				$default_value = $default_values[$field_name];

				switch ($field_value['type']) {

					case 'comment':
						$form .= '<p><strong>'.__($field_value['label'], $this->textdomain).'</strong></p>';
					break;

					case 'separator':
						$form .= '<hr />';
					break;

					case 'numeric':
						$form .= "\n".'<p><label for="'.$item_name.'">'.__($field_value['label'], $this->textdomain).': '.
						         "\n".'<input type="text" id="'.$item_name.'" name="'.$item_name.'" value="'.$default_value.'" size="10" />'.
								 "\n".'</label></p>';
					break;

					case 'text':
					case 'ftext':
						$form .= "\n".'<p><label for="'.$item_name.'">'.__($field_value['label'], $this->textdomain).': '.
						         "\n".'<input type="text" id="'.$item_name.'" name="'.$item_name.'" value="'.format_to_edit($default_value).'" size="10" />'.
								 "\n".'</label></p>';
					break;
					case 'select':
						$form .= "\n".'<p><label for="'.$item_name.'">'.__($field_value['label'], $this->textdomain).': '.
						         "\n".$this->generate_select_form($item_name, $field_value['list'], $default_value).
								 "\n".'</label></p>';
					break;

					case 'radio':
						$form .= "\n".'<p><label for="'.$item_name.'">'.__($field_value['label'], $this->textdomain).'</label><br />';
						foreach ($field_value['list'] as $key => $value) {
							if ($default_value == $key) $string = 'checked'; else $string = '';
							$form .= "\n".'<input type="radio" id="'.$item_name.'" name="'.$item_name.'" value="'.$key.'" '.$string.' />'.__($value, $this->textdomain).'<br />';
						}
						$form .= "\n".'</p>';
					break;

					case 'checkbox':
						if (! isset($field_value['list'])) {
							$form .= "\n".'<p><input type="checkbox" id="'.$item_name.'" name="'.$item_name.'" value="1" '.($default_value==0?'':'checked').' /> <label for="'.$item_name.'">'.__($field_value['label'], $this->textdomain).'</label></p>';
						}
						else {
							$index = 0;
							$form .= "\n".'<p><label for="'.$item_name.'">'.__($field_value['label'], $this->textdomain).'</label><br />';
							foreach ($field_value['list'] as $key => $value) {
								if (is_array($default_value) && in_array($key, $default_value)) $string = 'checked'; else $string = '';
								$form .= "\n".'<input type="checkbox" id="'.$item_name.'['.$index.']" name="'.$item_name.'['.$index.']" value="'.$key.'" '.$string.' />'.__($value, $this->textdomain).'<br />';
								$index++;
							}
							$form .= "\n".'</p>';
						}
					break;
				}
			}
			$form .= "\n".'<input type="hidden" id="'.$this->id.($number<0?'':'-'.$number).'-submit" name="'.$this->id.'-'.$number.'-submit" value="1" />';
			return ($form);
		}

		/**
		 * get_form_values - Get the form values registered by user
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function update($number = -1) {

			if ($number < 0) $number = 1;
			$new_options = $this->options[$number];

			foreach( $this->fields as $field_name => $field_value) {

				$item_name     = $this->id.'-'.$number.'-'.$field_name;
				$default_value = $this->defaut_values[$field_name];

				switch ($field_value['type']) {
					case 'text':
					case 'ftext':
					case 'select':
					case 'radio':
						if (isset($_POST[$item_name])) $new_options[$field_name] = attribute_escape($_POST[$item_name]);
						else $new_options[$field_name] = $default_value;
					break;

					case 'numeric':
						$value = attribute_escape($_POST[$item_name]);
						if (is_numeric($value)) $new_options[$field_name] = intval($value);
						else $new_options[$field_name] = $default_value;
					break;

					case 'checkbox':
						if (! isset($field_value['list'])) {
							$new_options[$field_name] = (isset($_POST[$item_name])?1:0);
						}
						else {
							$new_options[$field_name] = $_POST[$item_name];
						}
					break;
				} // End of switch
				$this->options[$number] = $new_options;
				update_option($this->options_entry, $this->options);
			} // End of foreach field
		} // End of update

		/**
		 * default_to_options
		 *
		 * Copy defaults values to options (with translation if required)
		 *
		 * @package EG-Widgets
		 *
 		 * @param	array	$options		list of options,
		 * @param	array	$defaults		list of default values
		 * @return	none
		 */
		function default_to_options(&$options, $defaults) {
			foreach ($defaults as $key => $value) {
				if (! isset($options[$key])) {
					if (is_string($value)) $options[$key] = __($value, $this->textdomain);
					else $options[$key] = $value;
				}
			} // End of foreach
		} // End of default_to_options

		/**
		 * install_update_options
		 *
		 * Create or update options
		 *
		 * @package EG-Widgets
		 *
 		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function install_update_options($number = -1) {

			if ($number < 0) $number = 1;

			if ($this->options  === FALSE) {
				$this->default_to_options($this->options[$number], $this->default_values);
				$this->options['_multiwidget'] = 1;
				add_option($this->options_entry, $this->options);
			}
			else {
				if (! isset($this->options[$number])) {
					$this->options[$number] = array();
				}
				if (sizeof($this->options[$number]) != sizeof($this->default_values)) {
					$this->default_to_options($this->options[$number], $this->default_values);
					update_option($this->options_entry, $this->options);
				}
			}
		} // End of install_update_options

		/**
		 * is_visible - Return flag to know if the widget can be displayed or not
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function is_visible($number = -1) {
			global $locale;

			if ($number < 0) $number = 1;
			$options = & $this->options[$number];

			// By default: the widget is visible
			$value = TRUE;
			if (isset($options['show_when']) && $options['show_when'] != 'all') {

				// Id or list of id specifided?
				$id_list = '';
				if ($options['show_id'] != '') {
					$id_list = explode(',', $options['show_id']);
				}
				switch ($options['show_when']) {

					case 'home':
						$value = is_home();
					break;

					case 'categories':
						$value = is_category($id_list);
					break;

					case 'posts':
						$value = is_single($id_list);
					break;

					case 'tags':
						$value = is_tag($id_list);
					break;
				}
			}
			if (isset($options['show_lang']) && $options['show_lang'] != 'all') {
				$value = ($locale == $options['show_lang']);
			}
			if (isset($options['hide_lang']) && $options['hide_lang'] != 'none') {
				$value = ($locale != $options['hide_lang']);
			}
			return ($value);
		} /* End of function is_visible */

		/**
		 * setup - Get values from the widget admin page form, and save them
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	none
		 */
		function setup() {

			// Run only if user click on the right submit button
			if ( isset($_POST[$this->id.'_number_submit']) ) {

				// get the id
				$number = (int) attribute_escape($_POST[$this->id.'_number']);

				// Filter this id
				if ( $number > MULTI_WIDGET_MAX_NUMBER ) $number = MULTI_WIDGET_MAX_NUMBER;
				if ( $number < 1 ) $number = 1;

				// If the id is different than the previous, then save it
				if ($number != $this->current_number) {
					$this->current_number = $number;
					$this->register();
				}
			}
		} /* End of function setup */

		/**
		 * page - Display additional form in the widget admin page
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	none
		 */
		function page() {
		?>
			<div class="wrap">
				<form method="POST">
					<h2><?php _e($this->name, $this->textdomain); ?></h2>
					<p style="line-height: 30px;">
						<?php printf(__('How many %s widgets would you like?', $this->textdomain), $this->name); ?>
					<select id="<?php echo $this->id; ?>_number" name="<?php echo $this->id; ?>_number">
				<?php for ( $i = 1; $i <= MULTI_WIDGET_MAX_NUMBER; ++$i )
						echo '<option value="'.$i.'"'.($this->current_number==$i ? 'selected="selected"' : '').'>'.$i.'</option>';
				?>
					</select>
					<span class="submit"><input type="submit" name="<?php echo $this->id; ?>_number_submit" id="<?php echo $this->id; ?>_number_submit" value="<?php _e('Save', $this->textdomain); ?>" /></span></p>
				</form>
			</div>
		<?php
		} /* End of function page */

	} /* End of Class EG_Widget */

} /* End of if class_exists */

?>