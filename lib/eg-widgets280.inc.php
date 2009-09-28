<?php
/*
Package Name: EG-Widgets
Plugin URI:
Description:  Abstract class to create and manage widget
Version: 2.0
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

if (!class_exists('EG_Widget_200')) {

	/**
	  *  EG_Widget - Class
	  *
	  * {@internal Missing Long Description}
	  *
	  * @package EG-Widgets
	  *
	  */
	class EG_Widget_200 extends WP_Widget {

		var $textdomain;
		var $plugin_corefile;
		var $cexpiration;
		var $display_conditions;
		var $fields;
		var $default_values;

		var $language_list = array(
			'fr_FR' => 'Fran&ccedil;ais',
			'en_US' => 'English',
			'es_ES' => 'Espa&ntilde;ol',
			'de_DE' => 'Deutsch',
			'it_IT' => 'Italiano'
		);

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
			$this->textdomain 		  = $textdomain;
			$this->plugin_corefile	  = $plugin_corefile;
			$this->cache_expiration   = $cache_expiration;
		}

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
			$this->fields         = $fields;
			$this->default_values = default_values;
			$this->display_conditions = $display_conditions;

			if ($this->display_conditions) {

				$this->fields['separator'] = array(
					'type'    => 'separator'
				);
				$this->fields['show_when'] = array(
						'type'    => 'select',
						'label'   => 'Show widget on pages',
						'list'  => array( 'all'        => 'All',
										  'home'	   => 'Home',
										  'categories' => 'Categories',
										  'posts'      => 'Posts',
										  'tags'       => 'Tags')
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
		}

		/**
		 * is_visible - Return flag to know if the widget can be displayed or not
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function is_visible($values) {
			global $locale;

			// By default: the widget is visible
			$value = TRUE;
			if (isset($values['show_when']) && $values['show_when'] != 'all') {

				// Id or list of id specifided?
				$id_list = '';
				if ($values['show_id'] != '') {
					$id_list = explode(',', $values['show_id']);
				}
				switch ($values['show_when']) {
				
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
			if (isset($values['show_lang']) && $values['show_lang'] != 'all') {
				$value = ($locale == $values['show_lang']);
			}
			if (isset($values['hide_lang']) && $values['hide_lang'] != 'none') {
				$value = ($locale != $values['hide_lang']);
			}
			return ($value);
		} /* End of function is_visible */

		/**
		 * get_form_values - Get the form values registered by user
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	$number	int		id of the widget (in case of multi-widget only)
		 */
		function update($new_instance, $old_instance) {

			$instance = $old_instance;

			foreach( $this->fields as $field_name => $field_value) {

				switch ($field_value['type']) {
					case 'text':
					case 'ftext':
					case 'select':
					case 'radio':
						$instance[$field_name] = stripslashes($new_instance[$field_name]);
					break;

					case 'numeric':
						$value = $new_instance[$field_name];
						if (is_numeric($value)) $instance[$field_name] = intval($value);
					break;

					case 'checkbox':
						if (! isset($field_value['list'])) {
							if (isset($new_instance["$field_name"])) $instance[$field_name] = 1;
							else $instance[$field_name] = 0;
						}
						else {
							$instance[$field_name] = $new_instance["$field_name"];
						}
					break;
				} // End of switch
			} // End of foreach field
			return ($instance);
		} // End of update

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
		function generate_select_form($id, $name, $values, $default) {
			$select_string = '<select id="'.$id.'" name="'.$name.'">';
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
		 * generate_form - Display the widget control panel form
		 *
		 * {@internal Missing Long Description}
		 *
		 * @package EG-Widgets
		 *
 		 * @param	object	$instance		widget options
		 */
		function form($instance) {

			$default_values = wp_parse_args( (array) $instance, $this->default_values );

			$form = '';
			foreach ($this->fields as $field_name => $field_value) {

				$form_field_id   = $this->get_field_id($field_name);
				$form_field_name = $this->get_field_name($field_name);

				if (!is_array($default_values[$field_name])) {
					$def_value = attribute_escape($default_values[$field_name]);
					if (is_string($def_value)) $def_value = __($def_value, $this->textdomain);
				}
				else {
					$def_value = $default_values[$field_name];
				}

				switch ($field_value['type']) {

					case 'comment':
						$form .= '<p><strong>'.__($field_value['label'], $this->textdomain).'</strong></p>';
					break;

					case 'separator':
						$form .= '<hr />';
					break;

					case 'numeric':
						$form .= "\n".'<p><label for="'.$form_field_name.'">'.__($field_value['label'], $this->textdomain).': '.
						         "\n".'<input type="text" id="'.$form_field_id.'" name="'.$form_field_name.'" value="'.$def_value.'" size="10" />'.
								 "\n".'</label></p>';
					break;

					case 'text':
					case 'ftext':
						$form .= "\n".'<p><label for="'.$form_field_name.'">'.__($field_value['label'], $this->textdomain).': '.
						         "\n".'<input type="text" id="'.$form_field_id.'" name="'.$form_field_name.'" value="'.format_to_edit($def_value).'" size="10" />'.
								 "\n".'</label></p>';
					break;
					case 'select':
						$form .= "\n".'<p><label for="'.$form_field_name.'">'.__($field_value['label'], $this->textdomain).': '.
						         "\n".$this->generate_select_form($form_field_id, $form_field_name, $field_value['list'], $def_value).
								 "\n".'</label></p>';
					break;

					case 'radio':
						$form .= "\n".'<p><label for="'.$form_field_name.'">'.__($field_value['label'], $this->textdomain).'</label><br />';
						foreach ($field_value['list'] as $key => $value) {
							if ($def_value == $key) $string = 'checked'; else $string = '';
							$form .= "\n".'<input type="radio" id="'.$form_field_id.'" name="'.$form_field_name.'" value="'.$key.'" '.$string.' />'.__($value, $this->textdomain).'<br />';
						}
						$form .= "\n".'</p>';
					break;

					case 'checkbox':
						if (! isset($field_value['list'])) {
							$form .= "\n".'<p><input type="checkbox" id="'.$form_field_id.'" name="'.$form_field_name.'" value="1" '.($def_value==0?'':'checked').' /> <label for="'.$form_field_name.'">'.__($field_value['label'], $this->textdomain).'</label></p>';
						}
						else {
							$index = 0;
							$form .= "\n".'<p><label for="'.$form_field_name.'">'.__($field_value['label'], $this->textdomain).'</label><br />';
							foreach ($field_value['list'] as $key => $value) {
								if (is_array($def_value) && in_array($key, $def_value)) $string = 'checked'; else $string = '';
								$form .= "\n".'<input type="checkbox" id="'.$form_field_id.'['.$index.']" name="'.$form_field_name.'['.$index.']" value="'.$key.'" '.$string.' />'.__($value, $this->textdomain).'<br />';
								$index++;
							}
							$form .= "\n".'</p>';
						}
					break;
				} // End of switch
			} // End of foreach
			echo $form;
		} // End of form

	} // End of class

} // End of if exist class