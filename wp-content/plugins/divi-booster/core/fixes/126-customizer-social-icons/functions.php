<?php
if (!defined('ABSPATH')) { exit(); } // No direct access

// Register the icon styles
function db121_enqueue_scripts() { 
	wp_register_style('db121_socicons', plugin_dir_url(__FILE__).'icons.css', array(), BOOSTER_VERSION);
}
add_action('wp_enqueue_scripts', 'db121_enqueue_scripts');

// === Define supported networks 
function db121_get_networks() {
	return array(
	''=>'--- Select Icon ---', /*'custom'=>'[Custom Icon]',*/ 
	
"8tracks"=>"8tracks",
"500px"=>"500px",
"portfolio"=>"Adobe Portfolio",
"airbnb"=>"Airbnb",
"alibaba"=>"Alibaba",
"aliexpress"=>"Aliexpress",
"alliance"=>"Alliance",
"amazon"=>"Amazon",
"amplement"=>"amplement",
"android"=>"Android",
"angellist"=>"AngelList",
"angieslist"=>"Angie's List",
"apple"=>"Apple",
"appnet"=>"appnet",
"appstore"=>"Appstore",
"ask"=>"Ask",
"augment"=>"AUGMENT",
"baidu"=>"baidu",
"bandcamp"=>"bandcamp",
"battlenet"=>"BATTLE.NET",
"beatport"=>"beatport",
"bebee"=>"beBee",
"bebo"=>"bebo",
"behance"=>"Behance",
"bing"=>"Bing",
"bitbucket"=>"Bitbucket",
"blackberry"=>"BlackBerry",
"blizzard"=>"Blizzard",
"blogger"=>"Blogger",
"bloglovin"=>"BLOGLOVIN'",
"bonanza"=>"bonanza",
"bookbub"=>"BookBub",
"booking"=>"Booking",
"buffer"=>"Buffer",
"calendly"=>"Calendly",
"chrome"=>"Chrome",
"codepen"=>"Codepen",
"codered"=>"CodeRED",
"coderwall"=>"coderwall",
"craigslist"=>"Craigslist",
"crunchbase"=>"crunchbase",
"curse"=>"Curse",
"dailymotion"=>"Dailymotion",
"deezer"=>"DEEZER",
"delicious"=>"Delicious",
"deviantart"=>"deviantART",
"diablo"=>"Diablo",
"digg"=>"Digg",
"discord"=>"Discord",
"disqus"=>"DISQUS",
"doodle"=>"Doodle",
"douban"=>"douban",
"draugiem"=>"draugiem.lv",
"dribbble"=>"dribbble",
"drupal"=>"Drupal",
"ebay"=>"ebay",
"ello"=>"Ello",
"endomondo"=>"endomondo",
"envato"=>"Envato",
"etsy"=>"Etsy",
"facebook"=>"Facebook",
"messenger"=>"Facebook Messenger",
"feedburner"=>"FeedBurner",
"filmweb"=>"FILMWEB",
"firefox"=>"Firefox",
"fiverr"=>"Fiverr",
"flattr"=>"Flattr",
"flickr"=>"flickr",
"formulr"=>"FORMULR",
"forrst"=>"Forrst",
"foursquare"=>"foursquare",
"freelancer"=>"Freelancer",
"friendfeed"=>"FriendFeed",
"fundable"=>"Fundable",
"fyuse"=>"FYUSE",
"gamefor"=>"GameFor",
"gamejolt"=>"Game Jolt",
"gamewisp"=>"GameWisp",
"ghost"=>"ghost",
"github"=>"GitHub",
"gitlab"=>"GitLab",
"goodreads"=>"goodreads",
"google"=>"Google",
"googleplus"=>"Google+",
"googlecalendar"=>"Google Calendar",
"googlegroups"=>"Google Groups",
"googlemaps"=>"Google Maps",
"googlephotos"=>"Google Photos",
"play"=>"Google Play",
"googlescholar"=>"Google Scholar",
"gotomeeting"=>"GoToMeeting",
"grooveshark"=>"grooveshark",
"guru"=>"Guru",
"gust"=>"Gust",
"hackernews"=>"Hacker News",
"hackerone"=>"hackerone",
"hackerrank"=>"HackerRank",
"hearthstone"=>"Hearthstone",
"hellocoton"=>"Hellocoton",
"heroes"=>"Hereos of the Storm",
"hitbox"=>"hitbox",
"homeadvisor"=>"HomeAdvisor",
"homes"=>"Homes",
"homify"=>"homify",
"horde"=>"Horde",
"houzz"=>"houzz",
"icq"=>"icq",
"identica"=>"Identica",
"imdb"=>"IMDb",
"indiedb"=>"indie DB",
"instagram"=>"Instagram",
"instructables"=>"instructables",
"internet"=>"Internet",
"issuu"=>"issuu",
"istock"=>"iStock",
"itunes"=>"iTunes",
"keybase"=>"Keybase",
"kobo"=>"Kobo",
"lanyrd"=>"Lanyrd",
"lastfm"=>"last.fm",
"line"=>"LINE",
"linkedin"=>"Linkedin",
"livejournal"=>"LiveJournal",
"livemaster"=>"livemaster",
"logmein"=>"LogMeIn",
"loomly"=>"loomly",
"lyft"=>"lyft",
"macos"=>"macOS",
"mail"=>"Mail",
"mailru"=>"Mail.ru",
"mastodon"=>"Mastodon",
"medium"=>"Medium",
"meetup"=>"Meetup",
"microsoft"=>"Microsoft",
"mixcloud"=>"Mixcloud",
"mixer"=>"Mixer",
"mobcrush"=>"mobcrush",
"moddb"=>"Mod DB",
"modelmayhem"=>"Model Mayhem",
"persona"=>"Mozilla Persona",
"mumble"=>"mumble",
"myanimelist"=>"MyAnimeList",
"myspace"=>"Myspace",
"napster"=>"napster",
"natgeo"=>"Natgeo",
"newsvine"=>"NewsVine",
"nextdoor"=>"Nextdoor",
"niconico"=>"niconico",
"nintendo"=>"Nintendo Network",
"npm"=>"npm",
"odnoklassniki"=>"Odnoklassniki",
"openaigym"=>"OpenAI Gym",
"openid"=>"OpenID",
"opera"=>"Opera",
"origin"=>"Origin",
"outlook"=>"Outlook",
"overwatch"=>"Overwatch",
"pandora"=>"Pandora",
"patreon"=>"Patreon",
"paypal"=>"Paypal",
"periscope"=>"Periscope",
"pinterest"=>"Pinterest",
"pixiv"=>"pixiv",
"player"=>"Player.me",
"playstation"=>"PlayStation",
"pocket"=>"Pocket",
"qobuz"=>"Qobuz",
"qq"=>"QQ",
"quora"=>"Quora",
"raidcall"=>"RaidCall",
"ravelry"=>"Ravelry",
"realtor"=>"Realtor",
"reddit"=>"reddit",
"redfin"=>"Redfin",
"renren"=>"renren",
"researchgate"=>"ResearchGate",
"residentadvisor"=>"Resident Advisor",
"reverbnation"=>"REVERBNATION",
"rss"=>"RSS",
"seedrs"=>"Seedrs",
"sharethis"=>"ShareThis",
"shopify"=>"Shopify",
"weibo"=>"Sina Weibo",
"sketchfab"=>"Sketchfab",
"skype"=>"skype",
"slack"=>"Slack",
"slideshare"=>"SlideShare",
"smashwords"=>"Smashwords",
"smugmug"=>"SmugMug",
"snapchat"=>"Snapchat",
"songkick"=>"songkick",
"soundcloud"=>"soundcloud",
"spip"=>"SPIP",
"spotify"=>"Spotify",
"spreadshirt"=>"spreadshirt",
"squarespace"=>"Squarespace",
"stackexchange"=>"StackExchange",
"stackoverflow"=>"stackoverflow",
"starcraft"=>"Starcraft",
"stayfriends"=>"StayFriends",
"steam"=>"steam",
"storehouse"=>"Storehouse",
"strava"=>"STRAVA",
"streamjar"=>"StreamJar",
"stumbleupon"=>"StumbleUpon",
"swarm"=>"Swarm",
"teamspeak"=>"TeamSpeak",
"teamviewer"=>"TeamViewer",
"technorati"=>"Technorati",
"telegram"=>"Telegram",
"tidal"=>"Tidal",
"toneden"=>"ToneDen",
"toptal"=>"Toptal",
"traxsource"=>"Traxsource",
"trello"=>"Trello",
"tripadvisor"=>"tripadvisor",
"tripit"=>"Tripit",
"triplej"=>"triplej",
"trulia"=>"Trulia",
"tumblr"=>"tumblr",
"tunein"=>"tunein",
"twitch"=>"Twitch",
"twitter"=>"Twitter",
"uber"=>"UBER",
"udemy"=>"udemy",
"unsplash"=>"Unsplash",
"upwork"=>"Upwork",
"ventrilo"=>"Ventrilo",
"viadeo"=>"Viadeo",
"viber"=>"Viber",
"viewbug"=>"viewbug",
"vimeo"=>"vimeo",
"vine"=>"Vine",
"vkontakte"=>"VKontakte",
"warcraft"=>"Warcraft",
"wechat"=>"WeChat",
"whatsapp"=>"WhatsApp",
"wickr"=>"Wickr",
"wikipedia"=>"Wikipedia",
"windows"=>"Windows",
"wix"=>"Wix",
"wordpress"=>"WordPress",
"wykop"=>"wykop",
"xbox"=>"XBOX",
"xing"=>"Xing",
"yahoo"=>"Yahoo!",
"yammer"=>"Yammer",
"yandex"=>"Yandex",
"yelp"=>"yelp",
"younow"=>"Younow",
"youtube"=>"YouTube",
"yt-gaming"=>"Youtube Gaming",
"zapier"=>"Zapier",
"zerply"=>"Zerply",
"zillow"=>"Zillow",
"zomato"=>"zomato",
"zoom"=>"Zoom",
"zynga"=>"zynga"
	
	);
}

