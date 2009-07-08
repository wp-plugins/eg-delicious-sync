=== Plugin Name ===
Contributors: Emmanuel Georjon
Donate link: http://www.emmanuelgeorjon.com/
Tags: delicious, bookmark, del.icio.us, backup, synchronization
Requires at least: 2.6.0
Tested up to: 2.8.1 RC1
Stable tag: 0.6.5

**EG-Delicious-Sync** backups the Delicious links into WordPress links database, and gives you many Delicious features.

== Description ==

Most of plugins related to Delicious allow to display Delicious links, or synchronize the last links. They use RSS features of Delicious, with three main disadvantages:

* They can adress only the last updated or added links,
* Use RSS, even with a cache, can slow down your blog,
* Links are not stored permanently in the WordPress database.

**EG-Delicious-Sync** use the HTTP API of Delicious, and allows to perform a true full backup of your Delicious links, into the WordPress database, in the standard table (wp_links). So, after this synchronization, you can use the standard features of WordPress to manage and display these links (widgets, template tags ...), as if the Delicious posts are local.

You can customize the backup (or synchronization) with a number of options:

* You can entirely control the links classification, by assigning the WordPress categories of links, with the Delicious bundles, or the Delicious tags,
* You can specify if you want a single categories per link, or allow several categories,
* Specify if you want to keep the links already existing in the WordPress databases

Planned features for next version:

* Display Delicious network badge (widgets, or template tags),
* Display Tagsroll,

== Installation ==

= Requirements =

**EG-Delicious-Sync** requires 

* with WordPress 2.6.x:
	* PHP 4.3 
	* PHP option "allow_url_fopen=On" 
* with WordPress 2.7.x
	* PHP 4.3
	* PHP curl module installed option (activated in most of PHP platforms)

= Installation =
* The plugin is available for download on the WordPress repository,
* Once downloaded, uncompress the file eg-delicious-wp-sync.zip,
* Copy or upload the uncompressed files in the directory wp-content/plugins in your WordPress platform
* Activate the plugin, in the administration interface, through the menu Plugins

The plugin is now ready to be used.
You can also use the WordPress 2.7/2.8 features, and install the plugin directly from the WordPress interface.

Then you can go to menu **Settings / EG-Delicious** to set plugin parameters

= Configuration =

1. The configuration panel is available in the **Settings / EG-Delicious** menu,
1. First, you have to give your Delicious username, and password. These parameters will allow the plugin to query Delicious API, to get posts list, tags list ...
1. After you press **Save changes**, a new form appears, with synchronization parameters,
1. The mandatory parameters are the assignemnts between Wordpress categories of links, and Delicious bundles or tags. If the plugin cannot find the right categories, links won't be synchronized,
1. Once you fill the assignments table, click on "Save changes",
1. Options are saved, and you are ready to lanch your first synchronisation session.

= Synchronize =

1. To synchronize the Delicious and WordPress links databases, use the menu **Links / Delicious Sync.**,
1. Click on the **start synchronization** button,
1. The plugin sends requests to Delicious and WordPress to collect links, tags, and bundles, and build synchronization table,
1. When the calculation is terminated, the plugin displays this synchronization table. You can choose the categories, and the action to operate on each link,
1. When you click on the **Update changes** button, the plugin proceed with all links when the action, and categories fields are not empty
1. The session is terminated, when you synchronize all links, or when you press the **Stop synchronization** button.

== Frequently Asked Questions ==

= The plugin backups of Delicious links into the WordPress links database. To have a true synchronization, can we update the Delicious database with the WordPress links? =

No, for the moment, because of constraints of the Delicious API: 

* This API allows us to create a link in Delicious database, but we cannot launch a big amount of requests at the same time, because of Delicious securization.
* With this API, we cannot update a link. We just can delete and re-create it, but in this case, the date is wrong.

== Screenshots ==

1. Plugins options: Delicious username and password
2. Links Management options
3. Categories synchronization parameters
4. WordPress categories / Delicious tags or bundles assignements
5. Sample of a synchronization session

== Changelog ==

= Version 0.6.5 - July 8th, 2009 =
* Buf fix:
	* Must click twice on submit button after giving Delicious username and password,
	* Error message "You do not have sufficient permissions to access this page." while installing plugin,
* New feature:
	* French translation
	* Interface improvements

= Version 0.6.0 - July 6th, 2009 =
* Initiale release

== Licence ==

This plugin is released under the GPL, you can use it free of charge on your personal or commercial blog.

== Translations ==

The plugin comes with French and English translations, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the eg_attachments.pot file which contains all defintions and may be used with a [gettext](http://www.gnu.org/software/gettext/) editor like [Poedit](http://www.poedit.net/) (Windows).