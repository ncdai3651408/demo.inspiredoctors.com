<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

 // Register icons - priority 50 for Divi Icon King compatibility
add_filter('et_pb_font_icon_symbols', 'db014_register_icons', 50);

// Style the icons in the icon picker
add_action('db_admin_css', 'db014_css_for_custom_icons_in_icon_picker');
add_action('db_vb_css', 'db014_css_for_custom_icons_in_icon_picker');

// Style and render the icons on the front end
add_action('wp_head.css', 'db014_user_css_for_individual_button_icons');
add_action('wp_head.css', 'db014_user_css_for_custom_button_icons');
add_action('wp_head.css', 'db014_user_css_for_custom_icons');
add_action('wp_head.css', 'db014_user_css_for_inline_icons');
add_action('wp_head.css', 'db014_user_css_for_custom_hover_icon');
add_action('wp_head.css', 'db014_user_css_to_hide_unwanted_icons');
add_action('db_user_jquery', 'db014_user_js_functions');

// Handle visual builder changes that might affect the icons
add_action('db_vb_jquery_ready', 'db014_visual_builder_js_admin');

// Divi Icon King compatibility
add_action('db_admin_jquery', 'db014_add_divi_icon_king_filter_link');
add_action('db_vb_jquery', 'db014_add_divi_icon_king_filter_link');


// === Register the icons ===

function db014_register_icons($icons) {
	$icon_urls = db014_get_icon_urls();
	foreach($icon_urls as $id=>$url) {
		if (!empty($url)) {
			$icons[] = $id;
		}
	}
	return $icons;
}


// === Style the icons in the icon picker ===

function db014_css_for_custom_icons_in_icon_picker() { 

	$icon_urls = db014_get_icon_urls();
	foreach($icon_urls as $id=>$url) {
		if (empty($url)) {
			continue; 
		}
		$url = esc_html($url);
		echo <<<END
		.et-fb-option--select-icon li[data-icon="{$id}"]:after, 	/* fb & bfb */
		.et-pb-option--select_icon li[data-icon="{$id}"]:before,	/* classic */
		.et-pb-option ul.et_font_icon li[data-icon="{$id}"]::before /* classic (old versions) */
		{ 
			background: url('{$url}') no-repeat center center; 
		}
END;
	}

	echo <<<END
	.et-fb-option--select-icon li[data-icon^="wtfdivi014"]:after, 	/* fb & bfb */
	.et-pb-option--select_icon li[data-icon^="wtfdivi014"]:before,	/* classic */
	.et-pb-option ul.et_font_icon li[data-icon^="wtfdivi014"]::before /* classic (old versions) */
	{ 
		-webkit-background-size: cover; 
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size:cover; 
		content:'a' !important; 
		width:16px !important; 
		height:16px !important; 
		color:rgba(0,0,0,0) !important; 
	}
END;
}


// === Style the icons on the front end ===

function db014_user_css_for_individual_button_icons($plugin) { 	
	$icon_urls = db014_get_icon_urls();
	foreach($icon_urls as $id=>$url) {
		db014_user_css_for_custom_button_icon($id, $url);
	} 
}

function db014_user_css_for_custom_icons() { ?>
	.db-custom-icon img { 
		height: 1em;
	}
	<?php	
}

function db014_user_css_for_custom_button_icon($id, $url) { 
	$icon = '.et_pb_custom_button_icon[data-icon="'.esc_html($id).'"]';
	$bg_img = empty($url)?'none':"url('".esc_html($url)."')";
	echo <<<END
	$icon:before, 
	$icon:after {
		background-image: $bg_img;		
	}
END;

	$is_svg = preg_match('#\.svg(\?[^.]*)?$#', $url);
	if ($is_svg) {
		// IE SVG background-size (as "auto" not supported) 
		// - width = half the 2em padding allocated for icon, and 50% height of button
		echo <<<END
		body.ie $icon:before, 
		body.ie $icon:after {
			background-size: 1em 50%; 	
		}
END;
	}
}

