<?php
/*
Plugin Name: Warning! Deprecated!
Plugin URI: http://top-frog.com
Description: Get custom messages and notices as you browse the site. <b>Only works for site admins. File paths may not translate on Winders servers.</b> :shrug:
Version: 1.0
Author: Shawn Parker
Author URI: http://top-frog.com/wp-dev-notices
*/

/**
 * Copyright (c) 2010 Shawn Parker. All rights reserved.
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

define('WPI_DEBUG', false);
define('WPI_PLUGIN_DIR', trailingslashit(realpath(dirname(dirname(__FILE__)))));

wp_enqueue_script('jquery');

global $wpi_deprecated_log, $wpi_messages_log, $wpi_auto_expand;
$wpi_deprecated_log = $wpi_messages_log = array();
$wpi_auto_expand = false;

// Auto Expand

	function wpi_set_auto_expand($state) {
		global $wpi_auto_expand;
		return $wpi_auto_expand = (bool) $state;
	}

// Messages

	function wpi_add_message($message, $level = 'notice', $auto_expand = null) {
		if (!empty($auto_expand)) {
			$auto_expand = (bool) $auto_expand;
			wpi_set_auto_expand($auto_expand);
		}
		
		$backtrace = debug_backtrace();
		$file_args = wpi_extract_backtrace($backtrace, 'current', '|^(wpi_add_message)|');
		$file_args['_func'] = $backtrace[1]['function'];
		extract($file_args);
		
		wpi_log_item(compact('message', 'level', '_file', '_line', '_func'), 'message');
	}

// Deprecated Functions
	function wpi_log_deprecated_function($function, $replacement, $version) {
		$type = 'function';

		$file_args = wpi_extract_backtrace(debug_backtrace());
		extract($file_args);

		wpi_log_item(compact('type', 'function', 'replacement', 'version', '_file', '_line'), 'deprecated');
	}
	add_action('deprecated_function_run', 'wpi_log_deprecated_function', 10, 3);

// Deprecated Files
	function wpi_log_deprecated_files($file, $replacement, $version, $message) {
		$type = 'file';
	
		$file_args = wpi_extract_backtrace(debug_backtrace());
		extract($file_args);
	
		wpi_log_item(compact('type', 'file', 'replacement', 'version', 'message', '_file', '_line'), 'deprecated');
	}
	add_action('deprecated_file_included', 'wpi_log_deprecated_files', 10, 4);

// Deprecated Argument
	function wpi_log_deprecated_argument($function, $message, $version) {
		$type = 'argument';

		// gleaned from Nacin's log-deprecated-notices plugin
		$backtrace = debug_backtrace();
		switch ($function) {
			case 'define()':
			case 'define':
				$file_args = wpi_extract_backtrace($backtrace, 'current');
				break;
			case 'has_cap':
				$file_args = wpi_extract_backtrace($backtrace, 'next', '|^(current_user_can)|');
				$function = $file_args['_func'];
				break;
			default:
				$file_args = wpi_extract_backtrace($backtrace);
		}
		extract($file_args);
	
		wpi_log_item(compact('type', 'function', 'message', 'version', '_file', '_line'), 'deprecated');
	}
	add_action('deprecated_argument_run', 'wpi_log_deprecated_argument', 10, 3);

// Utility

	/**
	 * Strip ABSPATH from the file path for more concise output
	 * Known to work on OS X & Linux
	 *
	 * @param string $path 
	 * @return string
	 */
	function wpi_truncate_path($path) {
		return str_replace(trailingslashit(ABSPATH), '', $path);
	}

	/**
	 * Fish out the relevant lines from the debug backtrace
	 *
	 * @param array $backtrace 
	 * @param string $from 
	 * @param string $find 
	 * @return array
	 */
	function wpi_extract_backtrace($backtrace, $from = 'next', $find = '|^(_deprecated)|') {
		$halt_on_next = $file = $line = false;
		foreach ($backtrace as $step) {
			if ($halt_on_next) {
				$_func = $step['function'];
				$_file = wpi_truncate_path($step['file']);
				$_line = $step['line'];
				break;
			}
		
			if (preg_match($find, $step['function'])) {
				if ($from == 'next') {
					$halt_on_next = true;
				}
				elseif ($from == 'current') {
					$_func = $step['function'];
					$_file = wpi_truncate_path($step['file']);
					$_line = $step['line'];
					break;
				}
			}
		}
	
		// last ditch effort. Janky? Yes. Functional? Mostly. 
		if (empty($_line) && $find != '|^(_deprecated)|') {
			$file_args = wpi_extract_backtrace($backtrace);
			extract($file_args);
		}
	
		return compact('_file', '_line', '_func');
	}

