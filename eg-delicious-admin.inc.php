<?php

if (! class_exists('EG_Forms_104')) {
	require('lib/eg-forms.inc.php');
}

if (! class_exists('EG_Delicious_Admin')) {

	define('EG_DELICIOUS_ERROR_NONE',       0);
	define('EG_DELICIOUS_ERROR_GET_WPLINK', 10);
	define('EG_DELICIOUS_ERROR_GET_WPCAT',	11);
	define('EG_DELICIOUS_ERROR_USER_RIGHT', 12);
	define('EG_DELICIOUS_ERROR_CONFIG', 	13);
	define('EG_DELICIOUS_ERROR_DELQUERY',	14);
	define('EG_DELICIOUS_ERROR_LISTCHG',	15);
	define('EG_DELICIOUS_ERROR_NOTAG',		16);
	define('EG_DELICIOUS_ERROR_NOBUNDLE',	17);
	define('EG_DELICIOUS_ERROR_NOPUBLISH',	18);
	define('EG_DELICIOUS_ERROR_BACKUP_PATH',19);
	define('EG_DELICIOUS_ERROR_CANTDEL',    20);

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
		'sync_cat_multi'			 => 'single',		// single or multi
		'sync_cat_type'		 		 => 'bundle',		// bundle or tag
		'sync_links_default_target'	 => 'none',			// none, _blank, _top
		'sync_links_default_visible' => 'Y',			// Y or N
		'sync_links_other_item'		 => EG_DELICIOUS_NOSYNC_ID,
		'sync_links_not_classified'  => EG_DELICIOUS_NOSYNC_ID,
		'uninstall_options'			 => 0,
		'bundles_assignment'		 => array(),
		'tags_assignment'			 => array(),
		'wp_link_update'			 => 1,
		'sync_status'				 => 'stopped',   	// started, ended, error
		'sync_date'					 => 0,
		'sync_user'					 => '',
		'last_sync_date' 			 => 0,
		'sync_tags_type'			 => 'update',		// update or replace
		'publish_post'				 => 0,
		'publish_post_use_tags'		 => 1,
		'publish_post_use_cats'		 => 1,
		'publish_post_use_spec'		 => '',
		'publish_post_share'		 => 0
	);

	/**
	 * Class EG_Delicious_Admin
	 *
	 *
	 *
	 * @package EG-Delicious
	 */
	Class EG_Delicious_Admin extends EG_Plugin_106 {

		var $options_form;
		var $plugin_temp;
		var $wp_link_categories;
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
		var $error_msg;
		var $error_details;

		var $links_min_user_rights = 'manage_links';
		var $tags_min_user_rights  = 'manage_categories';

		var $HELP = array(
			'sync_tags_type_update'  => 'All tags existing in Delicious and NOT in WordPress will be added in the WordPress database.',
			'sync_tags_type_replace' => 'All tags existing in WordPress and NOT in Delicious will be deleted, and all tags existing in Delicious and NOT in WordPress will be added.'
		);

		var	$ERROR_MESSAGES = array(
				EG_DELICIOUS_CORE_ERROR_NONE 	=> 'No error.',
				EG_DELICIOUS_ERROR_GET_WPLINK	=> 'Error while requesting WordPress links.',
				EG_DELICIOUS_ERROR_GET_WPCAT	=> 'Error while getting WordPress links categories.',
				EG_DELICIOUS_ERROR_USER_RIGHT	=> 'You cannot access to the page. You haven\'t the "Manage links" capability. Please contact the blog administrator.',
				EG_DELICIOUS_ERROR_CONFIG		=> 'Plugin not configured! Please go to <strong>Settings / EG-Delicious</strong> page to enter required parameters.',
				EG_DELICIOUS_ERROR_DELQUERY		=> 'Error while querying Delicious.',
				EG_DELICIOUS_ERROR_LISTCHG		=> 'Delicious <strong>Tags</strong> or <strong>Bundles</strong> changed since last options settings. A check is recommended.',
				EG_DELICIOUS_ERROR_NOTAG		=> 'No tag downloaded from Delicious. Switch to bundle mode.',
				EG_DELICIOUS_ERROR_NOBUNDLE		=> 'No bundle downloaded from Delicious. Switch to tag mode.',
				EG_DELICIOUS_ERROR_NOPUBLISH	=> 'Cannot publish post in Delicious.',
				EG_DELICIOUS_ERROR_BACKUP_PATH  => 'Cannot create backup path. Backup failed',
				EG_DELICIOUS_ERROR_CANTDEL		=> 'Cannot delete post in Delicious'
			);

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
							$this->links_min_user_rights, 		/* Access level / capability			*/
							'egdel_options',					/* file 								*/
							'options_page',						/* function								*/
							'load_eg_delicious_pages');

			// Add links synchronisation page
			$this->add_page('links',
							'Blogroll Synchronisation',			/* Page title					*/
							'EG-Delicious Sync.',				/* Menu title 					*/
							$this->links_min_user_rights, 		/* Access level / capability	*/
							'egdel_links_sync',					/* file 						*/
							'links_sync',						/* function						*/
							'load_eg_delicious_pages');

			// Add tags synchronization page
			$this->add_page('posts',
							'Delicious tag synchronization',	/* Page title					*/
							'EG-Delicious Tags',				/* Menu title 					*/
							$this->tags_min_user_rights, 		/* Access level / capability	*/
							'egdel_tags_sync',					/* file 						*/
							'tags_sync',						/* function						*/
							'load_eg_delicious_pages');

			// Add backup Delicious page
			$this->add_page('tools',
							'Delicious Backup',					/* Page title					*/
							'EG-Delicious Backup',				/* Menu title 					*/
							$this->links_min_user_rights, 		/* Access level / capability	*/
							'egdel_backup',						/* file 						*/
							'backup_delicious',					/* function						*/
							'load_eg_delicious_pages');

		} // End of plugins_loaded

		/**
		 * ainit
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
		}

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

			if (current_user_can( 'publish_posts' ) && $this->options['publish_post']) {
				add_action('transition_post_status', array(&$this, 'publish_post'), 10, 3);
				add_action('delete_post', array(&$this, 'delete_post'));
			}

			add_action('admin_notices', array(&$this, 'notice_error'));
			if ($this->options['wp_link_update']) {
				add_action('edit_link', array(&$this, 'update_wp_link_date'));
				add_action('add_link',  array(&$this, 'update_wp_link_date'));
			}
		} // End of admin_init

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
			if ($this->options['error_code'] != EG_DELICIOUS_ERROR_NONE) {
				$this->display_error($this->options['error_code'], FALSE, $this->options['error_detail']);
				$this->options['error_code']   = EG_DELICIOUS_ERROR_NONE;
				$this->options['error_detail'] = '';
				$this->save_options();
			}
		} // End of notice_error

		/**
		 * check_bundles_tags_modification
		 *
		 * Check if bundles or tags changed since the last options saving
		 *
		 * @package EG-Delicious
		 *
		 * @param 	boolean		$display	TRUE to display error message, FALSE otherwise
		 * @param 	boolean		$set_error	TRUE to set the error at plugin level
		 * @return 	boolean					True if change, False if not change
		 */
		function check_bundles_tags_modification($display=TRUE, $set_error=TRUE) {

			// Array_diff_key cannot be use because available only with PHP 5.

			if ($this->options['sync_cat_type'] == 'tag') {
				if (isset($this->options['tags_assignment']))
					$table = array_keys($this->options['tags_assignment']);
				if (isset($this->tags_list) && $this->tags_list!==FALSE)
					$list  = array_keys($this->tags_list);
			}
			else {
				if (isset($this->options['bundles_assignment']))
					$table = array_keys($this->options['bundles_assignment']);
				if (isset($this->bundles_list) && $this->bundles_list!==FALSE)
					$list = array_keys($this->bundles_list);
			}
			if (! isset($list)) $list = array();

			$returned_code = FALSE;
			if ( /* isset($table) && isset($list) && */ sizeof($table)>0 && sizeof($list)>0) {
				// Array_diff_key cannot be use because available only with PHP 5.
				$returned_code = (sizeof(array_diff($table, $list))>0 || sizeof(array_diff($list, $table))>0);
			}
			if ($returned_code) {
				if ($set_error) $this->error_code = EG_DELICIOUS_ERROR_LISTCHG;
				if ($display)   $this->display_error(EG_DELICIOUS_ERROR_LISTCHG);
			}
			return ($returned_code);

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

			$form = new EG_Forms_104('', '', '', $this->textdomain, '', '', 'egdel_options', 'mailto:'.$this->plugin_author_email);

			$id_section = $form->add_section('Delicious account');
			$id_group   = $form->add_group($id_section, 'Username');
			$form->add_field($id_section, $id_group, 'text', 'Username', 'username');

			$id_group   = $form->add_group($id_section, 'Password');
			$form->add_field($id_section, $id_group, 'password', 'Password', 'password');

			if ( $this->is_user_defined() ) {

				$this->tags_list = $this->delicious_data->get_data('tags');
				if ($this->tags_list === FALSE) {
					$this->delicious_data->get_error($this->error_code, $this->error_msg);
					$this->error_details =  'Cannot get Delicious tags';
				}
				$this->bundles_list = $this->delicious_data->get_data('bundles');
				if ($this->bundles_list === FALSE) {
					$this->delicious_data->get_error($this->error_code, $this->error_msg);
					$this->error_details =  'Cannot get Delicious bundles';
				}

				if ($this->error_code != EG_DELICIOUS_ERROR_NONE) {
					$this->display_error();
				}
				else {

					if ($this->options['sync_cat_type'] == 'tag') {
						if ( sizeof($this->tags_list)==0 && sizeof($this->bundles_list)>0 ) {
							$this->options['sync_cat_type'] = 'bundle';
							$this->error_code = EG_DELICIOUS_ERROR_NOTAG;
						}
					}
					else {
						if ( sizeof($this->bundles_list)==0 &&  sizeof($this->tags_list)>0) {
							$this->options['sync_cat_type'] = 'tag';
							$this->error_code = EG_DELICIOUS_ERROR_NOBUNDLE;
						}
					}
					if ($this->error_code != EG_DELICIOUS_ERROR_NONE)
						$this->display_error();

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

					$id_section = $form->add_section('Categories synchronization');

					$id_group   = $form->add_group($id_section, 'Replace or update', 'When a WordPress link is updated:');
					$form->add_field($id_section, $id_group, 'radio', 'Replace or update', 'sync_cat_update', '', '' , '','', 'regular', array('replace' => 'Replace the existing categories by those coming from Delicious,', 'update' => 'Add categories coming from Delicious.', 'none' => 'Keep the WordPress categories'));

					$id_group   = $form->add_group($id_section, 'Allow multiple categories', 'During category synchronisation:', 'If you choose "Add categories" in the previous question, some links will have several categories, evenif you choose "Allow only one category".');
					$form->add_field($id_section, $id_group, 'radio', 'Allow multiple categories', 'sync_cat_multi', '', '' , '','', 'regular', array('single' => 'Allow only one category per WordPress link,', 'multi' => 'Allow several categories per WordPress link.'));

					$id_group   = $form->add_group($id_section, 'Alignment key', 'Do you want to synchronize the WordPress link categories with', 'Click on <strong>Save changes</strong> button to change the following alignment table');
					$form->add_field($id_section, $id_group, 'radio', 'Alignment key', 'sync_cat_type', '', '' , '','', 'regular', array('tag' => 'Delicious tags', 'bundle' => 'Delicious Bundle'));

					$wp_link_categories = $this->get_wp_links_categories(TRUE);

					if ($this->options['sync_cat_type'] == 'tag') {
						$id_group = $form->add_group($id_section, 'Tags / Categories assignments');
						$tags_categories = array( 'header' => array('Delicious Tags', 'WordPress Categories'));
						foreach ($this->tags_list as $tag => $values) {
							$tags_categories['list'][] = array( 'value' => $tag, 'select' => $wp_link_categories);
						}
						$form->add_field($id_section, $id_group, 'grid select', 'Tags / Categories assignments', 'tags_assignment', '', '', '', '', 'regular', $tags_categories );
					}
					else {
						$id_group = $form->add_group($id_section, 'Bundles / Categories assignments');
						$bundles_categories = array( 'header' => array('Delicious Bundle', 'WordPress Categories'));
						foreach ($this->bundles_list as $bundle => $values) {
							$bundles_categories['list'][] = array( 'value' => $bundle, 'select' => $wp_link_categories);
						}
						$form->add_field($id_section, $id_group, 'grid select', 'Bundles / Categories assignments', 'bundles_assignment', '', '', '', '', 'regular', $bundles_categories );
					}

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
				$form = $this->add_options_form();

				$results = $form->get_form_values($this->options, $EG_DELICIOUS_DEFAULT_OPTIONS);
				if ($results) {
					$sync_cat_type     = $this->options['sync_cat_type'];
					$username_password = $this->options['username'].$this->options['password'];
					$this->options = $results;
					$this->save_options();

					if ($sync_cat_type     != $this->options['sync_cat_type'] ||
					    $username_password != $this->options['username'].$this->options['password']) {
						$form = $this->add_options_form();
					}
				}
				$this->check_bundles_tags_modification();
				$form->display_form($this->options);
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
		 * @param 	boolean	$add_nosync	add a category named nosync
		 * @return array				list of categories
		 */
		function get_wp_links_categories($add_nosync=FALSE) {

			if (!isset($this->wp_link_categories)) {
				$this->wp_link_categories = array();
				// Cache is managed inside the get_terms function
				$results = get_terms('link_category', array('hide_empty' => FALSE));
				if ($results) {
					foreach ($results as $result) {
						$this->wp_link_categories[$result->term_id] = $result->name;
					}
				}
				else {
					$this->error_code = EG_DELICIOUS_ERROR_GET_WP_CATEGORIES;
					return FALSE;
				}
			}
			if ($add_nosync)
				return ( array(EG_DELICIOUS_NOSYNC_ID => EG_DELICIOUS_NOSYNC_LABEL) + $this->wp_link_categories );
			else
				return ($this->wp_link_categories);

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

			$categories_list = $this->get_wp_links_categories($add_nosync);
			$select_string = '';
			if ($categories_list !== FALSE) {
				$select_string = '<select name="egdel_wp_categories'.($index>0?'['.$index.']':'').'">';
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

			$this->get_wp_links_categories();

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

			if ( false === $wpdb->query( $wpdb->prepare('UPDATE '.$wpdb->links.' SET link_updated=NOW() WHERE link_id=%d', intval($link_id) ))) {
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
		function display_error($error_code=FALSE, $error_msg='', $error_details='') {

			if ($error_code === FALSE) $error_code = $this->error_code;

			if ($error_code != EG_DELICIOUS_ERROR_NONE) {
				if ($error_msg == '') {
					if ($this->error_msg == '') $error_msg = __($this->ERROR_MESSAGES[$error_code], $this->textdomain);
					else $error_msg = __($this->error_msg, $this->textdomain);
				}
				if ($error_details != '') {
					$error_details = __($error_details, $this->textdomain);
				}
				elseif ($this->error_details != '') {
					$error_details = __($this->error_details, $this->textdomain);
				}

				echo '<div id="message" class="error fade"><p>'.
					__('Error ', $this->textdomain).$error_code.': '.$error_msg.' '.$error_details.
					'</p></div>';
			}
		} // End of display_error


		/**
		 * links_sync_change_date
		 *
		 * Change 'link_updated' field of the wp_link table
		 *
		 * @package EG-Delicious
		 *
		 * @param 	int		$link_id		id of the link to update
		 * @param	int		$timestamp		unix timestamp
		 * @return 	boolean					TRUE if all is OK, FALSE otherwise
		 */
		function links_sync_change_date($link_id, $timestamp) {
			global $wpdb;

			$query = 'UPDATE '.$wpdb->links.' SET link_updated=FROM_UNIXTIME(%s) WHERE link_id=%d';
			if ( false === $wpdb->query( $wpdb->prepare($query, $timestamp, $link_id ))) {
				if ( $wp_error )
					return new WP_Error( 'db_update_error', __( 'Could not update link in the database' ), $wpdb->last_error );
				else
					return 0;
			} // End of if query
		} // End of links_sync_change_date

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

			$this->egdel_sync_submit = FALSE;
			if (isset($_POST['egdel_sync_start']))        $this->egdel_sync_submit = 'start';
			elseif (isset($_POST['egdel_sync_stop']))     $this->egdel_sync_submit = 'stop';
			elseif (isset($_POST['egdel_sync_restart']))  $this->egdel_sync_submit = 'restart';
			elseif (isset($_POST['egdel_sync_save']))     $this->egdel_sync_submit = 'save';
			elseif (isset($_POST['egdel_sync_continue'])) $this->egdel_sync_submit = 'continue';

			if ($this->options['sync_status'] == 'started') {
				if (! isset($this->links_db) && file_exists($this->file_linksdb))
					$this->links_db = unserialize(file_get_contents($this->file_linksdb));

				if (! isset($this->linksdb_index) && file_exists($this->file_linksdb))
					$this->linksdb_index = unserialize(file_get_contents($this->file_linksdb_index));
			}

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
						$this->links_sync_build_list();
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
										$this->links_sync_change_date($link_id, $link['link_updated']);
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
		} // End of links_sync_update

		/**
		 * links_sync_compute
		 *
		 * Collect data from WordPress and Delicious
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync_compute() {
			global $wpdb;

			switch ($this->options['sync_status']) {

				case 'stopped':
					// Collect last WordPress links update
					$this->last_wp_update = wp_cache_get('last_wp_update', $this->cache_group);
					if ($this->last_wp_update === FALSE) {
						$results = $wpdb->get_results( 'SELECT UNIX_TIMESTAMP(MAX(link_updated)) as wp_last_update FROM '.$wpdb->links );
						if (sizeof($results) != 0 && $results[0]->wp_last_update != 0) {
							$this->last_wp_update = $results[0]->wp_last_update;
							wp_cache_set('last_wp_update', $this->last_wp_update, $this->cache_group);
						}
					}

					// Collect last Delicious link update
					$this->last_delicious_update = $this->delicious_data->get_data('update');
					if ($this->last_delicious_update === FALSE) {
						$this->delicious_data->get_error($this->error_code, $this->error_msg);
						$this->error_details = 'Cannot collect last Delicious update date.';
					}
				break;

			} // End of switch
		} // End of links_sync_compute

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

			$this->display_error();

			switch ($this->options['sync_status']) {
				case 'started':
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
							$this->links_sync_display_list();
						} // End of check referrer
					} // End of synchronization start by current user

				break;

				case 'stopped':
					if ($this->last_wp_update === FALSE) $last_wp_upd = __('Unknown', $this->delicious);
					else $last_wp_upd = date_i18n($this->datetime_format, $this->last_wp_update);

					if ($this->last_delicious_update === FALSE) $last_del_upd = __('Unknown', $this->delicious);
					else $last_del_upd = date_i18n($this->datetime_format, $this->get_local_time($this->last_delicious_update));

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

					echo '<form method="POST" action=""><p class="submit">'.
						 wp_nonce_field('egdel_links_sync').
						 '<input class="button" type="submit" name="egdel_sync_start" value="'.__('Start synchronization', $this->textdomain).'" />'.'</p></form>';

				break;

				case 'ended':
					echo '<p>'.
						sprintf(__('There is no link to synchronize. <br />Synchronization session is ended successfully.<br/>You can see the result of the synchronisation by <a href="%s">browsing links</a>', $this->textdomain), admin_url('link-manager.php')).'</p>';
					$this->options['sync_status'] = 'stopped';
					$this->save_options();
				break;

				case 'error':
					echo '<p>'.
						__('Synchronisation failed', $this->textdomain).',<br />'.
						sprintf(__('Click <a href="%1s">here</a> if you want to start a new session', $this->textdomain), admin_url('link-manager.php?page=egdel_links_sync')).'</p>';
					$this->options['sync_status'] = 'stopped';
					$this->save_options();
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
			echo '<div id="icon-link-manager" class="icon32"><br /></div>'.
				 '<div class="wrap">'.
				 '<h2>'.__('BlogRoll synchronization', $this->textdomain).'</h2>';

			if ($this->check_requirements(TRUE)) {

				if (! current_user_can($this->links_min_user_rights)) $this->error_code = EG_DELICIOUS_ERROR_USER_RIGHT;
				elseif (! $this->is_user_defined()) $this->error_code = EG_DELICIOUS_ERROR_CONFIG;

				if ($error_code != EG_DELICIOUS_ERROR_NONE) {
					$this->display_error();
				}
				else {
					// Event collect (three submit buttons: Start, Stop and Update.
					$this->links_sync_update();

					// Collect data from WordPress and Delicious
					$this->links_sync_compute();

					// Display pages
					$this->links_sync_display_page();

				} // End of no error
			} // End of requirements ok
			echo '</div>';
		} // End of links_sync

		/**
		 * links_sync_build_list
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function links_sync_build_list($update_sync = FALSE) {
			global $wpdb;

			$this->error_code = EG_DELICIOUS_ERROR_NONE;
			$sync_wp_cleanup  = ($this->options['sync_links_wp_del'] && !$update_sync);

			// Get the date since last update
			if ($update_sync) $update_sync = $this->get_local_time($this->options['last_sync_date']);

			// Get all links from Delicious
			// TODO: add parameter or array of parameters for the request
			$this->posts_list = $this->delicious_data->get_data('posts');
			if ($this->posts_list === FALSE) {
				$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
				$this->delicious_data->get_error($this->error_code, $this->error_msg);
				$this->error_details =  'Cannot get Delicious posts';
			}
			else {
				$this->tags_list = $this->delicious_data->get_data('tags');
				if ($this->tags_list === FALSE) {
					$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
					$this->delicious_data->get_error($this->error_code, $this->error_msg);
					$this->error_details =  'Cannot get Delicious tags';
				}
				else {
					$this->bundles_list = $this->delicious_data->get_data('bundles');
					if ($this->bundles_list === FALSE) {
						$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
						$this->delicious_data->get_error($this->error_code, $this->error_msg);
						$this->error_details = 'Cannot get Delicious bundles';
					}
				}
			}

			if ( $this->posts_list!==FALSE && $this->tags_list!==FALSE && $this->bundles_list!==FALSE ) {

				$this->check_bundles_tags_modification(TRUE, FALSE);

				// We have Delicious links.
				// Prepare WordPress link List: default WordPress functions don't give link_categories => use SQL query
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

					// Phase 1: Formatting WordPress links list
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
							$this->linksdb_index[$href] = array(
											'title' => $link->link_name,
											'date'  => $link->link_updated);
						}
						$previous_link_id = $link->link_id;
					} // end Foreach WP link
					unset($wp_links_list);
				} // End of Get WP links Ok.

				// Phase 2: Check if Delicious links exists in WordPress list or not
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
									'link_category'     => array()
							);
					} // End add mode
					else {
						// Update mode:
						// if option is not auto, and action=always, => action is update.
						if ($this->options['sync_links_update'] != 'auto') {
							$action = ($this->options['sync_links_update'] == 'always'?'upd_wp':'none');
						}
						else {
							// Leave plugin decide if update is required or not
							if ($this->links_db[$href]['link_updated'] != 0 &&
								$this->links_db[$href]['link_updated'] < $delicious_link_datetime)
								$action = 'upd_wp';
							else
								$action = 'none';
						}
						$this->links_db[$href]['action'] = $action;
					} // End of update mode

					// Build Delicious link information
					$this->links_db[$href]['link_name']        	 = $link['DESCRIPTION'];
					$this->links_db[$href]['link_description'] 	 = $link['EXTENDED'];
					$this->links_db[$href]['link_updated']	   	 = $delicious_link_datetime;
					$this->links_db[$href]['tags']			   	 = $link['TAG'];
					$bundles_list          						 = $this->get_bundles_from_tags($link['TAG']);
					$this->links_db[$href]['bundles']			 = array_unique($bundles_list);

					// Try to calculate categories from tags or bundles
					$this->links_db[$href]['suggested_category'] = $this->suggested_categories(	$link['TAG'],
										$bundles_list, $this->links_db[$href]['link_category']);
					$this->linksdb_index[$href] = array(
								'title' => $link['DESCRIPTION'],
								'date'  => $delicious_link_datetime);
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
					// Sort the index
					// asort($this->linksdb_index);

					// Save the result
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
			} /* End synchro ok */

		} // End of links_sync_build_list

		function sort_by_title_asc($a, $b) {
			if ($a['title'] == $b['title'])
				return 0;
			else
				return ($a['title'] < $b['title']) ? -1 : 1;
		} // End of sort_by_title_asc

		function sort_by_title_desc($a, $b) {
			if ($a['title'] == $b['title'])
				return 0;
			else
				return ($a['title'] > $b['title']) ? -1 : 1;
		} // End of sort_by_title_desc

		function sort_by_date_asc($a, $b) {
			if ($a['date'] == $b['date'])
				return 0;
			else
				return ($a['date'] < $b['date']) ? -1 : 1;
		} // End of sort_by_date_asc

		function sort_by_date_desc($a, $b) {
			if ($a['date'] == $b['date'])
				return 0;
			else
				return ($a['date'] > $b['date']) ? -1 : 1;
		} // End of sort_by_date_desc

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

			// Page Header: display a "number of page" selector, and a page navigation links
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

			$order_by = 'title';
			if ( isset($_GET['egdel_sync_order_by']) &&
				($_GET['egdel_sync_order_by'] == 'title' || $_GET['egdel_sync_order_by'] == 'date'))
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
			    '</form>'.
				'<form method="POST" action="'.$form_url.'?page=egdel_links_sync">'.
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
			if (!is_array($this->linksdb_index) || sizeof($this->linksdb_index)==0) {
				echo '<tr><td colspan="9">'.__('No links to display',$this->textdomain).'</td></tr>';
			}
			else {

				if ($order_by == 'title') {
					if ($order == 'ASC') uasort($this->linksdb_index, array(&$this, 'sort_by_title_asc'));
					else  uasort($this->linksdb_index, array(&$this, 'sort_by_title_desc'));
				}
				else {
					if ($order == 'ASC')  uasort($this->linksdb_index, array(&$this, 'sort_by_date_asc'));
					else  uasort($this->linksdb_index, array(&$this, 'sort_by_date_desc'));
				}

				// Divide page list into pages
				$pages_list  = array_chunk($this->linksdb_index , $links_per_page, true);
				$page_number = min($page_number, sizeof($pages_list));
				$index = ($page_number-1)*$links_per_page + 1;
				$current_page = $pages_list[$page_number-1];

				foreach ($current_page as $href => $last_update) {

					$attrs = $this->links_db[$href];
					$class_row = ($class_row == ''?'class="alternate"':'');
					// $class_row = ($attrs['suggested_category'] == EG_DELICIOUS_NOSYNC_ID?'class="error"':$class_row);
					echo '<input type="hidden" name="egdel_list['.$index.']" id="egdel_list['.$index.']" value="'.$href.'" />'.
						'<tr '.$class_row.'>'.
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

			if ($this->tags_list != FALSE && $this->bundles_list != FALSE) {
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
				if ($this->options['sync_cat_type'] == 'tag') {
					$sync_table = $this->options['tags_assignment'];
					$list       = $tags;
				}
				else {
					$sync_table = $this->options['bundles_assignment'];
					$list       = $bundles;
				}

				if ($mode == 'update') $categories_list = $existing_categories;
				else $categories_list = array();

				if (sizeof($list) == 0 || (sizeof($list)==1 && $list[0] == '')) {
					$categories_list[] = $this->options['sync_links_not_classified'];
				}
				else {
					foreach ($list as $item_name) {
						if ( isset($sync_table[$item_name]))
							$new_category = $sync_table[$item_name];
						else
							$new_category = $this->options['sync_links_other_item'];

						if ($new_category != EG_DELICIOUS_NOSYNC_ID)
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
		 * tags_sync_display_page
		 *
		 * Display tags synchronisation table
		 *
		 * @package EG-Delicious
		 *
		 * @param none
		 * @return none
		 */
		function tags_sync_display_page() {

			$mode = $this->options['sync_tags_type'];
			echo '<p>'.sprintf(__( 'Synchronisation mode: <strong>%s</strong>.' ,$this->textdomain), __($mode, $this->textdomain)).'<br /><em>'.__( $this->HELP['sync_tags_type_'.$mode], $this->textdomain).'</em></p>'.
				'<p>'.sprintf(__( 'You can modify this mode, by <a href="%s">editing options</a>', $this->textdomain),admin_url('options-general.php?page=egdel_options')).'</p>'.
				'<form method="POST" action="">'.
				wp_nonce_field('egdel_tags_sync').
				'<table class="wide widefat egdel_tag_sync">'.
			    '<thead><tr>'.
				'<th>#</th>'.
				'<th>'.__('WordPress', $this->textdomain).'</th>'.
				'<th>'.__('Action', $this->textdomain).'</th>'.
				'<th>'.__('Delicious', $this->textdomain).'</th>'.
				'<th>'.__('Count', $this->textdomain).'</th>'.
				'<th>'.__('Description', $this->textdomain).'</th>'.
				'</tr></thead><tbody>';

			if (!is_array($this->sync_tags) || sizeof($this->sync_tags)==0) {
				echo '<tr><td colspan="6">'.__('No tags to display',$this->textdomain).'</td></tr>';
			}
			else {
				$index = 1;
				foreach ($this->sync_tags as $attrs) {

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
				if (sizeof($terms_added_to_wp)>0) {
					echo '<h3>'.__( 'Tags added to WordPress:', $this->textdomain).'</h3>'.
						implode(', ', $terms_added_to_wp);
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

					if (! current_user_can($this->tags_min_user_rights)) $this->error_code = EG_DELICIOUS_ERROR_USER_RIGHT;
					elseif (! $this->is_user_defined()) $this->error_code = EG_DELICIOUS_ERROR_CONFIG;

					if ($this->error != EG_DELICIOUS_ERROR_NONE) {
						$this->display_error();
					}
					else {
						$this->tags_sync_build_list();
						if ($this->error == EG_DELICIOUS_ERROR_NONE) {
							$this->tags_sync_display_page();
						}
					} // No error
				} // End of check requirements
			}
			echo '</div>';
		} // End of tags_sync

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

			$this->error_code = EG_DELICIOUS_ERROR_NONE;

			// Getting Delicious tags
			$this->tags_list = $this->delicious_data->get_data('tags');
			if ($this->tags_list === FALSE) {
				$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
				$this->delicious_data->get_error($this->error_code, $this->error_msg);
				$this->error_details = 'Cannot get Delicious tags';
			}
			else {
				// Getting WordPress tags
				$results = get_terms('post_tag', array('hide_empty' => FALSE));

				// Synchronisation Phase 1: Build the list from the WordPress tags
				// if tags doesn't exist in the Delicious list => action = delete WordPress link
				foreach ($results as $tag) {
					$name = strtolower($tag->name);
					$this->sync_tags[$name] = array(
						'action'		=> 'none',
						'count' 		=> $tag->count,
						'description' 	=> $tag->description,
						'term_id'		=> $tag->term_id,
						'wp_name'		=> $tag->name,
						'del_name'      => ''
					);
					if (! isset($this->tags_list[$name]) && $this->options['sync_tags_type'] == 'replace')
						$this->sync_tags[$name]['action'] =  'del_wp';
				} // End of foreach phase 1

				// Synchronization Phase 2: Add all link existing in Delicious but not in WordPress
				foreach ($this->tags_list as $tag => $attrs) {
					$name = strtolower($tag);

					if (isset($this->sync_tags[$name])) {
						$this->sync_tags[$name]['del_name'] = $tag;
					}
					else {
						$this->sync_tags[$name] = array (
							'term_id'	=> 0,
							'del_name'  => $tag,
							'count'		=> $attrs['COUNT'],
							'action'	=> 'add_wp',
							'wp_name'	=> '');
					}
				} // End of foreach phase 2
			}
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
			list($date_part, $time_part)  = split(' ', $mysql_date);
			list($year, $month, $day)     = split('-', $date_part);
			list($hour, $minute, $second) = split(':', $time_part);
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
				$string = wp_html_excerpt ( $current_post->post_excerpt, $max_length);
			}
			else {
				// No excerpt, try to find the <!--more--> tag, or cut content.
				$char_count = min( $max_length, strpos($current_post->post_content, '<!--more-->')-1);

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

			$value = TRUE;
			if ($new_status == 'publish' && $old_status != 'publish') {
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
				if (sizeof($post_tags)>0) $tags_string = implode(',', $post_tags);
				else $tags_string = '';

				$dt = EG_Delicious_Core::timestamp_to_iso($this->mysql_to_unix_timestamp($post->post_date));

				$params = array( 'description' 	=> sanitize_title($post->post_title),
								 'extended'    	=> $this->get_post_description($post),
								 'dt' 			=> $dt,
								 'tags'			=> $tags_string,
								 'url'			=> get_permalink($post->ID),
								 'share'		=> ($this->options['publish_post_share']?'yes':'no'),
								 'replace'		=> 'no'
				);

				$error_code = EG_DELICIOUS_ERROR_NONE;
				$error_msg  = '';
				$value = $this->delicious_data->push_data('post_add', $params);
				if ($value !== TRUE) {
					$this->delicious_data->get_error($error_code, $error_msg);
					$this->options['error_code']   = EG_DELICIOUS_ERROR_NOPUBLISH;
					$this->options['error_detail'] = $error_msg;
					$this->save_options();
				}
			}
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
			if ($post_id != 0 && $this->is_user_defined()) {
				$post = get_post($post_id, OBJECT);
				if ($post && $post->post_type == 'post') {
					$permalink = get_permalink($post_id);
					$error_code = EG_DELICIOUS_ERROR_NONE;
					$error_msg  = '';
					$value = $this->delicious_data->push_data('post_del', array('url' => $permalink));
					if ($value !== TRUE) {
						$this->delicious_data->get_error($error_code, $error_msg);
						$this->options['error_code']   = EG_DELICIOUS_ERROR_CANTDEL;
						$this->options['error_detail'] = $error_msg;
						$this->save_options();
					}
				} // post exist
			}
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
			$this->posts_list = $this->delicious_data->get_data('posts');
			if ($this->posts_list === FALSE) {
				$this->error_code = EG_DELICIOUS_ERROR_DELQUERY;
				$this->delicious_data->get_error($this->error_code, $this->error_msg);
				$this->error_details =  'Cannot get Delicious posts';
			}
			else {
				echo '<p>'.__( 'Generating backup file', $this->textdomain).'</p>';
				$output = '<!doctype netscape-bookmark-file-1>'."\n".
						'<meta http-equiv="content-type" content="text/html; charset=UTF-8">'."\n".
						'<!-- This is an automatically generated file. '.
						'It will be read and overwritten. Do Not Edit! -->'.
						'<title>Bookmarks</title>'."\n".
						'<h1>Bookmarks</h1><dl><p>'."\n";

				foreach ($this->posts_list as $href => $link) {
					$delicious_link_datetime = $this->get_local_time($link['TIME']);
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
				if ( false !== $fd ) {
					fputs($fd, $output);
				}
				else {
					if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Backup Error '.$file_name);
				}
				@fclose($fd);

				if ($this->error_code != EG_DELICIOUS_ERROR_NONE) {
					echo '<p><span class="red">'.__( 'Backup failed', $this->textdomain).'</span></p>';
					$this->display_error();
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

			$backup_path  = $this->plugin_path.'backup/';
			$backup_url  = $this->plugin_url.'backup/';

			echo '<div id="icon-tools" class="icon32"><br></div>'.
				'<div class="wrap backup_delicious">'.
				'<h2>'.__('Delicious Backup', $this->textdomain).'</h2>';

			$this->error_code = EG_DELICIOUS_ERROR_NONE;

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

			$status                = TRUE;
			$backup_list           = array();
			$last_backup_date      = 0;
			$last_delicious_update = 0;

			echo '<h3>'.__('Checking configuration', $this->textdomain).'</h3><table width="90%"><tbody>';

			// Check if backup directory exists
			echo  '<tr><td>'.__('Checking backup folder', $this->textdomain).'<br />'.
					'<span class="path">('.$path.')</span> ... </td><td>';
			if (@is_dir(stripslashes($path))) {
				echo '<span class="green">'.__('Backup path exists', $this->textdomain).'</span></td></tr>';
			}
			else {
				echo '<span class="red">'.__('Backup path doesn\'t exist, try to create it', $this->textdomain).'</span></td></tr>';

				$result = @mkdir($backup_path);
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
					'<tr><td colspan="2">'.__($this->ERROR_MESSAGES[EG_DELICIOUS_ERROR_CONFIG], $this->textdomain).'</td></tr>';
				}
			}

			if ($status) {
				echo '<tr><td>'.__('Checking Delicious connection', $this->textdomain).' ... </td><td>';
				$this->last_delicious_update = $this->delicious_data->get_data('update');
				if ($this->last_delicious_update === FALSE) {
					$status = FALSE;
					echo '<span class="red">'.__('Connection failed.', $this->textdomain).'</span></td></tr>';
				}
				else {
					echo '<span class="green">'.__('Connection done.', $this->textdomain).'</span></td></tr>';
					$last_delicious_update = $this->get_local_time($this->last_delicious_update);
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

			echo '<form method="POST" action="" >'.
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
$eg_delicious_admin->set_stylesheets(FALSE, 'eg-delicious-admin.css') ;
if (function_exists('wp_remote_request'))
	$eg_delicious_admin->set_php_version('4.3', FALSE, 'curl');
else
	$eg_delicious_admin->set_php_version('4.3', 'allow_url_fopen');

$eg_delicious_admin->load();

?>