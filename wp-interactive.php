<?php
/*
Plugin Name: WP Interactive
Plugin URI: http://top-frog.com
Description: Interactively run PHP from the WordPress admin. *NIX required. THIS IS A DEVELOPER TOOL! If you're not careful you can really really break things.
Version: 0.5
Author: Shawn Parker
Author URI: http://top-frog.com 
*/

define('WPI_VERSION', '0.1');

include_once('lib/wp_interactive.class.php');
include_once('lib/wp_interactive_snippets.php');
include_once('lib/notices.php');
include_once('lib/wpi-debug-response.php');

// handle JS request quickly
if (!empty($_GET['wpi_action'])) {
	switch ($_GET['wpi_action']) {
		case 'wpi_js':
			header('content-type: text/javascript');
			$file = file_get_contents(trailingslashit(realpath(dirname(__FILE__))).'js/wpi.js');
			break;	
		case 'wpi_css':
			header('content-type: text/css');
			$file = file_get_contents(trailingslashit(realpath(dirname(__FILE__))).'css/wpi.css');
			break;
	}
	echo str_replace('self::BASENAME', wp_interactive::BASENAME, $file);
	exit;
}

function wp_interactive_init() {
	// interactive console
	if (is_admin()) {
		global $wp_interactive;
		$wp_interactive = new wp_interactive;
	}
}
add_action('init', 'wp_interactive_init');

?>