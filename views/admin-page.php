<?php
$snippets = $this->get_snippets();
?>
<div class="wrap" id="<?php echo self::BASENAME; ?>-wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('WP Interactive', self::BASENAME); ?></h2>
	<div class="<?php echo self::BASENAME; ?>-description">
		<p><?php _e('Use the <i>Input</i> section below to send PHP code directly to WordPress for execution.', self::BASENAME); ?></p>
		<p><?php _e('Additional snippets can be filtered in using the <code>wpi-snippets</code> filter.', self::BASENAME); ?></p>
	</p>
	<form id="<?php echo self::BASENAME; ?>-form">
		<!-- input -->
		<fieldset class="<?php echo self::BASENAME; ?>-input">
			<h2><?php _e('Input:', self::BASENAME); ?></h2>
			<fieldset>
				<label for="<?php echo self::BASENAME; ?>-snippets">Snippets</label>
				<select name="<?php echo self::BASENAME; ?>-snippets" id="<?php echo self::BASENAME; ?>-snippets">';
<?php
	foreach ($snippets as $key => $snippet) {
		echo '
					<option value="'.esc_attr($key).'">'.$this->humanize($key).'</option>';
	}
?>
				</select>
				<input type="button" class="button" name="<?php echo self::BASENAME; ?>-insert-snippet" id="<?php echo self::BASENAME; ?>-insert-snippet" value="<?php _e('Insert Snippet', self::BASENAME); ?>" />
			</fieldset>
			<fieldset>
				<textarea id="<?php echo self::BASENAME; ?>-input" name="<?php echo self::BASENAME; ?>-code"><?php echo esc_textarea($this->default_text()); ?></textarea>
			</fieldset>
			<p>
				<input type="button" class="button" value="<?php _e('Clear Editor', self::BASENAME); ?>" name="<?php echo self::BASENAME; ?>-clear" id="<?php echo self::BASENAME; ?>-clear" />
				<input type="button" class="button button-primary" value="<?php _e('Eval', self::BASENAME); ?>" name="<?php echo self::BASENAME; ?>-submit" id="<?php echo self::BASENAME; ?>-submit" />
			</p>
		</fieldset>
	</form>
				
	<!-- output -->
	<h2><?php _e('Output:', self::BASENAME); ?></h2>
	<div id="<?php echo self::BASENAME; ?>-messages" class="<?php echo self::BASENAME; ?>-notice" style="display: none;"></div>
	<div id="<?php echo self::BASENAME; ?>-output"><pre class="output"><?php _e('It puts teh PHP codes above and it makes with the Eval.', self::BASENAME); ?></pre></div>
</div>
<script type="text/javascript">
	var wpi_snippets = <?php echo json_encode($snippets); ?>;
	var wpi_default_text = <?php echo json_encode($this->default_text()); ?>;
</script>