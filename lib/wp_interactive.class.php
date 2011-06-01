<?php

class wp_interactive {
	const BASENAME = 'wp-interactive';
	
	// protected $bespin_path;
	// protected $bespin = 'BespinEmbedded.js';
	
	protected $tmpfile = '/tmp/php-eval.php';	// yep, only supporting *nix
	protected $errors;
	protected $old_error_reporting;
	
	public function __construct() {
		$this->base_url = trailingslashit(WP_PLUGIN_URL).'wp-interactive';
		$this->lib_path = $this->base_url.'/lib';

		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('wp_ajax_wp_interactive', array($this, 'ajax_handler'));
		
		if (!empty($_GET['page']) && $_GET['page'] == self::BASENAME) {
			wp_enqueue_script('codemirror', $this->lib_path.'/CodeMirror-2.0/lib/codemirror.js', array(), WPI_VERSION);
			wp_enqueue_style('codemirror', $this->lib_path.'/CodeMirror-2.0/lib/codemirror.css', array(), WPI_VERSION);		
		
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
							wp_enqueue_script('codemirror-'.$type, $this->lib_path.'/CodeMirror-2.0/mode/'.$type.'/'.$type.'.js', array($config['requires']), WPI_VERSION);
							break;
						case 'css':
							wp_enqueue_style('codemirror-'.$type, $this->lib_path.'/CodeMirror-2.0/mode/'.$type.'/'.$type.'.css', array($config['requires']), WPI_VERSION);
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
		global $wpi_snippets;
		echo '
			<div class="wrap" id="'.self::BASENAME.'-wrap">
				<h2>'.__('WP Interactive', self::BASENAME).'</h2>
				<form id="'.self::BASENAME.'-form">
					<!-- input -->
					<fieldset class="'.self::BASENAME.'-input">
						<h2>'.__('Input:', self::BASENAME).'</h2>
						<fieldset>
							<label for="'.self::BASENAME.'-snippets">Snippets</label>
							<select name="'.self::BASENAME.'-snippets" id="'.self::BASENAME.'-snippets">';
		foreach ($wpi_snippets as $key => $snippet) {
			echo '
								<option value="'.esc_attr($key).'">'.$this->humanize($key).'</option>';
		}				
		echo '
							</select>
							<input type="button" class="button" name="'.self::BASENAME.'-insert-snippet" id="'.self::BASENAME.'-insert-snippet" value="'.__('Insert Snippet', self::BASENAME).'" />
						</fieldset>
						<fieldset>
							<textarea id="'.self::BASENAME.'-input" name="'.self::BASENAME.'-code">'.esc_textarea($this->default_text()).'</textarea>
						</fieldset>
						<p>
							<input type="button" class="button" value="'.__('Clear Editor', self::BASENAME).'" name="'.self::BASENAME.'-clear" id="'.self::BASENAME.'-clear" />
							<input type="button" class="button button-primary" value="'.__('Eval', self::BASENAME).'" name="'.self::BASENAME.'-submit" id="'.self::BASENAME.'-submit" />
						</p>
					</fieldset>
				</form>
							
				<!-- output -->
				<h2>'.__('Output:', self::BASENAME).'</h2>
				<div id="'.self::BASENAME.'-messages" class="'.self::BASENAME.'-notice" style="display: none;"></div>
				<div id="'.self::BASENAME.'-output"><pre class="output">'.__('It putz teh PHP codez above and it makes with the Eval.', self::BASENAME).'</pre></div>
			</div>
			<script type="text/javascript">
				var wpi_snippets = '.json_encode($wpi_snippets).';
				var wpi_default_text = '.json_encode($this->default_text()).';
			</script>';
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
		$ret = `/usr/bin/env php -l < $this->tmpfile`;
		if (strpos($ret, 'Errors parsing')) {
			unlink($this->tmpfile);
			$this->return_result(array(
				'success' => false,
				'eval' => 'NULL (see error report)',
				'message' => $ret
			));
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
	
	public function humanize($str) {
		$replacements = array(
			'-' => ' ',
			'_' => ' '
		);
		return ucwords(strtr($str, $replacements));
	}
}

?>