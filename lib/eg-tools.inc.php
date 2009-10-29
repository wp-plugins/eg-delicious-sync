<?php
/*
Plugin Name: EG-Tools
Plugin URI:
Description: Class for WordPress plugins
Version: 1.0.0
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


/**
 * Password_decode
 *
 * @package EG-tools
 *
 * @param	string 	str		String to decrypt
 * @return 	string			decrypted string
 */
function eg_password_decode($str, $secret_key='secret key') {

   $filter = md5($secret_key);
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
 * @package EG-tools
 *
 * @param	string 	str		String to encrypt
 * @return 	string			encrypted string
 */
function eg_password_encode($str, $secret_key='secret key') {

   $filter = md5($secret_key);
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


if (! class_exists('EG_Cache_100')) {

	define('EG_CACHE_DEFAULT_PATH',    '' );
	define('EG_CACHE_DEFAULT_TIMEOUT', 900);
	define('EG_CACHE_DEFAULT_FLAG',    '' );
	define('EG_CACHE_ENTRY',   		   'EG-cache-index');

	/**
	 * Object Cache
	 *
	 * @package EG-Plugin
	 * @subpackage Cache
	 * @since 1.0
	 */
	class EG_Cache_100 {

		var $cache = array ();
		var $index;
		var $cache_enabled;
		var $cache_path;
		var $default_expire;
		var $default_flag;

		/**
		 * PHP4 constructor;
		 *
		 * @package EG-Plugin
		 *
		 * @return EG_Cache
		 */
		function EG_Cache_100($flag=EG_CACHE_DEFAULT_FLAG, $expire=EG_CACHE_DEFAULT_TIMEOUT, $path=EG_CACHE_DEFAULT_PATH ) {
			return $this->__construct($flag, $expire, $path);
		} // End of EG_Cache

		/**
		 * PHP 5 style constructor
		 *
		 * @package EG-Plugin
		 *
		 * @return null|EG_Cache If cache is disabled, returns null.
		 */
		function __construct($flag=EG_CACHE_DEFAULT_FLAG, $expire=EG_CACHE_DEFAULT_TIMEOUT, $path=EG_CACHE_DEFAULT_PATH ) {

			// register_shutdown_function(array(&$this, '__destruct'));

			$this->cache_path     = trailingslashit($path);
			$this->default_expire = $expire;
			$this->default_flag   = $flag;

			// Define persistent cache method: object-cache fif active, transient if WP 2.8, home made in other cases.
			if ( file_exists(WP_CONTENT_DIR . '/object-cache.php') )
				$this->cache_enabled = 'WP';
			elseif (function_exists('get_transient'))
				$this->cache_enabled = 'transient';
			elseif ($path!='' && $expire!=0 && $flag!='')
				$this->cache_enabled  = 'EG';

			switch ($this->cache_enabled) {

				case 'WP':
					// Nothing
				break;

				case 'transient':
					// Nothing
				break;

				case 'EG':
					if (! is_dir($this->cache_path)) {
						@mkdir($this->cache_path);
					}
					global $wpmu_version, $blog_id;
					if (isset($wpmu_version) && isset($blog_id) ) {
						$this->cache_path .= $blog_id.'/';

						if (! is_dir($this->cache_path)) {
							@mkdir($this->cache_path);
						}
					} // End of is WPMU?
					if (!is_dir($this->cache_path))
						$this->cache_enabled = FALSE;

					$this->load_index();
				break;
			} // End of switch

		} // End of __construct

		function load_index() {
			if (file_exists($this->cache_path.'cache_index.php'))
				$this->index = unserialize(file_get_contents($this->cache_path.'cache_index.php'));
			else 
				$this->index = array();

		} // End of load_index

		function add_index_entry($key, $flag, $timeout) {
			if ($timeout == 0) 
				$this->index[$flag][$key] = 0;
			else 
				$this->index[$flag][$key] = time() + $timeout;
		} // End of add_index_entry

		function remove_index_entry($key, $flag) {
		
			if (isset($this->index[$flag][$key]))
				unset($this->index[$flag][$key]);

			if (sizeof($this->index[$flag] == 0))
				unset($this->index[$flag]);
		} // End of remove_index_entry

		function save_index() {
			$fd = @fopen($this->cache_path.'cache_index.php', 'w');
			if ( false !== $fd ) {
				fputs($fd, serialize($this->index));
			}
			@fclose($fd);
		} // End of save_index

		/**
		 * __destruct
		 *
		 * Will save the object cache before object is completely destroyed.
		 *
		 * @package EG-Plugin
		 *
		 * @return bool True value.
		 */
		function __destruct() {

			switch ($this->cache_enabled) {

				case 'WP':
					// Nothing to do here
				break;

				case 'transient':
					// Nothing
				break;

				case 'EG':
					// Save all cache entries stored in memory
					foreach ($this->index as $flag => $keys) {
						foreach ($keys as $key => $timeout) {
							if (isset($this->cache[$flag][$key])) {
								$cache_file = $this->get_cache_file($key, $flag);
								$string = base64_encode(serialize($this->cache[$flag][$key]));
								$fd = @fopen($cache_file, 'w');
								if ( false !== $fd ) {
									fputs($fd, $string);
								}
								@fclose($fd);
							} // End of object in memory
						} // For each key in group
					} // End of foreach group

					// Update index file
					$this->save_index();
				break;
			} // End of switch
		} // End of __destruct

		/**
		 * delete
		 * Remove the contents of the cache ID in the group
		 *
		 * @package EG-Plugin
		 *
		 * @param int|string 	$key 	What the contents in the cache are called
		 * @param string 		$flag	Where the cache contents are grouped
		 * @param bool 			$force	Optional. Whether to force the unsetting of the cache
		 * @return bool 				False if the contents weren't deleted and true on success
		 */
		function delete($key, $flag = 'default', $force = false) {

			$data = FALSE;
			$flag = ($flag == 'default'? $this->default_flag : $flag);

			switch ($this->cache_enabled) {
				case 'WP':
					$data = wp_cache_delete($key, $flag);
				break;

				case 'transient':
					$data = delete_transient($flag.'_'.$key);
					// delete_option('_transient_timeout_'.$flag.'_'.$key);
				break;

				case 'EG':
					if (isset($this->cache[$flag][$key]))
						unset($this->cache[$flag][$key]);

					if (isset($this->cache[$flag]) && sizeof($this->cache[$flag]) == 0)
						unset($this->cache[$flag]);

					$cache_file = $this->get_cache_file($key, $flag);
					if (file_exists($cache_file)) {
						@unlink($cache_file);
					}
					$this->remove_index_entry($key, $flag);
					$data = TRUE;
				break;
			} // End of switch
			return ($data);
		} // End of delete

		/**
		 * Clears the object cache of all data
		 *
		 * @package EG-Plugin
		 *
		 * @return bool Always returns true
		 */
		function flush() {

			switch ($this->cache_enabled) {

				case 'WP':
					wp_cache_flush();
				break;

				case 'transient':
					// Impossible to flush all cached objects, because we don't have list of these objects
				break;

				case 'EG':
					foreach ($this->index as $flag => $keys) {
						foreach ($keys as $key => $timeout) {
							$this->delete($key, $flag);
						}
					}
					unset($this->index);
					$this->index = array();
				break;
			} // End of switch
			return true;
		} // End of flush

		/**
		 * get_cache_file
		 *
		 * Build cache file name
		 *
		 * @package EG-Plugin
		 *
		 * @param	string	$key	name of cache file
		 * @return 	string			file name
		 */
		function get_cache_file($key, $flag=FALSE) {

			if ($flag === FALSE) $flag = $this->default_flag;
			// return ($this->cache_path.($flag==''?'':$flag.'_').$key.'.txt');
			return ($this->cache_path.($flag==''?'':md5($flag).'_').md5($key).'.txt');
		} // End of get_cache_file


		/**
		 * get
		 *
		 * Retrieves the cache contents, if it exists
		 *
		 * @package EG-Plugin
		 *
		 * @param int|string	$key 	What the contents in the cache are called
		 * @param string		$flag   Where the cache contents are grouped
		 * @return bool|mixed 			False on failure to retrieve contents or the cache
		 *								contents on success
		 */
		function get($key, $flag = 'default') {

			$data = FALSE;
			$flag = ($flag == 'default'? $this->default_flag : $flag);

			switch ($this->cache_enabled) {
				case 'WP':
					$data = wp_cache_get($key, $flag);
				break;

				case 'transient':
					$data = get_transient($flag.'_'.$key);
					if ($data === FALSE)
						$this->remove_index_entry($key, $flag);
				break;

				case 'EG':
					if (isset($this->index[$flag][$key])) {
						$timeout = $this->index[$flag][$key];
						if ( $timeout != 0 && $timeout < time() ) {
							$this->delete($key, $flag);
						}
						else {
							if (isset($this->cache[$flag][$key]))
								$data = $this->cache[$flag][$key];
							else {
								$cache_file = $this->get_cache_file($key, $flag);
								$data = unserialize(base64_decode(@ file_get_contents($cache_file)));
								$this->cache[$flag][$key] = $data;
							} // End of get data in file
						} // End of data not expired
					} // End of object exists in cache
				break;
			} // End of switch
			return ($data);
		} // End of get

		/**
		 * Sets the data contents into the cache
		 *
		 * @package EG-Plugin
		 *
		 * @param int|string 	$key 	What to call the contents in the cache
		 * @param mixed 		$data 	The contents to store in the cache
		 * @param string 		$flag 	Where to group the cache contents
		 * @param int 			$expire object life duration
		 * @return bool 				Always returns true
		 */
		function set($key, $data, $flag = 'default', $expire = FALSE) {

			$flag   = ( $flag == 'default' ? $this->default_flag   : $flag   );
			$expire = ( $expire === FALSE  ? $this->default_expire : $expire );

			switch ($this->cache_enabled) {
				case 'WP':
					wp_cache_set($key, $data, $flag, $expire);
				break;

				case 'transient':
					set_transient($flag.'_'.$key, $data, $expire);
					$this->add_index_entry($key, $flag, $expire);
				break;

				case 'EG':				
					$this->cache[$flag][$key] = $data;
					$this->add_index_entry($key, $flag, $expire);
				break;
			} // End of switch
			return true;
		} // End of set

		/**
		 * add
		 *
		 * Adds data to the cache if it doesn't already exist.
		 *
		 * @package EG-Plugin
		 *
		 * @param int|string 	$id 	What to call the contents in the cache
		 * @param mixed 		$data 	The contents to store in the cache
		 * @param string 		$flag 	Where to group the cache contents
		 * @param int 			$expire When to expire the cache contents
		 * @return bool					False if cache ID and group already exists, true on success
		 */
		function add($id, $data, $flag = 'default', $expire = '') {
			return $this->set($id, $data, $flag, $expire);
		}

		/**
		 * replace
		 *
		 * Replace the contents in the cache, if contents already exist
		 *
		 * @package EG-Plugin
		 *
		 * @param int|string 	$id 	What to call the contents in the cache
		 * @param mixed 		$data 	The contents to store in the cache
		 * @param string 		$flag 	Where to group the cache contents
		 * @param int 			$expire When to expire the cache contents
		 * @return bool 				False if not exists, true if contents were replaced
		 */
		function replace($id, $data, $flag = 'default', $expire = '') {
			return $this->set($id, $data, $flag, $expire);
		}
	} // End of EG_Cache
} // End of class_exists


if (! class_exists('EG_Error_100')) {

	/**
	 * Object Cache
	 *
	 * @package EG-Plugin
	 * @subpackage Cache
	 * @since 1.0
	 */
	class EG_Error_100{

		var $code    = FALSE;
		var $msg     = '';
		var $detail  = '';
		var $level   = 0;
		var $list    = array();
		var $no_error_code = 0;

		function EG_Error_100($no_error_code) {

			return ($this->__construct($no_error_code));
		} // End of EG_Error_100

		function __construct($no_error_code) {

			$this->no_error_code = $no_error_code;
		} // End of __construct

		/**
		 * clear_error
		 *
		 * Clear the error code
		 *
		 * @package EG-Delicious
		 *
		 * @param  none
		 * @return none
		 */
		function clear() {
			$this->code   = $this->no_error_code;
			$this->msg    = '';
			$this->detail = '';
			$this->level  = 0;
		}

		/**
		 * add_error
		 *
		 * Add an error
		 *
		 * @package EG-Delicious
		 *
		 * @param 	int			$code		error code
		 * @param	string		$msg		error message
		 * @param	int			$level		error level
		 * @param	string	$error_msg		Error message
		 * @return none
		 */
		function add_error($code, $msg, $level) {
			$this->list[$code] = array ( 'msg' => $msg, 'level' => $level);
		} // End of add_error

		/**
		 * add_error_list
		 *
		 * Add a complete error list to the object
		 *
		 * @package EG-Delicious
		 *
		 * @param 	array	$error_list		list of errors
		 * @return none
		 */
		function add_error_list($error_list) {
			$this->list = $error_list;
		} // End of add_error_list

		/**
		 * set
		 *
		 * Set error to the specified error code
		 *
		 * @package EG-Delicious
		 *
		 * @param 	int		$code 		error code
		 * @return none
		 */
		function set($code) {
			$this->code = $code;
		} // End of set

		/**
		 * is_error
		 *
		 * Get if error no set or not
		 *
		 * @package EG-Delicious
		 *
		 * @param  none
		 * @return none
		 */
		function is_error() {
			return ( $this->code != $this->no_error_code);
		} // End of is_error

		/**
		 * set_details
		 *
		 * Set error detail
		 *
		 * @package EG-Delicious
		 *
		 * @param 	string		$detail 		error detail
		 * @return	none
		 */
		function set_details($details) {
			$this->detail = $details;
		} // End of set

		/**
		 * get
		 *
		 * Get Error values
		 *
		 * @package EG-Delicious
		 *
		 * @param 	none
		 * @return	array		error (code, msg, and detail)
		 */
		function get() {
			if ($this->code == $this->no_error_code)
				return (array( 'code' => $this->code, 'msg' => '',         'detail' => ''));
			else
				return (array( 'code' => $this->code, 'msg' => $this->msg, 'detail' => $this->detail));
		} // End of set


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
		function display($error_code = FALSE, $error_msg='', $error_detail='') {

			if ($error_code === FALSE) $error_code = $this->code;

			if ($error_code != $this->no_error_code) {
				$level = $this->list[$error_code]['level'];
				if ($error_msg == '') {
					if ($this->msg == '')
						$error_msg = __($this->list[$error_code]['msg'], $this->textdomain);
					else
						$error_msg = __($this->msg, $this->textdomain);
				}
				if ($error_detail != '') {
					$error_detail = ' - '.__($error_detail, $this->textdomain);
				}
				elseif ($this->detail != '') {
					$error_detail = ' - '.__($this->detail, $this->textdomain);
				}

				echo '<div class="eg_message eg_error_'.$level.'">'.
						'<p>'.
							__('Error ', $this->textdomain).$error_code.': '.$error_msg.$error_detail.
						'</p>'.
					'</div>';
			}
		} // End of display_error

	} // End of class EG_Error_100

} // End of class_exists

?>