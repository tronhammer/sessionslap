=== Session Slap ===
Contributors: smurray
Tags: session, keepalive, ping
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Session Slap mimics a keepalive ping by making an AJAX request to the current page to refresh active session timestamp.

== Description ==

This plugin will mimic a keep-alive ping by utilizing jQuery to perform an ajax request to the current page, which will update 
the session token tied to PilotPress and or any other system attached using PHP built in sessions. Session Slap will also provide 
a settings interface to allow admins to configure details relating to interval amount (which is defauled to 5 minutes less than the 
session.maxlifetime set in php.ini).

A few notes about the sections above:

*   Configuration options are available in the Settings -> Session Slap menu.
*   Updates built in PHP sessions only.
*   Attempts to access session.maxlifetime ini settings, but will default to 24 minutes if not available.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Unzip `sessionslap.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets 
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png` 
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.8.1 =
* Code consolodation where possible.
* Use INI settings for default interval amount.

= 1.7.1 =
* Settings API used for building out an intuitive Settings Page.

= 1.0.0 =
* Basic implementation utilizing jQuery.
