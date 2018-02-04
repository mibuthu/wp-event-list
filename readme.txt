=== Event List ===
Contributors: mibuthu, clhunsen
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W54LNZMWF9KW2
Tags: event, events, list, listview, calendar, schedule, shortcode, page, category, categories, filter, admin, attribute, widget, sidebar, feed, rss
Requires at least: 4.2
Tested up to: 4.9
Stable tag: 0.8.1
Plugin URI: http://wordpress.org/extend/plugins/event-list
Licence: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your events and show them on your site.


== Description ==

The purpose of this plugin is to to show a list of events with date, time, content, location, etc. on your site by using a shortcode or a widget.

= Current Features =
* Admin pages to view/create/manage/modify events
* Available event data fields: event title, event startdate, event enddate, event starttime, event location, event content
* Beginning and end dates for multi-day events
* Wordpress's WYSIWYG editor for the event content. So you can include styled text, links, images and other media in your events.
* A duplicate function for events to easier create similar event copies
* Import multiple events via csv files
* Event categories
* Sync event categories with post categories
* Filter events according to dates or categories
* Include an event feed in your site

= Usage: =
New events can be added in the WordPress admin area.

To display the events on your site simply insert the shortcode `[event-list]` into a page or post.
You can modify the listed events and their style with attributes. All available attributes can be found on the Event List -> About page in the Wordpress admin area.

Additionally there is also a widget available to show the upcoming events in your sidebar.

