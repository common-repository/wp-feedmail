<?php
/* 
Plugin Name: WP-Feedmail
Plugin URI: http://www.casperweb.in/wordpress/plugins/wp-feedmail/
Description: WP-Feedmail is used to display a subscription form to the sidebar where visitors can subscribe to google feedburner directly. Also the subscribers list can be available under Feedmail Tab. Requires WordPress 2.7 or higher.
Version: 1.1.1
Author: Vipin P.G
Author URI: http://www.casperweb.in
License: GPL v3

WP-Feedmail - Google Feedburner Subsription Plugin for the site
Version 1.0.1
Copyright (C) 2012-2013 Vipin P.G
Released 2012-08-05

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Contact Vipin P.G at http://www.casperweb.in/

*/

// +---------------------------------------------------------------------------+
// | WP hooks                                                                  |
// +---------------------------------------------------------------------------+

/* WP actions */

register_activation_hook( __FILE__, 'feedmail_install' );
register_deactivation_hook( __FILE__, 'feedmail_deactivate' );
add_action('admin_menu', 'feedmail_addpages');
add_action( 'admin_init', 'register_feedmail_options' );
add_action('init', 'feedmail_addcss');
add_action('init', 'feedmail_addjs');
add_action('plugins_loaded', 'feedmail_Set');
add_shortcode('casper-feedmail', 'show_feedmail_form');

function register_feedmail_options() { // whitelist options
  register_setting( 'feedmail-option-group', 'casper_linktext' );
  register_setting( 'feedmail-option-group', 'casper_linkurl' );
  register_setting( 'feedmail-option-group', 'casper_deldata' );
  register_setting( 'feedmail-option-group', 'casper_admng' );
  register_setting( 'feedmail-option-group', 'casper_feedburner_id' );

  
}

function unregister_feedmail_options() { // unset options
  unregister_setting( 'feedmail-option-group', 'casper_linktext' );
  unregister_setting( 'feedmail-option-group', 'casper_linkurl' );
  unregister_setting( 'feedmail-option-group', 'casper_deldata' );
  unregister_setting( 'feedmail-option-group', 'casper_admng' );
  unregister_setting( 'feedmail-option-group', 'casper_feedburner_id' );
  
}


function feedmail_addcss() { // include style sheet
  	  wp_enqueue_style('feedmail_css', '/' . PLUGINDIR . '/wp-feedmail/css/wp-feedmail-style.css' );
} 
function feedmail_addjs() { // include script 
	  wp_enqueue_script('feedmail_js', '/' . PLUGINDIR . '/wp-feedmail/js/wp-feedmail-script.js' ); 
}   
// +---------------------------------------------------------------------------+
// | Create admin links                                                        |
// +---------------------------------------------------------------------------+

function feedmail_addpages() { 

	if (get_option('casper_admng') == '') { $casper_admng = 'update_plugins'; } else {$casper_admng = get_option('casper_admng'); }

// Create top-level menu and appropriate sub-level menus:
	add_menu_page('Feedmail', 'Feedmail', $casper_admng, 'feedmail_manage', 'feedmail_show_subsribers', plugins_url('/wp-feedmail/feedmail-icon.png'));
	add_submenu_page('feedmail_manage', 'Settings', 'Settings', $casper_admng, 'feedmail_config', 'feedmail_options_page');

}

// +---------------------------------------------------------------------------+
// | Create table on activation                                                |
// +---------------------------------------------------------------------------+

function feedmail_install () {
   global $wpdb;
   $table_name = $wpdb->prefix . "feedmail";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
 
		if ( $wpdb->supports_collation() ) {
				if ( ! empty($wpdb->charset) )
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				if ( ! empty($wpdb->collate) )
					$charset_collate .= " COLLATE $wpdb->collate";
		}
		
	   $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . "(
		fsub_id int( 15 ) NOT NULL AUTO_INCREMENT ,
		fsub_name text,
		fsub_email text,
		fsub_date date,
		PRIMARY KEY ( `fsub_id` )
		) ".$charset_collate.";";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);


	// insert default settings into wp_options 
	$toptions = $wpdb->prefix ."options";
	$defset = "INSERT INTO ".$toptions.
		"(option_name, option_value) " .
		"VALUES ('casper_admng', 'update_plugins'),('casper_deldata', ''),('casper_feedburner_id','')";
	$dodef = $wpdb->query( $defset );

	} 


	// add default values for core settings if current version is older than 3.0
	if (get_option('feedmail_version') < '3.0') { 
		$toptions = $wpdb->prefix ."options";
		$defset = "INSERT INTO ".$toptions.
			"(option_name, option_value) " .
			"VALUES ('casper_admng', 'update_plugins')";
		$dodef = $wpdb->query( $defset );
	}

	
	
	// update version in options table
	  delete_option("feedmail_version");
	  add_option("feedmail_version", "1.0.1");
}

// +---------------------------------------------------------------------------+
// | Add Settings Link so Plugins Page                                         |
// +---------------------------------------------------------------------------+

