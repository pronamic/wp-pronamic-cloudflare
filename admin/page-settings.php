<?php
/**
 * Admin page settings
 *
 * @package Pronamic\WordPressCloudflare
 */

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'pronamic_cloudflare' ); ?>

		<?php do_settings_sections( 'pronamic_cloudflare' ); ?>

		<?php submit_button(); ?>
	</form>
</div>
