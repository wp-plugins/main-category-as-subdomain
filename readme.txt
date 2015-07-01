=== Main Category As Subdomain ===
Contributors: dersax
Donate link: 
Tags: subdomain, subdomains , category ,categories
Requires at least: 3.9.1
Tested up to: 4.2.2
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This Plugin allows users to setup there main categories as subdomains

== Description ==

This Plugin allows users to setup Main categories as subdomains, 

Features

*   Setup main categories as subdomains
*   Custom themes for each subdomains
*   Redirect Old Url
*   Change Child Categories to Categories Or Subdomain
*	Change Front page on subdomain
*   Many More..

== Installation ==

= A. Configuring Wildcard Subdomains =

*   Make a sub-domain named "*" (wildcard) at your CPanel (*.example.com). Make sure to point this at the same folder location where your wordpress folder is located.

= B. Upload This Plugin into wordpress =
*   Upload this plugin to the `/wp-content/plugins/` directory
*   Or Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

None

== Screenshots ==

1. Main Page Where you can change selected category to subdomain or change the theme
2. All Settings on subdomain

== Changelog ==

= 2.1 =

* Bugs fixed:
	* Minor Error in Save Button Text on Main Categories tab

* New features:
	* Added Menus Location For Each Subdomain

= 2.0.1 =

* Bugs fixed:
	* Fix Customize Subdomain can't be saved
	* Tell Users About Sitemap XML Plugins Disaster

= 2.0 =

* Bugs fixed:
	* Change theme now supports child theme
	* Fix css relative background
	* Category url in subdomain can use "category base"
	* Better Redirection with support paged, comment-page, etc
	

* New features:
	* Added Testing Mode.
	* Detect wildcard subdomain has been set up or not
	* Added "Find Subdomain" option for multicategories post
	* %category% permalink can be removed completely or change to child category if exist
	* User can shorten url with only single category
	* Change Front page on subdomain with selected "page"
	* Added how many count post show up on blog pages
	

= 1.0 =
* Born


== Upgrade Notice ==

= 2.0 =
*Upgrade db option mcs_categories

= 1.0 =
* None
