<?php
/*
Plugin Name: EG-Forms
Plugin URI:
Description: Class to build admin forms
Version: 1.0.5
Author: Emmanuel GEORJON
Author URI: http://www.emmanuelgeorjon.com/
*/

/*
    Copyright 2009 Emmanuel GEORJON  (email : blog@georjon.eu)

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

if (!class_exists('EG_Forms_105')) {

	Class EG_Forms_105 {

		var $sections = array();
		var $fields   = array();
		var $buttons  = array();

		var $title ;
		var $header;
		var $footer;
		var $textdomain ;
		var $url;
		var $id_icon ;
		var $security_key ;
		var $author_address;
		var $access_level;
		
		
		/**
		 * EG_Forms (constructor)
		 *
		 * Init object
		 *
		 * @package EG-Forms
		 *
		 * @param 	string	$title			form title
		 * @param 	string	$header		text to display before the first section or field
		 * @param	string	$footer		text to display at the form's bottom (before submit button)
		 * @param	string	$textdomain	textdomain
		 * @param	string	$url			url for form action
		 * @param	string	$id_icon		icon id to display before the title
		 * @param	string	$security_key	key to generate nonce
		 * @param	string	$author_address	author email or URL (must include mailto: or http:
		 * @return 	none
		 */
		function EG_Forms_105($title, $header, $footer, $textdomain, $url, $id_icon, $security_key, $author_address, $access_level=FALSE) {
			register_shutdown_function(array(&$this, "__destruct"));
			$this->__construct($title, $header, $footer, $textdomain, $url, $id_icon, $security_key, $author_address, $access_level);
		}

		/**
		 * __construct(constructor)
		 *
		 * Init object
		 *
		 * @package EG-Forms
		 *
		 * @param 	string	$title			form title
		 * @param 	string	$header		text to display before the first section or field
		 * @param	string	$footer		text to display at the form's bottom (before submit button)
		 * @param	string	$textdomain	textdomain
		 * @param	string	$url			url for form action
		 * @param	string	$id_icon		icon id to display before the title
		 * @param	string	$security_key	key to generate nonce
		 * @param	string	$author_address	author email or URL (must include mailto: or http:
		 * @return 	none
		 */
		function __construct($title, $header, $footer, $textdomain, $url, $id_icon, $security_key, $author_address,$access_level=FALSE) {
			$this->title          = $title;
			$this->header         = $header;
			$this->footer         = $footer;
			$this->textdomain     = $textdomain;
			$this->url    		  = $url; // sanitize_url($url);
			$this->id_icon    	  = $id_icon;
			$this->security_key   = $security_key;
			$this->author_address = $author_address;
			$this->access_level   = $access_level;
		}

		/**
		 * __destruct(constructor)
		 *
		 * @package EG-Forms
		 *
		 * @param	none
		 * @return 	none
		 */
		function __destruct() {
		}

		/**
		 * save_form
		 *
		 * @package EG-Forms
		 *
		 * @param	string	$file_path	configuration file
		 * @return 	none
		 */
		function save_form($file_path) {
			$handle = fopen($file_path, 'w');
			fwrite($handle, serialize($this));
			fclose($handle);
		}

		/**
		 * read_form
		 *
		 * @package EG-Forms
		 *
		 * @param	string	$file_path	configuration file
		 * @return 	object				object of EG_Forms class
		 */
		function read_form($file_path) {
			return unserialize(file_get_contents($file_path));
		}

		/**
		 * add_section
		 *
		 * Init object
		 *
		 * @package EG-Forms
		 *
		 * @param 	string	$section_title	title of a section (can be '')
		 * @param 	string	$header		text to display before the first field of the section
		 * @param	string	$footer		text to display after the last field of the section
		 * @return 	string				id of the section
		 */
		function add_section($section_title, $header='', $footer='') {
			$index = 'eg_form_s'.sizeof($this->sections);
			$this->sections[$index]->title  = $section_title;
			$this->sections[$index]->header = $header;
			$this->sections[$index]->footer = $footer;
			$this->sections[$index]->groups = array();
			return ($index);
		}

		/**
		 * add_group
		 *
		 * Init object
		 *
		 * @package EG-Forms
		 *
		 * @param	string	$section_id	id of the section within we have to add the group
		 * @param 	string	$group_title	title of the group
		 * @param 	string	$header		text to display before the first field of the group
		 * @param	string	$footer		text to display after the last field of the group
		 * @return 	string				id of the group
		 */
		function add_group($section_id, $group_title, $header='', $footer='') {

			$index = FALSE;
			if (isset($this->sections[$section_id])) {
				$groups = & $this->sections[$section_id]->groups;
				$index = $section_id.'_g'.sizeof($groups);
				$groups[$index]->title  = $group_title;
				$groups[$index]->header = $header;
				$groups[$index]->footer = $footer;
				$groups[$index]->fields = array();
			}
			return ($index);
		}

		/**
		 * set_field_values
		 *
		 * for select and input radio only
		 * define list of values to use with these HTML tag
		 *
		 * @package EG-Forms
		 *
		 * @param	string	$option_name	id of the field to modify
		 * @param 	array		$values		list of value to use
		 * @return 	none
		 */
		function set_field_values($option_name, $values) {
			if (is_array($values)) {
				foreach ($values as $key => $value) {
					$values[$key] = $value;
				}
			}
			$this->fields[$option_name]->values = $values;
		}

		/**
		 * add_field
		 *
		 * Add a field the form
		 *
		 * @package EG-Forms
		 *
		 * @param	string	$section_id		id of the section within we have to add the field
		 * @param 	string	$group_id		id of the group within we have to add the field
		 * @param 	string	$type			text, select, checkbox, radio
		 * @param	string	$label			label of the field
		 * @param	string	$text_before	text to display before the field
		 * @param	string	$text_after		text to display after the field
		 * @param	string	$description	description of the field
		 * @param	string	$option_name	id of the field (must be same name than option entry we want to modify)
		 * @param	string	$status			disabled for example
		 * @param	string	$size			small, regular or large
		 * @param	array	$values			list of values to use (for select and radio only)
		 * @return 	string					id of the field
		 */
		function add_field($section_id,
							$group_id,
							$type,
							$label,
							$option_name,
							$text_before = '',
							$text_after  = '',
							$description = '',
							$status      = '',
							$size        = 'regular',
							$values      = FALSE) {
			$index = FALSE;
			if (isset($this->sections[$section_id]) && isset($this->sections[$section_id]->groups[$group_id]) ) {
				$this->sections[$section_id]->groups[$group_id]->list[] = $option_name;

				$index = $option_name;
				$this->fields[$index]->type         = $type;
				$this->fields[$index]->label        = $label;
				$this->fields[$index]->text_after   = $text_after;
				$this->fields[$index]->text_before  = $text_before;
				$this->fields[$index]->description  = $description;
				$this->fields[$index]->status  		= $status;
				$this->fields[$index]->size   		= $size;
				$this->set_field_values($index, $values);
			}
			return ($index);
		}

		/**
		 * add_button
		 *
		 * Add button to the form
		 *
		 * @package EG-Forms
		 *
		 * @param 	string	$type		submit, reset
		 * @param	string	$name		name of the button
		 * @param	string	$value		value
		 * @return 	none
		 */
		function add_button($type, $name, $value, $callback='submit') {
			$index = sizeof($this->buttons);
			$this->buttons[$index]->type  = $type;
			$this->buttons[$index]->name  = $name;
			$this->buttons[$index]->value = $value;
			$this->buttons[$index]->callback = 'submit';
			if ($callback != 'submit' &&
				is_callable(array(&$this, $callback)) &&
				method_exists($this, $callback) ) $this->buttons[$index]->callback = $callback;
		}

		/**
		 * reset_to_default
		 *
		 * Reset form values to defaults
		 *
		 * @package EG-Forms
		 *
		 * @param 	array		$options	list of the options to update with the form value
		 * @param	array		$defaults	list of default values
		 * @return 	array				updated options
		 */
		function reset_to_defaults($options, $defaults) {
			return ($defaults);
		}

		/**
		 * get_form_values
		 *
		 * Add button to the form
		 *
		 * @package EG-Forms
		 *
		 * @param 	array		$options	list of the options to update with the form value
		 * @param	array		$defaults	list of default values
		 * @return 	array				updated options
		 */
		function get_form_values($options, $defaults, $update_options=FALSE) {

			// Which button to we use?
			$is_submitted = FALSE;
			foreach ($this->buttons as $button) {
				if ($button->type == 'submit') {
					if (isset($_POST[$button->name]) && $_POST[$button->name] == __($button->value, $this->textdomain)) {
						$is_submitted = $button->callback;
					break;
					}
				}
			}

			$new_options = FALSE;
			// Security ok and submit button hit
			if ($is_submitted !== FALSE) {

				if ( !wp_verify_nonce($_POST['_wpnonce'], $this->security_key) ) {
					echo '<div class="wrap">';
					if (function_exists('screen_icon')) screen_icon();
/*
					($this->id_icon!=''?'<div id="'.$this->id_icon.'" class="icon32"></div>':'').
*/
					echo ($this->title==''?'':'<h2>'.__($this->title, $this->textdomain).'</h2>').
					'<div id="message" class="error fade"><p>'.sprintf(__('Security problem. Try again. If this problem persist, contact <a href="%s">plugin author</a>.', $this->textdomain), $this->author_address).'</p></div>'.
					'</div>';

					die();
				}

				if ($is_submitted != 'submit') {
					if (is_array($is_submitted))
						$new_options = call_user_func($is_submitted, $options, $defaults);
					else
						$new_options = call_user_func(array(&$this, $is_submitted), $options, $defaults);
				}
				else {
					$new_options = $options;
					foreach ($this->fields as $key => $field) {
						if (isset($options[$key])) {
							if (isset($_POST[$key])) {
								if (!is_array($_POST[$key])) {
									if (is_float($_POST[$key])) $new_options[$key] = floatval($_POST[$key]);
									elseif (is_int($_POST[$key])) $new_options[$key] = intval($_POST[$key]);
									else $new_options[$key] = attribute_escape($_POST[$key]);
								}
								else {
									$new_options[$key] = (array)$_POST[$key];
								}
							}
							elseif ($field->type == 'checkbox') {
								$new_options[$key] = 0;
							}
						}
					}
				}
				if ($update_options !== FALSE && $update_options!='' && $new_options!=$options) update_option($update_options, $new_options);
			}
			return ($new_options);
		}

		/**
		 * display_field
		 *
		 * Add button to the form
		 *
		 * @package EG-Forms
		 *
		 * @param 	string	$option_name	id of the field to display
		 * @param	boolean	$group		is the field in a group or standalone
		 * @param 	array		$default_values	list of default values
		 * @return 	string				HTML code to display
		 */
		function display_field($option_name, $group, $default_values) {

			// if field doesn't exist => stop
			if (! isset($this->fields[$option_name]))
				return '';
			else {
				// Get field
				$field = $this->fields[$option_name];

				// in all the procedure: if group = TRUE, we are in a set of field. if group = FALSE, the current group contains ony one field
				$string = ($group?'<li>':'');
				switch ($field->type) {
					case 'text':
					case 'password':
						if ($field->text_before!= '' || $field->text_after != '') {
							$string .= ($group?'<label for="'.$option_name.'">'.__($field->label, $this->textdomain):'').
								($field->text_before== ''?'':__($field->text_before, $this->textdomain)).
								'<input type="'.$field->type.'" class="'.$field->size.'-text" name="'.$option_name.'" id="'.$option_name.'" value="'.$default_values[$option_name].'" '.$field->status.'/> '.
								($field->text_after== ''?'':__($field->text_after, $this->textdomain)).
								($group?'</label>':'');
						} else {
							$string .= ($group?'<label for="'.$option_name.'">'.__($field->label, $this->textdomain).'</label>':'').
								'<input type="'.$field->type.'" class="'.$field->size.'-text" name="'.$option_name.'" id="'.$option_name.'" value="'.$default_values[$option_name].'" '.$field->status.'/> ';
						}
					break;

					case 'checkbox':
						if (! is_array($field->values)) {
							$string .= ($group?'<label for="'.$option_name.'">':'').
									($field->text_before== ''?'':__($field->text_before, $this->textdomain)).
									'<input type="checkbox" name="'.$option_name.'" id="'.$option_name.'" value="1" '.($default_values[$option_name]==1?'checked':'').' '.$field->status.' /> '.
									__($field->label, $this->textdomain).
									($field->text_after== ''?'':__($field->text_after, $this->textdomain)).
									($group?'</label>':'');
						}
						else {
							$string .= '<fieldset><legend class="hidden">'.__($field->label, $this->textdomain).'</legend>'.
										($field->text_before== ''?'':__($field->text_before, $this->textdomain).'<br />').
										__($field->label, $this->textdomain).'<br />';

							foreach ($field->values as $key => $value) {
								if (!is_array($default_values[$option_name])) {
									$checked = ($key === $default_values[$option_name]?'checked':'');
								}
								else {
									$checked = (in_array($key, $default_values[$option_name])===FALSE?'':'checked');
								}
								$string .= ($group?'<label for="'.$option_name.'['.$key.']">':'').
									'<input type="checkbox" name="'.$option_name.'['.$key.']" id="'.$option_name.'['.$key.']" value="'.$key.'" '.$checked.' '.$field->status.' /> '.
									__($value, $this->textdomain).
									($group?'</label>':'').
									'<br />';
							}
							$string .= ($field->text_after== ''?'':__($field->text_after, $this->textdomain)).'</fieldset>';
						}
					break;

					case 'select':
						$string .= ($group?'<label for="'.$option_name.'">'.__($field->label, $this->textdomain):'').
									($field->text_before== ''?'':__($field->text_before, $this->textdomain)).
								  '<select name="'.$option_name.'" id="'.$option_name.'" >';
						foreach ($field->values as $key => $value) {
							$selected = ($default_values[$option_name]==$key?'selected':'');
							$string .= '<option value="'.$key.'" '.$selected.'>'.($value==''?'':__($value, $this->textdomain)).'</option>';
						}
						$string .= '</select>'.($field->text_after== ''?'':__($field->text_after, $this->textdomain)).($group?'</label>':'');
					break;

					case 'radio':
						$string .= '<fieldset><legend class="hidden">'.__($field->label, $this->textdomain).'</legend>';
						foreach ($field->values as $key => $value) {
							$checked = ($default_values[$option_name]==$key?'checked':'');
							$string .= ($group?'<label for="'.$option_name.'">':'').
								'<input type="radio" name="'.$option_name.'" id="'.$option_name.'" value="'.$key.'" '.$checked.' '.$field->status.'/> '.
								__($value, $this->textdomain).
								($group?'</label>':'').
								'<br />';
						}
						$string .= '</fieldset>';
					break;

					case 'grid select':
						if (! isset($field->values['header']) || sizeof($field->values['header']) == 0 ||
							! isset($field->values['list'])   || sizeof($field->values['list'])   == 0) {
							$string .= '<p><font color="red">'.__('No data available', $this->textdomain).'</font></p>';
						}
						else {
							$grid_default_values = $default_values[$option_name];
							$string .= '<fieldset><legend class="hidden">'.__($field->label, $this->textdomain).'</legend><table border="0"><thead><tr>';
							foreach ($field->values['header'] as $item) {
								$string .= '<th>'.__($item, $this->textdomain).'</th>';
							}
							$string .= '</tr></thead><tbody>';
							foreach ($field->values['list'] as $item) {
								$string .= '<tr><td>'.
									'<input type="text" value="'.$item['value'].'" disabled /></td><td>'.
									($group?'<label for="'.$option_name.'['.$item['value'].']">':'').
									'<select name="'.$option_name.'['.$item['value'].']" id="'.$option_name.'['.$item['value'].']" >';
								foreach ($item['select'] as $key => $value) {
									if ($key == $grid_default_values[$item['value']]) $selected = 'selected';
									else $selected = '';
									$string .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
								}
								$string .=	'</select>'.($group?'</label>':'').'</td></tr>';
							}
							$string .= '</tbody></table></fieldset>';
						}
					break;
				}
				// Adding description
				if ($field->description) $string .= '<br /><span class="setting-description">'.__($field->description, $this->textdomain).'</span>';

				// Close the list (if group = TRUE only)
				$string .= ($group?'</li>':'');

				return $string;
			}
		}

		/**
		 * display_group
		 *
		 * Display a set of fields
		 *
		 * @package EG-Forms
		 *
		 * @param 	object	$group		group to display
		 * @param 	array		$default_values	list of default values
		 * @return 	none
		 */
		function display_group($group, $default_values) {

			// How many field do we have in this group?
			if (sizeof($group->list) == 1) {
				// Get the field
				$option_name = current($group->list);
				echo '<tr valign="top"><th scope="row">'.
					'<label for="'.$option_name.'">'.__($group->title, $this->textdomain).'</label>'.
					'</th><td>'.
					($group->header==''?'':'<p>'.__($group->header, $this->textdomain).'</p>').
					$this->display_field($option_name, FALSE, $default_values).
					($group->footer==''?'':'<p>'.__($group->footer, $this->textdomain).'</p>').
					'</td></tr>';
			} else {
				// Several field for this group
				echo '<tr valign="top"><th scope="row">'.__($group->title, $this->textdomain).'</th><td>'.
					($group->header==''?'':'<p>'.__($group->header, $this->textdomain).'</p>').
					'<fieldset><legend class="hidden">'.__($group->title, $this->textdomain).'</legend><ul>';
				// Displaying all of fields
				foreach ($group->list as $option_name) {
					echo $this->display_field($option_name, TRUE, $default_values);
				}
				echo '</ul></fieldset>'.
					($group->footer==''?'':'<p>'.__($group->footer, $this->textdomain).'</p>').
					'</td></tr>';
			}
		}

		/**
		 * display_section
		 *
		 * Display an entire form section
		 *
		 * @package EG-Forms
		 *
		 * @param 	object	$section		section to display
		 * @param 	array		$default_values	list of default values
		 * @return 	none
		 */
		function display_section($section, $default_values) {
			echo '<h3>'.__($section->title, $this->textdomain).'</h3>'.
				($section->header==''?'':'<p>'.__($section->header, $this->textdomain).'</p>').
				'<table class="form-table">'.
				'<tbody>';

			foreach ($section->groups as $group) {
				$this->display_group($group, $default_values);
			}
			echo '</tbody>'.
				'</table>'.
				($section->footer==''?'':'<p>'.__($section->footer, $this->textdomain).'</p>');
		}

		/**
		 * display_form
		 *
		 * Display the current form
		 *
		 * @package EG-Forms
		 *
		 * @param 	array		$default_values	list of default values
		 * @return 	none
		 */
		function display_form($default_values) {
			$display_wrap = ($this->title!='' || $this->id_icon!='' || $this->header!='');
			echo ($display_wrap?'<div class="wrap">':'').
				($this->id_icon!=''?'<div id="'.$this->id_icon.'" class="icon32"></div>':'').
				($this->title==''?'':'<h2>'.__($this->title, $this->textdomain).'</h2>').
				($this->header==''?'':'<p>'.__($this->header, $this->textdomain).'</p>');
				
			if ($this->access_level !== FALSE && ! current_user_can($this->access_level)) {
				echo '<div id="message" class="error fade"><p>'.
					sprintf(__('You cannot access to the synchronization page. You haven\'t the "%1s" capability. Please contact <a href="%2s">the blog administrator</a>.', $this->textdomain), $this->access_level, $this->author_address).
					'</p></div>';
			}
			else {
				echo '<form method="post" action="'.$this->url.'">'.
					wp_nonce_field($this->security_key);

				foreach ($this->sections as $section) {
					$this->display_section($section, $default_values);
				}

				echo ($this->footer==''?'':'<p>'.$this->footer.'</p>').'<p>&nbsp;</p>';
				foreach ($this->buttons as $button) {
					echo '<input type="'.$button->type.'" class="button-primary" name="'.$button->name.'" value="'.__($button->value, $this->textdomain).'"/> ';
				}
				echo '</form>';
			}
			echo ($display_wrap?'</div>':'');
		}

	} /* End of class EG_Forms */
} /* End of Class_exists */
?>