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
	$debug_info = debug_backtrace();
	$output = date('d-M-Y H:i:s').' - '.$debug_info[1]['function'].' - ';
	echo $output.$msg.'<br />';
} // End of eg_delicious_debug_info

if (! class_exists('EG_Delicious_Core')) {

	define('EG_DELICIOUS_CORE_ERROR_NONE',			0);
	define('EG_DELICIOUS_CORE_ERROR_UNKNOWN_QUERY',	1);
	define('EG_DELICIOUS_CORE_ERROR_READING',		2);
	// define('EG_DELICIOUS_CORE_ERROR_NO_DATA',		3);
	define('EG_DELICIOUS_CORE_ERROR_PARSING',		4);
	define('EG_DELICIOUS_CORE_ERROR_PUSH',			5);

	define('EG_DELICIOUS_PASSWORD_SECRET_KEY',		'EG-Delicious');
	/**
	 * Class EG_Delicious_Core
	 *
	 * Implement functions to get and operate Delicious data
	 *
	 * @package EG-Delicious
	 */
	Class EG_Delicious_Core {

		// public static $password_secret_key = 'EG-Delicious';

		var $cache_path;
		var $use_cache;

		var $username;
		var $password;

		var $error_code;
		var $error_msg;

		var $cache_group      = EG_DELICIOUS_CACHE_GROUP;
		var $cache_expiration = 900;

		var $parsed_data;

		var $ERROR_MESSAGES = array(
			EG_DELICIOUS_CORE_ERROR_NONE 			=> 'No error.',
			EG_DELICIOUS_CORE_ERROR_UNKNOWN_QUERY	=> 'Unknown query.',
			EG_DELICIOUS_CORE_ERROR_READING			=> 'Error while querying Delicious.',
			/* EG_DELICIOUS_CORE_ERROR_NO_DATA			=> 'No data available from Delicious.', */
			EG_DELICIOUS_CORE_ERROR_PARSING			=> 'Parse error.',
			EG_DELICIOUS_CORE_ERROR_PUSH			=> 'Error while adding post in Delicious'
		);

		var $DELICIOUS_QUERY = array(
			'posts' 		=> array(
				'type'		=> 'array',
				'parser'	=> 'parse_posts',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/all?meta=yes'
			),
			'bundles'   	=> array(
				'type'		=> 'array',
				'parser'	=> 'parse_bundles',
				'url'  		=> 'https://{username}:{password}@api.del.icio.us/v1/tags/bundles/all'
			),
			'tags'			=> array(
				'type'		=> 'array',
				'parser'	=> 'parse_tags',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/tags/get'
			),
			'update'		=> array(
				'type'		=> 'string',
				'parser'	=> 'parse_update',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/update'
			),
			'post_add'		=> array(
				'type'		=> 'array',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/add'
			),
			'post_del'		=> array(
				'type'		=> 'array',
				'url'		=> 'https://{username}:{password}@api.del.icio.us/v1/posts/delete'
			)
		);

		/**
		  * Class contructor for PHP 4 compatibility
		  *
		  * @package EG-Delicious
		  * @return object
		  *
		  */
		function EG_Delicious_Core($path, $username, $password, $is_singleton=FALSE) {

			register_shutdown_function(array(&$this, "__destruct"));
			$this->__construct($path, $username, $password, $is_singleton);
		} // End of EG_Delicious_Core

		/**
		  * Class contructor for PHP
		  *
		  * @package EG-Delicious
		  *
		  * @param	string	$path			Path for temp file
		  * @param	string	$username		Delicious username
		  * @param	string	$password		Delicious password
		  * @param	boolean	$is_singleton	Security flag (singleton pattern design)
		  * @return object
		  */
		function __construct($path, $username, $password, $is_singleton=FALSE) {
			$is_singleton || die('Cannot instantiate '.get_class($this).' class directly!\n');

			global $wp_object_cache;
			$this->use_cache  = ( $wp_object_cache->cache_enabled === TRUE ) ? TRUE : FALSE;
			$this->cache_path = $path;
			$this->username   = $username;
			$this->password   = $password;
		} // End of __construct

		/**
		 * __destruct
		 *
		 * PHP Destuctor
		 *
		 * @package EG-Delicious
		 *
		 * @param	none
		 * @return 	none
		 */
		function __destruct() {
			// Nothing for the moment
		} // End of __destruct

		/**
		 * get_instance
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$path			Path for temp file
		 * @param	string	$username		Delicious username
		 * @param	string	$password		Delicious password
		 * @return 	none
		 */
		function &get_instance($path, $username, $password) {
			static $eg_delicious_instance;

			if (!is_object($eg_delicious_instance))
				$eg_delicious_instance = new EG_Delicious_Core($path, $username, $password, TRUE);

			return $eg_delicious_instance;
		} /* End of get_instance */

		/**
		 * get_cache_file
		 *
		 * Build cache file name
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 * @return 	string				file name
		 */
		function get_cache_file($string) {
			// return trailingslashit($this->cache_path).md5($string, EG_DELICIOUS_SECRET_KEY);
			return trailingslashit($this->cache_path).'cache_'.$string.'.txt';
		} // End of get_cache_file

		/**
		 * cache_get
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 * @return 	array				cached data
		 */
		function cache_get($query) {

			// if WP cache is activated, use it
			if ($this->use_cache) {
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Use existing cache mecanism');
				return wp_cache_get($query, $this->cache_group);
			}
			else {
				// WP cache not activated, use home made cache
				$cache_file = $this->get_cache_file($query);
				if (! file_exists($cache_file)) {
					if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('File doesn\'t exists');
					return (FALSE);
				}
				else {
					$now = time();
					if ((filemtime($cache_file) + $this->cache_expiration) <= $now) {
						if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Cache expired.');
						$this->cache_del($query);
						return (FALSE);
					}
					else {
						if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Getting data from Cache');
						$data = unserialize(base64_decode(@ file_get_contents($cache_file)));
						return $data;
					}
				}
			}
		} // End of cache_get

		/**
		 * cache_set
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 * @param	mixed	$data		data to cache
		 *
		 * @return 	none
		 */
		function cache_set($query, $data) {

			// if WP cache is activated, use it
			if ($this->use_cache) {
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Use existing cache mecanism');
				wp_cache_set($query, $data, $this->cache_group, $this->cache_expiration);
			}
			else {
				// WP cache not activated, use home made cache
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Use home made cache mecanism');
				$cache_file = $this->get_cache_file($query);
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('cache file name is '.$cache_file);
				$string = base64_encode(serialize($data));
				$fd = @fopen($cache_file, 'w');
				if ( false !== $fd ) {
					fputs($fd, $string);
				}
				else {
					if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Write cache error '.$cache_file);
				}
				@fclose($fd);
			}
		} // End of cache_set

		/**
		 * cache_del
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 *
		 * @return 	none
		 */
		function cache_del($query) {

			// if WP cache is activated, use it
			if ($this->use_cache)
				wp_cache_delete($query, $this->cache_group);
			else {
				// WP cache not activated, use home made cache
				$cache_file = $this->get_cache_file($query);
				if (file_exists($cache_file)) {
					@unlink($cache_file);
				}
			}
		} // End of cache_del

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

			$result = FALSE;
			if (version_compare($wp_version, '2.8', '>=')) {
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('WP 2.8. Use wp_remote_request');
				$response = wp_remote_request($query, array('sslverify' => false));
				if (! is_wp_error($response)) {
					$result   = wp_remote_retrieve_body($response);
				}
				else {
					if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Error message: '.htmlentities($response->get_error_message()));
					$result = FALSE;
				}
			}
			else {
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('WP < 2.8. Use curl');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 			 $query);
				curl_setopt($ch, CURLOPT_FAILONERROR, 	 TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, 		 FALSE); 
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				$result = curl_exec($ch);
				if (curl_errno($ch)) $result = FALSE;
				curl_close($ch);
			}
			if ($result === FALSE) {
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Previous method doesn\'t work use file_get_contents');
				$result = @file_get_contents($query);
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('File_get_contents doesn\'t work also. Bad news!');
			}
			return ($result);
		} // End of http_request

		/**
		 * get_data
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 *
		 * @return 	array				data collected
		 */
		function get_data($query) {

			$this->error_code = EG_DELICIOUS_CORE_ERROR_NONE;

			if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Getting '.$query);

			if (!isset($this->DELICIOUS_QUERY[$query])) {
				// Query doesn't exist
				$this->error_code = EG_DELICIOUS_CORE_ERROR_UNKNOWN_QUERY;
			}
			else {
				// Get data from cache if exists
				$this->parsed_data = $this->cache_get($query);

				// Data exists ?
				if ($this->parsed_data === FALSE) {
					// No => Query Delicious

					// In debug mode, we use internal file, to avoid sending query to Delicious
					if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('No Data on cache, querying Delicious');
					if (EG_DELICIOUS_USE_LOCAL_DATA)
						$query_string = trailingslashit($this->cache_path).'debug/'.$query.'.txt';
					else {
						// Building query
						$query_string = str_replace('{username}', $this->username, $this->DELICIOUS_QUERY[$query]['url']);
						$query_string = str_replace('{password}', $this->password, $query_string);
					}
					// Read the file
					$xml_string = FALSE;
					$xml_string = $this->http_request($query_string);

					if (! isset($xml_string)     || $xml_string === FALSE ||
					    ! is_string($xml_string) || $xml_string == '') {
						$this->error_code = EG_DELICIOUS_CORE_ERROR_READING;
						$this->error_msg  = $this->ERROR_MESSAGES[$this->error_code];
					}
					else {
						// Parsing result
						$parsing_result = call_user_func(array(&$this, $this->DELICIOUS_QUERY[$query]['parser']), $xml_string);

						if ($parsing_result === FALSE) {
							$this->cache_del($query);
							$this->error_code = EG_DELICIOUS_CORE_ERROR_PARSING;
						}
						else {
							if ($this->DELICIOUS_QUERY[$query]['type'] == 'string') {
								$this->parsed_data = (string) $this->parsed_data;
							}
							else {
								if ( ! is_array($this->parsed_data) || sizeof($this->parsed_data) == 0 ) {
									if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Empty list for '.$query);
									$this->parsed_data = array();
									// $this->error_code = EG_DELICIOUS_CORE_ERROR_NO_DATA;
								}
							}
							$this->cache_set($query, $this->parsed_data);
						} // End of no error while parsing
					} // End of no error while reading

				} // End of no data in cache
			} // End of if QUERY exists

			if ($this->error_code == EG_DELICIOUS_CORE_ERROR_NONE) {
				return ($this->parsed_data);
			}
			else {
				$this->error_msg = $this->ERROR_MESSAGES[$this->error_code];
				return (FALSE);
			}
		} // End of get_data

		/**
		 * get_data
		 *
		 * @package EG-Delicious
		 *
		 * @param	string	$query		query id
		 * @param	array	$params		list of query parameters
		 * @return 	boolean				TRUE if no error occured, FALSE otherwise
		 */
		function push_data($query, $params = FALSE) {

			$this->error_code = EG_DELICIOUS_CORE_ERROR_NONE;

			if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Getting '.$query);

			if (!isset($this->DELICIOUS_QUERY[$query])) {
				// Query doesn't exist
				$this->error_code = EG_DELICIOUS_CORE_ERROR_UNKNOWN_QUERY;
			}
			else {
				$query_string = str_replace('{username}', $this->username, $this->DELICIOUS_QUERY[$query]['url']);
				$query_string = str_replace('{password}', $this->password, $query_string);

				$param_string = '';
				if ($params !== FALSE) {
					foreach ($params as $key => $value) {
						$param_string .= ($param_string==''?'?':'&').$key.'='.$value;
					}
				}
				$query_string = sanitize_url($query_string.$param_string);

				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Query: '.$query_string);
				// Read the file
				$xml_string = FALSE;
				$xml_string = $this->http_request($query_string);
				if ($xml_string === FALSE || strstr($xml_string, '<result code="done"') === FALSE) {
					$this->error_code = EG_DELICIOUS_CORE_ERROR_PUSH;
				}
				if (EG_DELICIOUS_DEBUG_MODE) eg_delicious_debug_info('Error code: '.$this->error_code);
			}
			return ($this->error_code == EG_DELICIOUS_CORE_ERROR_NONE);
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
				$attrs['TAG']  = split(' ', strtolower($attrs['TAG']));
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
		}

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
				$attrs['TAGS'] = split(' ', strtolower($attrs['TAGS']));
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
		 * parse_bundels
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
		 * get_error
		 *
		 * @package EG-Delicious
		 *
		 * @param	integer	$error_code		Error code
		 * @param	string	$error_msg		Error code
		 * @return 	none
		 */
		function get_error(& $error_code, & $error_msg) {
			$error_code = $this->error_code;
			$error_msg  = $this->error_msg;
		}

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
		}

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
		 * Password_decode
		 *
		 * @package EG-Delicious
		 *
		 * @param	string 	str		String to decrypt
		 * @return 	string			decrypted string
		 */
		function password_decode($str) {
		   //$filter = md5(self::$password_secret_key);
		   $filter = md5(EG_DELICIOUS_PASSWORD_SECRET_KEY);
		   $letter = -1;
		   $newstr = '';
		   $str = base64_decode($str);
		   $strlen = strlen($str);

		   for ( $i = 0; $i < $strlen; $i++ ) {
			  $letter++;
			  if ( $letter > 31 ) $letter = 0;
			  $neword = ord($str{$i}) - ord($filter{$letter});
			  if ( $neword < 1 ) $neword += 256;
			  $newstr .= chr($neword);
		   }
		   return $newstr;
		} // End of password_decode

		/**
		 * Password_encode
		 *
		 * @package EG-Delicious
		 *
		 * @param	string 	str		String to encrypt
		 * @return 	string			encrypted string
		 */
		function password_encode($str) {
		   // $filter = md5(self::$password_secret_key);
		   $filter = md5(EG_DELICIOUS_PASSWORD_SECRET_KEY);
		   $letter = -1;
		   $newpass = '';

		   $strlen = strlen($str);

		   for ( $i = 0; $i < $strlen; $i++ )
		   {
			  $letter++;
			  if ( $letter > 31 ) $letter = 0;
			  $neword = ord($str{$i}) + ord($filter{$letter});
			  if ( $neword > 255 ) $neword -= 256;
			  $newstr .= chr($neword);
		   }
		   return base64_encode($newstr);
		} // End of password_encode

	} // End of class EG_Delicious_Core

} // End of if class_exists

?>