function add_settings_link($links, $file) {
	static $feedmail_plugin;
	if (!$feedmail_plugin) $feedmail_plugin = plugin_basename(__FILE__);
	
	if ($file == $feedmail_plugin){
		$settings_link = '<a href="admin.php?page=feedmail_config">'.__("Settings").'</a>';
		 // array_unshift($links, $settings_link);
		 $links[] = $settings_link;
	}
	return $links;
}

function feedmail_Set() {
	if (current_user_can('update_plugins')) 
	add_filter('plugin_action_links', 'add_settings_link', 10, 2 );
}





// +---------------------------------------------------------------------------+
// | Sidebar - show feedmail subscription form in sidebar                           |
// +---------------------------------------------------------------------------+

function show_feedmail_form() { ?>
    <div class="feedmail-form">
    <?php if(get_option('casper_linktext')){?>
    <p><?php echo get_option('casper_linktext'); ?></p>
    <?php } ?>
	<form name="feedmail_addsub" id="feedmail_addsub" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input name="fsub_name" type="text" placeholder="Name">
	<input name="fsub_email" type="text" placeholder="Email" onclick="return hideMessage();">
    <div class="response1" id="response1">Please enter your email address.</div>
    <div class="response2" id="response2">Please enter a valid email address.</div>
	<input type="submit" name="feedmail_addsub" value="<?php _e('Subscribe', 'feedmail_addsub' ) ?>" onclick="return check_feed_form();" />
	</form>
    </div>
    
    		<?php if (isset($_POST['feedmail_addsub'])) 
			{ feedmail_insert(); } 
}
/* insert feedmail subsriber into DB */
function feedmail_insert() {
	global $wpdb;
	$table_name = $wpdb->prefix . "feedmail";
	
	$fsub_name = $wpdb->escape($_POST['fsub_name']);
	if($fsub_name == '' || $fsub_name == 'Name'){
		$fsub_name = 'Unknown';
	}
	$fsub_email = $wpdb->escape($_POST['fsub_email']);
	$fsub_date = date("Y-m-d");
	
	$insert = "INSERT INTO " . $table_name .
	" (fsub_name,fsub_email,fsub_date) " .
	"VALUES ('$fsub_name','$fsub_email','$fsub_date')";
	
	$results = $wpdb->query( $insert );
	@wp_redirect('http://feedburner.google.com/fb/a/mailverify?uri='.get_option('casper_feedburner_id').'&email='.urlencode($fsub_email));

}


// +---------------------------------------------------------------------------+
// | Widget for feedmail form in sidebar                                         |
// +---------------------------------------------------------------------------+
if (version_compare($wp_version, '2.8', '>=')) { // check if this is WP2.8+

	### Class: WP-Feedmail Widget
	 class feedmail_widget extends WP_Widget {
		// Constructor
		function feedmail_widget() {
			$widget_ops = array('description' => __('Displays Feedburner Subscription Form', 'wp-feedmail'));
			$this->WP_Widget('feedmail', __('Feedmail'), $widget_ops);
		}
	 
		// Display Widget
		function widget($args, $instance) {
			extract($args);
			$title = esc_attr($instance['title']);
	
			echo $before_widget.$before_title.$title.$after_title;
	
				show_feedmail_form();
	
			echo $after_widget;
		}
	 
		// When Widget Control Form Is Posted
		function update($new_instance, $old_instance) {
			if (!isset($new_instance['submit'])) {
				return false;
			}
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			return $instance;
		}
	 
		// DIsplay Widget Control Form
		function form($instance) {
			global $wpdb;
			$instance = wp_parse_args((array) $instance, array('title' => __('Feedmail', 'wp-feedmail')));
			$title = esc_attr($instance['title']);
	?>
	 
	 
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wp-feedmail'); ?>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
	<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
	<?php
		}
	}
	 
	### Function: Init WP-Feedmail  Widget
	add_action('widgets_init', 'widget_feedmail_init');
	function widget_feedmail_init() {
		register_widget('feedmail_widget');
	}
} else { // this is an older WP so use old widget structure
	function widget_feedmailwidget($args) {
		extract($args);
	?>
			<?php echo $before_widget; ?>
				<?php echo $before_title
					. 'Feedmail'
					. $after_title; ?>
			<?php echo $after_widget; ?>
	<?php
	}
	add_action('plugins_loaded', 'feedmail_sidebarWidgetInit');
	function feedmail_sidebarWidgetInit()
	{
		register_sidebar_widget('Feedmail', 'widget_feedmailwidget');
	}
}



// +---------------------------------------------------------------------------+
// | Configuration options for feedmail                                   |
// +---------------------------------------------------------------------------+

