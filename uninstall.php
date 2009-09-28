<?php

 if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

// --- Get options ---
define('EG_DELICIOUS_OPTIONS_ENTRY', 'EG-Delicious-Options');
$eg_delicious_options = get_option(EG_DELICIOUS_OPTIONS_ENTRY);

// --- Remove widgets from the sidebar
if (function_exists('wp_unregister_sidebar_widget')) {
	wp_unregister_sidebar_widget('egdel_blogroll');
	wp_unregister_sidebar_widget('egdel_badge');
	wp_unregister_sidebar_widget('egdel_tagrolls');
}

// --- Delete options (plugins and widgets ---
if ($eg_delicious_options['uninstall_options']) {
	delete_option(EG_DELICIOUS_OPTIONS_ENTRY);

	if (get_option('widget_egdel_blogroll') !== FALSE)
		delete_option('widget_egdel_blogroll');

	if (get_option('widget_egdel_tagrolls') !== FALSE)
		delete_option('widget_egdel_tagrolls');

	if (get_option('widget_egdel_badge') !== FALSE)
		delete_option('widget_egdel_badge');

}

?>