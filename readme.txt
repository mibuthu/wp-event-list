=== Event List ===
Contributors: mibuthu
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W54LNZMWF9KW2
Tags: event, events, list, listview, calendar, schedule, shortcode, page, category, categories, admin, attribute, widget, sidebar
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 0.4.5
Plugin URI: http://wordpress.org/extend/plugins/event-list
Licence: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your events and show them in a list view on your site.


== Description ==

The purpose of this plugin is to to show a list of events with date, time, description, place, etc. on your site by using a shortcode or a widget.

= Current Features =
* Admin pages to view/create/manage/modify events
* Available event data fields: event title, event start time, event location, event details
* Beginning and end dates for multiple-day events
* Wordpress's WYSIWYG editor for the event details. So you can include styled text, links, images and other media in your event list.
* A duplicate function for events
* Event navigation to view only upcoming events or past/future events filtered by year
* Event categories
* Sync event categories with post categories

The event list can be placed in any page or post on your Wordpress site. Just include the following short code where you want the calendar to appear:

‘[event-list]’

You can modify the listed events and their style with attributes. All available attributes can be found on the Event List -> About page.
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

= Can I call the shortcode directly via php e.g. for my own template, theme or plugin? =
Yes, you can create an instance of the "SC_Event_List" class which located in "includes/sc_event-list.php" in the plugin folder and call the function show_html($atts).With $atts you can specify all the shortcode attributes you require. Another possibility would be to call the wordpress function "do_shortcode()".


== Screenshots ==

1. Admin page: Main page with the event list table
2. Admin page: New/edit event form
3. Admin page: Settings page (category tab)
4. Admin page: About page with help and shortcode attributes list
5. Admin page: Widget with the available options
6. Example page created with [event-list] shortcode


== Changelog ==

= 0.4.5 (2013-08-05) =

* added capability to sync the event categories with the post categories (manually or automatically)
* fixed problem with empty category list
* fixed link to category page in new event page
* fixed indention in in category parent combo box

= 0.4.4 (2013-07-20) =

* added support for sub-categories
* moved category administration to seperate page
* improved category sorting

= 0.4.3 (2013-07-05) =

* added possibility to edit existing categories
* added tooptip texts for the widget option
* changed css classes to differ between event-list-view and single-event-view
* added missing permission check for new events and about page
* do not change publish date and user when an event is modified
* fixed a small issue in info messages
* code improvements and cleanup in admin pages

= 0.4.2 (2013-06-09) =

* fixed links urls to events in eventlist-widget
* added option to show date only once per day

= 0.4.1 (2013-05-31) =

* fixed deleting of categories
* fixed url to calendar icon in new/edit event form
* fixed date format localization in new/edit event form
* added some widget options
* only show links in widget if all required info are available
* small security improvements

= 0.4.0 (2013-05-04) =

* added category support
* added settings page
* small changes in add/edit event admin page
* added settings page
* added option "no_event_text"
* execute shortcodes in event details field on front page
* change of plugin folder structure and file names
* small fixes in widget code

= 0.3.4 (2013-03-16) =

* fixed deleting of events
* removed link to not available settings page in about page
* changed parameter values from numbers to a more significant wording
* added the options 'event_list_only' and 'single_event_only' for the shortcode attributes 'show_nav', 'show_location', 'show_details' and 'link_to_event'
* added shortcode attribute details_length to truncate details

= 0.3.3 (2013-03-01) =

* fixed event creation/modification problem with php versions < 5.3
* improved truncate of details in admin event table

= 0.3.2 (2013-02-24) =

* removed empty settings page (will be added again when settings are available)
* fixed view of details in admin event table
* fixed adding or modifying events with alternative date formats
* only set time format in output if a known time format was entered

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
