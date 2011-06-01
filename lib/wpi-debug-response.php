<?php
class wpi_debug_response {
	public $level; // error, warning, or notice
	public $message;
	
	public $auto_open;
	
	public $_file;
	public $_line;
	public $_func;
	
	public function __construct($level, $message, $auto_open = false) {
		$this->message = $message;
		$this->level = $level;

		$db = current(debug_backtrace());
		$this->_file = $db['file'];
		$this->_line = $db['line'];
		$this->_func = $db['function'];
		
		$this->auto_open = (bool) $auto_open;
	}
	
	public function get_array() {
		return array(
			'level' => $this->level,
			'message' => $this->message,
			'_file' => wpi_truncate_path($this->_file),
			'_line' => $this->_line,
			'_func' => $this->_func.'()'
		);
	}
	
	public function __toString() {
		return array(
			'html' => wpi_message_table_row($this->get_array()),
			'auto_open' => $this->auto_open
		);
	}	
}

?>