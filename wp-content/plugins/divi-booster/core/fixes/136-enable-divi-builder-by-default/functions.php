<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

// === Classic Builder === 

add_action('load-post-new.php', 'db136_load_post_new_php'); 

function db136_load_post_new_php() {
	
	// Exit if BFB enabled
	if (function_exists('et_builder_bfb_enabled') && et_builder_bfb_enabled()) { 
		return; 
	}
	
	// Enable classic builder
	add_filter('et_builder_always_enabled', '__return_true');
}; 
