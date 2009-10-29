<?php

/**
  * eg_delicious_debug_info
  *
  * @package EG-Delicious
  *
  * @param	string	$msg	Display a message with the date and the current function
  * @return none
  */
function eg_delicious_debug_info($msg) {
	if (EG_DELICIOUS_DEBUG_MODE) {
		$debug_info = debug_backtrace();
		$output = date('d-M-Y H:i:s').' - '.$debug_info[1]['function'].' - '.$debug_info[2]['function'].' - ';
		echo $output.$msg.'<br />';
	}
} // End of eg_delicious_debug_info

	// General parameters

	define('EG_DELICIOUS_LINKS_PER_PAGE',			25					);
	define('EG_DELICIOUS_NOSYNC_ID', 				'nosync'			);
	define('EG_DELICIOUS_NOSYNC_LABEL', 			''					);
	define('EG_DELICIOUS_UNBUNDLED',				'Unbundled tag'		);
	define('LINKS_MIN_USER_RIGHTS', 				'manage_links'		);
	define('TAGS_MIN_USER_RIGHTS',					'manage_categories'	);
	define('EG_DELICIOUS_PASSWORD_SECRET_KEY',		'EG-Delicious'		);
	define('EG_DELICIOUS_CACHE_GROUP',	 			'egdel'				);
	define('EG_DELICIOUS_CACHE_TIMEOUT',			900					);

	// Error code
	define('EG_DELICIOUS_ERROR_NONE',				 0);
	define('EG_DELICIOUS_ERROR_UNKNOWN_QUERY',		 1);
	define('EG_DELICIOUS_ERROR_PUSH',				 2);
	define('EG_DELICIOUS_ERROR_GET_WPLINK',			 3);
	define('EG_DELICIOUS_ERROR_GET_WPCAT',			 4);
	define('EG_DELICIOUS_ERROR_USER_RIGHT',			 5);
	define('EG_DELICIOUS_ERROR_CONFIG',				 6);
	define('EG_DELICIOUS_ERROR_DELQUERY',			 7);
	define('EG_DELICIOUS_ERROR_LISTCHG',			 8);
	define('EG_DELICIOUS_ERROR_NOTAG',				 9);
	define('EG_DELICIOUS_ERROR_NOBUNDLE',			10);
	define('EG_DELICIOUS_ERROR_BACKUP_PATH',		11);
	define('EG_DELICIOUS_ERROR_GET_TAGS',			12);
	define('EG_DELICIOUS_ERROR_GET_BUNDLES',		13);
	define('EG_DELICIOUS_ERROR_GET_POSTS',			14);
	define('EG_DELICIOUS_ERROR_ADD_POST',			15);
	define('EG_DELICIOUS_ERROR_DEL_POST',			16);
	define('EG_DELICIOUS_ERROR_ALREADY_STARTED',	17);
	define('EG_DELICIOUS_ERROR_NOTAG_NOBUNDLE',		18);
	define('EG_DELICIOUS_ERROR_TAG_BUNDLE_CHG',		19);
	define('EG_DELICIOUS_ERROR_BUNDLE_CHG',			20);
	define('EG_DELICIOUS_ERROR_TAG_CHG',			21);

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
		'sync_links_private'	     => 0,
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
		'publish_post_share'		 => 0,
		'schedule_frequency'		 => 'none',
		'schedule_daily_freq'	 	 => 'daily',
		'schedule_hourly_freq'	 	 => 'hourly',
		'schedule_daily_hour'	 	 => 7,
	);


