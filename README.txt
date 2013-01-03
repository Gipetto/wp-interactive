=== WP Interactive ===
Contributors: shawnparker
Donate link: http://top-frog.com/donate
Tags: wordpress, interactive, php, development, theme, plugin, console
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.0.2
 
This plugin allows admin users to run PHP code directly via the WordPress Admin to assist with development of plugins and themes.


== Description ==

The PHP code is captured using the `admin-ajax` hooks and executes within a fully baked WordPress scope. All WordPress functions are available for use.

**This plugin is NOT recommended for production environments.**

- Adds `WP Interactive` submenu in the `Tools` menu
- Allows user to enter PHP code to be directly executed inside a WordPress scoped environment
- Users can add common snippets for re-use using the `wpi-snippets` filter


== Installation ==

Installation is sorta easy:

1. Clone the WP Interactive plugin on your server using Git
1. Init submodules on the checkout
1. Active the plugin in WordPress

== Frequently Asked Questions ==

= Will you hate me if I put this on a Production Server? =

Yes.

= Will it work properly on IIS or Windows based servers? =

I have no idea! There's some file path translation stuff that'll probably fail. I don't personally develop on Windows but will make any adjustments as needed if someone wants to test and submit any necessary fixes.


== Screenshots ==

1. WP Interactive in action.
2. WP Interactive reporting a parsing error in the supplied code.


== Props ==

Syntax highlighting is provided by [CodeMirror](http://codemirror.net/)


== Known Issues ==

- Deleting posts with the Google Sitemaps plugin enabled will trigger a crap-ton of notices. I just haven't had it in me to track these down yet.


== Changelog ==
= 1.0.2 =
* Work with the latest CodeMirror
* Change CodeMirror submodule as the old one no longer exists

= 1.0.1 =
* Consistency tweaks
* Prepping for Windows compat (should anyone ever want to try)

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0 =
* Initial release