function feedmail_options_page() {
?>
	<div class="wrap">
	<?php if ($_REQUEST['updated']=='true') { ?>
	<div id="message" class="updated fade"><p><strong>Settings Updated</strong></p></div>
	<?php  } ?>

	<h2>Feedmail Settings</h2>
	<?php echo '<p align="right">Need help?  <a  target="_blank" href="http://www.casperweb.in/wordpress/plugins/wp-feedmail/">support page</a></p>'; ?>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	<?php settings_fields( 'feedmail-option-group' ); ?>
	
	<table cellpadding="5" cellspacing="5">

	<tr valign="top">
	<td>Minimum user level to manage Feedmail</td>
	<td>
	<?php if (get_option('casper_admng') == 'update_plugins') { ?>
	<input type="radio" name="casper_admng" value="update_plugins" checked /> Administrator
	<?php } else { ?>
	<input type="radio" name="casper_admng" value="update_plugins" /> Administrator
	<?php } ?>	
	<?php if (get_option('casper_admng') == 'edit_pages') { ?>
	<input type="radio" name="casper_admng" value="edit_pages" checked /> Editor
	<?php } else { ?>
	<input type="radio" name="casper_admng" value="edit_pages" /> Editor
	<?php } ?>
	<?php if (get_option('casper_admng') == 'publish_posts') { ?>
	<input type="radio" name="casper_admng" value="publish_posts" checked /> Author
	<?php } else { ?>
	<input type="radio" name="casper_admng" value="publish_posts" /> Author
	<?php } ?>
	</td>
	</tr>


	<tr valign="top">
	<td>Google Feedburner ID</td>
	<td><input type="text" name="casper_feedburner_id" value="<?php echo get_option('casper_feedburner_id'); ?>" /></td>
	</tr>
	<tr valign="top">
	<td>Feedmail Description</td>
	<td><textarea name="casper_linktext" class="feedmail-textarea"><?php echo get_option('casper_linktext'); ?></textarea> (The text will displayed before the Feedmail Form in the sidebar)</td>
	</tr>    
	<tr valign="top">
	<td>Remove table when deactivating plugin</td>
	<td>
	<?php $casper_deldata = get_option('casper_deldata'); 
	if ($casper_deldata == 'yes') { ?>
	<input type="checkbox" name="casper_deldata" value="yes" checked /> (If checked, all data will be deleted!)
	<?php } else { ?>
	<input type="checkbox" name="casper_deldata" value="yes" /> (If checked, all data will be deleted!)
	<?php } ?>
	</td>
	</tr>
	
	
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="casper_admng,casper_linktext,casper_linkurl,casper_deldata,casper_feedburner_id" />
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>

<p>Feedmail subscription widget can added to the sidebar either by dragging it from</p><p> the widget area or can be used in any other area by using the shortcode : <code>[casper-feedmail]</code></p></br>
<p>Google feedburner id can be easily obtained from feedburner url : <a href="#">http://feeds.feedburner.com/xxxxxxxx</a> where 'xxxxxxxx' is your feedburner id</p>
<br /><br />


	  <p align="center">WP-Feedmail - <?php echo("".$yearcright."".date('Y').""); ?>, Developed by <a href="http://www.casperweb.in/" target="_blank">Vipin P.G</a> and distributed under the <a href="http://www.fsf.org/licensing/licenses/quick-guide-gplv3.html" target="_blank">GPL v3 General Public License</a>. 
	  Please share your valuable feedbacks by contacting me via <a href="http://www.facebook.com/pages/CASPER-WEB/386046161433463" target="_blank">facebook page</a> or via <a href="http://www.casperweb.in/" target="_blank">website</a>.</p>
	
	</div>
<?php 
}

// +---------------------------------------------------------------------------+
// | Show all Feedmail Subscribers                                   |
// +---------------------------------------------------------------------------+

function feedmail_show_subsribers()
{
	?>
    <div class="wrap feedmail-list">
    <div class="feedmail-icon"></div>
	<h2>Feedmail Subscribers List</h2>
	<?php
	$feedmail_count = 0;
	global $wpdb;
	$table_name = $wpdb->prefix . "feedmail";
	$feedmails = $wpdb->get_results("SELECT * FROM $table_name"); ?>
    <?php if(count($feedmails) > 0) {?>
	<table align="left" class="wp-list-table widefat fixed bookmarks">
    <tr><th align="center" width="10%">&nbsp;</th><th align="left">Name</th><th align="left">Email</th><th align="left">Subscribed On</th></tr>
    	<?php foreach ($feedmails as $feedmail) { $feedmail_count++; ?>
		<tr><td align="center"><?php echo $feedmail_count; ?></td>
        <td><?php echo $feedmail->fsub_name; ?></td>
		<td><?php echo $feedmail->fsub_email; ?></td>
		<td><?php echo $feedmail->fsub_date; ?></td></tr>
	 <?php } ?>
	 </table>
     <?php } else { ?>
     <p>Currently there are no feedmail subscribers.....</p>
     <?php } ?>
</div>
<?php }
// +---------------------------------------------------------------------------+
// | Uninstall plugin                                                          |
// +---------------------------------------------------------------------------+

function feedmail_deactivate () {
	global $wpdb;
	$table_name = $wpdb->prefix . "feedmail";
	$casper_deldata = get_option('casper_deldata');
	if ($casper_deldata == 'yes') {
		$wpdb->query("DROP TABLE {$table_name}");
		delete_option("casper_linktext");
		delete_option("casper_linkurl");
		delete_option("casper_deldata");
		delete_option("casper_admng");
		delete_option("casper_feedburner_id");		
 	}
    delete_option("feedmail_version");
	unregister_feedmail_options();

}
?>