$egdel_cache = new EG_Cache_100(EG_DELICIOUS_CACHE_GROUP, EG_DELICIOUS_CACHE_TIMEOUT, dirname(__FILE__).'/tmp');
$egdel_error = new EG_Error_100(EG_DELICIOUS_ERROR_NONE);
$egdel_error->add_error_list( array(
		EG_DELICIOUS_ERROR_NONE 				=> array(
				'msg' => 'No error.',
				'level' => 0 ),
		EG_DELICIOUS_ERROR_UNKNOWN_QUERY		=> array(
				'msg' => 'Unknown query.',
				'level' => 3 ),
		EG_DELICIOUS_ERROR_GET_TAGS				=> array(
				'msg' => 'Cannot get tags from Delicious',
				'level' => 3 ),
		EG_DELICIOUS_ERROR_GET_BUNDLES			=> array(
				'msg' => 'Cannot get bundles from Delicious',
				'level' => 3 ),
		EG_DELICIOUS_ERROR_GET_POSTS			=> array(
				'msg' => 'Cannot get posts from Delicious',
				'level' => 3 ),
		EG_DELICIOUS_ERROR_NOTAG				=> array(
				'msg' => 'No tag downloaded from Delicious. Switch to bundle mode.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_NOBUNDLE				=> array(
				'msg' => 'No bundle downloaded from Delicious. Switch to tag mode.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_ALREADY_STARTED		=> array(
				'msg' => 'A synchronization is started. You cannot modify options now. Please wait, and retry later.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_NOTAG_NOBUNDLE		=> array(
				'msg' => 'No tag and no bundle downloaded. You can\'t perform the configuration.',
				'level' => 3 ),
		EG_DELICIOUS_ERROR_TAG_BUNDLE_CHG		=> array(
				'msg' => 'Tags list and bundles list changed since the last synchronization. Please check configuration settings.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_BUNDLE_CHG			=> array(
				'msg' => 'Bundles list changed since the last synchronization. Please check configuration settings.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_TAG_CHG				=> array(
				'msg' => 'Tags list changed since the last synchronization. Please check configuration settings.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_GET_WPLINK			=> array(
				'msg' => 'Error while requesting WordPress links.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_GET_WPCAT			=> array(
				'msg' => 'Error while getting WordPress links categories.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_USER_RIGHT			=> array(
				'msg' => 'You cannot access to the page. You haven\'t the "Manage links" capability. Please contact the blog administrator.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_CONFIG				=> array(
				'msg' => 'Plugin not configured! Please go to <strong>Settings / EG-Delicious</strong> page to enter required parameters.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_DELQUERY				=> array(
				'msg' => 'Error while querying Delicious.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_LISTCHG				=> array(
				'msg' => 'Delicious <strong>Tags</strong> or <strong>Bundles</strong> changed since last options settings. A check is recommended.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_ADD_POST				=> array(
				'msg' => 'Cannot publish post in Delicious.',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_BACKUP_PATH 			=> array(
				'msg' => 'Cannot create backup path. Backup failed',
				'level' => 2 ),
		EG_DELICIOUS_ERROR_DEL_POST				=> array(
				'msg' => 'Cannot delete post in Delicious',
				'level' => 2 )
	)
);

if (! function_exists('eg_delicious_schedules')) {

	function eg_delicious_schedules() {
		return array(
				'hourly_2'  => array( 'interval' =>   7200, 'display' => 'Every 2 hours'  ),
				'hourly_4'  => array( 'interval' =>  14400, 'display' => 'Every 4 hours'  ),
				'hourly_8'  => array( 'interval' =>  28800, 'display' => 'Every 8 hours'  ),
				'hourly_16' => array( 'interval' =>  57600, 'display' => 'Every 16 hours' ),
				'daily_2'   => array( 'interval' => 172800, 'display' => 'Every 2 days'   ),
				'daily_4'   => array( 'interval' => 345600, 'display' => 'Every 4 days'   ),
				'weekly'    => array( 'interval' => 604800, 'display' => 'Weekly'         )
			);
	} // End of eg_delicious_schedules

} // End of function_exists

if (! function_exists('egdel_links_sync_change_date')) {

	/**
	 * egdel_links_sync_change_date
	 *
	 * Change 'link_updated' field of the wp_link table
	 *
	 * @package EG-Delicious
	 *
	 * @param 	int		$link_id		id of the link to update
	 * @param	int		$timestamp		unix timestamp
	 * @return 	boolean					TRUE if all is OK, FALSE otherwise
	 */
	function egdel_links_sync_change_date($link_id, $timestamp=FALSE) {
		global $wpdb;

		if ($timestamp === FALSE)
			$query = $wpdb->prepare('UPDATE '.$wpdb->links.' SET link_updated=NOW() WHERE link_id=%d',$link_id );
		else
			$query = $wpdb->prepare('UPDATE '.$wpdb->links.' SET link_updated=FROM_UNIXTIME(%s) WHERE link_id=%d',$timestamp, $link_id );

		if ( false === $wpdb->query($query) ) {
			if ( $wp_error )
				return new WP_Error( 'db_update_error', __( 'Could not update link in the database' ), $wpdb->last_error );
			else
				return 0;
		} // End of if query
	} // End of egdel_links_sync_change_date
}

if (! function_exists('egdel_save_options')) {

	/**
	 * egdel_save_options
	 *
	 * Update options, including password
	 *
	 * @package EG-Delicious
	 *
	 * @param none
	 * @return none
	 */
	function egdel_save_options($options_entry, $options) {
		$options['password'] = eg_password_encode($options['password'], EG_DELICIOUS_PASSWORD_SECRET_KEY);
		update_option($options_entry, $options);
	} // End of egdel_save_options

} // End of if not function_exists

if (! function_exists('egdel_load_options')) {
	/**
	 * egdel_load_options
	 *
	 * Load options, and decrypt password
	 *
	 * @package EG-Delicious
	 *
	 * @param none
	 * @return none
	 */
	function egdel_load_options($options) {
		if (isset($options['password']) && $options['password']!='') {
			$options['password'] = eg_password_decode($options['password'], EG_DELICIOUS_PASSWORD_SECRET_KEY);
		}
		return ($options);
	} // End of egdel_load_options
} // End of if not function_exists


if (! class_exists('EG_Delicious_Core')) {

	/**
	 * Class EG_Delicious_Core
	 *
	 * Implement functions to get and operate Delicious data
	 *
	 * @package EG-Delicious
	 */
	Class EG_Delicious_Core {

		var $options;
		var $options_entry;
		var $parsed_data;
		var $bundles_tags_assoc = FALSE;
		var $timezone_offset    = FALSE;
		var $textdomain         = '';

		var $DELICIOUS_QUERY = array(
			'posts' 		=> array(
				'type'		=> 'array',
				'parser'	=> 'parse_posts',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/all?meta=yes',
				'error'     => EG_DELICIOUS_ERROR_GET_POSTS
			),
			'bundles'   	=> array(
				'type'		=> 'array',
				'parser'	=> 'parse_bundles',
				'url'  		=> 'https://{username}:{password}@api.del.icio.us/v1/tags/bundles/all',
				'error'     => EG_DELICIOUS_ERROR_GET_BUNDLES
			),
			'tags'			=> array(
				'type'		=> 'array',
				'parser'	=> 'parse_tags',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/tags/get',
				'error'     => EG_DELICIOUS_ERROR_GET_TAGS
			),
			'update'		=> array(
				'type'		=> 'string',
				'parser'	=> 'parse_update',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/update',
				'error'     => EG_DELICIOUS_ERROR_GET_DATE
			),
			'post_add'		=> array(
				'type'		=> 'array',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/add',
				'error'     => EG_DELICIOUS_ERROR_ADD_POST
			),
			'post_del'		=> array(
				'type'		=> 'array',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/delete',
				'error'     => EG_DELICIOUS_ERROR_DEL_POST
			)
		);

		/**
		  * Class contructor for PHP 4 compatibility
		  *
		  * @package EG-Delicious
		  *
		  * @param 	array	$options	delicious account & synchronization parameters
		  *
		  * @return object
		  *
		  */
		function EG_Delicious_Core($options_entry, $options, $textdomain) {

			$this->__construct($options_entry, $options, $textdomain);
		} // End of EG_Delicious_Core

		/**
		  * Class contructor for PHP
		  *
		  * @package EG-Delicious
		  *
		  * @param 	array	$options	delicious account & synchronization parameters
		  *
		  * @return object
		  */
		function __construct($options_entry, $options, $textdomain) {
			$this->options_entry = $options_entry;
			$this->options       = $options;
			$this->textdomain    = $textdomain;
		} // End of __construct

		/**
		  * set_user
		  *
		  * @package EG-Delicious
		  *
		  * @param 	string	$username	name of the delicious user
		  * @param 	string	$password	password of the delicious user
		  *
		  * @return object
		  */
		function set_user($username, $password) {
			$this->options['username'] = $username;
			$this->options['password'] = $password;
		} // End of set_user

		/**
		 * http_request
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query to send to Delicious
		 *
		 * @return 	mixed				object or array: result of the query
		 */
		function http_request($query) {
			global $wp_version;
			global $wp_header_to_desc;
			global $egdel_error;

			$result = FALSE;
			if (version_compare($wp_version, '2.8', '>=')) {
				eg_delicious_debug_info('WP 2.8. Use wp_remote_request');
				$response           = wp_remote_request($query, array('sslverify' => false));
				$request_error_code = wp_remote_retrieve_response_code($response);
				if (! is_wp_error($response) &&  $request_error_code == 200) {
					$result = wp_remote_retrieve_body($response);
				}
				else {
					if (isset($wp_header_to_desc[$request_error_code]))
						$egdel_error->set_details('Http error code : '.$wp_header_to_desc[absint($request_error_code)]);
					else
						$egdel_error->set_details('Http error code : unknown');

					eg_delicious_debug_info('Error message: '.
								htmlentities(wp_remote_retrieve_response_message($response)));
					$result = FALSE;
				}
			} // End of version 2.8 and upper
			else {
				eg_delicious_debug_info('WP < 2.8. Use curl');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 			 $query);
				curl_setopt($ch, CURLOPT_FAILONERROR, 	 TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, 		 FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				$result = curl_exec($ch);
				if (curl_errno($ch)) {
					$result = FALSE;
					$egdel_error->set_details(curl_error($ch));
				}
				curl_close($ch);
			}
			if ($result === FALSE) {
				eg_delicious_debug_info('Previous method doesn\'t work use file_get_contents');
				$result = @file_get_contents($query);
				if (!$result) {
					$egdel_error->set_details('Error during querying Delicious');
					eg_delicious_debug_info('File_get_contents doesn\'t work also. Bad news!');
				}
			}
			return ($result);
		} // End of http_request

		/**
		 * Build_query
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 * @param	array   $params		query parameters. Default value = FALSE
		 *
		 * @return 	string				sanitized query
		 */
		function build_query($query, $params=FALSE) {

			$param_string = '';
			if ($params !== FALSE) {
				foreach ($params as $key => $value) {
					$param_string .= ($param_string==''?'?':'&').$key.'='.$value;
				}
			}

			if (EG_DELICIOUS_USE_LOCAL_DATA) {
				$query_string = dirname(__FILE__).'/tmp/debug/'.$query.'.txt';
			} else {
				// Building query
				$query_string  = str_replace('{username}', $this->options['username'], $this->DELICIOUS_QUERY[$query]['url']);
				$query_string  = str_replace('{password}', $this->options['password'], $query_string);
				$query_string .= $param_string;
			}
			return ($query_string);
		} // End of build_query

		/**
		 * get_data
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 *
		 * @return 	array				data collected
		 */
		function get_data($query, $params=FALSE) {
			global $egdel_error;
			global $egdel_cache;

			$egdel_error->clear();
			$this->parsed_data = FALSE;

			eg_delicious_debug_info('Getting '.$query);

			if (!isset($this->DELICIOUS_QUERY[$query])) {
				// Query doesn't exist
				$egdel_error->set(EG_DELICIOUS_ERROR_UNKNOWN_QUERY);
			}
			else {
				// Building Query
				$query_string = $this->build_query($query, $params);
				eg_delicious_debug_info('Query: '.$query.', URL: '.$query_string);

				// Get data from cache
				eg_delicious_debug_info('Trying to get data from cache');

				// Building cache entry
				$cache_entry = $query.sanitize_file_name($params===FALSE ? '' : '_'.implode('_', array_values($params)));
				eg_delicious_debug_info('Cache entry: '.$cache_entry);
				$this->parsed_data = $egdel_cache->get($cache_entry);

				// No data in cache, query Delicious
				if ($this->parsed_data == FALSE) {
					eg_delicious_debug_info('No data in cache, querying');
					// Read the file
					$xml_string = $this->http_request($query_string);

					if ( $xml_string !== FALSE && $xml_string != '') {

						// Parsing result
						$parsing_result = call_user_func(array(&$this, $this->DELICIOUS_QUERY[$query]['parser']), $xml_string);

						if ($parsing_result === FALSE) {
							$egdel_error->set_details('Parsing error');
						}
						else {
							switch ($this->DELICIOUS_QUERY[$query]['type']) {
								case 'string':
									$this->parsed_data = (string) $this->parsed_data;
								break;

								case 'array':
									if ( ! is_array($this->parsed_data) || sizeof($this->parsed_data) == 0 ) {
										$this->parsed_data = array();
									}
								break;
							} // End of switch
						} // End of no error while parsing
					} // End of parsing block

					if ($this->parsed_data === FALSE) $egdel_error->set($this->DELICIOUS_QUERY[$query]['error']);
					else $egdel_cache->set($cache_entry, $this->parsed_data);

				} // End of no data in cache
			} // End of if QUERY exists
			return ($this->parsed_data);
		} // End of get_data

		/**
		 * push_data
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 * @param	array	$params		list of query parameters
		 * @return 	boolean				TRUE if no error occured, FALSE otherwise
		 */
		function push_data($query, $params = FALSE) {
			global $egdel_error;

			$egdel_error->clear();
			eg_delicious_debug_info('Getting '.$query);

			if (!isset($this->DELICIOUS_QUERY[$query])) {
				// Query doesn't exist
				$egdel_error->set(EG_DELICIOUS_ERROR_UNKNOWN_QUERY);
			}
			else {
				$query_string = $this->build_query($query, $params);

				eg_delicious_debug_info('Query: '.$query_string);
				// Read the file
				$xml_string = FALSE;
				$xml_string = $this->http_request($query_string);
				if ($xml_string === FALSE) {
					$egdel_error->set($this->DELICIOUS_QUERY[$query]['error']);
				}
				else {
					if (strstr($xml_string, '<result code="done"') === FALSE) {
						$egdel_error->set($this->DELICIOUS_QUERY[$query]['error']);
						ereg('result code="([^"]+)', $xml_string, $results);
						$egdel_error->set_details( __('Delicious message: ', $this->textdomain).$results[1]);
					}
				}
				eg_delicious_debug_info('Error code: '.$egdel_error->code);
			} // End of Query exists
			return ( ! $egdel_error->is_error() );
		} // End of push_data

		/**
		 * parse_update
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$xml_string		content of xml file
		 * @return 	array					list of posts
		 */
		function parse_update($xml_string) {
			$findme = 'update time="';
			$extract_string = 'YYYMMDDTHH:MM:SSZ';
			$index = strpos($xml_string, $findme);
			if ($index === FALSE) {
				return (FALSE);
			}
			else {
				$data = $this->iso_to_timestamp(substr($xml_string, $index + strlen($findme), strlen($extract_string)+1));
				$this->parsed_data = $data;
				return (TRUE);
			}
		} // End of parse_update

		/**
		 * start_posts_element_parser
		 *
		 * @package EG-Delicious
		 *
		 * @param	object	$parser
		 * @param	string	$name		xml item name
		 * @param	array	$attrs		attibutes of the xml item
		 * @return 	none
		 */
		function start_posts_element_parser($parser, $name, $attrs) {

			if (strcasecmp($name, 'POST')===0) {
				$href = html_entity_decode($attrs['HREF']);
				unset($attrs['HREF']);
				$attrs['TAG']  = explode(' ', strtolower($attrs['TAG']));
				$attrs['TIME'] = $this->iso_to_timestamp($attrs['TIME']);
				$this->parsed_data[$href] = $attrs;
			}
		} // End of function start_posts_element_parser

		/**
		 * end_posts_element_parser
		 *
		 * @package EG-Delicious
		 *
		 * @param	object	$parser
		 * @param	string	$name		xml item name
		 * @return 	none
		 */
		function end_posts_element_parser($parser, $name) {
			// Nothing
		} // End of end_posts_element_parser

		/**
		 * parse_posts
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$xml_string		content of xml file
		 * @return 	array					list of posts
		 */
		function parse_posts($xml_string) {
			$this->parsed_data = FALSE;
			$xml_parser = xml_parser_create();
			xml_set_element_handler($xml_parser, array(&$this, 'start_posts_element_parser'), array(&$this, 'end_posts_element_parser') );
			$returned_code = xml_parse($xml_parser, $xml_string, TRUE);
			xml_parser_free($xml_parser);

			return ($returned_code);
		} // End of parse_posts

		/**
		 * start_tags_element_parser
		 *
		 * @package EG-Delicious
		 *
		 * @param	object	$parser
		 * @param	string	$name		xml item name
		 * @param	array	$attrs		attibutes of the xml item
		 * @return 	none
		 */
		function start_tags_element_parser($parser, $name, $attrs) {

			if (strcasecmp($name, 'TAG')===0) {
				$tag = strtolower($attrs['TAG']);
				unset($attrs['TAG']);
				$this->parsed_data[$tag] = $attrs;
			}
		} // End of function start_tags_element_parser

		/**
		 * end_tags_element_parser
		 *
		 * @package EG-Delicious
		 *
		 * @param	object	$parser
		 * @param	string	$name		xml item name
		 * @return 	none
		 */
		function end_tags_element_parser($parser, $name) {
			// Nothing
		} // End of end_tags_element_parser

		/**
		 * parse_tags
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$xml_string		content of xml file
		 * @return 	array					list of posts
		 */
		function parse_tags($xml_string) {
			$this->parsed_data = FALSE;
			$xml_parser = xml_parser_create();
			xml_set_element_handler($xml_parser, array(&$this, 'start_tags_element_parser'), array(&$this, 'end_tags_element_parser') );
			$returned_code = xml_parse($xml_parser, $xml_string, TRUE);
			xml_parser_free($xml_parser);

			return ($returned_code);
		} // End of parse_tags

		/**
		 * start_bundles_element_parser
		 *
		 * @package EG-Delicious
		 *
		 * @param	object	$parser
		 * @param	string	$name		xml item name
		 * @param	array	$attrs		attibutes of the xml item
		 * @return 	none
		 */
		function start_bundles_element_parser($parser, $name, $attrs) {
			if ( strcasecmp($name, 'BUNDLE') === 0 ) {
				$bundle = strtolower($attrs['NAME']);
				unset($attrs['NAME']);
				$attrs['TAGS'] = explode(' ', strtolower($attrs['TAGS']));
				$this->parsed_data[$bundle] = $attrs;
			}
		} // End of function start_bundles_element_parser

		/**
		 * End_bundles_element_parser
		 *
		 * @package EG-Delicious
		 *
		 * @param	object	$parser
		 * @param	string	$name		xml item name
		 * @return 	none
		 */
		function end_bundles_element_parser($parser, $name) {
			// Nothing
		} // End of end_bundles_element_parser

		/**
		 * parse_bundles
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$xml_string		content of xml file
		 * @return 	array					list of posts
		 */
		function parse_bundles($xml_string) {
			$this->parsed_data = FALSE;
			$xml_parser = xml_parser_create();
			xml_set_element_handler($xml_parser, array(&$this, 'start_bundles_element_parser'), array(&$this, 'end_bundles_element_parser') );
			$returned_code = xml_parse($xml_parser, $xml_string, TRUE);
			xml_parser_free($xml_parser);

			return ($returned_code);
		} // End of parse_bundles

		/**
		 * iso_to_timestamp
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$iso		date in ISO format
		 * @return 	integer				unix timestamp
		 */
		function iso_to_timestamp($iso) {
			sscanf($iso,"%4u-%u-%uT%u:%2u:%2uZ", $year, $month, $day, $hour, $minute, $second);
			return (mktime($hour, $minute, $second, $month, $day, $year ));
		} // End of iso_to_timestamp

		/**
		 * timestamp_to_iso
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$timestamp		unix timestamp
		 * @return 	string					Date in the ISO format
		 */
		function timestamp_to_iso($timestamp) {
			return (date('Y-m-d\TH:i:s\Z', $timestamp));
		} // End of timestamp_to_iso

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
			global $egdel_cache;

			if ($this->timezone_offset === FALSE) {
				if (function_exists('wp_timezone_override_offset')) 
					$this->timezone_offset = wp_timezone_override_offset() * 3600;
				else
					$this->timezone_offset = get_option('gmt_offset') * 3600;
			} // End of Timezoneoffset not defined
			return ($utc_time + $this->timezone_offset);
		} // End of get_local_time

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
			global $egdel_error;

			// Cache is managed inside get_terms
			$results = get_terms('link_category', array('hide_empty' => FALSE));
			if ($results) {
				foreach ($results as $result) {
					$link_categories[$result->term_id] = $result->name;
				}
				if ($add_nosync)
					return ( array(EG_DELICIOUS_NOSYNC_ID => EG_DELICIOUS_NOSYNC_LABEL) + $link_categories );
				else
					return ($link_categories);
			}
			else {
				$egdel_error->set(EG_DELICIOUS_ERROR_GET_WP_CATEGORIES);
				return FALSE;
			}
		} // End of get_wp_links_categories

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
		function tags_to_bundle(& $tags_list, $bundles_list) {
			global $egdel_cache;

			if ($this->bundles_tags_assoc === FALSE) {

				if ($tags_list != FALSE && $bundles_list != FALSE) {
					foreach ($bundles_list as $bundle => $bundle_attrs) {
						foreach ($bundle_attrs['TAGS'] as $tag) {
							$tags_list[$tag]['bundles'][] = $bundle;
						}
					}
					$this->bundles_tags_assoc = TRUE;
					$egdel_cache->replace('tags', $tags_list);
				}
			} // Tags <-> bundles association not already done
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
		function get_bundles_from_tags($tags, & $tags_list, $bundles_list) {

			$this->tags_to_bundle($tags_list, $bundles_list);

			$bundles = array();
			foreach ($tags as $tag) {
				if (isset($tags_list[$tag]['bundles'])) {
					$temp = $bundles;
					$bundles = array_merge($temp, $tags_list[$tag]['bundles']);
				}
				else {
					$bundles[] = __(EG_DELICIOUS_UNBUNDLED, $this->textdomain);
				}
			} // End of foreach
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

					if (sizeof($categories_list)>1) {
						if ($this->options['sync_cat_multi'] == 'single')  {
							$pareto_table = array_count_values($categories_list);
							arsort($pareto_table);
							$categories_list = array( key($pareto_table ) );
						}
						else {
							$categories_list = array_unique($categories_list);
						}
					} // End of 2 or more categories
				} // End of list not empty
			} // End of $mode != none
			return ($categories_list);
		} // End of suggested_categories

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
			global $egdel_error;
			global $egdel_cache;

			eg_delicious_debug_info('Mode : '.($update_sync===TRUE?'Sync':'Full') );
			$egdel_error->clear();
			$sync_wp_cleanup  = ($this->options['sync_links_wp_del'] && !$update_sync);

			// Get the date since last update
			$params = FALSE;
			if ($update_sync) {
				$update_sync    = $this->get_local_time($this->options['last_sync_date']);
				$params         = array( 'dt' => $this->timestamp_to_iso($update_sync) );
				$sql_where_date = '';
			}

			// Get all links from Delicious
			// TODO: add parameter or array of parameters for the request
			$posts_list = $this->get_data('posts', $params);
			if ($posts_list !== FALSE) {
				$tags_list = $this->get_data('tags');
				if ($tags_list !== FALSE)
					$bundles_list = $this->get_data('bundles');
			}

			if ( $posts_list!==FALSE && $tags_list!==FALSE && $bundles_list!==FALSE ) {

				// $this->check_bundles_tags_modification(TRUE, FALSE);

				// We have Delicious links.
				// Prepare WordPress link List: default WordPress functions don't give link_categories => use SQL query
				$wp_links_list = wp_cache_get('wp_links_list', EG_DELICIOUS_CACHE_GROUP);
				eg_delicious_debug_info('Get WordPress links');
				if ($wp_links_list === FALSE) {
					eg_delicious_debug_info('No data in cache. Querying database');
					$query = 'SELECT lin.link_id, lin.link_name, lin.link_url, UNIX_TIMESTAMP(lin.link_updated) as link_updated, lin.link_description, tax.term_id as link_category FROM '.$wpdb->links.' AS lin, '.$wpdb->term_relationships.' AS rel, '.$wpdb->term_taxonomy.' AS tax WHERE tax.taxonomy = "link_category" AND lin.link_id = rel.object_id AND rel.term_taxonomy_id = tax.term_taxonomy_id order by lin.link_id';

					$wp_links_list = $wpdb->get_results($query);
					if ($wp_links_list === FALSE)
						$egdel_error->set(EG_DELICIOUS_ERROR_GET_WPLINK);
					else
						wp_cache_set('wp_links_list', $wp_links_list, EG_DELICIOUS_CACHE_GROUP);
				}
				eg_delicious_debug_info('WordPress links: '.sizeof($wp_links_list).' found.');

				eg_delicious_debug_info('Getting WP links categories... ');
				$wp_link_categories = $this->get_wp_links_categories();
				eg_delicious_debug_info('WP links categories: '.sizeof($wp_link_categories).' found.');

				if ($wp_links_list !== FALSE && isset($wp_link_categories)) {

					// Phase 1: Formatting WordPress links list
					$previous_link_id = -1;
					foreach ($wp_links_list as $link) {
						if ($previous_link_id == $link->link_id) {
							$links_db[$href]['link_category'][]      = $link->link_category;
							$links_db[$href]['suggested_category'][] = $link->link_category;
							$links_db[$href]['link_cat_names']      .= ', '.$wp_link_categories[$link->link_category];
						}
						else {
							$href = html_entity_decode($link->link_url);

							$links_db[$href] = array(
									'action'		    => ($sync_wp_cleanup?'del_wp':'none'),
									'link_id' 	    	=> $link->link_id,
									'link_url'		    => html_entity_decode($href),
									'link_name'		    => $link->link_name,
									'link_description'  => $link->link_description,
									'link_updated'		=> $link->link_updated,
									'link_category'     => array($link->link_category),
									'suggested_category'=> array($link->link_category),
									'link_cat_names'    => $wp_link_categories[$link->link_category]
								);
							$linksdb_index[$href] = array(
											'title' => $link->link_name,
											'date'  => $link->link_updated);
						}
						$previous_link_id = $link->link_id;
					} // end Foreach WP link

					unset($wp_links_list);
					eg_delicious_debug_info('Phase 1: '.sizeof($links_db).' links from WP');
				} // End of Get WP links Ok.

				// Phase 2: Check if Delicious links exists in WordPress list or not
				foreach ($posts_list as $href => $link) {

					$delicious_link_datetime = $this->get_local_time($link['TIME']);
					// link exists in wordpress database?
					if (! isset($links_db[$href])) {
						// No, action = add
						$links_db[$href] = array(
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
							if ($links_db[$href]['link_updated'] != 0 &&
								$links_db[$href]['link_updated'] < $delicious_link_datetime)
								$action = 'upd_wp';
							else
								$action = 'none';
						}
						$links_db[$href]['action'] = $action;
					} // End of update mode

					// Build Delicious link information
					$links_db[$href]['link_name']        = $link['DESCRIPTION'];
					$links_db[$href]['link_description'] = $link['EXTENDED'];
					$links_db[$href]['link_updated']	 = $delicious_link_datetime;
					$links_db[$href]['tags']			 = $link['TAG'];
					$build_bundles_list  				 = $this->get_bundles_from_tags($link['TAG'], $tags_list, $bundles_list);
					$links_db[$href]['bundles']		     = array_unique($build_bundles_list);

					// Try to calculate categories from tags or bundles
					$links_db[$href]['suggested_category'] = $this->suggested_categories( $link['TAG'],
										$build_bundles_list, $links_db[$href]['link_category']);
					$linksdb_index[$href] = array(
								'title' => $link['DESCRIPTION'],
								'date'  => $delicious_link_datetime);
				} // End foreach delicious link

				// Clean links with no action to do
				// if ($sync_wp_cleanup) {
				if ($update_sync !== FALSE) {
					foreach ($links_db as $href => $attrs) {
						if ($attrs['action'] == 'none') {
							unset($links_db[$href]);
							unset($linksdb_index[$href]);
						}
					}
				}

				if (sizeof($links_db) == 0)
					$egdel_error->set(EG_DELICIOUS_SYNC_EMPTY_LIST);
				else {
					$egdel_cache->set('links_db',      $links_db,      'default', 0);
					$egdel_cache->set('linksdb_index', $linksdb_index, 'default', 0);
				}
			} /* End synchro ok */
		} // End of links_sync_build_list

		/**
		 * links_sync_action
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param 	array	$sync_list		List of links to synchronize
		 * @param	array	$linkdb_index   Index of links
		 * @param	string	$where			Schedule or admin
		 * @param	array	$logs			Actions done.
		 * @return 	string					Status (
		 */
		function links_sync_action($sync_list, $links_db, & $linksdb_index, $where) {
			global $egdel_cache;
			
			// if (is_admin()) {
				remove_action('edit_link', 'egdel_links_sync_change_date');
				remove_action('add_link',  'egdel_links_sync_change_date');
			// }

			$logs = array();
			$change_occured = FALSE;
			// Foreach link
			foreach ($sync_list as $index => $item) {
				$href       = $item['link_url'];
				$action     = $item['action'];
				$link 		= $links_db[$href];
				$categories = $link['suggested_category'];
				if (!isset($categories))
					$categories = array();
				else {
					if (! is_array($categories) && $categories != '')
						$categories = array($categories);
				}
				switch ($action) {

					case 'del_wp':
						wp_delete_link($link['link_id']);
						$logs[] = array( 'type' => 'info', 'action' => $action, 'msg' => $href);
						$change_occured = TRUE;
					break;

					case 'add_wp':
					case 'upd_wp':
						if (sizeof($categories)>0 && $categories != array(EG_DELICIOUS_NOSYNC_ID)) {
							$link['link_category'] = $categories;
							$link_id = wp_insert_link($link);
							egdel_links_sync_change_date($link_id, $link['link_updated']);
							$change_occured = TRUE;
							$logs[] = array( 'date' => date('d/M/Y H:i:s'), 'type' => 'info', 'action' => $action, 'msg' => $href);
						}
					break;
				} // End of switch
				unset($linksdb_index[$href]);
			} // End of foreach

			if ($change_occured) {
				// WordPress Blogroll changed,  so clear cache
				wp_cache_delete('wp_links_list', EG_DELICIOUS_CACHE_GROUP);
			}
			// Check if there is still some links
			if (sizeof($linksdb_index) > 0) {
				$egdel_cache->set('linksdb_index', $linksdb_index, 'default', 0);
			}
			else {
				$egdel_cache->delete('links_db');
				$egdel_cache->delete('linksdb_index');
			}
			return ($logs);
		} // End of links_sync_action

	} // End of class EG_Delicious_Core

} // End of if class_exists

?>