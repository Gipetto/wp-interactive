<?php
/*
Plugin Name: WP Interactive
Plugin URI: http://top-frog.com
Description: Interactively run PHP from the WordPress admin. *NIX required. THIS IS A DEVELOPER TOOL! If you're not careful you can really really break things.
Version: 1.0.2
Author: Shawn Parker
Author URI: http://top-frog.com 
*/

/**
 * Copyright (c) 2011 Shawn Parker. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

define('WPI_VERSION', '1.0.1');

include_once('lib/wp_interactive.class.php');
include_once('lib/wp_interactive_snippets.php');
#include_once('lib/VariableStream.php'); // keeping for possible future use
#include_once('lib/wpi-debug-response.php'); // a nice thought, might still use this
#include_once('lib/notices.php'); // there's also some functionality in here for setting and viewing notices, play with it if you like, its almost ready

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