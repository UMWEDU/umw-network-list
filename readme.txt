=== Network List ===
Contributors: cgrymala
Donate link: http://giving.umw.edu/
Tags: multisite, networks, site list, sitemap
Requires at least: 4.1
Tested up to: 4.2.1
Stable tag: 0.1a
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Aggregates a list of all registered sites within a disparate group of multisite installations.

== Description ==

This plugin allows you to set up a list of disparate WordPress installations. On a regular basis, it will retrieve a list of all of the sites registered within those individual multisite installations.

== Installation ==

1. Upload the `network-list` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings -> Network List in the Network Admin area to set the list of networks

== Frequently Asked Questions ==

= Why are some of my networks not showing the list of their subsites? =

This plugin relies on there being a site.xml file on each network, and reads the list of sites from there. If one or more of your multisite networks are missing that file, the list of subsites cannot be retrieved. Instead, the plugin will only list the network URL (which also makes it possible to add individual WordPress installations to the aggregated list of sites).

= How do I add a site.xml file a multisite installation? =

The [Better WP Google XML Sitemaps plugin](https://wordpress.org/plugins/bwp-google-xml-sitemaps/) is capable of doing this for you automatically if you choose the appropriate options when it's network-activated. If you don't have that plugin, or don't want to configure it that way, it's possible other SEO plugins are capable of doing the same thing. It's also possible for you to generate your own list.

== Changelog ==

= 0.1a =
* Initial version
