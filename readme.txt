=== Plugin Name ===
Contributors: Emmanuel Georjon
Donate link: http://www.emmanuelgeorjon.com/
Tags: delicious, bookmark, del.icio.us, backup, synchronization
Requires at least: 2.6.0
Tested up to: 2.8.5
Stable tag: 1.2.0

**EG-Delicious-Sync** backups the Delicious links into WordPress links database, and gives you many Delicious features.

== Description ==

Most of plugins related to Delicious allow to display Delicious links, or synchronize the last links. They use RSS features of Delicious, with three main disadvantages:

* They can adress only the last updated or added links,
* Use RSS, even with a cache, can slow down your blog,
* Links are not stored permanently in the WordPress database.

**EG-Delicious-Sync** use the HTTP API of Delicious, and allows to perform a true full backup of your Delicious links, into the WordPress database, in the standard table (wp_links). So, after this synchronization, you can use the standard features of WordPress to manage and display these links (widgets, template tags ...), as if the Delicious posts are local.

Two benefits:

* You have a backup of your Delicious database,
* You manage only one list (Delicious), and you are sure that WordPress links are up to date.

You can customize the backup (or synchronization) with a number of options:

* You can entirely control the links classification, by assigning the WordPress categories of links, with the Delicious bundles, or the Delicious tags,
* You can specify if you want a single categories per link, or allow several categories,
* Specify if you want to keep the links already existing in the WordPress databases

Other features:

* Tags synchronization: align the Delicious tags with Wordpress tags,
* An enhanced blogroll widget: display whay you want, where you want!
* Widgets to display the Delicious Network Badge, and the Delicious tags,
* A full backup (without synchronization) of the Delicious database,
* You can also automaticaly add your WordPress posts in Delicious, when you publish them. With this feature, you can see your post popularity in Delicious (how many people bookmark your posts).
* Ability to schedule links synchronization (hourly or daily frequency)

== Installation ==

= Requirements =

**EG-Delicious-Sync** requires 

* with WordPress 2.6.x:
	* PHP 4.3 
	* PHP option "allow_url_fopen=On" 
* with WordPress 2.7.x
	* PHP 4.3
	* PHP curl module installed option (activated in most of PHP platforms)

The next release of the plugin will run only with WordPress 2.7 and higher.
	
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

= Synchronize links =

1. To synchronize the Delicious and WordPress links databases, use the menu **Links / Delicious Sync.**,
1. Click on the **start synchronization** button,
1. The plugin sends requests to Delicious and WordPress to collect links, tags, and bundles, and build synchronization table,
1. When the calculation is terminated, the plugin displays this synchronization table. You can choose the categories, and the action to operate on each link,
1. When you click on the **Update changes** button, the plugin proceed with all links when the action, and categories fields are not empty
1. The session is terminated, when you synchronize all links, or when you press the **Stop synchronization** button.

= Synchronize tags =

This feature synchronizes the lists of tags of Delicious and WordPress.

1. You can first, go to **Settings/EG-Delicious**, to set the options of this synchronization,
1. You can choose to add Delicious tags into WordPress, or align the Delicious tags with the WordPress tags,
1. Once the configuration is done, you can start the synchronization, using the menu **Posts / Tags Delicious Sync**,
1. When you click on this menu, the plugin downloads the list of Delicious tags, and compares it with the WordPress list. Once this comparison is terminated, the plugin displays a table with tags, and suggested actions,
1. For each tag, you can specify the action you want (add to WordPress, del from WordPress ...),
1. When you set all parameters, click on **Save changes** to launch the synchronization.

= Posts publication = 

This feature automatically add in Delicious, the post you publish in WordPress.

1. The feature can be activated in the menu **Settings/EG-Delicious**
1. In the **Posts publication** part, click on Activation checkbox,
1. You can choose also which tags will be used to add your post in Delicious. You can use WordPress tags, WordPress categories, or specify tags manually.
1. Once settings are saved, each time you edit a post, and click on the **Publish button**, your post will be add to Delicious with the specified tags.
1. If you delete a post in WordPress, it will be also deleted in Delicious

= Widgets =

**EG-Delicious Blogroll widget**
This widget allows you to display your blogroll, but it gives you more options than the standard widget.

* You can display the blogroll, in 1, or 2 colomns,
* The option **Minimum number of links** is the limit under which the widget uses only one columns.
* You can choose the categories you want to display
* If you check **Group links by category**, the widget will display one "block" per category, each block starting by the name of category,
* if you have a page or a post, displaying all your links, you can specify its ID in the field **Page/Post ID to see all links**. The widget will display an additional link named *All bookmarks*, linked to the specified page or post.

**Network Badge widget**
This widget displays a summary of your Delicious profil (Name, number of posts, ...).
You can choose:

* the title,
* the size of the icon,
* the field (name, number of posts, size of your network, number of your fans, ...),


**Delicious tags widget**
This widget displays the Delicious tags, as you can do with the WordPress tags.
Options are:

