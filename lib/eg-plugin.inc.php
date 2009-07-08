<?php
/*
Plugin Name: EG-Plugin
Plugin URI:
Description: Framework for plugin development
Version: 1.0.3
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

if (!class_exists('EG_Plugin_103')) {

	/**
	  * Class EG_Plugin
	  *
	  * Provide some functions to create a WordPress plugin
	  *
	 */
	Class EG_Plugin_103 {

		var $plugin_name;
		var $plugin_version;
		var $plugin_path;
		var $plugin_url;
		var $plugin_core_file;
		var $plugin_lang_path;

		var $plugin_author_name;
		var $plugin_author_url;
		var $plugin_author_email;

		var $stylesheet;
		var $admin_stylesheet;

		var $options_entry;
		var $options;
		var $default_options;

		var $tinyMCE_button;
		var $cacheexpiration = 0;
		var $textdomain      = '';

		var $wp_version_min = '2.5';
		var $wp_version_max;
		var $wpmu_version_min;
		var $wpmu_version_max = '2.7';
		var $php_version_min;
		var $php_extensions;
		var $php_options;
		var $requirements_error_msg = '';

		var $pages;

		/**
		  * Class contructor for PHP 4 compatibility
		  *
		  * @package EG-Plugins
		  * @return object
		  *
		  */
		function EG_Plugin_103($name, $version, $core_file, $options_entry, $default_options=FALSE) {

			register_shutdown_function(array(&$this, "__destruct"));
			$this->__construct($name, $version, $core_file, $options_entry, $default_options);
		}

		/**
		  * Class contructor
		  * Define the plugin url and path. Declare action INIT and HEAD.
		  *
		  * @package EG-Plugins
		  * @return object
		  */
		function __construct($name, $version, $core_file, $options_entry, $default_options=FALSE) {

			$this->plugin_name     = $name;
			$this->plugin_version  = $version;
			$this->options_entry   = $options_entry;
			$this->default_options = $default_options;

			// Define WP_CONTENT_URL and WP_CONTENT_DIR for WordPress < 2.6
			if ( !defined('WP_CONTENT_URL') ) {
			    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
			}
			if ( !defined('WP_CONTENT_DIR') )
			    define( 'WP_CONTENT_DIR', trailingslashit(ABSPATH).'wp-content' );

			if ( !defined( 'WP_PLUGIN_URL' ) )
				define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );

			if ( !defined('WP_PLUGIN_DIR') )
			    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

			// Sanitize windows path (with backslashes)
			$wp_plugin_dir = str_replace('\\', '/', WP_PLUGIN_DIR);
			$abspath       = str_replace('\\', '/', ABSPATH);

			/* Define the plugin path */
			$plugin_base_path		= basename( dirname($core_file) );
			$this->plugin_path 		= trailingslashit($wp_plugin_dir.'/'.$plugin_base_path);
			$this->plugin_url  		= trailingslashit(WP_PLUGIN_URL.'/'.$plugin_base_path);
			$this->plugin_core_file = $this->plugin_path.basename($core_file);
			$this->plugin_lang_path = str_replace($abspath, '', $wp_plugin_dir).'/'.$plugin_base_path.'/lang';
		}

		/**
		 * Class destructor
		 *
		 * @package EG-Plugins
		 * @return boolean true
		 */
		function __destruct() {
			// Nothing for the moment
		}

		function load() {
			add_action('plugins_loaded', array(&$this, 'plugins_loaded'), 0);
			add_action('init', array( &$this, 'init'));
			add_action('wp_logout', array(&$this, 'wp_logout'));

		}

		function wp_logout() {
			// Nothing here.
		}
		
		/**
		 * set_owner
		 *
		 * Set owner parameters
		 *
		 * @package EG-Plugins
		 *
		 * @param	string	$author_name	Name of the plugin author
		 * @param	string	$author_url	Url of site or blog
		 * @param	string	$author_email	email of the author
		 *
		 * @return none
		 */
		function set_owner($author_name='', $author_url='', $author_email='') {
			$this->plugin_author_name  = $author_name;
			$this->plugin_author_url   = $author_url;
			$this->plugin_author_email = $author_email;
		}

		/**
		 * set_stylesheet
		 *
		 * Set stylesheet for public and admin interface
		 *
		 * @package EG-Plugins
		 *
		 * @param	string	$stylesheet			stylesheet for the public side
		 * @param	string	$admin_stylesheet	stylesheet for the admin side
		 * @return none
		 */
		function set_stylesheets($stylesheet, $admin_stylesheet=FALSE) {
			$this->stylesheet       = $stylesheet;
			$this->admin_stylesheet = $admin_stylesheet;
		}

		/**
		 * set_textdomain
		 *
		 * Set internationalization parameters
		 *
		 * @package EG-Plugins
		 *
		 * @param	string	$textdomain		text domain
		 * @return none
		 */
		function set_textdomain($textdomain='') {
			$this->textdomain = $textdomain;
		}

		/**
		 * get_textdomain
		 *
		 *
		 *
		 * @package EG-Plugins
		 *
		 * @param none
		 * @return string $textdomain
		 */
		function get_textdomain() {
			return ($this->textdomain);
		}

		/**
		 * set_wp_versions
		 *
		 * Set compliance parameters with WordPress and WordPress MU
		 *
		 * @package EG-Plugins
		 *
		 * @param	string	$wp_version_min		minimum version for WordPress
		 * @param	string	$wp_version_max	maximum version for WordPress
		 * @param	string	$wpmu_version_min	minimum version for WordPress MU
		 * @param	string	$wpmu_version_max	maximum version for WordPress MU
		 * @return none
		 */
		function set_wp_versions($wp_version_min, $wp_version_max='', $wpmu_version_min='', $wpmu_version_max='') {
			$this->wp_version_min	= $wp_version_min;
			$this->wp_version_max	= $wp_version_max;
			$this->wpmu_version_min	= $wpmu_version_min;
			$this->wpmu_version_max	= $wpmu_version_max;
		}

		/**
		 * set_php_version
		 *
		 * Set compliance parameters with WordPress and WordPress MU
		 *
		 * @package EG-Plugins
		 *
		 * @param	string	$min_version		minimum version for PHP
		 * @return 	none
		 */
		function set_php_version($version_min, $options_list=FALSE, $extensions_list=FALSE ) {
			$this->php_version_min = $version_min;
			$this->php_options     = $options_list;
			$this->php_extensions  = $extensions_list;
		}

		/**
		 * add_tinymce_button
		 *
		 * Add a TinyMCE button
		 *
		 * @package EG-Plugins
		 *
		 * @param	string	$button_name	Name of the button
		 * @return none
		 */
		function add_tinymce_button($button_name, $tinymce_plugin_path) {
			$this->tinyMCE_button[]->name                                = $button_name;
			$this->tinyMCE_button[sizeof($this->tinyMCE_button)-1]->path = $tinymce_plugin_path;
		}

		/**
		 * add_page
		 *
		 * Add a shortcode
		 *
		 * @package EG-Plugins
		 *
		 * @param
		 * @param
		 * @return none
		 */
		function add_page($page_type, $page_title, $menu_title, $access_level, $page_url, $callback) {
			$index = sizeof($this->pages);
			$this->pages[$index]->type         = $page_type;
			$this->pages[$index]->page_title   = __($page_title, $this->textdomain);
			$this->pages[$index]->menu_title   = __($menu_title, $this->textdomain);
			$this->pages[$index]->access_level = $access_level;
			$this->pages[$index]->page_url     = $page_url;
			$this->pages[$index]->callback     = $callback;

			if ($page_type == 'options' && !isset($this->option_page_url))
				$this->option_page_url = $page_url;
		}

		/**
		 * active_cache
		 *
		 * Active cache and set the expiration duration
		 *
		 * @package EG-Plugins
		 *
		 * @param	int	$expiration	Expiration duration (in second)
		 * @return none
		 */
		function active_cache($expiration = 3600) {
			$this->cacheexpiration = $expiration;
		}

		/**
		 * admin_init
		 *
		 * Perform init at admin level (add TinyMCE button ...)
		 *
		 * @package EG-Plugins
		 *
		 * @param	none
		 * @return	none
		 */
		function admin_init() {
			// Add only in Rich Editor mode
			if ( isset($this->tinyMCE_button) &&
				 get_user_option('rich_editing') == 'true' ) {
			// && current_user_can('edit_posts') && current_user_can('edit_pages') )  {

				// add the button for wp2.5 in a new way
				add_filter('mce_external_plugins', array (&$this, 'add_tinymce_plugin' ), 5);
				add_filter('mce_buttons',          array (&$this, 'register_button' ),    5);
			}
		}

		/**
		 * widgets_init
		 *
		 *
		 * Declare and register widgets here
		 *
		 * @package EG-Plugins
		 *
		 * @param	none
		 * @return	none
		 */
		function widgets_init() {
			// empty for this class
		}

		/**
		 * init
		 *
		 * Perform init
		 *
		 * @package EG-Plugins
		 *
		 * @param	none
		 * @return	none
		 */
		function init() {
			global $wp_version;

			/* --- Load translations file --- */
			if (function_exists('load_plugin_textdomain') && $this->textdomain != '') {
				if (version_compare($wp_version, '2.6', '<')) {
					// for WP < 2.6
					load_plugin_textdomain( $this->textdomain, $this->plugin_lang_path);
				} else {
					// for WP >= 2.6
					load_plugin_textdomain( $this->textdomain, FALSE , basename(dirname($this->plugin_core_file)).'/lang');
				}
			}
			$this->widgets_init();

			if (sizeof($this->pages) > 0) {
				add_action( 'admin_menu', array(&$this, 'add_plugin_pages') );
				if ($this->option_page_url) {
					if (version_compare($wp_version, '2.7', '<')) {
						add_filter('plugin_action_links', array(&$this, 'filter_plugin_actions_before_27'), 10, 2);
					}
					else {
						add_filter( 'plugin_action_links_' . plugin_basename($this->plugin_core_file),
									array( &$this, 'filter_plugin_actions_27_and_after') );
					}
				}
			}
			$this->include_stylesheets();
		}

		/**
		 * plugins_loaded
		 *
		 * Call by plugins_loaded WP action
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return none
		 */
		function plugins_loaded () {

			/* --- Check if WP version is correct --- */
			$this->check_requirements(FALSE);

			/* --- Get Plugin options --- */
			$this->get_plugin_option();

			if (is_admin()) {
				
				if ( function_exists('register_uninstall_hook') ) {
					register_uninstall_hook ($this->plugin_core_file, array(&$this, 'uninstall') );
				}

				add_action('admin_init',   array( &$this, 'admin_init')   );
				add_action('admin_header', array( &$this, 'admin_head')   );
				add_action('admin_footer', array( &$this, 'admin_footer') );
			}
			else {
				add_action('wp_head',   array( &$this, 'head')  );
				add_action('wp_footer', array( &$this, 'footer'));
			}

		}

		/**
		 * include_stylesheets
		 *
		 * Register plugin style sheets
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return none
		 */
		function include_stylesheets() {

			if (is_admin()) {
				if ($this->admin_stylesheet !== FALSE &&
					$this->admin_stylesheet != '' &&
					@file_exists($this->plugin_path.$this->admin_stylesheet)) {
					wp_enqueue_style( $this->plugin_name.'_admin_stylesheet', $this->plugin_url.$this->admin_stylesheet);
				}
			} /* is_admin */
			else {
				if (isset($this->options['load_css'])) $load_css = $this->options['load_css'];
				else $load_css = 1;

				$string = '';

				if ($this->stylesheet && $load_css) {

					if (@file_exists(TEMPLATEPATH.'/'.$this->stylesheet)) {
						wp_enqueue_style( $this->plugin_name.'_stylesheet', get_stylesheet_directory_uri().'/'.$this->stylesheet);
					}
					else {
					
						wp_enqueue_style( $this->plugin_name.'_stylesheet', $this->plugin_url.$this->stylesheet);
					}
				}
			} /* else is_admin */
		} /* End of include_stylesheets */

		/**
		 * Implement Head action
		 *
		 * Add links to the style sheet
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return none
		 */
		function head() {
			global $wp_version;
			
			if (version_compare($wp_version, '2.6.5', '<') && function_exists('wp_print_styles')) {
				wp_print_styles($this->plugin_name.'_stylesheet');
			}
		}

		/**
		 * admin_head
		 *
		 * Implement Head action
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return none
		 */
		function admin_head() {
			if (version_compare($wp_version, '2.6.5', '<') && function_exists('wp_print_styles')) {
				wp_print_styles($this->plugin_name.'_admin_stylesheet');
			}
		}

		/**
		 * Implement Footer action
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return none
		 */
		function footer() {
			// Nothing here
		}

		/**
		 * admin_footer
		 *
		 * Implement footer admin  actions
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return none
		 */
		function admin_footer() {

			// Nothing here
		}

		/**
		 * Filter_plugin_actions_27_and_after
		 * Filter dedicated to WP 27 and later versions
		 *
		 * Add a "settings" link to access to the option page from the plugin list
		 *
		 * @package EG-Plugins
		 * @param string	$link	list of existings links
		 * @return none
		 */
		function filter_plugin_actions_27_and_after($links) {

			$settings_link = '<a href="options-general.php?page='.$this->option_page_url.'">' . __('Settings') . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Filter_plugin_actions_27_and_after
		 * Function for version prior WP 2.7
		 *
		 * Add a "settings" link to access to the option page from the plugin list
		 *
		 * @package EG-Plugins
		 * @param string	$link	list of existings links
		 * @param string	$file	plugin core file path
		 * @return none
		 */
		function filter_plugin_actions_before_27($links, $file){
			static $this_plugin;

			if ( !$this_plugin ) $this_plugin = plugin_basename($this->plugin_core_file);

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="options-general.php?page='.$this->option_page_url.'">' . __('Settings') . '</a>';
				$links = array_merge( array($settings_link), $links);
			}
			return $links;
		}

		/**
		 * register_button()
		 * Insert button in wordpress post editor
		 *
		 * @package EG-Plugins
		 * @param array	$buttons	list of buttons
		 * @return array 	$buttons
		 */
		function register_button($buttons) {

			foreach ($this->tinyMCE_button as $value) {
				array_push($buttons, $value->name );
			}
			return $buttons;
		}

		/**
		 * add_tinymce_plugin()
		 * Load the TinyMCE plugin : editor_plugin.js
		 *
		 * @package EG-Plugins
		 * @param none
		 * @return $plugin_array
		 */
		function add_tinymce_plugin($plugin_array) {

			foreach ($this->tinyMCE_button as $value) {
				$plugin_array[$value->name] = $this->plugin_url.$value->path.'/editor_plugin.js';
			}
			return $plugin_array;
		}

		/**
		 * get_plugin_option
		 *
		 * Get the plugin options. If options don't exists, create them. In case of plugin upgrate, update them.
		 *
		 * @package EG-Plugins
		 * @param 		none
		 * @return array	$options	list of the options and values
		 */

		function get_plugin_option($options_entry=FALSE, $default_options=FALSE) {
/*
			if ($options_entry === FALSE)
				$options_entry = $this->options_entry;

			if (! $options_entry) {
				$options = FALSE;
			} else {
				$options = get_option( $options_entry );
			}
*/
			$this->options = get_option($this->options_entry);
			$this->install_upgrade();
			
			return ($this->options);
		} 

		/**
		 * options_reset
		 *
		 * Reset options to defaults
		 *
		 * @param 	none
		 * @return 	none
		 */
		function options_reset() {

			if (isset($this->default_options)) {
				$this->options            = $this->default_options;
				$this->options['version'] = $this->plugin_version;
				update_option($this->options_entry, $this->options);
			}
		} /* End of options_reset */

		/**
		 * upgrade
		 *
		 * Create or update plugin environment (options, database, files ...)
		 *
		 * @package EG-Plugins
		 * @param 	none
		 * @return  none
		 */
		 /*
			To use this function:
				function insta_upgrade() {
					$current_version = parent::install_upgrade();
					
					put here your upgrade or install features
				}
		 */
		function install_upgrade() {

			if ($this->options === FALSE) {
				// Create option from the defaults
				if (isset($this->default_options) && $this->default_options != FALSE) {
					$this->options = $this->default_options;
				}
				$this->options['version'] = $this->plugin_version;
				add_option($this->options_entry, $this->options);
			}
			else {	
				if (  isset($this->options['version'])) $current_version = $this->options['version'];
				else $current_version = '0.0.0';

				// Plugin previously installed. Check the version and update options
				if (version_compare($current_version, $this->plugin_version, '<')) {
					if (! isset($this->default_options) || $this->default_options === FALSE) {
						$new_options = $this->options;
					}
					else {
						$new_options = array();
						foreach ($this->default_options as $key => $value) {
							if (isset($this->options[$key])) $new_options[$key] = $this->options[$key];
							else $new_options[$key] = $value;
						}
					}
					$new_options['version'] = $this->plugin_version;
					update_option($this->options_entry, $new_options);
					$this->options = $new_options;
				}
			}
			return ($current_version);
		} // End of upgrade

		/**
		  * display_requirements_msg
		  *
		  * Build requirements string
		  *
		  * @package 	EG-Plugins
		  * @param 		string	name of the software (for example: WP, WP MU, PHP, MySQL)
		  * @param	 	int		version minimum
		  * @param 		int 	version maximum
		  * @return		string	the message
		  */
		function get_requirements_msg($soft, $min, $max=FALSE) {
			$string = '<strong>'.$this->plugin_name.'</strong>';
			if (! $min) {
				$string .= __(' cannot run with ', $this->textdomain).$soft.'.';
			}
			else {
				$string .= __(' requires ', $this->textdomain).
			          $soft.' '.$min.(!$max?__(' and later.', $this->textdomain):'').
					  ($max?__(' to ', $this->textdomain).$max:'').'.';
			}
			return ($string);
		}

		/**
		 * check_php_requirements
		 *
		 * Check PHP version required to run the plugin
		 *
		 * @package EG-Delicious
		 *
		 * @param boolean	display_msg		display error message or not
		 * @return boolean					TRUE if required PHP versio, FALSE if not
		 */
		function check_php_requirements() {
			$value = TRUE;

			if (isset($this->php_version_min) && version_compare(phpversion(), $this->php_version_min, '<')) {
				$value = FALSE;
				$this->requirements_error_msg .= ($this->requirements_error_msg==''?'':'<br />').$this->get_requirements_msg('PHP', $this->php_version_min);
			}
			return ($value);
		} /* End of check_php_requirements */

		/**
		 * check_php_exts
		 *
		 * Check PHP extensions
		 *
		 * @package EG-Delicious
		 *
		 * @param boolean	display_msg			display error message or not
		 * @param array		extensions_list		list of required extensions
		 * @return boolean						TRUE if required PHP versio, FALSE if not
		 */
		function check_php_exts($extensions_list=FALSE) {

			$value = TRUE;
			if (!$extensions_list)
				$extensions_list = $this->php_extensions;

			if ($extensions_list) {
				$loaded_exts = get_loaded_extensions();

				if (!is_array($extensions_list))
					$extensions_list = array($extensions_list);

				$available_exts = array_intersect($extensions_list, $loaded_exts);
				$missing_exts   = array_diff($extensions_list, $available_exts);
				$value          = (sizeof($missing_exts)==0);
			}
			if (!$value) {
				$this->requirements_error_msg .= ($this->requirements_error_msg==''?'':'<br />').sprintf(__('The plugin <strong>%1s</strong> requires the following PHP extensions: %2s',$this->textdomain), $this->plugin_name, implode(', ',$missing_exts));
			}
			return ($value);
		} /* End of check_php_exts */

		/**
		 * check_php_options
		 *
		 * Check PHP options
		 *
		 * @package EG-Delicious
		 *
		 * @param 	boolean		display_msg			display error message or not
		 * @param 	array		extensions_list		list of required extensions
		 * @return 	boolean							TRUE if required PHP versio, FALSE if not
		 */
		function check_php_options($options_list=FALSE) {

			$returned_code = TRUE;
			if (!$options_list) $options_list = $this->php_options;

			if ($options_list) {
				if (!is_array($options_list))
					$options_list = array($options_list);

				foreach ($options_list as $value) {
					if (! ini_get($value)) {
						$missing_options[] = $value;
						$returned_code = FALSE;
					}
				}
			}
			if (!$returned_code) {
				$this->requirements_error_msg .= ($this->requirements_error_msg==''?'':'<br />').sprintf(__('The plugin <strong>%1s</strong> requires the following PHP options: %2s.', $this->textdomain), $this->plugin_name, implode(', ', $missing_options));
			}
			return ($returned_code);
		} /* End of check_php_options */

		/**
		 * check_wp_requirements
		 *
		 * Check WordPress version required to run the plugin
		 *
		 * @package EG-Delicious
		 *
		 * @param boolean	display_msg		display error message or not
		 * @return boolean					TRUE if required WordPress version, FALSE if not
		 */
		function check_wp_requirements() {
			global $wp_version, $wpmu_version;

			// Are we using WP MU ?
			$is_wpmu = (isset($wpmu_version) || (strpos($wp_version, 'wordpress-mu') !== FALSE));

			$value = FALSE;
			if ($is_wpmu) {
				if ($this->wpmu_version_min) {
					$value = version_compare($wpmu_version, $this->wpmu_version_min, '>=');
					if ($this->wpmu_version_max) $value = $value && version_compare($wpmu_version, $this->wpmu_version_max, '<=');
				}
			}
			else {
				$value = version_compare($wp_version, $this->wp_version_min, '>=');
				if ($this->wp_version_max) $value = $value && version_compare($wp_version, $this->wp_version_max, '<=');
			}

			// if $value isn't empty, we have a message to display
			if (!$value) {
				if ($this->requirements_error_msg!='') $this->requirements_error_msg .= '<br />';
				if ($is_wpmu)
					$this->requirements_error_msg .= $this->get_requirements_msg('WordPress MU', $this->wpmu_version_min, $this->wpmu_version_max);
				else
					$this->requirements_error_msg .= $this->get_requirements_msg('WordPress', $this->wp_version_min, $this->wp_version_max);
			}
			return ($value);
		}

		/**
		  * Check_requirements
		  *
		  * Check if the wordpress version meets the plugin requirements.
		  *
		  * @package EG-Plugins
		  * @param 		none
		  * @return 	none
		  */
		function check_requirements($display_msg=TRUE) {

			$value = TRUE;
			if (is_admin() && ($display_msg || strpos($_SERVER['REQUEST_URI'], 'plugins.php')!==FALSE)) {

				$value = $this->check_wp_requirements();
				$value = $value & $this->check_php_requirements();
				$value = $value & $this->check_php_options();
				$value = $value & $this->check_php_exts();

				if (!$value) {
					echo '<div id="message" class="error fade"><p><strong>'.$this->requirements_error_msg.'</strong></p></div>';
				}
			}
			return ($value);
		}

		/**
		  * Uninstall
		  *
		  * Actions required to uninstall the plugin.
		  *
		  * @package EG-Plugins
		  * @param 		none
		  * @return 		none
		 */
		function uninstall() {
			if ( isset($this->options_entry) ) delete_option($this->options_entry);
		}

		/**
		  * add_plugin_pages
		  *
		  * Add an option page menu
		  *
		  * @package EG-Plugins
		  * @param 		none
		  * @return 		none
		 */
		function add_plugin_pages() {
			global $wp_version;

			if (version_compare($wp_version, '2.7', '<')) {
				$page_list = array ( 'posts'	=> 'add_management_page',
								 'options'	=> 'add_options_page',
								 'settings'	=> 'add_options_page',
								 'tools'	=> 'add_options_page',
								 'theme'	=> 'add_theme_page',
								 'users'	=> 'add_users_page',
								 'media'	=> 'add_management_page',
								 'links'	=> 'add_management_page',
								 'pages'	=> 'add_management_page');
			}
			else {
				$page_list = array ( 'posts'	=> 'add_posts_page',
								 'options'	=> 'add_options_page',
								 'settings'	=> 'add_options_page',
								 'tools'	=> 'add_management_page',
								 'theme'	=> 'add_theme_page',
								 'users'	=> 'add_users_page',
								 'media'	=> 'add_media_page',
								 'links'	=> 'add_links_page',
								 'pages'	=> 'add_pages_page');
			}

			// Add a new submenu under Options:
			$option_page_url = '';
			foreach ($this->pages as $page) {
				if ($page->type == 'options') {
					$option_page_url = $page->page_url;
				}
				call_user_func($page_list[$page->type],
								__($page->page_title, $this->textdomain),
								__($page->menu_title, $this->textdomain),
								$page->access_level,
								$page->page_url,
								array(&$this, $page->callback));
			}
			return ($option_page_url);
		}

		/**
		  * display_message
		  *
		  * Display message at the top of page
		  *
		  * @package EG-Plugins
		  * @param 	string	$message	message to display
		  * @param 	string	$status	update, error
		  * @return 		none
		  */
		function display_message($message, $status ='') {
			if ( $message != '') {
			?>
				<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
					<p><strong><?php echo $message; ?></strong></p>
				</div>
			<?php
			}
		} /* --- end of display_message --- */
	} /* End of class */
} /* End of class_exists */



?>