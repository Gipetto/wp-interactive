<?php

class wp_interactive {
	const BASENAME = 'wp-interactive';
	const CODEMIRROR = 'CodeMirror2';
	
	protected $tmpfile = '/tmp/php-eval.php';	// fallback on unix if uploads not writable
	protected $errors;
	protected $old_error_reporting;
		
	public function __construct() {
		$this->base_path = trailingslashit(WP_PLUGIN_DIR).'wp-interactive';
		$this->base_url = trailingslashit(WP_PLUGIN_URL).'wp-interactive';
		$this->lib_url = $this->base_url.'/lib';

		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('wp_ajax_wp_interactive', array($this, 'ajax_handler'));
		add_action('tool_box', array($this, 'tool_box'));
		
		if (!empty($_GET['page']) && $_GET['page'] == self::BASENAME) {
			wp_enqueue_script('codemirror', $this->lib_url.'/'.self::CODEMIRROR.'/lib/codemirror.js', array(), WPI_VERSION);
			wp_enqueue_style('codemirror', $this->lib_url.'/'.self::CODEMIRROR.'/lib/codemirror.css', array(), WPI_VERSION);		
		
			$resources = array(
				'xml' => array(
					'requires' => 'codemirror',
					'has' => array('js', 'css')
				),
				'javascript' => array(
					'requires' => 'codemirror-xml',
					'has' => array('js', 'css')
				),
				'css' => array(
					'requires' => 'codemirror-xml',
					'has' => array('js', 'css')
				),
				'clike' => array(
					'requires' => 'codemirror-xml',
					'has' => array('js', 'css')
				),
				'php' => array(
					'requires' => 'codemirror-xml',
					'has' => array('js')
				)
			);
			
			foreach ($resources as $type => $config) {
				foreach ($config['has'] as $r_type) {
					switch ($r_type) {
						case 'js':
							wp_enqueue_script('codemirror-'.$type, $this->lib_url.'/'.self::CODEMIRROR.'/mode/'.$type.'/'.$type.'.js', array($config['requires']), WPI_VERSION);
							break;
						case 'css':
							wp_enqueue_style('codemirror-'.$type, $this->lib_url.'/'.self::CODEMIRROR.'/mode/'.$type.'/'.$type.'.css', array($config['requires']), WPI_VERSION);
							break;
					}
				}
			}

			wp_enqueue_script('wp-interactive-js', '/index.php?wpi_action=wpi_js', array('jquery', 'codemirror'), WPI_VERSION);
			wp_enqueue_style('wp-interactive-css', '/index.php?wpi_action=wpi_css', array(), WPI_VERSION, 'all');
		}
	}
	
	public function admin_menu() {
		$this->menu_item_id = add_submenu_page('tools.php', __('WP Interactive', self::BASENAME), __('WP Interactive', self::BASENAME), 'manage_options', self::BASENAME, array($this, 'admin_page'));
		$contextual_help = $this->load_view('contextual-help');
		add_contextual_help($this->menu_item_id, $contextual_help);
	}
	
// Admin page

	public function admin_page() {
		echo $this->load_view('admin-page');
	}
	
	
	public function tool_box() {
		if (current_user_can('manage_options')) {
			echo $this->load_view('tool-box');
		}
	}

// Ajax
	
	public function ajax_handler() {
		if (!empty($_POST['wpi_action'])) {
			$method = $_POST['wpi_action'];
			
			if (method_exists($this, $method)) {
				$ret = $this->$method();
			}
			
			$this->return_result($ret);
		}
	}

// Core
	
	protected function process() {
		$code = stripslashes($_POST['code']);
		$eval = $message = null;
		
		$uploads = wp_upload_dir();
		if (is_writable($uploads['basedir'])) {
			$this->tmpfile = trailingslashit($uploads['basedir']).'php-eval.php';
		}		
		file_put_contents($this->tmpfile, $code);

		if (PHP_SHLIB_SUFFIX == 'so') {
			// unix
			$ret = `/usr/bin/env php -l < $this->tmpfile 2>&1`;
		}
		else {
			// win, not sure if this is even right, maybe a win user can halp?
			$ret = `php.exe -l $this->tmpfile`;
		}
		
		if (strpos($ret, 'Errors parsing') !== false) {
			unlink($this->tmpfile);
			return array(
				'success' => false,
				'eval' => __('NULL (see error report)', self::BASENAME),
				'message' => nl2br($ret)
			);
		}
				
		$this->set_error_handler();
		
		ob_start();
		include($this->tmpfile);
		$eval = ob_get_clean();
		$eval = htmlentities($eval);
		
		unlink($this->tmpfile);
		$this->restore_error_handler();

		if (empty($this->errors)) {
			$success = true;
		}
		else {
			$success = false;
			$message .= '';
			foreach ($this->errors as $error) {
				$message .= $error['errno'].': '.$error['errstr'].' in '.$error['errfile'].' on line '.$error['errline'].PHP_EOL;
			}
		}
		
		return compact('success', 'eval', 'message');
	}
	
	protected function return_result($result) {
		header('content-type: text/javascript');
		$json = json_encode($result);
		if (json_last_error() != JSON_ERROR_NONE) {
			$json_errors = array(
			    JSON_ERROR_NONE => __('No error has occurred', self::BASENAME),
			    JSON_ERROR_DEPTH => __('The maximum stack depth has been exceeded', self::BASENAME),
			    JSON_ERROR_CTRL_CHAR => __('Control character error, possibly incorrectly encoded', self::BASENAME),
			    JSON_ERROR_SYNTAX => __('Syntax error', self::BASENAME),
				JSON_ERROR_UTF8 => __('Malformed UTF-8 characters, possibly incorrectly encoded', self::BASENAME)
			);
			error_log(__METHOD__.' - '.__('JSON DECODE ERROR:', self::BASENAME).' '.$json_errors[json_last_error()]);
		}
		echo $json;
		exit;
	}

	protected function set_error_handler() {
		$this->old_error_reporting = ini_get('error_reporting');
		error_reporting(-1);
		set_error_handler(array($this, 'handle_errors'));
	}
	
	protected function restore_error_handler() {
		ini_set('error_reporting', $this->old_error_reporting);
		restore_error_handler();
	}

	public function handle_errors($errno, $errstr, $errfile, $errline) {
		switch ($errno) {
			case E_USER_ERROR:
				$this->return_result(array(
					'success' => false,
					'eval' => __('NULL (see error reoprt)', self::BASENAME),
					'message' => $errno.': '.$errstr.' in '.$errfile.' on line '.$errline.PHP_EOL
				));
				break;
			default:
				$this->errors[] = compact('errno', 'errstr', 'errfile', 'errline');
				break;
		}
		return true;
	}

// Utils

	public function load_view($viewfile, $params = array()) {
		$ret = '';
		$view_filename = $this->base_path.'/views/'.$viewfile.'.php';
		
		if (is_file($view_filename)) {
			ob_start();
			extract($params);
			include($view_filename);
			$ret = ob_get_clean();
		}
		
		return $ret;
	}
	
	public function get_snippets() {
		$snippets = apply_filters('wpi-snippets', $GLOBALS['wpi_snippets']);
		ksort($snippets);
		return $snippets;
	}
	
	public function humanize($str) {
		$replacements = array(
			'-' => ' ',
			'_' => ' '
		);
		return ucwords(strtr($str, $replacements));
	}
	
	protected function default_text() {
		return '<?php'.PHP_EOL.PHP_EOL.PHP_EOL."\t".PHP_EOL.'?>';
	}
}

?>