* Number of tags to display,
* The minimum and maximum font size,
* The sort key (popularity, or just alphabetical), and sort order (ascending or descending),
* The style you want to use: flat or simple list,
* You can also display your Delicious Name, and sentence such as "Add me to your network"

In these three widgets, you can choose where or when to display them:
* Where: only home page, categories pages, or post/page pages. You can specify also, a specific category, tags or post/page.
* When: you can choose to display or hide widgets according the current selected language ...

= Backup tool =

This feature is available in the **Tools / Delicious backup** menu. It backups all Delicious links and tags, and store backup file in the plugin directory.

You can download the resulting files, or read them on a browser. You can also re-import them in Delicious, in case of errors.

== Frequently Asked Questions ==

= The plugin backups of Delicious links into the WordPress links database. To have a true synchronization, can we update the Delicious database with the WordPress links? =

No, for the moment, because of constraints of the Delicious API: 

* This API allows us to create a link in Delicious database, but we cannot launch a big amount of requests at the same time, because of Delicious securization.
* With this API, we cannot update a link. We just can delete and re-create it, but in this case, the date is wrong.

= How can modify styles or widgets? = 
You have to modify the file `eg-delicious.css` located in the EG-Delicious plugin directory. Two ways:

* Modify directly the `eg-delicious.css` in the plugin directory,
* Or copy this file, in your theme directory, and modify this copy.

The second way is recommended to ensure that your customization won't be lost during a plugin upgrade.

== Screenshots ==

1. Plugins options: Delicious username and password,
2. Links Management options,
3. Categories synchronization parameters,
4. WordPress categories / Delicious tags or bundles assignements,
5. Sample of a links synchronization session,
6. Sample of tags synchronization,
7. Options to automatically add your WordPress published posts to Delicious
8. Options to schedule synchronization
8. Delicious Backup screen

== Changelog ==

= Version 1.2.0 - Oct 29th, 2009 =

* New: Ability to schedule links synchronization,
* Change: cache mecanism rewritten (a lot of bug fixes),
* Change: error notification system rewritten (again a lot of bug fixes),
* Bug fix: space deleted in the post description (publish post feature),
* Bug fix: in synchronisation page, filter settings was not kept when click on Update button,
* Bug fix: blank options page after new installation.

= Version 1.1.6 - Sept 29th, 2009 =

* Bug fix: Bad constant settings

= Version 1.1.5 - Sept 29th, 2009 =

* New: Introduce the incremental mode,
* New: Hightlight change in the synchronisation table,
* Bug fix: test if plugin is configured before displaying tags page,
* Bug fix: Publish post feature. Password and date error, shared option not used properly,
* Bug fix: some errors in French translation,
* Bug fix: button "restart" didn't work,
* Change: cache mecanism. Use existing object cache or "transient" cache (if WP 2.8.x) before home-made cache,
* Change: error handling. More detailed error messages,
* Change: in the synchronization page, highlight changes (pink rows).

= Version 1.1.0 - Aug 25th, 2009 =

* New: WordPress MU compatibility,
* Change: Cache management,
* Change: Better error management (during Delicious request),
* Change: Better uninstallation (include widget now),
* Bugfix: cannot create backup path,
* Bugfix: cannot get Bundles or Tags, immediatly after entering username and password

= Version 1.0.2 - Aug 20th, 2009 =

* New: ability to sort links by title or date during synchronization

= Version 1.0.1 - Aug 9th, 2009 =

* Bugfix: Error message "Call-time pass-by-reference has been deprecated"

= Version 1.0.0 - July 30th, 2009 =

* New: Synchronize tags
* New: Automaticaly add in Delicious, posts published in WordPress
* New: Widgets: network badge, delicious tags

= Version 0.8.0 - July 13th, 2009 =

* Bugfix: Warning message with array_keys function,
* Bugfix: Options didn't displayed when bundles not used,
* Bugfix: Uninstall doesn't delete option entries,

= Version 0.7.2 - July 12th, 2009 =

* Bugfix: Error during Delicious request with WordPrss 2.8
* New: Enhanced blogroll widget

= Version 0.7.1 - July 09th, 2009 =

* Bugfix: Temp directory didn't exist

= Version 0.7.0 - July 09th, 2009 =

* Bugfix: Securization in case of multi-user blog (cannot edit options during synchronization for example),
* Bugfix: Better support of http requests (didn't work with WP 2.7 !)
* Bugfix: Automatically stop the synchronization during logout	

= Version 0.6.5 - July 8th, 2009 =

* Bugfix: Must click twice on submit button after giving Delicious username and password,
* Bugfix: Error message "You do not have sufficient permissions to access this page." while installing plugin,
* New: French translation
* New: Interface improvements

= Version 0.6.0 - July 6th, 2009 =

* New: Initiale release

== Licence ==

This plugin is released under the GPL, you can use it free of charge on your personal or commercial blog.

== Translations ==

The plugin comes with French and English translations, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the eg_delicious.pot file which contains all defintions and may be used with a [gettext](http://www.gnu.org/software/gettext/) editor like [Poedit](http://www.poedit.net/) (Windows).