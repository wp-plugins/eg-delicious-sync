<?php

 require_once('lib/eg-forms.inc.php');

if (! class_exists('EG_Delicious_Admin')) {

	define('EG_DELICIOUS_ERROR_NONE',       0);
	define('EG_DELICIOUS_ERROR_GET_WPLINK', 10);
	define('EG_DELICIOUS_ERROR_GET_WPCAT',	11);
	define('EG_DELICIOUS_ERROR_USER_RIGHT', 12);
	define('EG_DELICIOUS_ERROR_CONFIG', 	13);
	define('EG_DELICIOUS_ERROR_DELQUERY',	14);

	define('EG_DELICIOUS_LINKS_PER_PAGE',	25);
	define('EG_DELICIOUS_NOSYNC_ID', 		'nosync');
	define('EG_DELICIOUS_NOSYNC_LABEL', 	'');
	define('EG_DELICIOUS_UNBUNDLED',		'Unbundled tag');

	$EG_DELICIOUS_DEFAULT_OPTIONS = array(
		'username' 		 			 => '',
		'password' 		 			 => '',
		'sync_links_wp_del'	 		 => 'delete', 		// delete or download
		'sync_links_update'	 		 => 'auto', 		// auto, never, always
		'sync_cat_update'	 		 => 'replace',		// replace, update, none
		'sync_cat_multi'			 => 'single',
		'sync_cat_type'		 		 => 'bundle',
		'sync_links_default_target'	 => 'none',
		'sync_links_default_visible' => 'Y',
		'sync_links_other_item'		 => EG_DELICIOUS_NOSYNC_ID,
		'sync_links_not_classified'  => EG_DELICIOUS_NOSYNC_ID,
		'uninstall_options'			 => 0,
		'bundles_assignment'		 => array(),
		'tags_assignment'			 => array(),
		'wp_link_update'			 => 1,
		'sync_status'				 => 'stopped',
		'sync_date'					 => 0,
		'sync_user'					 => '',
		'last_sync_date' 			 => 0
	);

	/**
	 * Class EG_Delicious_Admin
	 *
	 *
	 *
	 * @package EG-Delicious
	 */
	Class EG_Delicious_Admin extends EG_Plugin_103 {

		var $options_form;
		var $plugin_temp;
		var $wp_link_categories;
		var $dashboard;
		var $datetime_format;
		var $current_wp_user;

		var	$cache_group = EG_DELICIOUS_CACHE_GROUP;

		var $posts_list;
		var $tags_list;
		var $bundles_list;
		var $bundles_tags_assoc = FALSE;

		var $links_db;
		var $linksdb_index;
		var $file_linksdb_index;
		var $file_linksdb;

		var $error_code;

		var $links_min_user_rights = 'manage_links';

		/**
		 * plugins_loaded
		 *
		 * Add admins page (options ...)
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function plugins_loaded() {

			parent::plugins_loaded();

			$this->ERROR_MESSAGES = array(
				EG_DELICIOUS_CORE_ERROR_NONE 	=> __('No error.', $this->textdomain),
				EG_DELICIOUS_ERROR_GET_WPLINK	=> __('Error while requesting WordPress links.', $this->textdomain),
				EG_DELICIOUS_ERROR_GET_WPCAT	=> __('Error while getting WordPress links categories.', $this->textdomain),
				EG_DELICIOUS_ERROR_USER_RIGHT	=> __('You cannot access to the page. You haven\'t the "Manage links" capability. Please contact the blog administrator.', $this->textdomain),
				EG_DELICIOUS_ERROR_CONFIG		=> __('Plugin not configured! Please go to <a href="'.admin_url('options-general.php?page=egdel_options').'">Settings / EG-Delicious</a> page to enter required parameters.', $this->textdomain),
				EG_DELICIOUS_ERROR_DELQUERY		=> __('Error while querying Delicious.', $this->textdomain)
			);

			// Add plugin options page
			$this->add_page('options', 						/* page type: post, page, option, tool 	*/
							'EG-Delicious Options',			/* Page title 							*/
							'EG-Delicious',					/* Menu title 							*/
							$this->links_min_user_rights, 	/* Access level / capability			*/
							'egdel_options',				/* file 								*/
							'options_page');				/* function								*/

			// Add links synchronisation page
			$this->add_page('links',
							'Delicious Blogroll Synchronisation',	/* Page title					*/
							'Delicious Links Sync.',				/* Menu title 					*/
							$this->links_min_user_rights, 			/* Access level / capability	*/
							'egdel_links_sync',						/* file 						*/
							'wp_links_delicious_sync');				/* function						*/
							
		} // End of plugins_loaded

		/**
		 * admin_init
		 *
		 * Admin_init hook. File declarations
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function admin_init() {
			parent::admin_init();

			$this->plugin_temp        = $this->plugin_path.'tmp/';
			$this->file_linksdb       = $this->plugin_temp.'synchronize_links.txt';
			$this->file_linksdb_index = $this->plugin_temp.'synchronize_links_index.txt';
			
			$this->load_options();

			$this->delicious_data = & EG_Delicious_Core::get_instance(
											$this->plugin_temp, 
											$this->options['username'], 
											$this->options['password']
									);

			// Get current name and ID
			$logged_user 			= wp_get_current_user();
			$this->current_wp_user	= $logged_user->display_name;
			$this->datetime_format  = get_option('date_format').' '.get_option('time_format');

			if ($this->options['wp_link_update']) {
				add_action('edit_link', array(&$this, 'update_wp_link_date'));
				add_action('add_link',  array(&$this, 'update_wp_link_date'));
			}
		} // End of admin_init

		/**
		 * add_form_setup
		 *
		 * Create form for options page
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function add_options_form() {
			global $wpdb;

			if (isset($this->options_form)) unset($this->options_form);
			$this->options_form = new EG_Forms_103('', '', '', $this->textdomain, '', '', 'egdel_options', 'mailto:'.$this->plugin_author_email);
			$form = & $this->options_form;

			$id_section = $form->add_section('Delicious account');
			$id_group   = $form->add_group($id_section, 'Username');
			$form->add_field($id_section, $id_group, 'text', 'Username', 'username');

			$id_group   = $form->add_group($id_section, 'Password');
			$form->add_field($id_section, $id_group, 'password', 'Password', 'password');

			if ( $this->is_user_defined() ) {

				if ($this->options['sync_cat_type'] == 'tag') {
					$this->tags_list = $this->delicious_data->get_data('tags');
					if ($this->tags_list === FALSE) {
						$this->delicious_data->get_error($error_code, $error_msg);
						$this->display_error($error_code, $error_msg.' '.__('Cannot get Delicious tags', $this->textdomain));
					}
				}
				else {
					$this->bundles_list = $this->delicious_data->get_data('bundles');
					if ($this->bundles_list === FALSE) {
						$this->delicious_data->get_error($error_code, $error_msg);
						$this->display_error($error_code, $error_msg.' '.__('Cannot get Delicious bundles', $this->textdomain));
					}
				}

				if ($this->bundles_list !== FALSE && $this->tags_list !== FALSE) {

					$id_section = $form->add_section('WordPress Links');
					$id_group   = $form->add_group($id_section, 'Manage date', 'WordPress doesn\'t set the "update date" when you create or edit a link. Do you want to change the date of links when you create or edit them?');
					$form->add_field($id_section, $id_group, 'radio', 'Manage date', 'wp_link_update', '', '' , '','', 'regular', array('1' => 'Yes', '0' => 'No'));

					$id_section = $form->add_section('Links synchronisation');

					$id_group   = $form->add_group($id_section, 'WordPress links', 'When a link exists in WordPress, and does NOT exist in Delicious, do you want to:');
					$form->add_field($id_section, $id_group, 'radio', 'WordPress links', 'sync_links_wp_del', '', '' , '','', 'regular', array('delete' => 'Delete WordPress link', 'none' => 'Leave as-is'));
					$id_group   = $form->add_group($id_section, 'Update', 'When a link exist in WordPress AND Delicious databases, do you want:');
					$form->add_field($id_section, $id_group, 'radio', 'Update', 'sync_links_update', '', '' , '','', 'regular', array('always' => 'Always update the WordPress links,', 'never' => 'Never update WordPress links', 'auto' => 'Leave the plugin decide'));

					$id_group   = $form->add_group($id_section, 'Default parameters', 'Parameters to use when creating links in WordPress');
					$form->add_field($id_section, $id_group, 'select', 'Link target: ', 'sync_links_default_target', '', '', '', '', 'regular', array( 'none' => ' ', '_blank' => '_blank', '_top' => '_top') );
					$form->add_field($id_section, $id_group, 'select', 'Link visible: ', 'sync_links_default_visible', '', '', '', '', 'regular', array( 'Y' => 'Yes', 'N' => 'No') );

					$id_section = $form->add_section('Categories synchronisation');

					$id_group   = $form->add_group($id_section, 'Replace or update', 'When a WordPress link is updated:');
					$form->add_field($id_section, $id_group, 'radio', 'Replace or update', 'sync_cat_update', '', '' , '','', 'regular', array('replace' => 'Replace the existing categories by those coming from Delicious,', 'update' => 'Add categories coming from Delicious.', 'none' => 'Keep the WordPress categories'));

					$id_group   = $form->add_group($id_section, 'Allow multiple categories', 'During category synchronisation:', 'If you choose "Add categories" in the previous question, some links will have several categories, evenif you choose "Allow only one category".');
					$form->add_field($id_section, $id_group, 'radio', 'Allow multiple categories', 'sync_cat_multi', '', '' , '','', 'regular', array('single' => 'Allow only one category per WordPress link,', 'multi' => 'Allow several categories per WordPress link.'));

					$id_group   = $form->add_group($id_section, 'Alignment key', 'Do you want to synchronize the WordPress link categories with', 'Click on <strong>Save changes</strong> button to change the following alignment table');
					$form->add_field($id_section, $id_group, 'radio', 'Alignment key', 'sync_cat_type', '', '' , '','', 'regular', array('tag' => 'Delicious tags', 'bundle' => 'Delicious Bundle'));

					$wp_link_categories = $this->get_wp_links_categories(TRUE);

					if ($this->options['sync_cat_type'] == 'tag') {
						$tags_categories = array( 'header' => array('Delicious Tags', 'WordPress Categories'));
						foreach ($this->tags_list as $tag => $values) {
							$tags_categories['list'][] = array( 'value' => $tag, 'select' => $wp_link_categories);
						}
						$id_group = $form->add_group($id_section, 'Tags / Categories assignments');
						$form->add_field($id_section, $id_group, 'grid select', 'Tags / Categories assignments', 'tags_assignment', '', '', '', '', 'regular', $tags_categories );
					}
					else {
						$bundles_categories = array( 'header' => array('Delicious Bundle', 'WordPress Categories'));
						foreach ($this->bundles_list as $bundle => $values) {
							$bundles_categories['list'][] = array( 'value' => $bundle, 'select' => $wp_link_categories);
						}
						$id_group = $form->add_group($id_section, 'Bundles / Categories assignments');
						$form->add_field($id_section, $id_group, 'grid select', 'Bundles / Categories assignments', 'bundles_assignment', '', '', '', '', 'regular', $bundles_categories );

					}

					$id_group = $form->add_group($id_section, 'Other assignments');
					$form->add_field($id_section, $id_group, 'select', 'Other item: ', 'sync_links_other_item', '', '', '', '', 'regular', $wp_link_categories);
					$form->add_field($id_section, $id_group, 'select', 'Not classified link: ', 'sync_links_not_classified', '', '', '', '', 'regular', $wp_link_categories);

					$id_section = $form->add_section('Uninstall options', '', 'Be careful: these actions cannot be cancelled. All plugins options will be deleted while plugin uninstallation.');
					$id_group   = $form->add_group($id_section, 'Options');
					$form->add_field($id_section, $id_group, 'checkbox', 'Delete options during uninstallation', 'uninstall_options');
				}
			}
			$form->add_button('submit', 'egdel_options_submit', 'Save changes');
		} /* End of add_options_form */

		/**
		 * options_page
		 *
		 * Display and run plugin options page
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function options_page() {
			global $EG_DELICIOUS_DEFAULT_OPTIONS;

			echo '<div class="wrap">'.
				'<div id="icon-options-general" class="icon32"></div>'.
				'<h2>'.__('EG-Delicious Options', $this->textdomain).'</h2>';

			$display_option = FALSE;
			if (! current_user_can($this->links_min_user_rights)) {
				$this->error_code = EG_DELICIOUS_ERROR_USER_RIGHT;
				$this->display_error();
			}
			else {
				if ($this->check_requirements(TRUE)) {
					if ($this->options['sync_status'] == 'started') {
						echo sprintf(__('A synchronization is started by %1s, since %2s.<br />You cannot modify options now.<br />Please wait, and retry later.', $this->textdomain), $this->options['sync_user'], date_i18n($this->datetime_format, $this->options['sync_date']));
					}
					else {
						$display_option = TRUE;
					}
				}
			}

			if ($display_option !== FALSE ) {
				$this->add_options_form();

				$results = $this->options_form->get_form_values($this->options, $EG_DELICIOUS_DEFAULT_OPTIONS);
				if ($results) {
					$sync_cat_type     = $this->options['sync_cat_type'];
					$username_password = $this->options['username'].$this->options['password'];
					$this->options = $results;
					$this->save_options();

					if ($sync_cat_type     != $this->options['sync_cat_type'] || 
					    $username_password != $this->options['username'].$this->options['password']) {
						$this->add_options_form();
					}
				}
				$this->options_form->display_form($this->options);
			}
			echo '</div>';
		} // End of function options_page

		/**
		 * get_wp_links_categories
		 *
		 * Query WP database to get links categories
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return array	list of categories
		 */
		function get_wp_links_categories($add_nosync=FALSE) {

			if (!isset($this->wp_link_categories)) {
				$this->wp_link_categories = array();
				// Cache is managed by inside get_terms
				$results = get_terms('link_category', array('hide_empty' => FALSE));
				if ($results) {
					foreach ($results as $result) {
						$this->wp_link_categories[$result->term_id] = $result->name;
					}
				}
				else {
					$this->error_code = EG_DELICIOUS_ERROR_GET_WP_CATEGORIES;
				}
			}
			if ($this->error_code != EG_DELICIOUS_ERROR_NONE)
				return FALSE;
			else {
				if ($add_nosync)
					return ( array(EG_DELICIOUS_NOSYNC_ID => EG_DELICIOUS_NOSYNC_LABEL) + $this->wp_link_categories );
				else
					return ($this->wp_link_categories);
			}
		} // End of get_wp_links_categories

		/**
		 * wp_categories_selector
		 *
		 * Build a HTML Select to choose WP links categories
		 *
		 * @package EG-Delicious
		 *
		 * @param boolean	$add_nosync		Add a 'No synchronize' option
		 * @return string					HTML select string
		 */

		function wp_categories_selector($add_nosync=FALSE, $index, $default='') {
			if (!isset($this->wp_link_categories)) {
				$this->get_wp_links_categories($add_nosync);
			}
			$select_string = '';
			if (isset($this->wp_link_categories)) {
				$select_string = '<select name="egdel_wp_categories'.($index>0?'['.$index.']':'').'">';

				if ($add_nosync) {
					$categories_list = array(EG_DELICIOUS_NOSYNC_ID => EG_DELICIOUS_NOSYNC_LABEL) + $this->wp_link_categories;
				}
				else {
					$categories_list = $this->wp_link_categories;
				}
				foreach ($categories_list as $id => $name) {
					$selected = ( ($default!='' && $default == $id)? 'selected': '' );
					$select_string .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
				}
				$select_string .= '</select>';
			}
			return ($select_string);
		} // End of wp_categories_selector

		/**
		 * wp_categories_checkbox
		 *
		 * Build a HTML checkbox set to choose WP links categories
		 *
		 * @package EG-Delicious
		 *
		 * @param boolean	$add_nosync		Add a 'No synchronize' option
		 * @return string					HTML select string
		 */
		function wp_categories_checkbox($index=0, $defaults = array()) {
			if (!isset($this->wp_link_categories)) {
				$this->get_wp_links_categories();
			}
			$select_string = '';
			if (isset($this->wp_link_categories)) {
				$select_name = 'egdel_wp_categories'.($index>0?'['.$index.']':'');

				$select_string = '';
				$i=1;
				foreach ($this->wp_link_categories as $id => $name) {
					$selected = (array_search($id, $defaults)===FALSE?'':'checked');
					$select_string .= '<input type="checkbox" name="'.$select_name.'['.$i.']" value="'.$id.'" '.$selected.' />'.$name.'<br />';
					$i++;
				}
			}
			return ($select_string);
		} // End of wp_categories_checkbox

		/**
		 * get_local_time
		 *
		 * Build the local date/time, from a UTC Date/time
		 *
		 * @package EG-Delicious
		 *
		 * @param integer	$uct_time	utc unix timestamp
		 * @return integer				local timestamp
		 */
		function get_local_time($utc_time) {
			$offset = wp_cache_get('timezone_offset', $this->cache_group);
			if ($offset === FALSE) {
				if (function_exists('wp_timezone_override_offset')) $offset = wp_timezone_override_offset() * 3600;
				else $offset = get_option('gmt_offset') * 3600;

				wp_cache_set('timezone_offset', $offset, $this->cache_group);
			}
			return ($utc_time + $offset);
		} // End of get_local_time

		/**
		 * update_wp_link_date
		 *
		 * Update the field 'link_updated' of the links table
		 *
		 * @package EG-Delicious
		 *
		 * @param integer	$link_id	Id of the link to update
		 * @return boolean				TRUE if the link is updated
		 */
		function update_wp_link_date($link_id) {
			global $wpdb;

			if ( false === $wpdb->query( $wpdb->prepare('UPDATE '.$wpdb->links.' SET link_updated=NOW() WHERE link_id=%s', $link_id ))) {
				if ( $wp_error )
					return new WP_Error( 'db_update_error', __( 'Could not update link in the database' ), $wpdb->last_error );
				else
					return 0;
			} // End of if query
		} // End of update_wp_link_date

		/**
		 * is_user_defined
		 *
		 * check if delicious username and password are defined or not
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return boolean		TRUE if the user is defined, FALSE if the user is NOT defined
		 */
		function is_user_defined() {
			return (isset($this->options['username']) && $this->options['username']!='' &&
					isset($this->options['password']) && $this->options['password']!='');
		} // End of is_user_defined

		/**
		 * save_options
		 *
		 * Update options, including password
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function save_options() {
			$saved_options = $this->options;
			$saved_options['password'] = EG_Delicious_Core::password_encode($saved_options['password']);
			update_option($this->options_entry, $saved_options);
		} // End of save_options

		/**
		 * load_options
		 *
		 * Load options, and decrypt password
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function load_options() {
			if (isset($this->options['password']) && $this->options['password']!='') {
				$this->options['password'] = EG_Delicious_Core::password_decode($this->options['password']);
			}
		} // End of load_options


		/**
		 * change_wp_link_date
		 *
		 * Change 'link_updated' field of the wp_link table
		 *
		 * @package EG-Delicious
		 *
		 * @param 	int		$link_id		id of the link to update
		 * @param	int		$timestamp		unix timestamp
		 * @return 	boolean					TRUE if all is OK, FALSE otherwise
		 */
		function change_wp_link_date($link_id, $timestamp) {
			global $wpdb;

			$query = 'UPDATE '.$wpdb->links.' SET link_updated=FROM_UNIXTIME(%1s) WHERE link_id=%2s';
			if ( false === $wpdb->query( $wpdb->prepare($query, $timestamp, $link_id ))) {
				if ( $wp_error )
					return new WP_Error( 'db_update_error', __( 'Could not update link in the database' ), $wpdb->last_error );
				else
					return 0;
			} // End of if query
		} // End of change_wp_link_date

		/**
		 * synchronization_update
		 *
		 * Get response from the synchronization form, and operate actions
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function synchronization_update() {

			$this->egdel_sync_submit = FALSE;
			if (isset($_POST['egdel_sync_start']))        $this->egdel_sync_submit = 'start';
			elseif (isset($_POST['egdel_sync_stop']))     $this->egdel_sync_submit = 'stop';
			elseif (isset($_POST['egdel_sync_restart']))  $this->egdel_sync_submit = 'restart';
			elseif (isset($_POST['egdel_sync_save']))     $this->egdel_sync_submit = 'save';
			elseif (isset($_POST['egdel_sync_continue'])) $this->egdel_sync_submit = 'continue';

			if ($this->egdel_sync_submit !== FALSE) {

				check_admin_referer( 'egdel_links_sync' );

				switch ($this->egdel_sync_submit) {
					case 'stop':
						$this->options['sync_status'] = 'stopped';
						$this->options['sync_date']   = 0;
						$this->options['sync_user']   = '';
						$this->save_options();
						if (file_exists($this->file_linksdb)) @unlink($this->file_linksdb);
						if (file_exists($this->file_linksdb_index)) @unlink($this->file_linksdb_index);
					break;

					case 'start':
						$this->build_links_synchronisation_list();
						if ($this->error_code == EG_DELICIOUS_ERROR_NONE) {
							$this->options['sync_status'] = 'started';
							$this->options['sync_date']   = time();
							$this->options['sync_user']   = $this->current_wp_user;
							$this->save_options();
						}
						else {
							$this->options['sync_status'] = 'error';
							$this->save_options();
						}
					break;

					case 'restart':
						$this->options['sync_status'] = 'stopped';
						$this->options['sync_date']   = 0;
						$this->options['sync_user']   = '';
						$this->save_options();
						$this->build_links_synchronisation_list();
						if ($this->error_code == EG_DELICIOUS_ERROR_NONE) {
							$this->options['sync_status'] = 'started';
							$this->options['sync_date']   = time();
							$this->options['sync_user']   = $this->current_wp_user;
							$this->save_options();
						}
					break;

					case 'save':
						// During the update, we will use wp_insert_link. The date registered will be the current date.
						// So we remove action, and update the link_updated field manually.
						remove_action('edit_link', array(&$this, 'update_wp_link_date'));
						remove_action('add_link',  array(&$this, 'update_wp_link_date'));

						// Collect data
						$action_list = $_POST['egdel_action'];
						$link_list   = $_POST['egdel_list'];
						$categories  = $_POST['egdel_wp_categories'];

						// Foreach link
						foreach ($action_list as $index => $action) {
							$href = $link_list[$index];
							$link = $this->links_db[$href];

							switch ($action) {
								case 'del_wp':
									wp_delete_link($link['link_id']);
								break;

								case 'add_wp':
								case 'upd_wp':
									if (isset($categories[$index])) {
										if (is_array($categories[$index])) $link['link_category'] = $categories[$index];
										else $link['link_category'] = array($categories[$index]);
										$link_id = wp_insert_link($link);
										$this->change_wp_link_date($link_id, $link['link_updated']);
									}
								break;
							}
							unset($this->linksdb_index[$href]);
						}

						// Check if there is still some links
						if (sizeof($this->linksdb_index) > 0) {
							// Yes => Save them
							$fd = @fopen($this->file_linksdb_index, 'w');
							if ( false !== $fd ) {
								fputs($fd, serialize($this->linksdb_index));
							}
							@fclose($fd);
						}
						else {
							// No. We delete files, and move to Ended status.
							@unlink($this->file_linksdb);
							@unlink($this->file_linksdb_index);
							$this->options['sync_status']    = 'ended';
							$this->options['sync_user']      = '';
							$this->options['last_sync_date'] = $this->options['sync_date'];
							$this->options['sync_date']      = 0;
							$this->save_options();
						}
					break;
				} // End of switch
			} // Submit button pressed?
		} // End of synchronization_update

		/**
		 * synchronization_stopped
		 *
		 * Display synchronization page when synchronization is stopped
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function synchronization_stopped() {
			global $wpdb;

			// Collect last WordPress links update
			$this->last_wp_update = wp_cache_get('last_wp_update', $this->cache_group);
			if ($this->last_wp_update === FALSE) {
				$results = $wpdb->get_results( 'SELECT UNIX_TIMESTAMP(MAX(link_updated)) as wp_last_update FROM '.$wpdb->links );
				if (sizeof($results) == 0 || $results[0]->wp_last_update == 0) {
					$this->last_wp_update = FALSE;
					$last_wp_upd = __('Unknown', $this->delicious);
				}
				else {
					$this->last_wp_update = $results[0]->wp_last_update;
					wp_cache_set('last_wp_update', $this->last_wp_update, $this->cache_group);
					$last_wp_upd = date_i18n($this->datetime_format, $this->last_wp_update);
				}
			}

			// Collect last Delicious link update
			$this->last_delicious_update = $this->delicious_data->get_data('update');
			if ($this->last_delicious_update === FALSE) {
				$this->delicious_data->get_error($error_code, $error_msg);
				$this->display_error($error_code, $error_msg.' '.__('Cannot collect last Delicious update date.', $this->textdomain));
				$last_del_upd = __('Unknown', $this->delicious);
			}
			else
				$last_del_upd = date_i18n($this->datetime_format, $this->get_local_time($this->last_delicious_update));

			if ($this->options['last_sync_date'] == 0)
				$last_sync_date = __('No synchronization was done yet.', $this->textdomain);
			else
				$last_sync_date =  __('Last synchronization date: ', $this->textdomain).
									date_i18n($this->datetime_format, $this->options['last_sync_date']);


			echo '<p>'.
				__('Last Delicious update: ', $this->textdomain).$last_del_upd.',<br />'.
				__( 'Last WordPress update: ', $this->textdomain).$last_wp_upd.',<br />'.
				$last_sync_date.
				'</p>';

			echo '<form method="POST" action=""><p class="submit">'.
				 wp_nonce_field('egdel_links_sync').
				 '<input class="button" type="submit" name="egdel_sync_start" value="'.__('Start synchronization', $this->textdomain).'" />'.'</p></form>';

		} // End of synchronization_stopped

		/**

		 * synchronization_started
		 *
		 * Display synchronization page when synchronization is started
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function synchronization_started() {
			if ($this->options['sync_user'] != $this->current_wp_user) {
				echo '<p>'.
					sprintf(__('A synchronization is currently started by %s, please wait, and retry later.', $this->textdomain), $this->options['sync_user']).'</p>';
			}
			else {
				$referer = wp_get_referer();
				if (strpos($referer, 'page=egdel_links_sync') === FALSE && $this->egdel_sync_submit === FALSE) {
					echo '<form method="POST" action="">'.
						'<p>'.wp_nonce_field('egdel_links_sync').
						sprintf(__('Synchronization on-going, started on %s', $this->textdomain),
							date_i18n($this->datetime_format, $this->options['sync_date'])).
						'</p>'.
						'<p class="submit">'.__('Do you want to continue this session, or restart a new one?', $this->textdomain).'<br />'.
						'<input type="submit" name="egdel_sync_continue" value="'.__('Continue the current session', $this->textdomain).'" /> '.
						'<input type="submit" name="egdel_sync_restart" value="'.__('Start a new session', $this->textdomain).'" /> '.
						'<input type="submit" name="egdel_sync_stop" value="'.__('Stop the session', $this->textdomain).'" />'.
						'</p>'.
						'</form>';
				}
				else {
					echo '<p>'.
						sprintf(__('Synchronization on-going, started on %s', $this->textdomain),
							date_i18n($this->datetime_format, $this->options['sync_date'])).
						'</p>';
					$this->display_synchronization_table();
					// '<form method="POST" action="">'.wp_nonce_field('egdel_links_sync').
					echo '<p class="submit"><input class="button" type="submit" name="egdel_sync_save" value="'.__('Update changes', $this->textdomain).'" />'.'&nbsp;&nbsp;'.
						'<input class="button" type="submit" name="egdel_sync_stop" value="'.__('Stop synchronization', $this->textdomain).'" />'.
						'</p></form>';
				} // End of check referrer
			} // End of synchronization start by current user
		} // End of synchronization_started

		/**
		 * synchronization_ended
		 *
		 * Display synchronization page when synchronization is ended successfully
		 *
		 * @package EG-Delicious
		 *
		 * @param	none
		 * @return	none
		 */
		function synchronization_ended() {
			echo '<p>'.
				sprintf(__('There is no link to synchronize. <br />Synchronization session is ended successfully.<br/>You can see the result of the synchronisation by <a href="%s">browsing links</a>', $this->textdomain), admin_url('link-manager.php')).
				'</p>';
			$this->options['sync_status'] = 'stopped';
			$this->save_options();
		} // End of synchronization_ended

		/**
		 * synchronization_error
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function synchronization_error() {
			echo '<p>'.
				__('Synchronisation failed', $this->textdomain).',<br />'.
				sprintf(__('Click <a href="%1s">here</a> if you want to start a new session', $this->textdomain), admin_url('link-manager.php?page=egdel_links_sync')).'</p>';
			$this->options['sync_status'] = 'stopped';
			$this->save_options();
		} // End of synchronization_error

		/**
		 * display_error
		 *
		 * Display Error message
		 *
		 * @package EG-Delicious
		 *
		 * @param 	integer	$error_code		Code of the error to display
		 * @param	string	$error_msg		Error message
		 * @return none
		 */
		function display_error($error_code=FALSE, $error_msg='') {

			if ($error_code === FALSE) $error_code = $this->error_code;

			if ($error_code != EG_DELICIOUS_ERROR_NONE) {
				if ($error_msg == '') $error_msg = $this->ERROR_MESSAGES[$this->error_code];
				echo '<div id="message" class="error fade"><p>'.
					__('Error ', $this->textdomain).$error_code.': '.$error_msg.
					'</p></div>';
			}
		} // End of display_error

		/**
		 * wp_links_delicious_sync
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function wp_links_delicious_sync() {
			echo '<div id="icon-link-manager" class="icon32"><br /></div>'.
				 '<div class="wrap">'.
				 '<h2>'.__('BlogRoll synchronization', $this->textdomain).'</h2>';

			if ($this->check_requirements(TRUE)) {

				if (! current_user_can($this->links_min_user_rights)) {
					$this->error_code = EG_DELICIOUS_ERROR_USER_RIGHT;
					$this->display_error();
				}
				else {
					if (! $this->is_user_defined()) {
						$this->error_code = EG_DELICIOUS_ERROR_CONFIG;
						$this->display_error();
					}
					else {

						if ($this->options['sync_status'] == 'started') {
							if (! isset($this->links_db) && file_exists($this->file_linksdb))
								$this->links_db = unserialize(file_get_contents($this->file_linksdb));

							if (! isset($this->linksdb_index) && file_exists($this->file_linksdb))
								$this->linksdb_index = unserialize(file_get_contents($this->file_linksdb_index));
						}

						// Event collect (three submit buttons: Start, Stop and Update.
						$this->synchronization_update();

						switch ($this->options['sync_status']) {
							case 'started':
								$this->synchronization_started();
							break;

							case 'stopped':
								$this->synchronization_stopped();
							break;

							case 'ended':
								$this->synchronization_ended();
							break;

							case 'error':
								$this->synchronization_error();
						}
					} // End if user_defined
				} // End if user can manage links
			} // End of check_requirements
			echo '</div>';
		} // End of wp_links_delicious_sync

		/**
		 * tags_to_bundle
		 *
		 * Define bundles <- tags associations table
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function tags_to_bundle() {

			if (isset($this->tags_list) && isset($this->bundles_list)) {
				foreach ($this->bundles_list as $bundle => $bundle_attrs) {
					foreach ($bundle_attrs['TAGS'] as $tag) {
						$this->tags_list[$tag]['bundles'][] = $bundle;
					}
				}
				$this->bundles_tags_assoc = TRUE;
			}
		} // End of tags_to_bundle

		/**
		 * get_bundles_from_tags
		 *
		 * Define list of bundles from a list of tags.
		 *
		 * @package EG-Delicious
		 *
		 * @param array		$tags		list of tags
		 * @return array				list of bundles
		 */
		function get_bundles_from_tags($tags) {

			if ($this->bundles_tags_assoc === FALSE) {
				$this->tags_to_bundle();
			}
			$bundles = array();
			foreach ($tags as $tag) {
				if (isset($this->tags_list[$tag]['bundles'])) {
					$temp = $bundles;
					$bundles = array_merge($temp, $this->tags_list[$tag]['bundles']);
				}
				else {
					$bundles[] = __(EG_DELICIOUS_UNBUNDLED, $this->textdomain);
				}
			}
			return ($bundles);
		} // End of get_bundles_from_tags

		/**
		 * suggested_categories
		 *
		 * Generate list of categories from list of bundles
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function suggested_categories($tags, $bundles, $existing_categories) {

			$mode = $this->options['sync_cat_update'];
			if ($mode == 'none') {
				$categories_list = $existing_categories;
			}
			else {
				if ($this->options['sync_links_type'] == 'tag') {
					$sync_table = $this->options['tags_assignment'];
					$list       = $tags;
				}
				else {
					$sync_table = $this->options['bundles_assignment'];
					$list       = $bundles;
				}

				if ($mode == 'update') $categories_list = $existing_categories;
				else $categories_list = array();

				if (sizeof($list) == 0) {
					$categories_list = $this->options['sync_links_not_classified'];
				}
				else {
					foreach ($list as $item_name) {
						if ( isset($sync_table[$item_name]))
							$new_category = $sync_table[$item_name];
						else
							$new_category = $this->options['sync_links_other_item'];

						if ($new_categoty != EG_DELICIOUS_NOSYNC_ID)
							$categories_list[] = $new_category;
					} // End foreach $bundles_list

					if ($this->options['sync_cat_multi'] == 'single')  {
						$pareto_table = array_count_values($categories_list);
						arsort($pareto_table);
						$categories_list = array( key($pareto_table ) );
					}
					else {
						$categories_list = array_unique($categories_list);
					}
				}
			} // End of $mode != none
			return ($categories_list);
		} // End of suggested_categories

		/**
		 * select_action
		 *
		 * Build a HTML select/option form.
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function select_action($index, $default) {
			$actions_list = array(
				'none'    => __(' ', $this->textdomain),
				'add_wp'  => __('Add to WP', $this->textdomain),
				'upd_wp'  => __('Update WP', $this->textdomain),
				'del_wp'  => __('Delete WP', $this->textdomain)
			);
			$string  = '<select name="egdel_action['.$index.']">';
			if ($default == 'none') {
				$string .= '<option value="none" selected>'.$actions_list['none'].'</option>'.
							'<option value="del_wp">'.$actions_list['del_wp'].'</option>'.
							'<option value="upd_wp">'.$actions_list['upd_wp'].'</option>';
			}
			else {
				$string .= '<option value="none">'.$actions_list['none'].'</option>'.
							'<option value="'.$default.'" selected>'.$actions_list[$default].'</option>';
			}
			$string .= '</select>';

			return ($string);
		} // End of select_action

		/**
		 * build_links_synchronisation_list
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function build_links_synchronisation_list($update_sync = FALSE) {
			global $wpdb;

			$this->error_code = EG_DELICIOUS_ERROR_NONE;

			$sync_wp_cleanup = ($this->options['sync_links_wp_del'] && !$update_sync);

			// Get the date since last update
			if ($update_sync) $update_sync = $this->get_local_time($this->options['last_sync_date']);

			// Get all links from Delicious
			// TODO: add parameter or array of parameters for the request
			$this->posts_list = $this->delicious_data->get_data('posts');
			if ($this->posts_list === FALSE) {
				$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
				$this->delicious_data->get_error($this->error_code, $this->error_msg);
				$this->display_error($this->error_code, $this->error_msg.' '.__('Cannot get Delicious posts', $this->textdomain));
			}
			else {
				$this->tags_list = $this->delicious_data->get_data('tags');
				if ($this->tags_list === FALSE) {
					$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
					$this->delicious_data->get_error($this->error_code, $this->error_msg);
					$this->display_error($this->error_code, $this->error_msg.' '.__('Cannot get Delicious tags', $this->textdomain));
				}
				else {
					$this->bundles_list = $this->delicious_data->get_data('bundles');
					if ($this->bundles_list === FALSE) {
						$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
						$this->delicious_data->get_error($this->error_code, $this->error_msg);
						$this->display_error($this->error_code, $this->error_msg.' '.__('Cannot get Delicious bundles', $this->textdomain));
					}
				}
			}

			if ( $this->posts_list!==FALSE && $this->tags_list!==FALSE && $this->bundles_list!==FALSE ) {

				// We have Delicious links.
				// Prepare WordPress List
				$wp_links_list = wp_cache_get('wp_links_list', $this->cache_group);
				if ($wp_links_list === FALSE) {
					$query = 'SELECT lin.link_id, lin.link_name, lin.link_url, UNIX_TIMESTAMP(lin.link_updated) as link_updated, lin.link_description, tax.term_id as link_category FROM '.$wpdb->links.' AS lin, '.$wpdb->term_relationships.' AS rel, '.$wpdb->term_taxonomy.' AS tax WHERE tax.taxonomy = "link_category" AND lin.link_id = rel.object_id AND rel.term_taxonomy_id = tax.term_taxonomy_id order by lin.link_id';

					$wp_links_list = $wpdb->get_results($query);
					if ($wp_links_list === FALSE) {
						$this->error_code = EG_DELICIOUS_ERROR_GET_WPLINK;
					}
					else
						wp_cache_set('wp_links_list', $wp_links_list, $this->cache_group);
						$this->get_wp_links_categories();
				}

				if ($wp_links_list !== FALSE && isset($this->wp_link_categories)) {

					// Formatting links list
					$previous_link_id = -1;
					foreach ($wp_links_list as $link) {
						if ($previous_link_id == $link->link_id) {
							$this->links_db[$href]['link_category'][]      = $link->link_category;
							$this->links_db[$href]['suggested_category'][] = $link->link_category;
							$this->links_db[$href]['link_cat_names']      .= ', '.$this->wp_link_categories[$link->link_category];
						}
						else {
							$href = html_entity_decode($link->link_url);
							$this->links_db[$href] = array(
									'action'		    => ($sync_wp_cleanup?'del_wp':'none'),
									'link_id' 	    	=> $link->link_id,
									'link_url'		    => html_entity_decode($href),
									'link_name'		    => $link->link_name,
									'link_description'  => $link->link_description,
									'link_updated'		=> $link->link_updated,
									'link_category'     => array($link->link_category),
									'suggested_category'=> array($link->link_category),
									'link_cat_names'    => $this->wp_link_categories[$link->link_category]
								);
							$this->linksdb_index[$href] = $link->link_name;
						}
						$previous_link_id = $link->link_id;
					} // end Foreach WP link
					unset($wp_links_list);
				} // End of Get WP links Ok.

				foreach ($this->posts_list as $href => $link) {

					$delicious_link_datetime = $this->get_local_time($link['TIME']);
					// link exists in wordpress database?
					if (!isset($this->links_db[$href])) {
						// No, action = add
						$this->links_db[$href] = array(
									'action'			=> 'add_wp',
									'link_id'			=> 0,
									'link_url'			=> $href,
									'link_visible'		=> $this->options['sync_links_default_visible'],
									'link_target'		=> $this->options['sync_links_default_target'],
									'link_owner'		=> $this->current_user_id,
									'link_category'     => array(),
							);
					} // End add mode
					else {
						if ($this->options['sync_links_update'] != 'auto') {
							$action = ($this->options['sync_links_update'] == 'always'?'upd_wp':'none');
						}
						else {
							if ($this->links_db[$href]['link_updated'] != 0 &&
								$this->links_db[$href]['link_updated'] < $delicious_link_datetime)
								$action = 'upd_wp';
							else
								$action = 'none';
						}
						$this->links_db[$href]['action'] = $action;
					} // End of update mode

					$this->links_db[$href]['link_name']        	 = $link['DESCRIPTION'];
					$this->links_db[$href]['link_description'] 	 = $link['EXTENDED'];
					$this->links_db[$href]['link_updated']	   	 = $delicious_link_datetime;
					$this->links_db[$href]['tags']			   	 = $link['TAG'];
					$this->links_db[$href]['link_name']			 = $link['DESCRIPTION'];
					$bundles_list          						 = $this->get_bundles_from_tags($link['TAG']);
					$this->links_db[$href]['bundles']			 = array_unique($bundles_list);
					$this->links_db[$href]['suggested_category'] = $this->suggested_categories(	$link['TAG'],
										$bundles_list, $this->links_db[$href]['link_category']);
					$this->linksdb_index[$href] = $link['DESCRIPTION'];
				} // End foreach delicious link

				// Clean links with no action to do
				if ($update_sync === TRUE) {
					foreach ($this->links_db as $href => $attrs) {
						if ($attrs['action'] == 'none') {
							unset($this->links_db[$href]);
							unset($this->linksdb_index[$href]);
						}
					}
				}

				if (sizeof($this->links_db) == 0) {
					$this->error_code = EG_DELICIOUS_SYNC_EMPTY_LIST;
				}
				else {
					asort($this->linksdb_index);

					$fd = @fopen($this->file_linksdb, 'w');
					if ( false !== $fd ) {
						fputs($fd, serialize($this->links_db));
					}
					@fclose($fd);

					$fd = @fopen($this->file_linksdb_index, 'w');
					if ( false !== $fd ) {
						fputs($fd, serialize($this->linksdb_index));
					}
					@fclose($fd);
				}
				if ($this->error_code != EG_DELICIOUS_ERROR_NONE) {
					$this->display_error();
				}
			} /* End synchro ok */

		} // End of build_links_synchronisation_list
	
		/**
		 * html_select
		 *
		 * Build a html select string from categories' list
		 *
		 * @package EG-Delicious
		 *
		 * @param string	$select_name	Name/id of the select
		 * @param string	$default		default option
		 * @return string					html code
		 */
		function html_select($list, $select_name, $default=FALSE) {
			$string = '<select name="'.$select_name.'">';
			foreach($list as $key => $value) {
				if ($default!==FALSE && $default == $key) $selected = 'selected';
				else $selected = '';
				$string .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
			}
			$string .= '</select>';
			return ($string);
		} /* End of html_select */

		/**
		 * display_synchronization_table
		 *
		 * Display table with all links and actions
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function display_synchronization_table() {

			if (isset($_GET['paged'])) $page_number = $_GET['paged'];
			else $page_number = 1;

			if (isset($_GET['links_per_page'])) $links_per_page = $_GET['links_per_page'];
			else $links_per_page = EG_DELICIOUS_LINKS_PER_PAGE;

			$links_per_page_selector = $links_per_page;
			if ($links_per_page == 'all') {
				$links_per_page = sizeof($this->linksdb_index);
				$page_links     = '';
			}
			else {
				$page_links = paginate_links( array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'total'   => ceil(sizeof($this->linksdb_index) / $links_per_page ),
						'current' => $page_number
					)
				);
			}
			echo '<form id="egdel_filter" action="" method="GET">'.
				 '<input type="hidden" name="page" value="egdel_links_sync" />'.
			     '<div class="tablenav">';
			if ( $page_links )	echo '<div class="tablenav-pages">'.$page_links.'<br/>&nbsp;</div>';
			echo __('Links per page: ', $this->textdomain);
			echo $this->html_select(array( '25'  => '25',
									'50'  => '50',
									'75'  => '75',
									'100' => '100',
									'all' => 'All'),
								'links_per_page',
								$links_per_page_selector
							);
			echo '<input class="button" type="submit" id="egdel_sync_lpp" value="'.__('Change', $this->textdomain).'" />'.
				 '</div>'.
			     '</form>';

			$pages_list = array_chunk($this->linksdb_index , $links_per_page, true);

			echo '<form method="POST" action="">'.
					wp_nonce_field('egdel_links_sync').
				'<table class="wide widefat">'.
			    '<thead><tr>'.
				'<th rowspan="2">'.__('Link', $this->textdomain).'</th>'.
				'<th colspan="3">'.__('Delicious', $this->textdomain).'</th>'.
				'<th rowspan="2">'.__('Action', $this->textdomain).'</th>'.
				'<th colspan="3">'.__('WordPress', $this->textdomain).'</th>'.
				'</tr><tr>'.
				'<th>'.__('Tags', $this->textdomain).'</th>'.
				'<th>'.__('Bundles', $this->textdomain).'</th>'.
				'<th>'.__('Last<br />Update', $this->textdomain).'</th>'.
				'<th>'.__('Current<br />category', $this->textdomain).'</th>'.
				'<th>'.__('Suggested<br/>category', $this->textdomain).'</th>'.
				'</tr></thead><tbody>';

			if (!is_array($this->linksdb_index) || sizeof($this->linksdb_index)==0) {
				echo '<tr><td colspan="9">'.__('No links to display',$this->textdomain).'</td></tr>';
			}
			else {
				$index = ($page_number-1)*$links_per_page + 1;
				$current_page = $pages_list[$page_number-1];

				foreach ($current_page as $href => $name) {

					$attrs = $this->links_db[$href];
					$class_row = ($class_row == ''?'class="alternate"':'');
					// $class_row = ($attrs['suggested_category'] == EG_DELICIOUS_NOSYNC_ID?'class="error"':$class_row);
					echo '<input type="hidden" name="egdel_list['.$index.']" id="egdel_list['.$index.']" value="'.$href.'" />'.
						'<tr '.$class_row.'>'.
						'<td>'.$index.'. <a href="'.$href.'" target="_blank">'.htmlspecialchars($name).'</a></td>'.
						'<td>'.(sizeof($attrs['tags'])==0?'&nbsp;':implode(',<br />',$attrs['tags'])).'</td>'.
						'<td>'.($attrs['bundles']==''?'&nbsp;':implode(',<br />',$attrs['bundles'])).'</td>'.
						'<td>'.($attrs['link_updated']==0?'&nbsp;':date_i18n($this->datetime_format, $attrs['link_updated'] )).'</td>'.
						'<td>'.$this->select_action($index, $attrs['action']).'</td>'.
						'<td>'.($attrs['link_cat_names']==''?'&nbsp;':$attrs['link_cat_names']).'</td>';
					if ($this->options['sync_cat_multi'] == 'multi') {
						echo '<td>'.$this->wp_categories_checkbox($index, $attrs['suggested_category']).'</td>';
					}
					else {
						echo '<td>'.$this->wp_categories_selector(TRUE, $index, $attrs['suggested_category'][0]).'</td>';
					}
					echo '</tr>';
					$index++;
				}
			}
			echo '</tbody></table>';
			if ( $page_links )	{
				echo '<div class="tablenav">'.
						'<div class="tablenav-pages">'.
							$page_links.'<br/>&nbsp;'.
						'</div>'.
					'</div>';
			}
		} // End of display_synchronization_table	

	} // End of Class

} // End of if class_exists

$eg_delicious_admin = new EG_Delicious_Admin('EG-Delicious',
									EG_DELICIOUS_VERSION ,
									EG_DELICIOUS_COREFILE,
									EG_DELICIOUS_OPTIONS_ENTRY,
									$EG_DELICIOUS_DEFAULT_OPTIONS);
$eg_delicious_admin->set_textdomain(EG_DELICIOUS_TEXTDOMAIN);
$eg_delicious_admin->set_owner('Emmanuel GEORJON', 'http://www.emmanuelgeorjon.com/', 'blog@georjon.eu');
$eg_delicious_admin->set_wp_versions('2.6',	FALSE, FALSE, FALSE);
if (function_exists('wp_remote_request'))
	$eg_delicious_admin->set_php_version('4.3');
else
	$eg_delicious_admin->set_php_version('4.3', 'allow_url_fopen');

$eg_delicious_admin->load();

?>