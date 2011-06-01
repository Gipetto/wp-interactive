<?php
function wpi_test_messages() {
	# Test custom messages
	$array = array(
		'foo' => 'one',
		'bar' => array(
			'foo' => 'two',
			'bar' => 'three'
		)
	);

	#wpi_set_auto_expand(true);

	wpi_add_message('test error: '.PHP_EOL.wpi_dump($array).wpi_dump($array).wpi_dump($array).wpi_dump($array), 'error');
	wpi_add_message('test warning', 'warning');
	wpi_add_message('test notice', 'notice', true); // set notice & auto expand
}
add_action('admin_init', 'wpi_test_messages');

function wpi_test_deprecated() {
	# throw user-levels deprecated notice
	$u = new WP_User(1);
	$u->has_cap(10);
	
	if (is_admin()) {
		add_menu_page('page title', 'menu title', 10, basename(__FILE__), 'wpi_admin_page');
	}
}
add_action('admin_init', 'wpi_test_deprecated');

?>