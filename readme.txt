=== Event List ===
Contributors: mibuthu
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W54LNZMWF9KW2
Tags: event, events, list, listview, calendar, schedule, shortcode, page, category, categories, admin, attribute, widget, sidebar
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 0.3.1
Plugin URI: http://wordpress.org/extend/plugins/event-list
Licence: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your events and show them in a list view on your site.


== Description ==

The purpose of this plugin is to to show a list of events with date, time, description, place, etc. on your site by using a shortcode or a widget.
There are also admin sites available to access the plugin-settings and to manage the events.

= Current Features =
* beginning and end dates for multiple-day events
* Wordpress's WYSIWYG editor for the event description so you can include styled text, links, images and other media in your event list
* a duplicate function for events
* an possibility to view past events by year

The event list can be placed in any page or post on your Wordpress site. Just include the following short code where you want the calendar to appear:

‘[event-list]’

With the existing attributes it is possible to modify the listed events and their style.
There is also a widget available to view the upcoming events in a sidebar.

If you want to follow the development status have a look at the [git-repository on github](https://github.com/mibuthu/wp-event-list "wp-event-list git-repository").


== Installation ==

The easiest version of installing is to go to the admin page. There you can install new plugins in the menu Plugins -> Add new. Search for "Event List" and press "Install now".

If you want to install the plugin manually download the zip-file and extract the files into your wp-content/plugins folder.


== Frequently Asked Questions ==

= How do I get a calendar to show up in a Page or Post on my site? =
Insert the shortcode [event-list] in your page.

= How do I use styled text and images in the event descriptions? =
Event List uses the built-in Wordpress WYSIWYG editor. It's exactly the same process you use when creating Posts or Pages.


== Screenshots ==

1. Admin page: Main page with the event list table
2. Admin page: New event form
3. Admin page: About page with help and available attributes list
4. Admin page: Widget with the available options
5. Example page created with [event-list] shortcode


== Changelog ==

= 0.3.1 (2013-01-03) =

* added widget option "show_location"
* fixed wrong url for single event page link
* fixed issue with different shortcodes on one page or post
* changed required prevelegs for admin about page
* updated help messages on admin about page
* small style changes on frontpage


= 0.3.0 (2012-12-31) =

* added a widget to show upcoming events in a sidebar
* added some shortcode attributes to modify the output
* internal code changes
* fixed some html issues
* updated help texts on admin about page

= 0.2.2 (2012-11-18) =

* localization of date and time on the frontpage
* changed and localized date and time view in the admin event list table
* localization of date in the new event form

= 0.2.1 (2012-10-26) =

* changed field order and align in new/edit event form
* added datepicker for start and end date in new/edit event form
* improved multiday event selection in new/edit event form
* small changes in event table on admin page

= 0.2.0 (2012-09-29) =

* adapted menu names to wordpress standard (similar to posts and pages)
* adapted event list table admin page to wordpress standard layout
* used wordpress included table view for admin event table
* added sort functionality in admin event table
* added bulk action delete in admin event table
* added status messages for added, modified and deleted events on admin page

= 0.1.1 (2012-09-24) =

* fixed an issue with additional quotes after adding or editing an event
* fixed saving of wrong date when adding a new event
* fixed sorting of events when more events are at the same day
* added validation of data before saving to database

= 0.1.0 (2012-09-08) =

* Initial release
