// Interactive Console
;(function($){	
	$(function() {
		// init editor
		var WPIEditor = CodeMirror.fromTextArea(document.getElementById('self::BASENAME-input'), {
			lineNumbers: true,
			matchBrackets: true,
			mode: "application/x-httpd-php",
			indentUnit: 2,
			indentWithTabs: true,
			enterMode: "indent",
			tabMode: "shift",
			electricChars: true
		});
		WPIEditor.focus();
		WPIEditor.setCursor(2,2);
		
		// submit handler
		$('#self::BASENAME-submit').live('click', function(e) {
			$('#self::BASENAME-output pre.output').html('').closest('div').addClass('loading');
			$.post(
				ajaxurl,
				{
					'action': 'wp_interactive',
					'wpi_action': 'process',
					'code': WPIEditor.getValue()
				},
				function(ret) {
					$('#self::BASENAME-output pre.output').html(ret.eval).closest('div').removeClass('loading');
					
					var _messages = $('#self::BASENAME-messages');
					if (!ret.success) {
						_messages.html(ret.message).show();
					}
					else {
						_messages.html('').hide();
					}
				},
				'json'
			);
			
			// return false;
			e.preventDefault();
		});
		
		// insert a snippet at the current cursor position
		// @todo accommodate selected text in the editor
		$('#self::BASENAME-insert-snippet').live('click', function(e) {
			var snippet = $('#self::BASENAME-snippets').val();
			var cpos = WPIEditor.getCursor();
			WPIEditor.replaceRange(wpi_snippets[snippet], cpos);
			WPIEditor.focus();
			e.stopPropagation();
			e.preventDefault();
		});
		
		// clear editor and insert the default open and close php tags
		$('#self::BASENAME-clear').live('click', function() {
			WPIEditor.setValue(wpi_default_text);
			WPIEditor.focus();
			WPIEditor.setCursor(2,2);
		});
	});
})(jQuery);