// Convert json string to an array
// - returns an empty array on error
function db121_json2arr($val) {
	$result = json_decode($val, true); 
	return is_array($result)?$result:array(); 
}

/* Add customizer section */
add_action('customize_register', 'db121_customize_register');
function db121_customize_register($wp_customize){
	
	/* Custom controls */
	class DB121_Customize_Control extends WP_Customize_Control {
		
		public function render_content() {
		
			// Load the model
			$model = db121_json2arr($this->value()); 
			
			// Load the customizer jquery
			include(dirname(__FILE__).'/customizer.js.php'); 
			?>

			<input type="text" id="model_icons" <?php $this->link(); ?> value="<?php esc_attr_e($this->value()); ?>" style="display:none;"/>

			<?php 
			
			// Include the box template and new box button
			include(dirname(__FILE__).'/templates/box.php');
			include(dirname(__FILE__).'/templates/add-new.php');

		}
    }
	
	// Register "divi booster" customizer section 
	$wp_customize->add_panel('divibooster-main', array(
		'title'=>'Divi Booster',
		'priority' => 30 // make sure it shows above widgets / menus to stop jumping
	));
	
	// Register social media customizer sub-section
	$wp_customize->add_section('divibooster-social-icons', array(
		'title' => 'Social Media Icons',
		'panel' => 'divibooster-main'
	) );
	
	// Register the setting
	$wp_customize->add_setting('wtfdivi[fixes][126-customizer-social-icons][icons]', array(
		'type' => 'option',
		'transport' => 'refresh',
		'default'=>'[{"id":"","name":"(No network set)","url":""}]'
		)
	);
	$wp_customize->add_control(
		new DB121_Customize_Control($wp_customize, 'db121_control',
			array(
				'label'      => 'Select Icon',
				'section'    => 'divibooster-social-icons',
				'settings'   => 'wtfdivi[fixes][126-customizer-social-icons][icons]'
			)
		)
	); 
}

