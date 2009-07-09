<?php

if (!class_exists('EG_Delicious')) {
	
	class EG_Delicious extends EG_Plugin_103 {

		function init() {
			parent::init();
			
			$this->plugin_temp        = $this->plugin_path.'tmp/';
			$this->file_linksdb       = $this->plugin_temp.'synchronize_links.txt';
			$this->file_linksdb_index = $this->plugin_temp.'synchronize_links_index.txt';
		} // End of init
	
		function wp_logout($user_login) {
			// $options = get_option($this->options_entry);
			if ($this->options !== FALSE) {
			
				$logged_user = wp_get_current_user();
				if ($this->options['sync_status'] != 'stopped' && 
					$this->options['sync_user'] == $logged_user->display_name) {

					$this->options['sync_status'] = 'stopped';
					$this->options['sync_date']   = 0;
					$this->options['sync_user']   = '';
				
					update_option($this->options_entry, $this->options);
				
					if (file_exists($this->file_linksdb)) @unlink($this->file_linksdb);
					if (file_exists($this->file_linksdb_index)) @unlink($this->file_linksdb_index);
				} // End of test user
			} // End of test options and status
		} // End of wp_logout
		
	} // End of class EG_Delicious

} // End of class_exists
$eg_delicious_public = new EG_Delicious('EG-Delicious',
									EG_DELICIOUS_VERSION ,
									EG_DELICIOUS_COREFILE,
									EG_DELICIOUS_OPTIONS_ENTRY);
$eg_delicious_public->set_textdomain(EG_DELICIOUS_TEXTDOMAIN);
$eg_delicious_public->set_owner('Emmanuel GEORJON', 'http://www.emmanuelgeorjon.com/', 'blog@georjon.eu');
$eg_delicious_public->set_wp_versions('2.6', FALSE, FALSE, FALSE);
$eg_delicious_public->set_php_version('4.3');
// $eg_delicious_public->set_stylesheets('eg-delicious.css', FALSE) ;
$eg_delicious_public->load();

?>