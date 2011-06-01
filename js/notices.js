// Notices
jQuery(function($){
	// global ajax listener
	$("body").ajaxComplete(function(e, xhr, settings) {
		if (settings.context.dataType == 'json') {
			response = eval( '(' + xhr.responseText + ')');
			if (response.debug != undefined && $('#wpi-message-table').size() > 0) {
				$('#wpi-message-table tbody').append(response.debug.html);
				if (response.debug.auto_open && $('#wpi-output').is(':hidden')) {
					$('#wpi-banner .wpi-toggle-output').trigger('click');
				}
			}
		}
	});

	// attach click handler
	$("#wpi-banner .wpi-toggle-output").click(function(){
		_link = $(this);
		$("#wpi-output").slideToggle("normal", function() {
			_this = $(this);
			if (_this.is(":visible")) {
				_link.find("span").html(hide_text);
			}
			else {
				_link.find("span").html(show_text);							
			}
		});
		return false;
	});
	
	// auto expand
	if (auto_expand) {
		setTimeout("jQuery('#wpi-banner .wpi-toggle-output').trigger('click');", 1000);
	}
});