<?php

if (!class_exists('EG_Delicious')) {

	class EG_Delicious extends EG_Plugin_112 {

		var $current_wp_user;

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

			$logged_user 			= wp_get_current_user();
			$this->current_wp_user	= $logged_user->display_name;

			add_filter( 'cron_schedules',        'eg_delicious_schedules'         );
			add_action( 'eg_delicious_cron_sync', array(&$this, 'scheduled_sync') );
		} // End of init

		function wp_logout($user_login) {
			global $egdel_cache;

			if ($this->options !== FALSE) {

				if ($this->options['sync_status'] != 'stopped' &&
					$this->options['sync_user'] == $this->current_wp_user) {

					$this->options['sync_status'] = 'stopped';
					$this->options['sync_date']   = 0;
					$this->options['sync_user']   = '';

					update_option($this->options_entry, $this->options);

					$egdel_cache->delete('links_db');
					$egdel_cache->delete('linksdb_index');

				} // End of test user
			} // End of test options and status
		} // End of wp_logout

		/**
		 * write_logs
		 *
		 *
		 *
		 * @package EG-Delicious
		 *
		 * @param  array	$logs		list of rows to store in log file
		 * @return none
		 */
		function write_logs($logs) {

			$logfile_name = $this->plugin_path.'/eg-delicious-schedule.log';
			$fh = fopen($logfile_name, 'a');
			foreach ($logs as $link) {
				fwrite($fh, $link['date']."\t".$link['type']."\t".$link['action']."\t".$link['msg']."\n");
			}
			fclose($fh);
		} // End of write_logs

		/**
		 * scheduled_sync
		 *
		 * Performed synchronization without interaction
		 * Can be used with cron
		 *
		 * Function not fully tested. Don't use it before official release
		 * @package EG-Delicious
		 *
		 * @param  none
		 * @return none
		 */
		function scheduled_sync() {

			if (! function_exists('wp_insert_link')) {
				include_once(ABSPATH . '/wp-admin/includes/bookmark.php');
			}

			global $egdel_error;
			global $egdel_cache;

			$this->options = egdel_load_options($this->options);
			$sync_mode = $this->options['scheduled_sync_mode'];

			$start_time = date('d/M/Y H:i');
			$logs[] = array( 'date' => date('d/M/Y H:i:s'), 'type' => 'info', 'action' => 'start', 'msg' => 'Start synchronization' );

			if ($this->options['last_sync_date'] === 0) {
				$logs[] = array( 'date' => date('d/M/Y H:i:s'), 'type' => 'error', 'action' => 'no sync', 'msg' => 'No full synchronization previously done. Please perform a manual full synchronization before schedule incremental synchronization.');
			}
			else {
				if (! isset($this->deldata))
					$this->deldata = new EG_Delicious_Core($this->options_entry, $this->options, $this->textdomain);

				$this->deldata->links_sync_build_list( ($sync_mode == 'inc') );
				if ( $egdel_error->is_error() ) {
					$this->options['sync_status'] = 'error';
					$error = $egdel_error->get();
					$logs[] = array( 'date'  => date('d/M/Y H:i:s'), 'type'   => 'error', 'action' => 'Error'.$error->code,'msg' => $error->msg.' '.$error->detail);
					egdel_save_options($this->options_entry, $this->options);
				}
				else {
					$this->options['sync_status'] = 'started';
					$this->options['sync_date']   = time();
					$this->options['sync_user']   = $this->current_wp_user;
					egdel_save_options($this->options_entry, $this->options);

					$links_db      = $egdel_cache->get('links_db');
					$linksdb_index = $egdel_cache->get('linksdb_index');

					$logs = $this->deldata->links_sync_action($links_db, $links_db, $linksdb_index, 'shedule');

					$this->options['sync_status']    = 'stopped';
					$this->options['sync_user']      = '';
					$this->options['last_sync_date'] = $this->options['sync_date'];
					$this->options['sync_date']      = 0;
					egdel_save_options($this->options_entry, $this->options);

					if (sizeof($logs) == 0) 
						$logs[] = array( 'date' => date('d/M/Y H:i:s'), 'type' => 'info', 'action' => 'No link', 'msg' => __('No links changed during synchronization session.', $this->textdomain));
				} // End of no error
			} // End of last_sync_date exists
			
			// Write end of sync 
			$logs[] = array( 'date' => date('d/M/Y H:i:s'), 'type' => 'info', 'action' => 'end', 'msg' => 'End of  synchronization');
			$this->write_logs($logs);

			if (isset($this->deldata)) unset($this->deldata);

		} // End of scheduled_sync

	} // End of class EG_Delicious

} // End of class_exists


$eg_delicious_public = new EG_Delicious('EG-Delicious', EG_DELICIOUS_VERSION, EG_DELICIOUS_COREFILE, EG_DELICIOUS_OPTIONS_ENTRY);
$eg_delicious_public->set_textdomain(EG_DELICIOUS_TEXTDOMAIN);
$eg_delicious_public->set_wp_versions('2.6', FALSE, '2.7', FALSE);
if (function_exists('wp_remote_request'))
	$eg_delicious_public->set_php_version('4.3', FALSE, 'curl');
else
	$eg_delicious_public->set_php_version('4.3', 'allow_url_fopen');

$eg_delicious_public->set_stylesheets('eg-delicious.css', FALSE) ;
$eg_delicious_public->load();

?>