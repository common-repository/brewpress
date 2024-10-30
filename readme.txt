=== BrewPress - Brewing with WordPress ===
Author URI: http://brewpress.beer
Plugin URI: http://brewpress.beer
Contributors: brewpress
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 1.0.4
Requires PHP: 5.3
License: GNU Version 2 or Any Later Version
Tags: brewing, beer, brew, raspberry pi, homebrew, automation, automate, iot

Brew beer using WordPress!

== Description ==

BrewPress is a brewery controller that runs on the Raspberry Pi and allows you to brew beer using WordPress.

= <a href="https://brewpress.beer/demo/">TRY THE DEMO</a> =

> You can run BrewPress on any WordPress website to test and to create 'dummy' batches. To enable the control of physical heating elements and pumps, it needs to be run on a WordPress installation on the Raspberry Pi. 

= <a href="https://brewpress.beer/docs/getting-started/">Getting Started</a> ~ <a href="https://brewpress.beer/docs/">Docs</a> ~ <a href="https://brewpress.beer/extensions/">Extensions</a> =

Features of BrewPress:

* Fully automated brewing
* Step by Step automation
* Configure multiple DS18B20 temperature probes
* Configure multiple kettles & heating elements
* Configure multiple pumps
* Full temperature logging of all batches
* Create unlimited Batches
* Fully translatable

There is also an official [BrewPress theme](https://brewpress.beer/extensions/brewpress-theme) available to download for free. The theme is purpose built for the BrewPress plugin and contains lots of options to allow you to fully customize the theme without touching any code.

== Installation ==

1. Activate the plugin
2. Run the wizard
3. Visit the Settings page and configure options
4. Start creating batches and brewing beer

= Minimum Requirements =

* PHP version 5.3 or greater (PHP 5.6 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)
* BrewPress requires WordPress 4.4+

== Frequently Asked Questions ==

= Where can I find documentation? =

Docs can be found at [brewpress.beer/docs](https://brewpress.beer/docs/)

== Screenshots ==

1. The default Brewing page.
2. A brew session with Debug Mode active.
3. A paused brew session with Debug Mode active.
4. A brew session with the Black color scheme.
5. Dashboard.
6. Adding a new batch.
7. Settings page.
8. Settings page with Black color scheme.
9. Viewing all batches.

== Changelog ==

= 1.0.4 - 2018-08-03 =
* NEW - Add translation for en_AU
* UPDATE - Update main plugin file domain path and add donate link

= 1.0.3 - 2018-08-02 =
* NEW - Add 'brewpress_all_batches_page' function
* NEW - Add 'brewpress_get_sensor_vessel' function
* UPDATE - Add 'brewpress_get_temp_log_before_logging' action within the sse.php file
* FIX - Temp logging was commented out in sse.php. Remove the comments
* FIX - Error with zero values on dashboard page using array_sum and min. max functions
* FIX - Add 'if brewpress_testing' statements within class-switching.php file.

= 1.0.2 - 2018-08-02 =
* UPDATE - Add filters and actions to settings page to allow custom messages and a custom action before save.

= 1.0.1 - 2018-08-01 =
* FIX - Only force login on pages that have a BrewPress shortcode active. This allows proper testing on existing WP sites.

= 1.0.0 - 2018-07-31 =
* Release - Initial release