= Development: =
If you want to follow the development status have a look at the [git-repository on github](https://github.com/mibuthu/wp-event-list "wp-event-list git-repository").
Feel free to add your merge requests there, if you want to help to improve the plugin.

= Translations: =
Please help translating this plugin into multiple languages.
You can submit your translations at [transifex.com](https://www.transifex.com/projects/p/wp-event-list "wp-event-list at transifex").
There the source strings will always be in sync with the actual development version.


== Installation ==

The easiest version of installing is to go to the admin page. There you can install new plugins in the menu Plugins -> Add new. Search for "Event List" and press "Install now".

If you want to install the plugin manually download the zip-file and extract the files into your wp-content/plugins folder.


== Frequently Asked Questions ==

= How do I get an event list to show up in a Page or Post on my site? =
Insert the shortcode [event-list] in your page or post. You can modify the output by many available shortcode attributes (see Event List -> About page for more infos).

= How do I use styled text and images in the event content? =
Event List uses the built-in Wordpress WYSIWYG editor. It's exactly the same process like in creating Posts or Pages.

= Can I call the shortcode directly via php e.g. for my own template, theme or plugin? =
Yes, you can create an instance of the "SC_Event_List" class which is located in the plugin folder under "includes/sc_event-list.php" and call the function show_html($atts). With $atts you can specify all the shortcode attributes you require.
Another possibility would be to call the wordpress function "do_shortcode()".


== Screenshots ==

1. Admin page: Event-List table
2. Admin page: New/edit event form
3. Admin page: Event-List Categories
4. Admin page: Event-List Settings (general tab)
5. Admin page: Event-List Settings (frontend tab)
6. Admin page: Event-List Settings (admin page tab)
7. Admin page: Event-List Settings (feed tab)
8. Admin page: Event-List Settings (taxonomy tab)
9. Admin page: Event-List About page (general tab) with help and about page
10. Admin page: Event-List About page (shortcode tab) with shortcode attributes list and date range formats
11. Admin page: Event-List Widget with the available options
12. Example page with [event-list] shortcode
13. Example Event-List widget on frontpage


== Changelog ==

= 0.8.1 (2018-02-04) =
* added option to change events permalink slug
* added some additional upgrade check to improve the 0.8.0 upgrade
* fixed time display on frontpage
* fixed copy event function
* fixed issues with older php versions
* fixed issues with older WordPress versions

Attention:
This version fixes a lot of issues reported with the 0.8.0 version. It is now more safe to update, but in general the information provided in the changelog of 0.8.0 is still valid.
Also check the required manual changes provided there.

= 0.8.0 (2018-01-27) =
* switch plugin and data structure to use custom post types with custom metadata and wordpress categories (with all the advantages and new features provided for custom post types)
* huge rewrite of the plugin required to implement the above modification
* small css fixes

Attention:
The modifications in this versions are huge. This modifications will bring some huge improvements and is a good basis for future improvements (e.g. permalinks).
But inspite of a lot testing it doesn't eliminate any possibility for some regressions or problems during update process to the new data structure.

Due to this there are some steps you should consider before and after this upgrade:

* have a look at the support forum if there are issues reported with the new version, and wait with the upgrade until these are solved
* if you have a big productive site probably do not upgrade in the first days
* have a look at the PHP error file after upgrade, the upgrade function will write some informations regarding the upgrade process to this file
* check your shortcodes and widget and do the required manual changes provided below
* check your events, the event categories and the event output on the frontpage after the upgrade
* please report problems with the upgrade or issues/regressions after the upgrade in the support forum or on github

There are some manual changes required after the upgrade:

* renaming the shortcode attribute "show_details" to "show_content" in all shortcodes
* renaming the shortcode attribute "details_length" to "content_length" in all shortcodes
* renaming the shortcode attribute "collapse_details" to "collapse_content" in all shortcodes
* update your widget (goto Admin page -> Appearance -> Widget and "Safe" all event-list widgets)
* the following classes were renamed, adapt them in your custom CSS-styles if required: .start-date -> .startdate, .end-date -> .enddate, .event-details -> .event-content

= 0.7.12 (2017-10-09) =
* fixed some mature issues with older wordpress versions
* fixed event import for php version < 5.4
* fixed usage of html tags in event time, location and details
* fixed link to events in event-list widget

= 0.7.11 (2017-10-08) =
* more security improvments due to better sanitation of user inputs
* prepare additional strings for translations
* some code improvements

= 0.7.10 (2017-10-05) =
* fixed security vulnerability in admin category management
* general improvements of sanitation of all request parameters
* added number of events to the glance items in the dashboard
* parse shortcode in event feeds
* some changes and improvements in event feed
* fixed syncing of post categories

= 0.7.9 (2017-06-12) =
* fixed security vulnerability reported by WordPress
* fixed / improved time handling and sorting according to time (fixed sorting will only work in new or modified events)
* fixed problem with locale handling in older wordpress versions
* fixed url when going back from event details page to event list page with a drowdown filter
* fixed HTML format issue in admin event table (with not properly nested tag warning)

= 0.7.8 (2017-03-17) =
* improved datepicker style in new/edit event view
* show datepicker in correct language
* respect first day of week wordpress setting in the datepicker
* include event categories in the event feed
* added support for categories in event import file
* improvements and better error messages in event import
* fixed html tag handling truncate function
* splitted about page into 2 tabs
* changes in language file handling, additional option to define which language file shall be loaded first
* some improvements in the language files itself
* prepare more help strings for translation
* updated translations and added more german translations
* moved screenshots to assets folder to reduce download size
* changed mimimum required wordpress version to 3.8

= 0.7.7 (2017-01-13) =
* replaced custom admin menu icon with wordpress integrated icon for a consistent styling
* added support to truncate text via css to exactly 1 line (length='auto')
* added a link to the event at the ellipsis of a truncated details text
* improved code for deleting of events
* fixed some issues with the bulk action menu and button in admin event table
* improved security for external links
* updated translations

= 0.7.6 (2015-12-13) =
* added shortcode attribute "collapse_details"
* correct handling of "more"-tag in event details
* show "url_to_page" shortcode attribute in the documentation
* fixed wrong date format in events import sample
* some help texts improvements
* updated translation de_DE (78%) and fi_FI (35%)
* added italian translation it_IT (69%)
* added portuguese translation pt_BR (58%)
* added dutch translation nl_NL (46%)
* added spanish translation es_ES (39%)
* added spanish translation es_AR (18%)
* added frensh translation fr_FR (0%)
* Thanks to all translators at transifex!

= 0.7.5 (2015-07-19) =
* added support for transifex localization platform
* added sorting option (see initial_order shortcode option)
* added relative date format for weeks
* added import option to set date format in import file
* several fixes and improvements in truncate function
* some import improvements
* set standard import date format to mysql dateformat
* some speed improvements
* updated some dates and daterange helptexts and added german translations
* added finnish translation (thanks to jvesaladesign)

= 0.7.4 (2015-05-16) =
* fixed allowed daterange for datepicker with custom date formats
* added option to disable event-list.css
* added option to set considered daterange for multiday event

= 0.7.3 (2015-05-15) =
* added csv import functionality
* added relative and special date selection options for date filter
* changed required permission to view/edit category admin page
* added some missing translation functions
* added some more german translations
* only allow valid dates (>= 1.1.1970)
* only load some data on pages where they are required

= 0.7.2 (2015-03-21) =
* fixed an issue with multiday events when deleting a category
* fixed displaying the category slug instead of the category name in event listing
* fixed sub-category handling of deleted categories
* fixed sub-category handling when a category slug is changed
* fixed parent selection list in category edit mode
* some helptext fixes

= 0.7.1 (2015-02-01) =
* added options for month filterbar item
* only show years, months and cats with events in filterbar (acc. to available events and date/cat filter
* fixed event-list feed
* changed textdomain for translations to event-list
* some small code and html fixes
* some code improvements

= 0.7.0 (2014-12-22) =
* initial multilanguage support
* German translation (not complete yet)
* Unicode support in truncate function
* Changed position of admin menu
* Changed icon in admin menu

= 0.6.9 (2014-11-09) =
* added months option in filterbar items
* added a class for each category slug in each event li element
* fixed error due to wrong function name when using daterange in date filter

= 0.6.8 (2014-10-14) =
* added filterbar item "daterange" (to view all, upcoming and past)
* added options to change feed name and feed description
* added "Duplicate" and "Add new" button in edit event view
* corrected embedding of feed (should solve some problems and increase speed)
* corrected view of event details for single day events
* changed standard value for feed link in html head to true

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
