=== HTML Page Sitemap ===
Contributors: amandato, pluginspodcast
Donate link: http://www.pluginspodcast.com/contact/
Tags: sitemap, page, pages, shortcode, cms, static, short code, map, pagemap, site, html, html sitemap, nav, navigation
Requires at least: 2.7.0
Tested up to: 4.4
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds an HTML (Not XML) sitemap of your pages (not posts) by entering the shortcode [html_sitemap], perfect for those who use WordPress as a CMS.

== Description ==
This simple plugin adds an HTML (Not XML) sitemap of your pages (not posts) by entering the shortcode [html_sitemap] in the page content. This simple plugin is configured with the `[html_sitemap]` shortcode. This plugin can display a sitemap with specific depths, from a specific parent page and include/exclude specific children. Shortcode attributes include `sort_column`, `sort_order`, `exclude`, `include`, `depth`, `child_of`, `meta_key`, `meta_value`, `authors`, `number`,  `offset`, `post_type`,  and `post_status`, everything the [`wp_list_pages`](https://codex.wordpress.org/Function_Reference/wp_list_pages) function accepts. In addition to the `wp_list_pages` options you can also set `class`, `id`, and `ordered_list_type` options.

**This plugin is perfect for those who use WordPress as a CMS.**

Please see documentation for the [`wp_list_pages`](https://codex.wordpress.org/Function_Reference/wp_list_pages) for a complete list of options.

The following options may be configured in addition to options documented in [`wp_list_pages`](https://codex.wordpress.org/Function_Reference/wp_list_pages):

The `class` and `id` attributes may specified set class or id values for the initial HTML Page Sitemap `<ul>` list tag.

The `child_of` attribute value can be a numeric page ID (integer), or use the custom value CURRENT or PARENT. The value "CURRENT" will use the current page's ID. The value "PARENT" will uses the curent page's parent ID.

The `ordered_list_type` attribute is an HTML Page Sitemap plugin exclusive option for specfiying an ordered list type. Not setting a ordered_list_type will use an unordered list (default).

* `ordered_list_type="1"` - The list items will be numbered with numbers
* `ordered_list_type="A"` - The list items will be numbered with uppercase letters
* `ordered_list_type="a"` - The list items will be numbered with lowercase letters
* `ordered_list_type="I"` - The list items will be numbered with uppercase roman numbers
* `ordered_list_type="i"` - The list items will be numbered with lowercase roman numbers

Note: This plugin only works with Hierarchical Custom Post Types. This plugin will not work if your post type is not heirarchical.

= Examples =
First example shows how to add a sitemap for the entire site.

`[html_sitemap]`

Example shortcode will add a sitemap to a page displaying a depth limit of 3 and exclude page ID 708.

`[html_sitemap depth=3 exclude=708]`

Example shortcode will add a sitemap to a page displaying only children and grandchildren of the current page.

`[html_sitemap depth=2 child_of=CURRENT]`

Example shortcode will add a sitemap to a page displaying children and grandchildren of the parent page (would include the curent page as well).

`[html_sitemap depth=2 child_of=PARENT]`

Example shortcode will add a sitemap displaying the page modified date with the pages sorted by the menu order number.

`[html_sitemap show_date=modified sort_column=menu_order]`

Example shortcode excluding multiple pages with ids 708, 1024 and 42.

`[html_sitemap exclude="708,1024,42"]`

Example shortcode will add a class attribute to the `<ul>` tag.

`[html_sitemap class="custom-class"]`

Example shortcode will add an id attribute to the `<ul>` tag.

`[html_sitemap id="custom-element-id"]`

Example shortcode will display an ordered list using lowercase letters with a depth of 1.

`[html_sitemap ordered_list_type="a" depth=1]`

For the latest information visit the website.

[http://www.pluginspodcast.com/plugins/html-page-sitemap/](http://www.pluginspodcast.com/plugins/html-page-sitemap/)

= ATTENTION: HTML-SITEMAP SHORTCODE HAS CHANGED =
Though `html-sitemap` still works, the new HTML Sitemap shortcode is `html_sitemap`. The dash/hyphen may not get processed correctly if other plugins use the same prefix such as the Syntax Highlighter plugin. [read more](http://core.trac.wordpress.org/ticket/11948), [and more](http://core.trac.wordpress.org/ticket/17657)

== Frequently Asked Questions ==

 = Why is there no settings page for the plugin? =
 I put together this plugin in less than 2 hours, this readme.txt actually took longer to create. This plugin is meant to be simple and easy to use. To keep it simple, it doesn't add settings to your database or clutter to your admin screens.
 
== Installation ==
= Automatic Plugin Installation =
Install using the [built-in plugin installer](https://codex.wordpress.org/Administration_Screens#Add_New_Plugins):

1. Go to Plugins > Add New. 
2. Type in "HTML Page Sitemap" in *Search* Plugins box. 
3. Find "HTML Page Sitemap" in the search results.
4. Click *Install Now* to install the WordPress Plugin.
5. Click Activate to activate it.
6. Add the shortcode [html_sitemap] to the page(s) of your choice.

= Manual Plugin Installation =

1. Download this plugin to your desktop.
2. Extract the plugin zip file (or compressed folder) to your desktop.
3. With your FTP program, upload the plugin folder to the wp-content/plugins folder in your WordPress directory online.
4. Go to Plugins screen and find "HTML Page Sitemap" plugin in the list.
5. Click Activate to activate it.
6. Add the shortcode [html_sitemap] to the page(s) of your choice.

== Screenshots ==
1. HTML Page Sitemap in the Default WordPress theme.

== Changelog ==

= 1.3 =
* Released on 11/24/2015
* Added `ordered_list_type` option to use an ordered list rather than unordered. (Thanks Allie for the feature suggestion!)
* Tested with WordPress 4.4 beta 4


= 1.2 =
* Released on 9/15/2015
* Tested with WordPress 4.3.
* Added class and id attribute options for specifying HTML class and/or id attributes in the unordered list `<ul>`. (Thanks phorbidden for the feature suggestion!)
* Updated readme, added additional examples for the class and id attribute options.


= 1.1.4 =
* Released on 1/11/2015
* Tested with WordPress 4.1.
* End of source no longer includes closing PHP tag as recommended by WordPress.
* Updated readme, added additional example excluding multipe pages.


= 1.1.3 =
* Released 3/11/2013
* HTML Sitemap compatible with latest versions of WordPress up to 3.5.1
* Nothing has changed with this plugin other than the readme.txt.


= 1.1.2 =
* Released 1/31/2012
* Hyphen in shortcode changed to underscore. `html_sitemap` and `htmlsitemap` shortcodes work.  [read more here](http://wordpress.org/support/topic/plugins-wont-coexist?replies=5)
* HTML Sitemap compatible with latest versions of WordPress up to 3.3.1

= 1.1.1 =
* Released 6/27/2010
* HTML Sitemap compatible with latest versions of WordPress 2.9 and 3.0.

= 1.1.0 =
* Released on 11/24/2009
* Fixed typos in readme
* Added child_of options
** child_of=CURRENT (starts list of pages that are children of the current page)
** child_of=PARENT (starts list of pages that are of the same level as current page)

= 1.0.0 =
* Released on 09/05/2009
* Initial release of HTML Page Sitemap plugin.

== Upgrade Notice ==
None at this time.


== Contributors ==
Angelo Mandato, CIO [RawVoice](http://www.rawvoice.com) and host of the [Plugins Podcast](http://www.pluginspodcast.com) - Plugin author

== Follow us on Twitter == 
[http://twitter.com/pluginspodcast](http://twitter.com/pluginspodcast)
