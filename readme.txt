=== Event List ===
Contributors: mibuthu
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W54LNZMWF9KW2
Tags: event, events, list, listview, calendar, schedule, shortcode, page, category, categories, filter, admin, attribute, widget, sidebar, feed, rss
Requires at least: 3.3
Tested up to: 3.9.1
Stable tag: 0.6.7
Plugin URI: http://wordpress.org/extend/plugins/event-list
Licence: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your events and show them on your site.


== Description ==

The purpose of this plugin is to to show a list of events with date, time, description, place, etc. on your site by using a shortcode or a widget.

= Current Features =
* Admin pages to view/create/manage/modify events
* Available event data fields: event title, event date, event start time, event location, event details
* Beginning and end dates for multi-day events
* Wordpress's WYSIWYG editor for the event details. So you can include styled text, links, images and other media in your events.
* A duplicate function for events to easier create similar event copies
* Event categories
* Sync event categories with post categories
* Filter events according to dates or categories
* Include an event feed in your site

The event list can be placed in any page or post on your Wordpress site. Just include the following short code where you want the events to appear:

‘[event-list]’

You can modify the listed events and their style with attributes. All available attributes can be found on the Event List -> About page.
There is also a widget available to view the upcoming events in a sidebar with many options.

If you want to follow the development status have a look at the [git-repository on github](https://github.com/mibuthu/wp-event-list "wp-event-list git-repository").


== Installation ==

The easiest version of installing is to go to the admin page. There you can install new plugins in the menu Plugins -> Add new. Search for "Event List" and press "Install now".

If you want to install the plugin manually download the zip-file and extract the files into your wp-content/plugins folder.


== Frequently Asked Questions ==

= How do I get an event list to show up in a Page or Post on my site? =
Insert the shortcode [event-list] in your page or post. You can modify the output by many available shortcode attributes (see Event List -> About page for more infos).

= How do I use styled text and images in the event descriptions? =
Event List uses the built-in Wordpress WYSIWYG editor. It's exactly the same process like in creating Posts or Pages.

= Can I call the shortcode directly via php e.g. for my own template, theme or plugin? =
Yes, you can create an instance of the "SC_Event_List" class which is located in the plugin folder under "includes/sc_event-list.php" and call the function show_html($atts).With $atts you can specify all the shortcode attributes you require.
Another possibility would be to call the wordpress function "do_shortcode()".


== Screenshots ==

1. Admin page: Event list table
2. Admin page: New/edit event form
3. Admin page: Categories
4. Admin page: Settings (general tab)
5. Admin page: Settings (feed tab)
6. Admin page: About page with help and shortcode attributes list
7. Admin page: Widget with the available options
8. Example page created with [event-list] shortcode


== Changelog ==

= 0.6.7 (2014-06-18) =
* added month and day support in date_filter
* added available date and date range formats description in admin about page
* added support for wpautop in event details
* added "Past" entry in admin event table filter

= 0.6.6 (2014-06-16) =
* added date_filter shortcode option
* added option to change text for filterbar reset item
* added option "years_order" for years filterbar element
* preparations to include more date filters / filterbar elements in future versions
* small css style modification
* updated some help texts

= 0.6.5 (2014-04-26) =
* added shortcode attribute "initial_event_id"
* added an option to only show umpcoming events in the feed
* fixed a problem in truncate function

= 0.6.4 (2014-02-10) =
* fixed css file inclusion for shortcodes with parameters
* fixed css file inclusion for shortcodes outside post content (e.g. theme template)

= 0.6.3 (2014-02-09) =
* added options to allow html tags in event time and location
* fixed date check for PHP version 5.2
* fixed edit view after adding a new event
* strip slashes in event time field
* fixed url to event-list css file
* only load event-list css if required

= 0.6.2 (2014-02-01) =
* complete rewrite of date handling in new/edit event form and date validation
* added option to change date format in event new/edit form
* fixed a css issue in the filterbar hlist when list-style-image is used in the theme
* some css fixes in admin settings page
* some html output code style fixes

= 0.6.1 (2014-01-03) =
* fixed redirect issue in admin event table
* fixed a bug in filterbar javascript
* fixed a problem with wrong format of deatails in admin event table
* changed button text for event update from "Publish" to "Update"
* show required manual widget update message on the frontpage only to users with required privileges

= 0.6.0 (2013-12-31) =
* added adjustment options for the filterbar (shortcode attribute "filterbar_items")
* added "All" and "Past" options to years filter in filterbar
* added category filter option in filterbar
* change "cat_filter" behavior with additional features
* added option to display categorys/years filter in a dropdown
* added reset option to filterbar
* added category filter selection in admin event table
* added shortcode attribute "initial_date"
* renamed shortcode attribute "show_nav" into "show_filterbar"
* renamed url parameter "ytd" to "years"
* removed underline before event-list id in url
* some css fixes
* some help text updates

Attention:
In this version some of the shortcode attributes and the behavior of some existing attributes have changed and are not compatible with the old version! Please check all your shortcodes after the update.
Additionally the url parameter has changed. So if you are using existing links to an eventlist with parameters you have to update them.
Also existing widgets must be updated after plugin upgrade. Please visit the widget admin page and press save for all evenlist wigets.

= 0.5.2 (2013-11-09) =

* added number of events in Right Now dashboard widget
* fixed some css issues

= 0.5.1 (2013-10-27) =

* added site name in eventlist feed name (similar to standard feed captions)
* fixed not working feed link in header
* fixed problem with new widget options after upgrade
* fixed not working permalink for the eventlist feed

= 0.5.0 (2013-10-26) =

* added event feed with a lot of options
* added widget option for cat filter

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
