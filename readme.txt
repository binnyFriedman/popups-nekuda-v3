=== Popups Nekuda ===
Contributors: nekuda
Tags: popup, modal, exit intent, marketing, conversion
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, lightweight popup system for WordPress. Zero external dependencies on the frontend.

== Description ==

Popups Nekuda is a clean, modern popup plugin designed for performance and ease of use.

**Key Features:**

* **Zero frontend dependencies** - No jQuery required, pure vanilla JavaScript
* **Exit Intent & Timeout triggers** - Show popups based on user behavior
* **Desktop/Mobile content** - Customize content for different devices
* **Slideshow support** - Multiple slides with auto-advance
* **Display rules** - Show/hide on specific pages, posts, categories
* **Cookie control** - Control how often popups appear
* **Scheduling** - Set start and end dates for campaigns
* **Accessible** - Full keyboard navigation and screen reader support
* **RTL support** - Works great with right-to-left languages

**Perfect for:**

* Marketing campaigns
* Newsletter signups
* Special announcements
* Cookie consent notices
* Exit-intent offers

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/popups-nekuda`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Popups' in your admin menu to create your first popup

== Frequently Asked Questions ==

= Does this plugin slow down my site? =

No! Assets are only loaded on pages where popups are displayed, and the JavaScript is lightweight with zero dependencies.

= Can I show different content on mobile? =

Yes! Each popup has separate Desktop and Mobile content tabs. If you don't set mobile content, it will use the desktop content as a fallback.

= How do I target specific pages? =

Use the Include/Exclude rules in each popup. You can target by page, post, category, or post type.

= Can I have multiple slides in a popup? =

Yes! Use the "Add Slide" button to create a slideshow that auto-advances every 5 seconds.

== Screenshots ==

1. Popup editor with Desktop/Mobile tabs
2. Trigger and cookie settings
3. Display rules configuration
4. Frontend popup example

== Changelog ==

= 3.0.0 =
* Complete rewrite with modern architecture
* Added desktop/mobile content separation
* Added slideshow support with auto-advance
* Added pause button for accessibility
* Improved display rules with grouped search results
* Zero frontend dependencies (removed jQuery requirement)
* Better accessibility with focus trapping and keyboard navigation
* New prefixed post type to avoid conflicts

= 2.0.0 =
* Added exit intent trigger
* Added scheduling feature
* Improved admin UI

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 3.0.0 =
Major update with new features and improved performance. Existing popups will be automatically migrated to the new post type.

