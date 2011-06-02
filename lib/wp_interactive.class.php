<?php

class wp_interactive {
	const BASENAME = 'wp-interactive';
	const CODEMIRROR = 'CodeMirror2';
	
	protected $tmpfile = '/tmp/php-eval.php';	// yep, only supporting *nix
	protected $errors;
	protected $old_error_reporting;
		
	public function __construct() {
		$this->base_path = trailingslashit(WP_PLUGIN_DIR).'wp-interactive';
		$this->base_url = trailingslashit(WP_PLUGIN_URL).'wp-interactive';
		$this->lib_url = $this->base_url.'/lib';

		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('wp_ajax_wp_interactive', array($this, 'ajax_handler'));
		
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
	}
	
// Admin page

	public function admin_page() {
		echo $this->load_view('admin-page');
	}
	
	protected function default_text() {
		return '<?php'.PHP_EOL.PHP_EOL.PHP_EOL."\t".PHP_EOL.'?>';
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
		
		// check syntax - requires linux, system capabilities
		file_put_contents($this->tmpfile, $code);
		$ret = `/usr/bin/env php -l < $this->tmpfile 2>&1`;
		if (strpos($ret, 'Errors parsing') !== false) {
			unlink($this->tmpfile);
			return array(
				'success' => false,
				'eval' => 'NULL (see error report)',
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
		// $d = new wpi_debug_response('notice', 'test message', true);
		// $result['debug'] = $d->__toString();
		
		header('content-type: text/javascript');
		$json = json_encode($result);
		if (json_last_error() != JSON_ERROR_NONE) {
			$json_errors = array(
			    JSON_ERROR_NONE => 'No error has occurred',
			    JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
			    JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
			    JSON_ERROR_SYNTAX => 'Syntax error',
				JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);
			error_log(__METHOD__.' - JSON DECODE ERROR: '.$json_errors[json_last_error()]);
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
					'eval' => 'NULL (see error reoprt)',
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
		global $wpi_snippets;
		return apply_filters('wpi-snippets', $wpi_snippets);
	}
	
	public function humanize($str) {
		$replacements = array(
			'-' => ' ',
			'_' => ' '
		);
		return ucwords(strtr($str, $replacements));
	}
}

?>