function db014_user_css_for_custom_button_icons() { ?>
	.et_pb_custom_button_icon[data-icon^="wtfdivi014"]:before, 
	.et_pb_custom_button_icon[data-icon^="wtfdivi014"]:after {
		background-size: auto 1em;
		background-repeat: no-repeat;
		min-width: 20em;
		height: 100%;
		content: "" !important;
		background-position: left center;
		position: absolute;
		top: 0;
	}
	.et_pb_custom_button_icon[data-icon^="wtfdivi014"] { 
		overflow: hidden;
	}
	<?php
}

function db014_user_css_for_inline_icons() { ?>
	.et_pb_posts .et_pb_inline_icon[data-icon^="wtfdivi014"]:before,
	.et_pb_portfolio_item .et_pb_inline_icon[data-icon^="wtfdivi014"]:before {
		content: '' !important;
		-webkit-transition: all 0.4s;
		-moz-transition: all 0.4s;
		transition: all 0.4s;
	}
	.et_pb_posts .entry-featured-image-url:hover .et_pb_inline_icon[data-icon^="wtfdivi014"] img,
	.et_pb_portfolio_item .et_portfolio_image:hover .et_pb_inline_icon[data-icon^="wtfdivi014"] img { 
		margin-top:0px; transition: all 0.4s;
	}
	.et_pb_posts .entry-featured-image-url .et_pb_inline_icon[data-icon^="wtfdivi014"] img, 
	.et_pb_portfolio_item .et_portfolio_image .et_pb_inline_icon[data-icon^="wtfdivi014"] img { 
		margin-top: 14px; 
	}
	<?php
}

function db014_user_css_for_custom_hover_icon() { ?>
	.db014_custom_hover_icon { 
		width:auto !important; 
		max-width:32px !important; 
		min-width:0 !important;
		height:auto !important; 
		max-height:32px !important; 
		min-height:0 !important;
		position:absolute;
		top:50%;
		left:50%;
		-webkit-transform: translate(-50%,-50%); 
		-moz-transform: translate(-50%,-50%); 
		-ms-transform: translate(-50%,-50%); 
		transform: translate(-50%,-50%); 
	}
	<?php	
}

function db014_user_css_to_hide_unwanted_icons() { ?>
	/* Hide extra icons */
	.et_pb_gallery .et_pb_gallery_image .et_pb_inline_icon[data-icon^="wtfdivi014"]:before,
	.et_pb_blog_grid .et_pb_inline_icon[data-icon^="wtfdivi014"]:before,
	.et_pb_image .et_pb_image_wrap .et_pb_inline_icon[data-icon^="wtfdivi014"]:before { 
		display:none; 
	}
	<?php
}


// === Render the icons on the front end ===