// Log

	/**
	 * Simple central function for putting together the log array
	 *
	 * @param array $array 
	 * @return int - 0 indicates failure
	 */
	function wpi_log_item($array, $type = 'deprecated') {
		global $wpi_deprecated_log, $wpi_messages_log;
		switch ($type) {
			case 'deprecated':
				$ret = array_push($wpi_deprecated_log, $array);
				break;
			case 'message':
				$ret = array_push($wpi_messages_log, $array);
				break;
		}
		return $ret;
	}

// Output

	/**
	 * Add actions for site administrators only
	 *
	 * @return void
	 */
	function wpi_init() {
		if (current_user_can('activate_plugins')) {
			add_action('wp_footer', 'wpi_deprecated_footer_output');
			add_action('admin_footer', 'wpi_deprecated_footer_output');
		}
	}
	add_action('init', 'wpi_init');

	function wpi_message_table_row($item) {
		if (!empty($item['function']) && !preg_match('|\(\)$|m', $item['function'])) {
			$item['function'] .= '()';
		}
		return '
					<tr>
						<td><span class="wpi-'.strtolower($item['level']).'">'.__(ucfirst($item['level']), 'wp-dev-notices').'</span></td>
						<td>'.__('Function:', 'wp-dev-notices').' '.$item['_func'].'() <br />
							'.__('In file:', 'wp-dev-notices').' '.$item['_file'].'<br />
							'.__('On line:', 'wp-dev-notices').' '.$item['_line'].'</td>
						<td class="wpi-messg"><div>'.$item['message'].'</div></td>
					</tr>';
	}

	/**
	 * Our Janky Output
	 * Only outputs if there's something to display
	 *
	 * @return void
	 */
	function wpi_deprecated_footer_output() {
		global $wpi_deprecated_log, $wpi_messages_log, $wpi_auto_expand;
	
		echo '
		<div id="wpi-banner">
			<div id="wpi-output" class="wpi-shaded" style="display: none;">
				<div id="wpi-output-wrap">

					<h2>'.__('Messages', 'wp-dev-notices').'</h2>
					<table id="wpi-message-table">
						<thead>
							<tr>
								<th class="wpi-col-lvl">'.__('Level', 'wp-dev-notices').'</th>
								<th class="wpi-col-loc">'.__('Location', 'wp-dev-notices').'</th>
								<th class="wpi-col-messg">'.__('Message', 'wp-dev-notices').'</th>
							</tr>
						</thead>
						<tbody>';
		if (count($wpi_messages_log)) {
			foreach ($wpi_messages_log as $item) {
				echo wpi_message_table_row($item);
			}
		}
		echo '
						</tbody>
					</table>';			
		
		if (count($wpi_deprecated_log)) {
			echo '
					<h2>'.__('Deprecated Items', 'wp-dev-notices').'</h2>
					<table id="wpi-deprecated-table">
						<thead>
							<tr>
								<th class="wpi-col-type">'.__('Type', 'wp-dev-notices').'</th>
								<th class="wpi-col-loc">'.__('Location', 'wp-dev-notices').'</th>
								<th class="wpi-col-repl">'.__('Replacement', 'wp-dev-notices').'</th>
								<th class="wpi-col-vers">'.__('Version', 'wp-dev-notices').'</th>
							</tr>
						</thead>
						<tbody>';
			foreach ($wpi_deprecated_log as $item) {
				if (!empty($item['function']) && !preg_match('|\(\)$|m', $item['function'])) {
					$item['function'] .= '()';
				}
				echo '
							<tr>
								<td>'.__($item['type'], 'wp-dev-notices').'</td>
								<td>'.(!empty($item['function']) ? __('Function:', 'wp-dev-notices').' '.$item['function'] : __('File:', 'wp-dev-notices').' '.$item['file']).'<br />
									'.__('In file:', 'wp-dev-notices').' '.$item['_file'].'<br />
									'.__('On line:', 'wp-dev-notices').' '.$item['_line'].'</td>
								<td>'.(!empty($item['replacement']) ? $item['replacement'] : '').(!empty($item['message']) && !empty($item['replacement']) ? '<br />' : '').(!empty($item['message']) ? $item['message'] : '').'</td>
								<td>'.(!empty($item['version']) ? $item['version'] : '').'</td>
							</tr>';
			}
			echo '
						</tbody>
					</table>';
		}
		
		echo '
				</div>
			</div>
			<div class="wpi-notice wpi-shaded">
				<p><img class="wpi-warning-icon" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAkRJREFUeNqMU01rE1EUPW9m0tgk/TTRlrS0RRFxp0ixqCvdqOBK6D+oWPo3dONWWuq6m4IrF24siFAUC7pQ0CwiSls1NqZJbfPVZOZ67swrRqTigzMz795zbs6798V8xF+ri5ghbhFTNvaSeEQ8JPY7ySb3p3gGDhaTZxz0jwsSGQmDjaLBzmeD3fcBJMBtWygq8Pa3+I5JYH70qkHjh4P2hoNYyQ3tSDqAGfHhZQKsrwB+VWYZXggLrB3YdtCcuGZQpnAv52LQGPQRCaImgh3CPe0jOeoj/4TOAsT1OE5DLdJ67IRBsWCw/sFhDmjbw6p43+6/M7ddcNB90oQa1To1JQHTPcOCLxsGraggqhTu2l/Wt+41XiAnNRxpVOvVaZFrMp5ksgycWl2NGmfRuWJa4PJFjCcA6iY15tVsskWf2vNsNot/rTxZzWboIBynV43ir8tlTCVTwMrYGNRTks5c7TKiwr49VqoXqFT4DbxToVPng1jeZAMHBqK563PPnr9i37rXuHKUq5p6RxMXcgVBPCE4PiSH2teccpSrGtW6l2iVI/LZ/a2vFdyYGDI4wgmLcCKtSJhgg4+mBd09wIs8HfuYpeaVjta9wAJ3yX4OvDkbmK1P27jeFTMY7AdGOK50msV4Hb/x0GubHKePuXsii+RDtZ79Z2i/MvdFnvUKzt0sYbqvhCvUnQ8nxOLs29PHIss/Ac4Ax4gitW3Pujy4fEJCa0lkid9Lh7RCLDdQrZmLLhIeiHj2rvzPalGnRfBLgAEAS44AhDycvGwAAAAASUVORK5CYII=" alt="Danger!" /><b class="warning">'.__('Warning', 'wp-dev-notices').'</b> <a href="#wpi-toggle" class="wpi-toggle-output">&raquo; <span>'.__('Show', 'wp-dev-notices').'</span> '.__('Notices', 'wp-dev-notices').'</a></p>
			</div>
		</div>
		<script type="text/javascript">
			show_text = "'.__('Show', 'wp-dev-notices').'";
			hide_text = "'.__('Hide', 'wp-dev-notices').'";
			auto_expand = '.($wpi_auto_expand ? 'true' : 'false').';
			'.file_get_contents(WPI_PLUGIN_DIR.'js/notices.js').'
		</script>
		<style type="text/css">
			'.file_get_contents(WPI_PLUGIN_DIR.'css/notices.css').'
		</style>';
	}
	
// Output dump helpers

	/**
	 * Output helper to nicely format variable types of data for messages
	 *
	 * Types:
	 *  - print: does a `print_r` of the data
	 *  - export: does a `var_export` of the data
	 *  - dump: does a `var_dump` of the data via an output buffer
	 * 
	 * @param mixed $data 
	 * @param string $type
	 * @param string $tag - tag to wrap output with, leave empty for no wrapper
	 * @return string - formatted data output
	 */
	function wpi_dump($data, $type = 'print', $tag = 'pre') {
		switch ($type) {
			case 'export':
				$data = var_export($data, true);
				break;
			case 'dump':
				ob_start();
				var_dump($data);
				$data = ob_get_clean();
				break;
		 	case 'print':
			default:
				$data = print_r($data, true);
				break;
		}
		
		if (!empty($tag)) {
			$data = '<'.$tag.'>'.$data.'</'.$tag.'>';
		}
		
		return $data;
	}
	
// Enable Tests on Debug 

	if (defined('WPI_DEBUG') && WPI_DEBUG) {
		include_once(WPI_PLUGIN_DIR.'tests/tests.php');
	}
?>