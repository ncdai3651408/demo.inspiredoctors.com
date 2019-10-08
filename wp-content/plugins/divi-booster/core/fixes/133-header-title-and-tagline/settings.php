<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db133_add_setting($plugin) { 
	$plugin->setting_start(); 
	$plugin->techlink('https://divibooster.com/display-site-title-and-tagline-text-in-header/'); 
	$plugin->checkbox(__FILE__); ?> Show site title and tagline in header
	<div class="db_subsetting">
		Layout:
		<?php
		$options = array(
			'horizontal' => 'Tagline beside title',
			'vertical' => 'Tagline below title',
			'title_only' => 'Title only',
			'tagline_only' => 'Tagline only'
		);
		$selected = dbdb_option('133-header-title-and-tagline', 'layout', $default='horizontal');
		$plugin->selectpicker(__FILE__, '[layout]', $options, $selected);
		?>
	</div>
	<?php
	$plugin->setting_end(); 
} 
$wtfdivi->add_setting('header-main', 'db133_add_setting');	