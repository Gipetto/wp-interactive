<?php

global $wpi_snippets;
$wpi_snippets = array();

$wpi_snippets['rebuild_image_thumbnails'] = <<<REBUILDTHUMBS
/**
 * Rebuild all image thumbnails - this will probably run out of memory on large installs
 */
\$atts = new WP_Query(array(
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'showposts' => 0,
    'post_mime_type' => 'image',
    'fields' => 'ids'
));

if (!empty(\$atts->posts)) {
	foreach (\$atts->posts as \$post_id) {
		\$image_data = wp_get_attachment_metadata(\$post_id);
		echo 'rebuilding thumbnails for image_id: '.\$post_id.PHP_EOL;
		\$new_data = wp_generate_attachment_metadata(\$post_id, WP_CONTENT_DIR.'/uploads/'.\$image_data['file']);
		wp_update_attachment_metadata(\$post_id, \$new_data);
		//print_r(\$new_data);
	}
}
REBUILDTHUMBS;

$wpi_snippets['get_post_postmeta'] = <<<GETPOSTMETA
// set the post_ID you want to inspect
\$post_ID = 1;

// fetch
\$meta = get_post_meta(\$post_ID, '');
if (!empty(\$meta)) {
  foreach (\$meta as \$meta_key => \$meta_value) {
    echo \$meta_key.': ';
    // it doesn't happen often, but postmeta keys can have multiple values
    foreach (\$meta_value as \$value) {
      \$value = maybe_unserialize(\$value);

      if (is_scalar(\$value)) {
	var_dump(\$value);
      }
      else {
	print_r(\$value);
      }
    }
    echo '-------------------'.PHP_EOL;
  }
}
GETPOSTMETA;

?>