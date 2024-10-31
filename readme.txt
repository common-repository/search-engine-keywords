=== Plugin Name ===
Contributors: Copes Flavio
Tags: search engine, keywords, search query
Requires at least: 2.0?
Tested up to: 2.6.1
Stable tag: 1.1
Display to the visitor coming from search engines a message based upon what they're searching for.

== Description ==I created a Wordpress plugin that can be used to correlate the keywords used to find your page on the search engines to a box where you can put anything you want, for example your affiliate marketing links, a link to something you want your visitor to read, or a "hello" to the visitor.We all know from our site stats what our visitors are searching for on our site. Sometimes they come looking for something that isn't there. So, you can leave them a personalized message.Otherwise, you may be looking for ways to monetize your site: this plugin is fantastic for this, because it gives you the opportunity to display some tematic links (quite in a Adsense way, but you control it). You can send affiliate marketing links to the visitor interested in them.This is my first Wordpress plugin, so any suggestion is welcome. Please leave a comment on my weblog if you find it useful.
-----------------How does it work?-----------------It doesn't address a single page, but all the pages that are listed in the search engine SERPs for that particular key.Thanks to the functions written by Thomas Silkjær in his Landing Sites WP plugin, the plugin finds out what keyword the visitor typed in the search engine form to get to the site.In the administrator panel, you can set the keywords to search for, and the message you want to display, written in XHTML. You can then style it as you want using your CSS file, adding some selectors for the div #copesSearchEngineKeywordPlugin.

This plugin does not add any additional table in the database.
== Installation ==
Download the plugin, then unarchive it and install it by uploading the CopesSearchEngineKeywordPlugin directory to your Wordpress installation, in the wp-content/plugins directory.Then activate it from the administrator panel Plugins.In order to get this plugin to work, you have to put these lines in your template, wherever you want your message to appear. To give it a great visibility, I suggest to put it at the end of the header.php file, so that the message will be displayed before the post.<div id="copesSearchEngineKeywordPlugin"><?php if (class_exists("CopesSearchEngineKeywordsPlugin")) {$copesSEK = new CopesSearchEngineKeywordsPlugin();echo $copesSEK->addContent();}?></div>Hope you like it!