function db014_user_js_functions() { 
	?>	
	db014_update_icons_user();
	$(document).on('db_vb_custom_icons_updated', db014_update_icons_vb);
	$(document).on('db_vb_custom_icons_updated', db014_update_icons_user);

	function db014_update_icons_user() {
		db014_update_icons($(document));
	}
	
	function db014_update_icons_vb() {
		var $app_frame = $("#et-fb-app-frame");
		if ($app_frame) {
			db014_update_icons($app_frame.contents());
		}
	}
	
	function db014_update_icons(doc) { 
		db014_revert_custom_icons(doc);
		db014_revert_custom_inline_icons(doc);
		db014_update_custom_icons(doc);
		db014_update_custom_inline_icons(doc);
	}
	
	function db014_revert_custom_icons(doc) {
		doc.find('.et-pb-icon.db-custom-icon:not(:contains("wtfdivi014"))')
			.removeClass('db-custom-icon')
			.children()
				.remove('img');
	}
	
	function db014_revert_custom_inline_icons(doc) {	
		doc.find('.et_pb_inline_icon.db-custom-icon:not([data-icon^="wtfdivi014"])')
			.removeClass('db-custom-icon')
			.children()
				.remove('.db014_custom_hover_icon');
	}
	
	function db014_update_custom_icons(doc) {
		var $custom_icons = doc.find('.et-pb-icon:contains("wtfdivi014")');	
		var icons = db014_get_icons();   
		$.each(icons, function(icon_id, icon_url) {
			var icon_visible = (icon_url !== '');
			var $icons = $custom_icons.filter(function(){ return $(this).text() == icon_id; }); 
			$icons.addClass('db-custom-icon');
			$icons.html('<img src="'+icon_url+'"/>');
			$icons.toggle(icon_visible); 
		});
	}
	
	function db014_update_custom_inline_icons(doc) {
		var $custom_inline_icons = doc.find('.et_pb_inline_icon[data-icon^="wtfdivi014"]');
		var icons = db014_get_icons();   
		$.each(icons, function(icon_id, icon_url) {
			var icon_visible = (icon_url !== '');
			var $icons_inline = $custom_inline_icons.filter(function(){ return $(this).attr('data-icon') == icon_id; });
			$icons_inline.addClass('db-custom-icon');
			$icons_inline.each(function(){
				if ($(this).children('.db014_custom_hover_icon').length === 0) {
					$(this).html('<img class="db014_custom_hover_icon"/>');
				}
				$(this).children('.db014_custom_hover_icon').attr('src', icon_url);
			});
			$icons_inline.toggle(icon_visible);
		});
	}
	
	function db014_get_icons() {
		return <?php echo json_encode(db014_get_icon_urls()); ?>;
	}

<?php 
}

function db014_get_icon_urls() {
	$urls = array();
	$urlmax = dbdb_option('014-add-new-icons', 'urlmax', 0);
	for($i=0; $i<=$urlmax; $i++) {
		$urls["wtfdivi014-url$i"] = dbdb_option('014-add-new-icons', "url$i", '');
	}
	return $urls;
}

// Handle visual builder changes that might affect the icons

function db014_visual_builder_js_admin() { ?>
	
	db014_watch_for_changes_that_might_update_icons();
	
	function db014_watch_for_changes_that_might_update_icons() {
		// Observe when vb iframe is added
		var target = document.getElementById('et-fb-app'); 
		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				if (mutation.type === 'characterData') {
					$(document).trigger('db_vb_custom_icons_updated');
				} else if (mutation.type === 'childList') {
					if (db014_may_contain_icons(mutation.target)) {
						$(document).trigger('db_vb_custom_icons_updated');
					}
				} else if (mutation.type === 'attributes') {
					$(document).trigger('db_vb_custom_icons_updated');
				}

			});
		});
		observer.observe(
			document.getElementById('et-fb-app'), 
			{ 
				attributes: true, 
				attributeFilter: ["class"],
				childList: true, 
				characterData: true,
				subtree: true
			}
		);
	}
	
	function db014_may_contain_icons(target) {
		if (target.className === undefined) { 
			return false; 
		}
		var classes = target.className;
		if (classes.search === undefined) { 
			return false; 
		}
		if (classes.search(/(et-pb-icon|et_pb_inline_icon|et-fb-root-ancestor|et_pb_root--vb|et-fb-post-content|et_pb_section|et_pb_row|et_pb_column)/i) !== -1) {
			return true;
		}
		return false;
	}
<?php 
}


// === Divi Icon King compatibility === 

function db014_add_divi_icon_king_filter_link() {
	?>
	// Add filter link if Divi Icon King used
	$(document).on( 'click', '.dikg_icon_filter__btn', function() {
		$('[data-icon^=wtfdivi]').attr('data-family', 'divi-booster').removeClass('gtm-divi-king-icon--elegant-themes').toggleClass('gtm-divi-king-icon--divi-booster', true);
		if ($('.dikg_icon_filter__control_option[data-value=divi-booster]').length === 0) {
			$('.dikg_icon_filter__control_option[data-value=elegant-themes]').after($('<span class="dikg_icon_filter__control_option dikg_icon_filter__control_option--inactive dikg_icon_filter__control_family dikg_icon_filter__control_option--active" data-value="divi-booster">Divi Booster</span>'));
		}
	});
	<?php
}
