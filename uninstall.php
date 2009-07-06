<?php

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

// --- Get options ---	
define('EG_DELICIOUS_OPTIONS_ENTRY', 'EG-Delicious-Options');
$eg_delicious_options = get_option(EG_DELICIOUS_OPTIONS_ENTRY);

// --- Delete options (plugins and widgets ---
if ($eg_delicious_options['uninstall_del_option']) {
	delete_option(EG_DELICIOUS_OPTIONS_ENTRY);	
}

?>