=== Click to donate ===
Contributors: cesperanc, codedmind
Tags: clicks, visits, tracking, donate, donation, NGO, ONG, plugin, extension
Requires at least: 3.2.0
Tested up to: 3.7.1
Stable tag: 1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Provides the required functionality to manage and monitoring advertising campaigns based on clicks by the site visitors.


== Description ==

This extension provides the functionality to create and manage advertising campaigns where their sponsors give a donation to the promoter based on the number of visits into those campaigns.

The content manager can configure options like the campaign start and end dates, the time between user visits, maximum number of visits, etc. The system will automagically change the state of a campaign when those conditions are met (hidding the access links from the content).

[youtube http://www.youtube.com/watch?v=NAEfXK-M0TE]

This was an academic project developped on the [School of Technology and Management](http://www.estg.ipleiria.pt/ "Follow the link to visit the school site") - [Polytechnic Institute of Leiria](http://www.ipleiria.pt/ "Follow the link to visit the site") for the NGO [Ação para o Desenvolvimento](http://www.adbissau.org/ "Follow the link to visit the site").


== Screenshots ==

1. A special window is provided to create links to campaigns
2. There are many configuration options associated with a campaign to provide full control
3. The manager can easily monitor the campaign progress
4. On the dashboard, the manager can monitor the progress of all the campaigns
5. Also in the dashboard, the registered users can view their ranking compared with all the others users, while the manager can view the ranking of all the users
6. Contextual help is provided for assistance


== Frequently Asked Questions ==

= The text or image link associated with a campaign disappeared. What happened?   =

Before any content is printed the plugin verify if any link to a specific campaign is valid, and if the campaign is available. If not, the link and all the content associated with it is removed from the output. This can occur if the setted limits where met in any way.


= Which web browsers are supported?   =

For the public area, any web browser that supports links. For the backoffice, Internet Explorer 8+, Mozilla Firefox, Google Chrome, etc, should be ok.


== Changelog ==

= 1.0.6 = 
* Bug fix on the graph view when showing the results on the dashboard for just one campaign (thanks JML0691)
* Strict warning fixes on class methods invoked by WordPress hooks
* Updates to use the integrated jquery ui from wordpress library

= 1.0.5 = 
* Bug fix on the graph views date interval when using PHP on Windows
* CSS updates
* Misspelled class name correction

= 1.0.4 = 
* The text widget can now be used to provide a link for a campaign. Use the URL "#ctd-X" (where X is the ID of the campaign) on a href attribute to the link be recognized as a campaign link.
* Bug fixes
* Wordpress 3.4.1 compatibility tests

= 1.0.3 =
* Bug fixes and validation for Wordpress 3.4

= 1.0 =
* Initial plugin release