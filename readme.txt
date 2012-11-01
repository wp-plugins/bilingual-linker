=== Bilingual Linker ===
Contributors: jackdewey
Donate link: http://yannickcorner.nayanna.biz/wordpress-plugins/bilingual-linker
Tags: translation, link, bilingual
Requires at least: 3.0
Tested up to: 3.4.1
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

= 2.0.1 =
* Corrected problem with category meta table creation code

= 2.0 =
* Added support for multiple languages
* Added ability to assign translation links to categories
* Translation display link now works on all page types (front page, archives, search results, categories, tag)
* Created new display function (the_bilingual_link)

= 1.2.3 =
* Added option to specify whether the link should be echoed or sent as a function return value

= 1.2.2 =
* Added option to OutputBilingualLink to be able to provide a default URL to display if no translation link is found

= 1.2.1 =
* Fixed problem with posts extra field getting deleted

= 1.2 =
* Updated Bilingual Linker to support network installations
* Changed data storage method to use post meta data instead of custom table

= 1.1 =
* Added code to display Bilingual Linker on all post types, not only on posts and pages

= 1.0 =
* Initial functionality
* Ability to add custom link for translated text in post and page editors
* Ability to query this address from Wordpress theme

== Frequently Asked Questions ==

There are currently no FAQs

== Screenshots ==

There are currently no screenshots available