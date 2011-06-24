=== Bilingual Linker ===
Contributors: jackdewey
Donate link: http://yannickcorner.nayanna.biz/wordpress-plugins/bilingual-linker
Tags: translation, link, bilingual
Requires at least: 3.0
Tested up to: 3.2
Stable tag: trunk

The purpose of this plugin is to allow users to add a link to a translation version of a page or post in the admin and print this link on their blog, on a single post or or a page.

== Description ==

The purpose of this plugin is to allow users to add a link to a translation version of a page or post in the admin and print this link on their blog, on a single post or or a page.

* [Changelog](http://wordpress.org/extend/plugins/translation-linker/other_notes/)
* [Support Forum](http://wordpress.org/tags/translation-linker)

== Installation ==

1. Download the plugin
1. Upload entire bilingual-linker folder to the /wp-content/plugins/ directory
1. Activate the plugin in the Wordpress Admin
1. Add links to posts or pages in the Wordpress editor
1. Use the OutputBilingualLink function in the loop to display a link to the item translation.

OutputBilingualLink($post_id, $linktext, $beforelink, $afterlink);

When using in The Loop in any template, you can use $post->ID as the first argument to pass the current post ID being processed.

== Changelog ==

= 1.0 =
* Initial functionality
* Ability to add custom link for translated text in post and page editors
* Ability to query this address from Wordpress theme

== Frequently Asked Questions ==

There are currently no FAQs

== Screenshots ==

There are currently no screenshots available