function db121_icon_js() {
	$networks = db121_get_networks();
	$option = get_option('wtfdivi');
	if (empty($option['fixes']['126-customizer-social-icons']['icons'])) { return; }

	$icons = json_decode($option['fixes']['126-customizer-social-icons']['icons'], true); // decode json to php array

	// Exits
	if (empty($icons) || !is_array($icons)) { return; } // Icons not set
	if (count($icons) == 1) { return; } // Only have the icon template, no actual icons
	
	// Load the icon styles
	wp_enqueue_style('db121_socicons');
	
	?>
	<script>
	jQuery(function($) {
		<?php 
		
		foreach($icons as $k=>$icon) { 
			
			// Get the URL
			$url = empty($icon['url'])?'':$icon['url'];
			$scheme = parse_url($url, PHP_URL_SCHEME);
			$path = parse_url($url, PHP_URL_PATH);
			$url = (empty($scheme) && !empty($path))?"http://$url":$url; // add the scheme if missing
			
			// Get the ID
			$id = empty($icon['id'])?false:$icon['id'];
		
			// Ouput the jQuery to add the icon
			if ($id) {
				
				if ($id === 'custom') { // custom icon
					?>
					$('.et-social-icons').append('<li class="et-social-icon"><a href="<?php esc_attr_e($url); ?>" class="icon socicon socicon-custom"><img src="<?php esc_attr_e($icon['img']); ?>"></img></a></li>');
					$('.et-extra-social-icons').append('<li class="et-extra-social-icon"><a href="<?php esc_attr_e($url); ?>" class="et-extra-icon et-extra-icon-background-hover socicon socicon-custom"><img src="<?php esc_attr_e($icon['img']); ?>"></img></a></li>');
					<?php 
				} else { // pre-defined icon
					$span = isset($networks[$id])?'<span>'.esc_html($networks[$id]).'</span>':'';
					?>
					$('.et-social-icons').append('<li class="et-social-icon"><a href="<?php esc_attr_e($url); ?>" class="icon socicon socicon-<?php esc_attr_e($id) ?>"><?php echo $span; ?></a></li>');
					$('.et-extra-social-icons').append('<li class="et-extra-social-icon"><a href="<?php esc_attr_e($url); ?>" class="et-extra-icon et-extra-icon-background-hover socicon socicon-<?php esc_attr_e($id) ?>"></a></li>');
					<?php 
				}  
			}

		}
		?>
	});
	</script>
	<?php  
}
add_action('wp_head', 'db121_icon_js');

// In customizer preview, replace the red circle on icon links with an alert box, so it doesn't look like there has been an error adding the link
function db121_improve_customizer_warning() {
	if (is_customize_preview()) {
		?>
		<style>
		.et-social-icon > a.customize-unpreviewable { cursor: pointer !important; }
		</style>
		<script>
		jQuery(function($){
			/* Improve customizer link disabled notification */
			$(document).on('click', '.et-social-icon > a.customize-unpreviewable', function(){ 
				alert('External links are disabled in the customizer preview.'); 
			});
		});
		</script>
		<?php
	}
}
add_action('wp_head', 'db121_improve_customizer_warning');
