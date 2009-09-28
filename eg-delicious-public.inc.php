<?php

if (!class_exists('EG_Delicious')) {
	
	class EG_Delicious extends EG_Plugin_111 {
	
		function wp_logout($user_login) {
			if ($this->options !== FALSE) {
			
				$logged_user = wp_get_current_user();
				if ($this->options['sync_status'] != 'stopped' && 
					$this->options['sync_user'] == $logged_user->display_name) {

					$this->options['sync_status'] = 'stopped';
					$this->options['sync_date']   = 0;
					$this->options['sync_user']   = '';
				
					update_option($this->options_entry, $this->options);
				
					$this->cache_delete('links_db');
					$this->cache_delete('linksdb_index');
				
				} // End of test user
			} // End of test options and status
		} // End of wp_logout
		
	} // End of class EG_Delicious

} // End of class_exists
$eg_delicious_public = new EG_Delicious('EG-Delicious', EG_DELICIOUS_VERSION, EG_DELICIOUS_COREFILE, EG_DELICIOUS_OPTIONS_ENTRY);
$eg_delicious_public->set_textdomain(EG_DELICIOUS_TEXTDOMAIN);
$eg_delicious_public->set_wp_versions('2.6', FALSE, '2.7', FALSE);
if (function_exists('wp_remote_request'))
	$eg_delicious_public->set_php_version('4.3', FALSE, 'curl');
else
	$eg_delicious_public->set_php_version('4.3', 'allow_url_fopen');

$eg_delicious_public->cache_init('tmp', 900, 'eg_delicious');
$eg_delicious_public->set_stylesheets('eg-delicious.css', FALSE) ;
$eg_delicious_public->load();

?>