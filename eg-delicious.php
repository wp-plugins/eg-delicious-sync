<?php
/*
Plugin Name: EG-Delicious
Plugin URI: http://www.emmanuelgeorjon.com/en/eg-delicious-sync-1791
Description: Manage Delicious links (Import into WordPress database)
Version: 0.6.5
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

define('EG_DELICIOUS_COREFILE', 	 __FILE__);
define('EG_DELICIOUS_VERSION', 	  	 '0.6.5');
define('EG_DELICIOUS_OPTIONS_ENTRY', 'EG-Delicious-Options');
define('EG_DELICIOUS_TEXTDOMAIN', 	 'eg-delicious');
define('EG_DELICIOUS_CACHE_GROUP',	 'EG-Delicious');

define('EG_DELICIOUS_DEBUG_MODE',	 FALSE);

require_once('lib/eg-plugin.inc.php');
require_once('eg-delicious-core.inc.php');

if (is_admin()) {
 	require_once('eg-delicious-admin.inc.php');
}
else {
	require_once('eg-delicious-public.inc.php');
}

?>