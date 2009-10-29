<?php

if (! class_exists('EG_Forms_105')) {
	require('lib/eg-forms.inc.php');
}

if (! class_exists('EG_Delicious_Admin')) {

	define('EG_DELICIOUS_LOG_RETENTION', '200');

	/**
	 * Class EG_Delicious_Admin
	 *
	 *
	 *
	 * @package EG-Delicious
	 */
	Class EG_Delicious_Admin extends EG_Plugin_112	{

		var $datetime_format;
		var $current_wp_user;

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

			// Add plugin options page
			$this->add_page('options', 							/* page type: post, page, option, tool 	*/
							'EG-Delicious Options',				/* Page title 							*/
							'EG-Delicious',						/* Menu title 							*/
							LINKS_MIN_USER_RIGHTS, 				/* Access level / capability			*/
							'egdel_options',					/* file 								*/
							'options_page',						/* function								*/
							'load_eg_delicious_pages');

			// Add links synchronisation page
			$this->add_page('links',
							'Blogroll Synchronisation',			/* Page title					*/
							'EG-Delicious Sync.',				/* Menu title 					*/
							LINKS_MIN_USER_RIGHTS, 				/* Access level / capability	*/
							'egdel_links_sync',					/* file 						*/
							'links_sync',						/* function						*/
							'load_eg_delicious_pages');

			// Add tags synchronization page
			$this->add_page('posts',
							'Delicious tag synchronization',	/* Page title					*/
							'EG-Delicious Tags',				/* Menu title 					*/
							TAGS_MIN_USER_RIGHTS, 				/* Access level / capability	*/
							'egdel_tags_sync',					/* file 						*/
							'tags_sync',						/* function						*/
							'load_eg_delicious_pages');

			// Add backup Delicious page
			$this->add_page('tools',
							'Delicious Backup',					/* Page title					*/
							'EG-Delicious Backup',				/* Menu title 					*/
							LINKS_MIN_USER_RIGHTS, 				/* Access level / capability	*/
							'egdel_backup',						/* file 						*/
							'backup_delicious',					/* function						*/
							'load_eg_delicious_pages');

		} // End of plugins_loaded

		/**
		 * init
		 *
		 * Init hook. Download backup file if required.
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function init() {
			parent::init();

			// Manage the download feature (for backup file)
			$this->backup_delicious_download();

			add_filter( 'cron_schedules',        'eg_delicious_schedules'         );
			add_action( 'eg_delicious_cron_sync', array(&$this, 'scheduled_sync') );
		} // End of init

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
			global $pagenow;

			$posts_pages_list = array('post.php', 'post-new.php', 'page.php', 'page-new.php', 'edit.php', 'edit-pages.php');

			parent::admin_init();

			if (current_user_can( 'publish_posts' ) && $this->options['publish_post']) {
				add_action('transition_post_status', array(&$this, 'publish_post'), 10, 3);
				add_action('delete_post', array(&$this, 'delete_post'));
			}

			if ($this->options['wp_link_update']) {
				add_action('edit_link', 'egdel_links_sync_change_date');
				add_action('add_link',  'egdel_links_sync_change_date');
			}
			if (in_array($pagenow, $posts_pages_list) ) {
				add_action('admin_notices', array(&$this, 'notice_error'));
				add_action('load-'.$pagenow, array(&$this, 'load_eg_delicious_pages'));
			}
		} // End of admin_init

		/**
		 * Install_updgrade
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function install_updgrade() {
			global $wp_version;

			$previous_options = parent::install_upgrade();
			$previous_version = $previous_options['version'];

			// Delete cache
			if (version_compare($previous_version, '1.2.0', '<')) {
				if (version_compare($wp_version, '2.8.0', '<')) {
					// To do
				} // End of WP 2.7.x and previous
				else {
					delete_transient('posts');
					delete_transient('tags');
					delete_transient('bundles');
					delete_transient('update');
				} // End of WP 2.8 and upper
			} // End of version < 1.2.0
			else {
				if (version_compare($wp_version, '2.8.0', '<')) {
					// To do
				} // End of WP 2.7.x and previous
				else {
					delete_transient(EG_DELICIOUS_CACHE_GROUP.'_posts');
					delete_transient(EG_DELICIOUS_CACHE_GROUP.'_tags');
					delete_transient(EG_DELICIOUS_CACHE_GROUP.'_bundles');
					delete_transient(EG_DELICIOUS_CACHE_GROUP.'_update');
				} // End of WP 2.8 and upper
			} // End of 1.20 and upper
		} // End of install_upgrade

		/**
		 * desactivation
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function desactivation() {

			parent::desactivation();
			wp_clear_scheduled_hook('eg_delicious_cron_sync');

		} // End of desactivation

		/**
		 * load_eg_delicious_pages
		 *
		 * Load data for plugin pages
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function load_eg_delicious_pages() {

			$this->options = egdel_load_options($this->options);
			$this->deldata = new EG_Delicious_Core($this->options_entry, $this->options, $this->textdomain);

			// Get current name and ID
			$logged_user 			= wp_get_current_user();
			$this->current_wp_user	= $logged_user->display_name;
			$this->datetime_format  = get_option('date_format').' '.get_option('time_format');

		} // End of load_eg_delicious_pages

		/**
		 * notice_error
		 *
		 * Display error message at the top of the edit post page
		 *
		 * @package EG-Delicious
		 *
		 * @param 	none
		 * @return 	none
		 */
		function notice_error() {
			global $egdel_error;

			if (isset($this->options['errors'])) {
				if (isset($this->options['errors'][$this->current_wp_user])) {
					$error = $this->options['errors'][$this->current_wp_user];
					$egdel_error->display($error['code'], $error['msg'], $error['detail']);
					unset($this->options['errors'][$this->current_wp_user]);
					if (sizeof($this->options['errors']) == 0) {
						unset($this->options['errors']);
					}
					egdel_save_options($this->options_entry, $this->options);
				}
			}
		} // End of notice_error


		/**
		 * compare_list
		 *
		 * Compare two array keys
		 *
		 * @package EG-Delicious
		 *
		 * @param   array		$list1	first list
		 * @param	array		$list2	second list
		 * @return 	boolean				True if lists are different, False otherwise
		 */
		function compare_list($list1, $list2) {

			if (isset($list1)) $list1_keys = array_keys($list1);
			else $list1_keys = array();

			if ( isset($list2) && sizeof($list2)>0 ) $list2_keys = array_keys($list2);
			else $list2_keys = array();

			return (sizeof(array_diff($list1_keys, $list2_keys)) > 0 || sizeof(array_diff($list2_keys, $list1_keys)) > 0 );
		} // End of compare_list

		/**
		 * check_bundles_tags_modification
		 *
		 * Check if bundles or tags changed since the last options saving
		 *
		 * @package EG-Delicious
		 *
		 * @param   array		$tags_list		List of downloaded tags
		 * @param	array		$bundles_list	List of downloaded bundles
		 * @param 	boolean		$display	TRUE to display error message, FALSE otherwise
		 * @param 	boolean		$set_error	TRUE to set the error at plugin level
		 * @return 	boolean					True if change, False if not change
		 */
		function check_bundles_tags_modification($tags_list, $bundles_list, $display=TRUE, $set_error=TRUE) {
			global $egdel_error;

			// Check tags
			if ($this->compare_list($tags_list, $this->options['tags_assignment'])) {
				$egdel_error->set(EG_DELICIOUS_ERROR_TAG_CHG);
			}
			// Check bundles
			if ($this->compare_list($bundles_list, $this->options['bundles_assignment'])) {
				if (! $egdel_error->is_error())
					$egdel_error->set(EG_DELICIOUS_ERROR_BUNDLE_CHG);
				else
					$egdel_error->set(EG_DELICIOUS_ERROR_TAG_BUNDLE_CHG);
			}
			return (! $egdel_error->is_error());

		} // End of check_bundles_tags_modification

		/**
		 * add_options_form
		 *
		 * Create form for options page
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function add_options_form() {
			global $egdel_error;

			$form = new EG_Forms_105('', '', '', $this->textdomain, '', '', 'egdel_options', 'mailto:'.get_option('admin_email'));

			// Add user forms (username / password)
			$id_section = $form->add_section('Delicious account');
			$id_group   = $form->add_group($id_section, 'Username');
			$form->add_field($id_section, $id_group, 'text', 'Username', 'username');

			$id_group   = $form->add_group($id_section, 'Password');
			$form->add_field($id_section, $id_group, 'password', 'Password', 'password');

			$egdel_error->clear();

			// If user is not defined, stop here)
			if ( $this->is_user_defined() ) {

				// Downloaded bundles and tags from Delicious
				$tags_list = $this->deldata->get_data('tags');
				if ($tags_list !== FALSE)
					$bundles_list = $this->deldata->get_data('bundles');

				// If error => stop here
				if ($tags_list !== FALSE && $bundles_list !== FALSE) {

					// If both list are empty, stop here
					if (sizeof($tags_list) == 0 && sizeof($bundles_list) == 0 ) {
						$egdel_error->set(EG_DELICIOUS_ERROR_NOTAG_NOBUNDLE);
					}
					else {
						// switch mode if necessary
						if ($this->options['sync_cat_type'] == 'tag') {
							if ( sizeof($tags_list)==0 && sizeof($bundles_list)>0 ) {
								$this->options['sync_cat_type'] = 'bundle';
								$egdel_error->set(EG_DELICIOUS_ERROR_NOTAG);
							}
						}
						else {
							if ( sizeof($bundles_list)==0 &&  sizeof($tags_list)>0) {
								$this->options['sync_cat_type'] = 'tag';
								$egdel_error->set(EG_DELICIOUS_ERROR_NOBUNDLE);
							}
						}
					}
				}

				if (! $egdel_error->is_error()) {

					$this->check_bundles_tags_modification($tags_list, $bundles_list);

					// What append if we have no tags, and no bundles

					$id_section = $form->add_section('WordPress Links');
					$id_group   = $form->add_group($id_section, 'Manage date', 'WordPress doesn\'t set the "update date" when you create or edit a link. Do you want to change the date of links when you create or edit them?');
					$form->add_field($id_section, $id_group, 'radio', 'Manage date', 'wp_link_update', '', '' , '','', 'regular', array('1' => 'Yes', '0' => 'No'));

					$id_section = $form->add_section('Links synchronisation');

					$id_group   = $form->add_group($id_section, 'WordPress links', 'When a link exists in WordPress, and does NOT exist in Delicious, do you want to:');
					$form->add_field($id_section, $id_group, 'radio', 'WordPress links', 'sync_links_wp_del', '', '' , '','', 'regular', array('delete' => 'Delete WordPress link', 'none' => 'Leave as-is'));
					$id_group   = $form->add_group($id_section, 'Update', 'When a link exist in WordPress AND Delicious databases, do you want:');
					$form->add_field($id_section, $id_group, 'radio', 'Update', 'sync_links_update', '', '' , '','', 'regular', array('always' => 'Always update the WordPress links,', 'never' => 'Never update WordPress links', 'auto' => 'Leave the plugin decide'));

					$id_group   = $form->add_group($id_section, 'Private Links');
					$form->add_field($id_section, $id_group, 'radio', 'Private Links', 'sync_links_private', '', '' , '','', 'regular', array('0' => 'Synchronize ONLY SHARED links,', '1' => 'Synchronize ALL links'));

					$id_group   = $form->add_group($id_section, 'Default parameters', 'Parameters to use when creating links in WordPress');
					$form->add_field($id_section, $id_group, 'select', 'Link target: ', 'sync_links_default_target', '', '', '', '', 'regular', array( 'none' => ' ', '_blank' => '_blank', '_top' => '_top') );
					$form->add_field($id_section, $id_group, 'select', 'Link visible: ', 'sync_links_default_visible', '', '', '', '', 'regular', array( 'Y' => 'Yes', 'N' => 'No') );

					$id_section = $form->add_section('Categories synchronization');

					$id_group   = $form->add_group($id_section, 'Replace or update', 'When a WordPress link is updated:');
					$form->add_field($id_section, $id_group, 'radio', 'Replace or update', 'sync_cat_update', '', '' , '','', 'regular', array('replace' => 'Replace the existing categories by those coming from Delicious,', 'update' => 'Add categories coming from Delicious.', 'none' => 'Keep the WordPress categories'));

					$id_group   = $form->add_group($id_section, 'Allow multiple categories', 'During category synchronisation:', 'If you choose "Add categories" in the previous question, some links will have several categories, evenif you choose "Allow only one category".');
					$form->add_field($id_section, $id_group, 'radio', 'Allow multiple categories', 'sync_cat_multi', '', '' , '','', 'regular', array('single' => 'Allow only one category per WordPress link,', 'multi' => 'Allow several categories per WordPress link.'));

					$id_group   = $form->add_group($id_section, 'Alignment key', 'Do you want to synchronize the WordPress link categories with');
					$form->add_field($id_section, $id_group, 'radio', 'Alignment key', 'sync_cat_type', '', '' , '','', 'regular', array('tag' => 'Delicious tags', 'bundle' => 'Delicious Bundle'));

					$wp_link_categories = $this->deldata->get_wp_links_categories(TRUE);

					$id_group = $form->add_group($id_section, 'Bundles / Categories assignments');
					$bundles_categories = array( 'header' => array('Delicious Bundle', 'WordPress Categories'));
					foreach ($bundles_list as $bundle => $values) {
						$bundles_categories['list'][] = array( 'value' => $bundle, 'select' => $wp_link_categories);
					}
					$form->add_field($id_section, $id_group, 'grid select', 'Bundles / Categories assignments', 'bundles_assignment', '', '', '', '', 'regular', $bundles_categories );

					$id_group = $form->add_group($id_section, 'Tags / Categories assignments');
					$tags_categories = array( 'header' => array('Delicious Tags', 'WordPress Categories'));
					foreach ($tags_list as $tag => $values) {
						$tags_categories['list'][] = array( 'value' => $tag, 'select' => $wp_link_categories);
					}
					$form->add_field($id_section, $id_group, 'grid select', 'Tags / Categories assignments', 'tags_assignment', '', '', '', '', 'regular', $tags_categories );

					$id_group = $form->add_group($id_section, 'Other assignments');
					$form->add_field($id_section, $id_group, 'select', 'Other item: ', 'sync_links_other_item', '', '', '', '', 'regular', $wp_link_categories);
					$form->add_field($id_section, $id_group, 'select', 'Not classified link: ', 'sync_links_not_classified', '', '', '', '', 'regular', $wp_link_categories);

					$id_section = $form->add_section('Tags synchronization');
					$id_group   = $form->add_group($id_section, 'Synchronization mode');
					$form->add_field($id_section, $id_group, 'radio', 'Synchronization mode', 'sync_tags_type', '', '' , '','', 'regular', array('replace' => 'Replace the WordPress tags by those coming from Delicious,', 'update' => 'Update the WordPress tags with those coming from Delicious.'));

					$id_section = $form->add_section('Publish posts', 'The following parameters allow you to add to Delicious, the posts published in WordPress. You can use this feature to follow the popularity of your posts, for example.');
					$id_group   = $form->add_group($id_section, 'Activation');
					$form->add_field($id_section, $id_group, 'checkbox', 'Add posts to Delicious when publish them in Wordpress?', 'publish_post');
					$id_group   = $form->add_group($id_section, 'Classification', 'When publish post, use:');
					$form->add_field($id_section, $id_group, 'checkbox', 'Tags', 'publish_post_use_tags' );
					$form->add_field($id_section, $id_group, 'checkbox', 'Categories', 'publish_post_use_cats');
					$form->add_field($id_section, $id_group, 'text', 'or use specific values:', 'publish_post_use_spec', '', '(Give a list of comma-separated values)');
					$id_group   = $form->add_group($id_section, 'Delicious parameters', 'Do you want to share the post in Delicious?');
					$form->add_field($id_section, $id_group, 'radio', 'Do you want to share the post?', 'publish_post_share','', '', '','', 'regular', array('1' => 'Yes', '0' => 'No'));

					$id_section = $form->add_section('Scheduled synchronization');
					$id_group   = $form->add_group($id_section, 'Activation');
					$form->add_field($id_section, $id_group, 'radio', 'Activate scheduled synchronization?', 'schedule_frequency', '', '', '', '', 'regular', array( 'none' => 'Not activated', 'hourly' => 'Hourly', 'daily' => 'Daily') );

					$id_group = $form->add_group($id_section, 'Hourly schedules', 'Enter the following parameters if you choose "Hourly" frequency');
					$form->add_field($id_section, $id_group, 'select', 'Run synchronization every ', 'schedule_hourly_freq', 'Run synchronization every ', ' hours', '', '', 'regular', array(  'hourly' => 1, 'hourly_2' => 2, 'hourly_4' => 4, 'hourly_8' => 8, 'hourly_12' => 12, 'hourly_16' => 16 ));

					$id_group = $form->add_group($id_section, 'Daily schedules', 'Enter the following parameters if you choose "Daily" frequency');
					$form->add_field($id_section, $id_group, 'select', 'Run synchronization every ', 'schedule_daily_freq', '', ' days' , '','', 'regular',  array(  'daily' => 1, 'daily_2' => 2, 'dayly_4' => 4, 'weekly' => 7));
					$form->add_field($id_section, $id_group, 'select', 'Schedule hour: ', 'schedule_daily_hour', '', '', '', '', 'regular',
							array(   '0' => '12 AM',  '1' => '01 AM',  '2' => '02 AM',  '3' => '03 AM',
									 '4' => '04 AM',  '5' => '05 AM',  '6' => '06 AM',  '7' => '07 AM',
									 '8' => '08 AM',  '9' => '09 AM', '10' => '10 AM', '11' => '11 AM',
									'12' => '12 PM', '13' => '01 PM', '14' => '02 PM', '15' => '03 PM',
									'16' => '04 PM', '17' => '05 PM', '18' => '06 PM', '19' => '07 PM',
									'20' => '08 PM', '21' => '09 PM', '22' => '10 PM', '23' => '11 PM' )
					);

					$id_section = $form->add_section('Uninstall options', '', 'Be careful: these actions cannot be cancelled. All plugins options will be deleted while plugin uninstallation.');
					$id_group   = $form->add_group($id_section, 'Options');
					$form->add_field($id_section, $id_group, 'checkbox', 'Delete options during uninstallation', 'uninstall_options');
				}
			}
			$form->add_button('submit', 'egdel_options_submit', 'Save changes');

			return ($form);
		} // End of add_options_form

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
			global $egdel_error;

			if (isset($_GET['sync_result'])) {
				$this->display_shedule_sync_log();
			}
			else {
				echo '<div class="wrap">'.
					'<div id="icon-options-general" class="icon32"></div>'.
					'<h2>'.__('EG-Delicious Options', $this->textdomain).'</h2>';

				$requirements_status = TRUE;
				$egdel_error->clear();

				if (! current_user_can(LINKS_MIN_USER_RIGHTS)) {
					$egdel_error->set(G_DELICIOUS_ERROR_USER_RIGHT);
				}
				else {
					$requirements_status = $this->check_requirements(TRUE);
					if ($requirements_status) {
						if ($this->options['sync_status'] == 'started') {
							$egdel_error->set(EG_DELICIOUS_ERROR_ALREADY_STARTED);
							$egdel_error->set_details(sprintf(__('User: %1s, start time: %2s.', $this->textdomain), $this->options['sync_user'], date_i18n($this->datetime_format, $this->options['sync_date'])));
						}
					}
				}

				if ( $egdel_error->is_error() ||  ! $requirements_status) {
					$egdel_error->display();
				}
				else {
					$form = $this->add_options_form();

					$results = $form->get_form_values($this->options, $this->default_options);
					if ($results) {
						$previous_options  = $this->options;
						$username_password = $this->options['username'].$this->options['password'];
						$this->options     = $results;
						egdel_save_options($this->options_entry, $this->options);

						if ($previous_options['schedule_frequency'  ] != $this->options['schedule_frequency'   ] ||
						    $previous_options['schedule_hourly_freq'] != $this->options['schedule_hourly_freq' ] ||
							$previous_options['schedule_daily_freq' ] != $this->options['schedule_daily_freq'  ] ||
							$previous_options['schedule_daily_hour' ] != $this->options['schedule_daily_hour'  ] ) {

							wp_clear_scheduled_hook('eg_delicious_cron_sync');

							switch ($this->options['schedule_frequency']) {
								case 'hourly':
									if (!wp_next_scheduled('eg_delicious_cron_sync')) {
										$start_time = mktime( date('H')+1, date('i'), date('s'), date('n'), date('j'), date('Y'));
										wp_schedule_event( $start_time, $this->options['schedule_hourly_freq'], 'eg_delicious_cron_sync' );
									}
								break;

								case 'daily':
									if (!wp_next_scheduled('eg_delicious_cron_sync')) {
										if (date('H') > $this->options['schedule_daily_hour'])
											$start_time = mktime( $this->options['schedule_daily_hour'], 0, 0, date('n'), date('j')+1, date('Y'));
										else
											$start_time = mktime( $this->options['schedule_daily_hour'], 0, 0, date('n'), date('j'), date('Y'));

										wp_schedule_event( $start_time, $this->options['schedule_daily_freq'], 'eg_delicious_cron_sync' );
									}
								break;
							} // End of switch
						} // End of change schedule options

						if ( $username_password != $this->options['username'].$this->options['password']) {
							$this->deldata->set_user($this->options['username'], $this->options['password']);
							unset($form);
							$form = $this->add_options_form();
						}
					}
					$egdel_error->display();
					$form->display_form($this->options);

					echo '<p>'.sprintf(__('Click <a href="%s">HERE</a> to see results of the last scheduled synchronization', $this->textdomain),
									admin_url('options-general.php?page=egdel_options&sync_result=1')).'</p>';

				} // End of display options
				echo '</div>';
			} // End of standard option page
		} // End of function options_page

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

			$categories_list = $this->deldata->get_wp_links_categories($add_nosync);
			$select_string = '';
			if ($categories_list !== FALSE) {
				$select_string = '<select name="egdel_sync_list'.($index>0?'['.$index.'][suggested_category]':'').'">';
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

			$wp_link_categories = $this->deldata->get_wp_links_categories();

			$select_string = '';
			if ($wp_link_categories !== FALSE) {
				$select_name = 'egdel_sync_list'.($index>0?'['.$index.'][suggested_category]':'');

				$select_string = '';
				$i=1;
				foreach ($wp_link_categories as $id => $name) {
					$selected = (array_search($id, $defaults)===FALSE?'':'checked');
					$select_string .= '<input type="checkbox" name="'.$select_name.'['.$i.']" value="'.$id.'" '.$selected.' />'.$name.'<br />';
					$i++;
				}
			}
			unset($wp_link_categories);
			return ($select_string);
		} // End of wp_categories_checkbox

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
		 * links_sync_update
		 *
		 * Get response from the synchronization form, and operate actions
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync_update() {
			global $egdel_error;
			global $egdel_cache;

			$egdel_sync_submit = FALSE;
			if (isset($_POST['egdel_sync_start_full']))       $egdel_sync_submit = 'start_full';
			elseif (isset($_POST['egdel_sync_start_inc']))    $egdel_sync_submit = 'start_inc';
			elseif (isset($_POST['egdel_sync_stop']))         $egdel_sync_submit = 'stop';
			elseif (isset($_POST['egdel_sync_restart_full'])) $egdel_sync_submit = 'restart_full';
			elseif (isset($_POST['egdel_sync_restart_inc']))  $egdel_sync_submit = 'restart_inc';
			elseif (isset($_POST['egdel_sync_save']))         $egdel_sync_submit = 'save';
			elseif (isset($_POST['egdel_sync_continue']))     $egdel_sync_submit = 'continue';

			if ($egdel_sync_submit !== FALSE) {

				check_admin_referer( 'egdel_links_sync' );

				switch ($egdel_sync_submit) {
					case 'stop':
						$this->options['sync_status'] = 'stopped';
						$this->options['sync_date']   = 0;
						$this->options['sync_user']   = '';
						egdel_save_options($this->options_entry, $this->options);

						$egdel_cache->delete('links_db');
						$egdel_cache->delete('linksdb_index');
					break;

					case 'start_full':
					case 'start_inc':
						$this->deldata->links_sync_build_list( ($egdel_sync_submit=='start_inc') );
						if ( $egdel_error->is_error()) {
							$this->options['sync_status'] = 'error';
						}
						else {
							$this->options['sync_status'] = 'started';
							$this->options['sync_date']   = time();
							$this->options['sync_user']   = $this->current_wp_user;
						}
						egdel_save_options($this->options_entry, $this->options);
					break;

					case 'restart_full':
					case 'restart_inc':
						$this->options['sync_status'] = 'stopped';
						$this->options['sync_date']   = 0;
						$this->options['sync_user']   = '';
						egdel_save_options($this->options_entry, $this->options);
						$this->deldata->links_sync_build_list( ($egdel_sync_submit=='restart_inc') );
						if (! $egdel_error->is_error() ) {
							$this->options['sync_status'] = 'started';
							$this->options['sync_date']   = time();
							$this->options['sync_user']   = $this->current_wp_user;
							egdel_save_options($this->options_entry, $this->options);
						} // End of no error
					break;

					case 'save':
						$links_db      = $egdel_cache->get('links_db');
						$linksdb_index = $egdel_cache->get('linksdb_index');
						$sync_list = $_POST['egdel_sync_list'];
						if (isset($sync_list) && is_array($sync_list) && sizeof($sync_list)>0) {
							$this->deldata->links_sync_action($sync_list, $links_db, $linksdb_index, 'admin');
							if (sizeof($linksdb_index) == 0) {
								$this->options['sync_status']    = 'ended';
								$this->options['sync_user']      = '';
								$this->options['last_sync_date'] = $this->options['sync_date'];
								$this->options['sync_date']      = 0;
								egdel_save_options($this->options_entry, $this->options);
							} // End of $linksdb_index
						} // End of sync_list not empty
					break;
				} // End of switch
			} // Submit button pressed?
		} // End of links_sync_update

		/**
		 * get_last_wp_update
		 *
		 * Collect data from WordPress and Delicious
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function get_last_wp_update() {
			global $wpdb;

			$last_wp_update = wp_cache_get('last_wp_update', EG_DELICIOUS_CACHE_GROUP);
			if ($last_wp_update === FALSE) {
				$result = $wpdb->get_var( 'SELECT UNIX_TIMESTAMP(MAX(link_updated)) as wp_last_update FROM '.$wpdb->links );
				if ($result) {
					$last_wp_update = $result;
					wp_cache_set('last_wp_update', $last_wp_update, EG_DELICIOUS_CACHE_GROUP);
				}
			}
			return ($last_wp_update);
		} // End of get_last_wp_update

		/**
		 * links_sync_display_page
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync_display_page() {
			global $egdel_error;

			$egdel_error->display();

			switch ($this->options['sync_status']) {
				case 'started':
					if ($this->options['sync_user'] != $this->current_wp_user) {
						echo '<p>'.
							sprintf(__('A synchronization is currently started by %s, please wait, and retry later.', $this->textdomain), $this->options['sync_user']).'</p>';
					}
					else {
						$referer = wp_get_referer();
						if (strpos($referer, 'page=egdel_links_sync') === FALSE /* && $this->egdel_sync_submit === FALSE */) {
							echo '<form method="POST" action="'.admin_url('link-manager.php?page=egdel_links_sync').'">'.
								'<p>'.wp_nonce_field('egdel_links_sync').
								sprintf(__('Synchronization on-going, started on %s', $this->textdomain),
									date_i18n($this->datetime_format, $this->options['sync_date'])).
								'</p>'.
								'<p class="submit">'.__('Do you want to continue this session, or restart a new one?', $this->textdomain).'<br />'.
								'<input type="submit" name="egdel_sync_continue" value="'.__('Continue the current session', $this->textdomain).'" /> '.
								'<input type="submit" name="egdel_sync_restart_full" value="'.__('Start a new session, full mode', $this->textdomain).'" /> ';
							if ($this->options['last_sync_date'] != 0)
								echo '<input type="submit" name="egdel_sync_restart_inc" value="'.__('Start a new session, incremental mode', $this->textdomain).'" /> ';
							echo '<input type="submit" name="egdel_sync_stop" value="'.__('Stop the session', $this->textdomain).'" />'.
								'</p>'.
								'</form>';
						}
						else {
							echo '<p>'.
								sprintf(__('Synchronization on-going, started on %s', $this->textdomain),
									date_i18n($this->datetime_format, $this->options['sync_date'])).
								'</p>';
							$this->links_sync_display_list();
						} // End of check referrer
					} // End of synchronization start by current user

				break;

				case 'stopped':
					$last_wp_update = $this->get_last_wp_update();
					if ($last_wp_update === FALSE) $last_wp_upd = __('Unknown', $this->delicious);
					else $last_wp_upd = date_i18n($this->datetime_format, $last_wp_update);

					$last_delicious_update = $this->deldata->get_data('update');
					if ($last_delicious_update === FALSE) $last_del_upd = __('Unknown', $this->delicious);
					else $last_del_upd = date_i18n($this->datetime_format, $this->deldata->get_local_time($last_delicious_update));

					if ($this->options['last_sync_date'] == 0)
						$last_sync_date = __('No synchronization was done yet.', $this->textdomain);
					else
						$last_sync_date = __('Last synchronization date: ', $this->textdomain).
											date_i18n($this->datetime_format, $this->options['last_sync_date']);

					echo '<p>'.
						__('Last Delicious update: ', $this->textdomain).$last_del_upd.',<br />'.
						__( 'Last WordPress update: ', $this->textdomain).$last_wp_upd.',<br />'.
						$last_sync_date.
						'</p>';

					echo '<form method="POST" action="'.admin_url('link-manager.php?page=egdel_links_sync').'"><p class="submit">'.
						 wp_nonce_field('egdel_links_sync').
						 '<input class="button" type="submit" name="egdel_sync_start_full" value="'.__('Start full synchronization', $this->textdomain).'" />';
					if ($this->options['last_sync_date'] != 0) {
						echo '<input class="button" type="submit" name="egdel_sync_start_inc" value="'.__('Start incremental synchronization', $this->textdomain).'" />';
					}
					echo '</p></form>';
				break;

				case 'ended':
					echo '<p>'.
						sprintf(__('There is no link to synchronize. <br />Synchronization session is ended successfully.<br/>You can see the result of the synchronisation by <a href="%s">browsing links</a>', $this->textdomain), admin_url('link-manager.php')).'</p>';
					$this->options['sync_status'] = 'stopped';
					egdel_save_options($this->options_entry, $this->options);
				break;

				case 'error':
					echo '<p>'.
						__('Synchronisation failed', $this->textdomain).',<br />'.
						sprintf(__('Click <a href="%1s">here</a> if you want to start a new session', $this->textdomain), admin_url('link-manager.php?page=egdel_links_sync')).'</p>';
					$this->options['sync_status'] = 'stopped';
					egdel_save_options($this->options_entry, $this->options);
			} // End of switch
		} // End of links_sync_display_page

		/**
		 * links_sync
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync() {
			global $egdel_error;

			echo '<div id="icon-link-manager" class="icon32"><br /></div>'.
				 '<div class="wrap">'.
				 '<h2>'.__('BlogRoll synchronization', $this->textdomain).'</h2>';

			if ($this->check_requirements(TRUE)) {

				if (! current_user_can(LINKS_MIN_USER_RIGHTS))
					$egdel_error->set(EG_DELICIOUS_ERROR_USER_RIGHT);
				elseif (! $this->is_user_defined())
					$egdel_error->set(EG_DELICIOUS_ERROR_CONFIG);

				if ( $egdel_error->is_error() ) {
					$egdel_error->display();
				}
				else {
					// Event collect (three submit buttons: Start, Stop and Update.
					$this->links_sync_update();

					// Display pages
					$this->links_sync_display_page();

				} // End of no error
			} // End of requirements ok
			echo '</div>';
		} // End of links_sync

		/**
		 * links_sync_display_list
		 *
		 * Display table with all links and actions
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync_display_list() {
			global $egdel_cache;

			$links_db      = $egdel_cache->get('links_db');
			$linksdb_index = $egdel_cache->get('linksdb_index');

			if (is_array($linksdb_index) && sizeof($linksdb_index) > 0) {
				// Page Header: display a "number of page" selector, and a page navigation links
				if (isset($_GET['paged'])) $page_number = $_GET['paged'];
				else $page_number = 1;

				if (isset($_GET['links_per_page'])) $links_per_page = $_GET['links_per_page'];
				else $links_per_page = EG_DELICIOUS_LINKS_PER_PAGE;

				$links_per_page_selector = $links_per_page;
				if ($links_per_page == 'all') {
					$links_per_page = sizeof($linksdb_index);
					$page_links     = '';
				}
				else {
					$page_links = paginate_links( array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'total'   => ceil(sizeof($linksdb_index) / $links_per_page ),
							'current' => $page_number
						)
					);
				}

				$order_by = 'title';
				if ( isset($_GET['egdel_sync_order_by']) &&
				   ( $_GET['egdel_sync_order_by'] == 'title' || $_GET['egdel_sync_order_by'] == 'date'))
					$order_by = $_GET['egdel_sync_order_by'];

				if ($order_by == 'title') $order = 'ASC';
				else $order = 'DESC';
				if ( isset($_GET['egdel_sync_order']) &&
					($_GET['egdel_sync_order'] == 'ASC' || $_GET['egdel_sync_order'] == 'DESC'))
					$order = $_GET['egdel_sync_order'];

				$form_url = admin_url('link-manager.php');
				echo '<form id="egdel_filter" action="'.$form_url.'" method="GET">'.
					'<input type="hidden" name="page" value="egdel_links_sync" />'.
					'<div class="tablenav">';
				if ( $page_links )	echo '<div class="tablenav-pages">'.$page_links.'<br/>&nbsp;</div>';
				echo __('Links per page: ', $this->textdomain).
					$this->html_select(array( '25'  => '25',
										'50'  => '50',
										'75'  => '75',
										'100' => '100',
										'all' => 'All'),
									'links_per_page',
									$links_per_page_selector
					).
					'&nbsp;&nbsp;&nbsp;&nbsp;'.__('Order by: ', $this->textdomain).
					$this->html_select(array('title' => __('Title', $this->textdomain),
											 'date'  => __('Date', $this->textdomain)),
									'egdel_sync_order_by',
									$order_by).
					$this->html_select(array('ASC' => __('Ascending', $this->textdomain),
											 'DESC'  => __('Descending', $this->textdomain)),
									'egdel_sync_order',
									$order).
					'<input class="button" type="submit" id="egdel_sync_filter" value="'.__('Change', $this->textdomain).'" />'.
					'</div>'.
					'</form>';
			} // End of linksdb_index not empty

			$url_params = '?page=egdel_links_sync'.
			              '&links_per_page='.$links_per_page.
			              '&egdel_sync_order_by='.$order_by.
						  '&egdel_sync_order='.$order;

			echo '<form method="POST" action="'.$form_url.$url_params.'">'.
				wp_nonce_field('egdel_links_sync').
				'<table class="wide widefat egdel_links_sync">'.
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

			// If list is empty, just display a message
			if (!is_array($linksdb_index) || sizeof($linksdb_index)==0) {
				echo '<tr><td colspan="9">'.__('No links to display',$this->textdomain).'</td></tr>';
			}
			else {
				$order = ( strcasecmp($order,'ASC')==0 ? -1 : 1 );
				uasort($linksdb_index, create_function('$a,$b', '$a1=$a[\''.$order_by.'\']; $b1=$b[\''.$order_by.'\']; if ($a1==$b1) return 0; else return (($a1<$b1) ? '.$order.' : '.-$order.');') );

				// Divide page list into pages
				$pages_list      = array_chunk($linksdb_index , $links_per_page, true);
				$page_number     = min($page_number, sizeof($pages_list));
				$index           = ($page_number-1)*$links_per_page + 1;
				$current_page    = $pages_list[$page_number-1];
				$class_alternate = '';

				foreach ($current_page as $href => $last_update) {

					$attrs = $links_db[$href];
					$class_alternate = (strpos($class_alternate, 'alternate')===FALSE?'alternate':'');
					if ($attrs['suggested_category'] != EG_DELICIOUS_NOSYNC_ID && $attrs['action'] != 'none') {
						$class_alternate .= ($class_alternate==''?'':'-').'sync';
					}
					if ($class_alternate != '') $class_alternate = 'class="'.$class_alternate.'"';

					echo '<input type="hidden" name="egdel_sync_list['.$index.'][link_url]" id="egdel_sync_list['.$index.'][link_url]" value="'.$href.'" />'.
						'<tr '.$class_alternate.'>'.
						'<td>'.$index.'. <a href="'.$href.'" target="_blank">'.htmlspecialchars($attrs['link_name']).'</a></td>'.
						'<td>'.(sizeof($attrs['tags'])==0?'&nbsp;':implode(',<br />',$attrs['tags'])).'</td>'.
						'<td>'.($attrs['bundles']==''?'&nbsp;':implode(',<br />',$attrs['bundles'])).'</td>'.
						'<td>'.($attrs['link_updated']==0?'&nbsp;':date_i18n($this->datetime_format, $attrs['link_updated'] )).'</td>'.
						'<td>'.$this->links_sync_select_action($index, $attrs['action']).'</td>'.
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
			echo '<p class="submit"><input class="button" type="submit" name="egdel_sync_save" value="'.__('Update changes', $this->textdomain).'" />'.'&nbsp;&nbsp;'.
				'<input class="button" type="submit" name="egdel_sync_stop" value="'.__('Stop synchronization', $this->textdomain).'" />'.
				'</p></form>';
		} // End of links_sync_display_list

		/**
		 * links_sync_select_action
		 *
		 * Build a HTML select/option form.
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync_select_action($index, $default) {
			$actions_list = array(
				'none'    => __(' ', $this->textdomain),
				'add_wp'  => __('Add to WP', $this->textdomain),
				'upd_wp'  => __('Update WP', $this->textdomain),
				'del_wp'  => __('Delete from WP', $this->textdomain)
			);
			$string  = '<select name="egdel_sync_list['.$index.'][action]">';
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
		} // End of links_sync_select_action

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
		 * tags_sync
		 *
		 * Synchronize the WordPress and Delicious Tags database
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function tags_sync() {
			global $egdel_error;

			echo '<div id="icon-edit" class="icon32"><br /></div>'.
				 '<div class="wrap">'.
				 '<h2>'.__('Tags synchronization', $this->textdomain).'</h2>';

			// Button submit clicked => get form values, and update the database
			if (isset($_POST['egdel_sync_save'])) {

				check_admin_referer( 'egdel_tags_sync' );

				// Store the list of tags modified (for display)
				$terms_added_to_wp   = array();
				$term_delete_from_wp = array();

				// Get data
				$term_id_list   = $_POST['egdel_tags_id'];
				$term_name_list = $_POST['egdel_tags_name'];
				$action_list    = $_POST['egdel_tags_action'];

				// For each tags
				foreach ($action_list as $index => $action) {

					$name = $term_name_list[$index];
					$id   = intval($term_id_list[$index]);

					switch ($action) {
						case 'add_wp':
							if ($id == 0) {
								wp_insert_term($name, 'post_tag' );
								$terms_added_to_wp[] = $name;
							}
						break;

						case 'del_wp':
							if ($id != 0) {
								wp_delete_term( $id, 'post_tag' );
								$term_delete_from_wp[] = $name;
							}
						break;
					} // End of switch
				} // End of foreach

				// End of synchronization: display summary
				echo __('Delicious and WordPress tags are synchronized.', $this->textdomain);
				// Display the number of tags added in WordPress
				if ( sizeof($terms_added_to_wp) > 0 ) {
					echo '<h3>'.__( 'Tags added to WordPress:', $this->textdomain).'</h3>'.implode(', ', $terms_added_to_wp);
				}

				// Display the number of tags deleted from WordPress.
				if (sizeof($term_delete_from_wp)>0) {
					echo '<h3>'.__( 'Tags deleted from WordPress:', $this->textdomain).'</h3>'.
						implode(', ', $term_delete_from_wp);
				}

				echo '<p>'.sprintf(__('Delicious and WordPress tags are synchronized. Goto page <a href="%1s">Manage tags</a> to see results.', $this->textdomain), admin_url('edit-tags.php?taxonomy=post_tag')).'</p>';

			} // End of isset button save used
			else {
				if ($this->check_requirements(TRUE)) {

					if (! current_user_can(TAGS_MIN_USER_RIGHTS))
						$egdel_error->set(EG_DELICIOUS_ERROR_USER_RIGHT);
					elseif (! $this->is_user_defined())
						$egdel_error->set(EG_DELICIOUS_ERROR_CONFIG);
					else
						$sync_tags_list = $this->tags_sync_build_list();

					if ( $egdel_error->is_error())
						$egdel_error->display();
					else
						$this->tags_sync_display_page($sync_tags_list);
				} // End of check requirements
			}
			echo '</div>';
		} // End of tags_sync

		/**
		 * tags_sync_display_page
		 *
		 * Display tags synchronisation table
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function tags_sync_display_page($sync_tags) {

			$mode = $this->options['sync_tags_type'];
			echo '<p>'.sprintf(__( 'Synchronisation mode: <strong>%s</strong>.' ,$this->textdomain), __($mode, $this->textdomain)).'<br /><em>'.__( $this->HELP['sync_tags_type_'.$mode], $this->textdomain).'</em></p>'.
				'<p>'.sprintf(__( 'You can modify this mode, by <a href="%s">editing options</a>', $this->textdomain),admin_url('options-general.php?page=egdel_options')).'</p>'.
				'<form method="POST" action="'.admin_url('link-manager.php?page=egdel_tags_sync').'">'.
				wp_nonce_field('egdel_tags_sync').
				'<table class="wide widefat egdel_tag_sync">'.
			    '<thead><tr>'.
				'<th>#</th>'.
				'<th>'.__('WordPress',   $this->textdomain).'</th>'.
				'<th>'.__('Action',      $this->textdomain).'</th>'.
				'<th>'.__('Delicious',   $this->textdomain).'</th>'.
				'<th>'.__('Count',       $this->textdomain).'</th>'.
				'<th>'.__('Description', $this->textdomain).'</th>'.
				'</tr></thead>'.
				'<tbody>';

			if (sizeof($sync_tags)==0) {
				echo '<tr><td colspan="6">'.__('No tags to display',$this->textdomain).'</td></tr>';
			}
			else {
				$index = 1;
				foreach ($sync_tags as $attrs) {

					$id = $attrs['term_id'];
					$class_row = ($class_row == ''?'class="alternate"':'');
					echo '<input type="hidden" name="egdel_tags_id['.$index.']" value="'.$id.'" />'.
						'<input type="hidden" name="egdel_tags_name['.$index.']" value="'.($attrs['del_name']!=''?$attrs['del_name']:$attrs['wp_name']).'" />'.
						'<tr '.$class_row.'>'.
						'<td>'.$index.'</td>'.
						'<td>'.($attrs['wp_name']==''?'&nbsp;':$attrs['wp_name']).'</td>'.
						'<td>'.$this->tags_sync_select_action($index, $attrs['action']).'</td>'.
						'<td>'.($attrs['del_name']==''?'&nbsp;':$attrs['del_name']).'</td>'.
						'<td>'.$attrs['count'].'</td>'.
						'<td>'.htmlspecialchars(attribute_escape($attrs['description'])).'</td>'.
						'</tr>';
					$index++;
				}
			}
			echo '</tbody></table>'.
				'<p class="submit">'.
				'<input class="button" type="submit" name="egdel_sync_save" value="'.__('Update changes', $this->textdomain).'" />'.
				'</p></form>';
		} // End of tags_sync_display_page

		/**
		 * tags_sync_select_action
		 *
		 * Build a HTML select/option form.
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function tags_sync_select_action($index, $default) {
			$actions_list = array(
				'none'     => ' ',
				'add_wp'   => __('Add to WP', $this->textdomain),
				'del_wp'   => __('Delete from WP', $this->textdomain)
			);
			$output  = '<select name="egdel_tags_action['.$index.']">';

			if ($default == 'none')
				$output .= '<option value="none" selected>'.$actions_list['none'].'</option>'.
						   '<option value="del_wp" >'.$actions_list['del_wp'].'</option>';
			else
				$output .= '<option value="none" >'.$actions_list['none'].'</option>'.
						   '<option value="'.$default.'" selected>'.$actions_list[$default].'</option>';

						   $output .= '</select>';

			return ($output);
		} // End of tags_sync_select_action

		/**
		 * tags_sync_build_list
		 *
		 * Build a table of synchronization of WordPress and Delicious tags
		 *
		 * @package EG-Delicious
		 *
		 * @param  none
		 * @return interface	error code
		 */
		function tags_sync_build_list() {
			global $egdel_error;

			$egdel_error->clear();

			// Getting Delicious tags
			$tags_list = $this->deldata->get_data('tags');
			$sync_tags = FALSE;

			if ($tags_list !== FALSE) {

				// Getting WordPress tags
				$results = get_terms('post_tag', array('hide_empty' => FALSE));

				// Synchronisation Phase 1: Build the list from the WordPress tags
				// if tags doesn't exist in the Delicious list => action = delete WordPress link
				$sync_tags = array();
				foreach ($results as $tag) {
					$name = strtolower($tag->name);
					$sync_tags[$name] = array(
						'action'		=> 'none',
						'count' 		=> $tag->count,
						'description' 	=> $tag->description,
						'term_id'		=> $tag->term_id,
						'wp_name'		=> $tag->name,
						'del_name'      => ''
					);
					if (! isset($tags_list[$name]) && $this->options['sync_tags_type'] == 'replace')
						$sync_tags[$name]['action'] =  'del_wp';
				} // End of foreach phase 1

				// Synchronization Phase 2: Add all link existing in Delicious but not in WordPress
				foreach ($tags_list as $tag => $attrs) {
					$name = strtolower($tag);

					if (isset($sync_tags[$name])) {
						$sync_tags[$name]['del_name'] = $tag;
					}
					else {
						$sync_tags[$name] = array (
							'term_id'	=> 0,
							'del_name'  => $tag,
							'count'		=> $attrs['COUNT'],
							'action'	=> 'add_wp',
							'wp_name'	=> '');
					}
				} // End of foreach phase 2
			}
			return ($sync_tags);
		} // End of tags_sync_build_list

		/**
		 * mysql_to_unix_timestamp
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$mysql_date	date in mysql format
		 * @return 	integer				unix timestamp
		 */
		function mysql_to_unix_timestamp($mysql_date) {
			sscanf($mysql_date,"%4u-%u-%u %2u:%2u:%2u", $year, $month, $day, $hour, $minute, $second);
			return (mktime($hour, $minute, $second, $month, $day, $year ));
		} // End of mysql_to_unix_timestamp

		/**
		 * get_post_description
		 *
		 * Build a description from the text of a post
		 *
		 * @package EG-Delicious
		 *
		 * @param  	object	$current_post	a post
		 * @return 	string					the description
		 */
		function get_post_description($current_post) {

			$max_length = 150;
			$string = '';

			// If post has an excerpt?
			if (trim($current_post->post_excerpt) != '') {
				$string = wp_html_excerpt($current_post->post_excerpt, $max_length);
			}
			else {
				// No excerpt, try to find the <!--more--> tag, or cut content.
				$char_count = min( $max_length, strpos($current_post->post_content, '<!--more-->')-1 );

				// Extract description from the content
				$string = wp_html_excerpt ( $current_post->post_content, $char_count);
			}
			return ($string);
		} // End of get_post_description
	
		/**
		 * publish_post
		 *
		 * Create a Delicious post, while publish post in WordPress.
		 *
		 * @package EG-Delicious
		 *
		 * @param  	string		$new_status		new status of the post
		 * @param	string		$old_status		old status of the post
		 * @param	object		$post			post data
		 * @return 	none
		 */
		function publish_post( $new_status, $old_status, $post) {
			global $egdel_error;

			$egdel_error->clear();
			$value = TRUE;
			if ($new_status == 'publish' && $old_status != 'publish' &&  $this->is_user_defined()) {

				$post_tags = array();
				if ($this->options['publish_post_use_tags']) {
					$tags_list = get_the_tags($post->ID);
					if ($tags_list) {
						foreach ($tags_list as $tag) {
							$post_tags[] = $tag->name;
						}
					}
				}
				if ($this->options['publish_post_use_cats']) {
					$categories_list = get_the_category($post->ID);
					if ($categories_list) {
						foreach ($categories_list as $category) {
							$post_tags[] = $category->name;
						}
					}
				}
				if (trim($this->options['publish_post_use_spec'])!='') {
					$specific_list = split(',', $this->options['publish_post_use_spec']);
					foreach ($specific_list as $tag) {
						$post_tags[] = $tag;
					}
				}
				if (sizeof($post_tags)>0)
					$tags_string = implode(',', $post_tags);
				else
					$tags_string = '';

				$dt = $this->deldata->timestamp_to_iso($this->mysql_to_unix_timestamp($post->post_date));

				$params = array( 'description' 	=> urlencode(sanitize_title($post->post_title)),
								 'extended'    	=> urlencode($this->get_post_description($post)),
								 'dt' 			=> $dt,
								 'tags'			=> $tags_string,
								 'url'			=> get_permalink($post->ID),
								 'shared'		=> ($this->options['publish_post_share']==0?'no':'yes'),
								 'replace'		=> 'no'
				);

				$value = $this->deldata->push_data('post_add', $params);
				if ($value === FALSE) {
					$this->options['errors'][$this->current_wp_user] = $egdel_error->get();
					egdel_save_options($this->options_entry, $this->options);
				}
			} // End of move to publish state
			return ($value);
		} // End of publish_post

		/**
		 * delete_post
		 *
		 * Delete Delicious post when a WordPress post is deleted
		 *
		 * @package EG-Delicious
		 *
		 * @param	int		$post_id	id of the deleted WordPrss post
		 * @return 	none
		 */
		function delete_post($post_id=0) {
			global $egdel_error;

			$egdel_error->clear();
			if ($post_id != 0 && $this->is_user_defined()) {
				$post = get_post($post_id, OBJECT);
				if ($post && $post->post_type == 'post') {
					$permalink = get_permalink($post_id);
					$error_msg  = '';
					$value = $this->deldata->push_data('post_del', array('url' => $permalink));
					if ($value !== TRUE) {
						$this->options['errors'][$this->current_wp_user] = $egdel_error->get();
						egdel_save_options($this->options_entry, $this->options);
					}
				} // post exist
			} // End of (post defined && delicious user defined)
		} // End of delete_post

		/**
		 * backup_delicious_manual
		 *
		 * Execute a full backup of delicious database
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$backup_path	Path where will be stored backup files
		 * @return 	none
		 */
		function backup_delicious_manual($backup_path) {

			// Getting Delicious posts
			echo '<p>'.__( 'Downloading Delicious data', $this->textdomain).'</p>';
			$posts_list = $this->deldata->get_data('posts');
			if ($posts_list !== FALSE) {
				echo '<p>'.__( 'Generating backup file', $this->textdomain).'</p>';
				$output = '<!doctype netscape-bookmark-file-1>'."\n".
						'<meta http-equiv="content-type" content="text/html; charset=UTF-8">'."\n".
						'<!-- This is an automatically generated file. '.
						'It will be read and overwritten. Do Not Edit! -->'.
						'<title>Bookmarks</title>'."\n".
						'<h1>Bookmarks</h1><dl><p>'."\n";

				foreach ($posts_list as $href => $link) {
					$delicious_link_datetime = $this->deldata->get_local_time($link['TIME']);
					$output .= '<dt><a href="'.$href.'" TAGS="'.implode(' ',$link['TAG']).'" '.
							'ADD_DATE="'.$delicious_link_datetime.'" '.
							'>'.
							$link['DESCRIPTION'].'</a></dt>'."\n";
					if (trim($link['EXTENDED']) != '')
						$output .= '<dd>'.$link['EXTENDED'].'</dd>'."\n";
				}

				$output .= '</dl><p>'."\n".
					'<!-- fe11.feeds.del.ac4.yahoo.net uncompressed/chunked '.
					date('D M d H:i:s T Y').
					' -->';

				echo '<p>'.__( 'Saving backup file', $this->textdomain).'</p>';

				$file_name = $backup_path.date('YmdHi').'_delicious.html';
				$fd = @fopen($file_name, 'w');
				if ( false !== $fd )
					fputs($fd, $output);
				else
					$this->display_debug_info('Backup Error '.$file_name);

				@fclose($fd);

				if ($this->error_code != EG_DELICIOUS_ERROR_NONE) {
					echo '<p><span class="red">'.__( 'Backup failed', $this->textdomain).'</span></p>';
					$egdel_error->display();
				} // End of backup failed
				else {
					echo '<p><span class="green">'.__('Backup ended successfully', $this->textdomain).'</span></p>';
				}
				echo '<p>'.sprintf(__('Click on <a href="%s">this link</a> to return to backup configuration panel.', $this->textdomain), admin_url('tools.php?page=egdel_backup')).'</p>';
			}
		} // End of backup_delicious_manual

		/**
		 * backup_delicious
		 *
		 * Backup Delicious data
		 *
		 * @package EG-Delicious
		 *
		 * @param  	none
		 * @return 	none
		 */
		function backup_delicious() {
			global $egdel_error;

			$backup_path  = $this->plugin_path.'backup/';
			$backup_url  = $this->plugin_url.'backup/';

			global $wpmu_version, $blog_id;
			if (isset($wpmu_version) && isset($blog_id) ) {
				$backup_path .= $blog_id.'/';
				$backup_url	 .= $blog_id.'/';
			} // End of is WPMU?

			echo '<div id="icon-tools" class="icon32"><br></div>'.
				'<div class="wrap backup_delicious">'.
				'<h2>'.__('Delicious Backup', $this->textdomain).'</h2>';

			$egdel_error->clear();

			$submit = FALSE;
			if (isset($_POST['egdel_backup_manual'])
				/* && strcmp($_POST['egdel_backup_manual'],__('Start Manual backup', $this->textdomain))===0 */) {
				$submit = 'manual';
			}

			if (isset($_POST['egdel_backup_delete'])
				/* && strcmp($_POST['egdel_backup_delete'], __('Delete', $this->textdomain))===0 */ ) {
				$submit = 'delete';
			}

			if ($submit) {

				check_admin_referer( 'egdel_backup' );

				if ($submit == 'manual')
					$this->backup_delicious_manual($backup_path);
				elseif ($submit == 'delete' && isset($_POST['egdel_backup_files'])) {
					$list = $_POST['egdel_backup_files'];
					foreach ($list as $key) {
						if (is_numeric($key)) {
							$file_path = $backup_path.$key.'_delicious.html';
							@unlink($file_path);
						}
					}
				} // Delete button pressed
			} // Submit button pressed
			if ($submit === FALSE || $submit != 'manual')
				$this->backup_display_page($backup_path, $backup_url);

			echo '</div>';

		} // End of backup_delicious

		/**
		 * backup_check_status
		 *
		 * check if all is ok to perform a Delicious backup
		 *
		 * @package EG-Delicious
		 *
		 * @param  	string	$path			path of the backup directory
		 * @param	array	$backup_list	list of backup files (with size, date ...)
		 * @return 	boolean					TRUE if all is ok for the backup, FALSE otherwise
		 */
		function backup_check_status($path, & $backup_list) {
			global $egdel_cache;

			$status                = TRUE;
			$backup_list           = array();
			$last_backup_date      = 0;
			$last_delicious_update = 0;

			echo '<h3>'.__('Checking configuration', $this->textdomain).'</h3><table width="90%"><tbody>';

			// Check if backup directory exists
			echo  '<tr><td>'.__('Checking backup folder', $this->textdomain).'<br />'.
					'<span class="path">('.$path.')</span> ... </td><td>';
			if (@is_dir($path)) {
				echo '<span class="green">'.__('Backup path exists', $this->textdomain).'</span></td></tr>';
			}
			else {
				echo '<span class="red">'.__('Backup path doesn\'t exist, try to create it', $this->textdomain).'</span></td></tr>';

				$result = @mkdir($path);
				echo '<tr><td>'.__('Checking backup folder again ... ', $this->textdomain).'</td><td>';
				if (! @is_dir(stripslashes($path))) {
					echo '<span class="red">'.__('Backup path doesn\'t exist, and cannot create it!', $this->textdomain).'</span><br />'.__('Please create it manually and refresh this page', $this->textdomain).'</td></tr>';
					$status = FALSE;
				}
				else {
					echo '<span class="green">'.__('Backup path created successfully', $this->textdomain).'</span></td></tr>';
				}
			}

			// Check if backup directory is writeable
			if ($status) {
				echo '<tr><td>'.__('Checking if backup folder is writeable ... ', $this->textdomain).'</td><td>';
				if (@is_writable($path)) {
					echo '<span class="green">'.__('Path is writable!', $this->textdomain).'</span></td></tr>';
				}
				else {
					echo '<span class="red">'.__('Backup file is not writeable.', $this->textdomain).'</span></td></tr>'.
					'<tr><td colspan="2">'.__('Please change the security access of the directory before using this page.', $this->textdomain).'</td></tr>';
					$status = FALSE;
				}
			}

			// Collect backup already performed
			if ($status) {

				echo '<tr><td>'.__('Collecting backup files', $this->textdomain).' ... </td><td>';
				if ($dh = opendir($path)) {
					while (($file = readdir($dh)) !== false) {
						$file_path = $path.$file;
						if (preg_match('/([0-9])_delicious\.html/i', $file)) {
							sscanf($file,"%4u%2u%2u%2u%2u", $year, $month, $day, $hour, $minute);
							$file_date   = mktime($hour, $minute, 0, $month, $day, $year ); // filectime($file_path);
							$date_string = date_i18n($this->datetime_format, $file_date);
							$date_index  = date('YmdHi', $file_date);
							$backup_list[$date_index]['file']      = $file;
							$backup_list[$date_index]['datetime']  = $date_string;
							$backup_list[$date_index]['timestamp'] = $file_date;
							$backup_list[$date_index]['size']      = filesize($file_path);
						}
					} // End while
					closedir($dh);
				} // End opendir
				if (sizeof($backup_list)>0) {
					$backup_index = array_keys($backup_list);
					krsort($backup_index);
					$last_backup_date = $backup_list[current($backup_index)]['timestamp'];
				}
				echo '<span class="green">'.
						sprintf(__('done (%s collected).', $this->textdomain),sizeof($backup_list)).
						'</span></td></tr>';
			} // End of status ok

			// Collect Delicious data
			if ($status) {
				echo '<tr><td>'.__('Checking Delicious user', $this->textdomain).' ... </td><td>';
				if ( $this->is_user_defined() ) {
					echo '<span class="green">'.__('User defined.', $this->textdomain).'</span></td></tr>';
				} else {
					echo '<span class="red">'.__('User not defined.', $this->textdomain).'</span></td></tr>'.
					'<tr><td colspan="2">'.__('User defined.', $this->textdomain).'</td></tr>';
				}
			}

			if ($status) {
				echo '<tr><td>'.__('Checking Delicious connection', $this->textdomain).' ... </td><td>';
				$last_delicious_update = $egdel_cache->get('last_delicious_update');
				if ($last_delicious_update === FALSE) {
					$last_delicious_update = $this->deldata->get_data('update');
				}
				if ($last_delicious_update === FALSE) {
					$status = FALSE;
					echo '<span class="red">'.__('Connection failed.', $this->textdomain).'</span></td></tr>';
				}
				else {
					$egdel_cache->set('last_delicious_update', $last_delicious_update);
					echo '<span class="green">'.__('Connection done.', $this->textdomain).'</span></td></tr>';
					$last_delicious_update = $this->deldata->get_local_time($last_delicious_update);
				}
			}

			if ($status) {
				if ($last_backup_date != 0 && $last_delicious_update != 0) {
					if ($last_backup_date >= $last_delicious_update) {
						echo '<tr><td>'.__('The last backup is earlier than the last Delicious database change.', $this->textdomain).'</td><td><span class="green">'.__('Backup not required.', $this->textdomain).'</span></td></tr>';
					}
					else {
						echo '<tr><td>'.__('Delicious database evolved since the last backup.',$this->textdomain).'</td><td><span class="orange">'.__('Backup recommended.', $this->textdomain).'</span></td></tr>';
					}
				} // End of date not empty
			} // End of status Ok
			echo '</tbody></table>';
			return ($status);
		} // End of backup_check_status

		/**
		 * display_backup_page
		 *
		 * Diplay backup page and options
		 *
		 * @package EG-Delicious
		 *
		 * @param  	string	$backup_path	path where will be stored the backup files
		 * @param  	string	$backup_url		url to reach backup files
		 * @return 	none
		 */
		function backup_display_page($backup_path, $backup_url) {

			// Check if all is ok from WordPress and Delicious side
			$config_status = $this->backup_check_status($backup_path, $backup_list);

			echo '<form method="POST" action="'.admin_url('link-manager.php?page=egdel_backup').'" >'.
				wp_nonce_field('egdel_backup');

			// If config is Ok, propose the backup button
			if ($config_status) {
				echo '<h3>'.__( 'Manual backup', $this->textdomain).'</h3>'.
					'<p>'.__('Click on', $this->textdomain).' <input type="submit" name="egdel_backup_manual" value="'.__('Start Manual backup', $this->textdomain).'" /> '.' '.__('to start the backup immediately.', $this->textdomain).'</p>';
			}

			// Display backup history if exists
			echo '<h3>'.__('Backup history', $this->textdomain).'</h3>';
			echo '<table class="widefat backup_list">'.
				'<thead>'.
				'<tr>'.
				'<th>'.__('No.',       $this->textdomain).'</th>'.
				'<th>'.__('Select',    $this->textdomain).'</th>'.
				'<th>'.__('File',      $this->textdomain).'</th>'.
				'<th>'.__('Date/Time', $this->textdomain).'</th>'.
				'<th>'.__('Size',      $this->textdomain).'</th>'.
				'</tr>'.
				'</thead>'.
				'<tbody>';

			if (sizeof($backup_list) == 0) {
				echo '<tr><td colspan="5">'.
					__('No backup file found. No backup performed before.', $this->textdomain).
					'</td></tr>';
			}
			else {
				// Extract index from the list, in order to sort the list
				$backup_index = array_keys($backup_list);
				krsort($backup_index);

				$count    = 1;
				$sum_size = 0;
				$output = '';
				foreach ($backup_index as $index) {
					$file_name = $backup_list[$index]['file'];
					$output .= '<tr>'.
						'<td>'.$count.'</td>'.
						'<td><input type="checkbox" name="egdel_backup_files['.$count.']" value="'.$index.'" /></td>'.
						'<td><a href="'.$backup_url.$file_name.'" target="_blank">'.$file_name.'</a></td>'.
						'<td>'.$backup_list[$index]['datetime'].'</td>'.
						'<td>'.size_format($backup_list[$index]['size']).'</td>'.
						'</tr>';
					$sum_size += $backup_list[$index]['size'];
					$count++;
				}
				// Display the total size used
				$output .= '<tr class="total">'.
					'<td colspan="2">&nbsp;</td>'.
					'<td>'.__('Total', $this->textdomain).'</td>'.
					'<td>&nbsp;</td>'.
					'<td>'.size_format($sum_size).'</td>'.
					'</tr>';
			} // End backup list not empty
			echo $output.'</tbody></table>'.
				'<input type="submit" name="egdel_backup_download" value="'.__('Download', $this->textdomain).'" /> '.
				'<input type="submit" name="egdel_backup_delete" value="'.__('Delete', $this->textdomain).'" /> '.
				'</form>';

		} // End of display_backup_page

		/**
		 * backup_delicious_download
		 *
		 * download backup files
		 *
		 * @package EG-Delicious
		 *
		 * @param  	none
		 * @return 	none
		 */
		function backup_delicious_download() {

			// Check if we press the right button
			if (isset($_POST['egdel_backup_download'])
				/* && strcmp($_POST['egdel_backup_download'], __('Download', $this->textdomain)) === 0 */) {

				// Check security
				check_admin_referer( 'egdel_backup' );

				// is there a file checked?
				if (isset($_POST['egdel_backup_files'])) {
					$key = current($_POST['egdel_backup_files']);

					// Check if the key is really numeric
					if (is_numeric($key)) {
						$file_path = $this->plugin_path.'backup/'.$key.'_delicious.html';
						if (file_exists($file_path)) {
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header("Content-Disposition: attachment; filename=".basename($file_path).";");
							header("Content-Transfer-Encoding: binary");
							header("Content-Length: ".filesize($file_path));
							@readfile($file_path);
							exit;
						} // End of file_exists
					} // End of all is ok to download file.
				} // End of getting file $key
			} // End of checking $_POST
		} // End of backup_delicious_download

		/**
		 * scheduled_sync
		 *
		 * Performed synchronization without interaction
		 * Can be used with cron
		 * In admin part, the function is empty, and is only here to create cron entry
		 *
		 * @package EG-Delicious
		 *
		 * @param  none
		 * @return none
		 */
		function scheduled_sync() {
			// Nothing in admin part, see eg-delicious-public.inc.php
		}

		/**
		 * display_shedule_sync_log
		 *
		 * Display logs of scheduled synchronization
		 *
		 * @package EG-Delicious
		 *
		 * @param 	none
		 * @return 	none
		 */
		function display_shedule_sync_log() {

			$file = $this->plugin_path.'/eg-delicious-schedule.log';
			if (file_exists($file))
				$logs = file($file);
			else
				$logs = array();

			echo '<div class="wrap">'.
				'<div id="icon-options-general" class="icon32"></div>'.
				'<h2>'.__('EG-Delicious Options', $this->textdomain).'</h2>'.
				'<p>'.sprintf(__('Click <a href="%s">HERE</a> to return to the options page', $this->textdomain),
								admin_url('options-general.php?page=egdel_options')).'</p>'.
				'<table class="wide widefat">'.
				'<thead><tr>'.
				'<th>'.__('Type',    $this->textdomain).'</th>'.
				'<th>'.__('Date',    $this->textdomain).'</th>'.
				'<th>'.__('Event',   $this->textdomain).'</th>'.
				'<th>'.__('Message', $this->textdomain).'</th>'.
				'</tr></thead>';
			if (sizeof($logs) == 0) {
				echo '<tr><td colspan="4">'.__('No logs', $this->textdomain).'</td></tr>';
			}
			else {
				$class_alternate = '';
				reset($logs);
				$string = end($logs);
				while ($string !== FALSE) {
					list($date, $type, $event, $message) = explode("\t", $string);
					echo '<tr '.$class_alternate.'>'.
						'<td>'.$type.'</td>'.
						'<td>'.$date.'</td>'.
						'<td>'.$event.'</td>'.
						'<td>'.$message.'</td>'.
						'</tr>';
					if ($class_alternate == '') $class_alternate = 'class="alternate"';
					else $class_alternate = '';

					$string = prev($logs);
				} // End of foreach

				// Purge file to avoid uncontrolled growth
				if (sizeof($logs) > EG_DELICIOUS_LOG_RETENTION) {
					$new_logs = array_slice($logs, -EG_DELICIOUS_LOG_RETENTION, EG_DELICIOUS_LOG_RETENTION);

					$fh = fopen($file, 'w');
					foreach ($new_logs as $string)
						fwrite($fh, $string);
					fclose($fh);
				} // End of logs greater than n rows
			} // End of display logs
			echo '</table>'.
				'<p>'.sprintf(__('Click <a href="%s">HERE</a> to return to the options page', $this->textdomain),
				admin_url('options-general.php?page=egdel_options')).'</p>'.
				'</div>';
		} // End of display_shedule_sync_log


	} // End of Class

} // End of if class_exists

$eg_delicious_admin = new EG_Delicious_Admin('EG-Delicious',
									EG_DELICIOUS_VERSION ,
									EG_DELICIOUS_COREFILE,
									EG_DELICIOUS_OPTIONS_ENTRY,
									$EG_DELICIOUS_DEFAULT_OPTIONS);
$eg_delicious_admin->set_textdomain(EG_DELICIOUS_TEXTDOMAIN);
$eg_delicious_admin->set_wp_versions('2.6',	FALSE, '2.7', FALSE);
$eg_delicious_admin->set_stylesheets(FALSE, 'eg-delicious-admin.css') ;
$eg_delicious_admin->set_update_notice('The next version and above will be supported only with WordPress 2.7 and further.');
if (function_exists('wp_remote_request'))
	$eg_delicious_admin->set_php_version('4.3', FALSE, 'curl');
else
	$eg_delicious_admin->set_php_version('4.3', 'allow_url_fopen');

$eg_delicious_admin->set_debug_mode(EG_DELICIOUS_DEBUG_MODE, 'eg_delicious.log');
$eg_delicious_admin->load();

?>