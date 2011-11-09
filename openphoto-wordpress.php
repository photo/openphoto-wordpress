<?php
/*
Plugin Name: OpenPhoto for WordPress
Version: 0.1
Plugin URI: https://github.com/openphoto/openphoto-wordpress
Author: Randy Hoyt, Randy Jensen
Author URI: http://cultivatr.com/
Description: Connects a WordPress installation to an OpenPhoto installation.  
*/

new WP_OpenPhoto;
include ('openphoto-php/OpenPhotoOAuth.php');

class WP_OpenPhoto {

	function WP_OpenPhoto()
	{
		$this->__construct();
	}

	function __construct()
	{
		new WP_OpenPhoto_Settings;
		add_filter('media_upload_tabs', array( &$this, 'media_add_openphoto_tab' ));
		add_action('media_upload_openphoto', array( &$this, 'media_include_openphoto_iframe'));
	}

	function media_add_openphoto_tab($tabs) {
		$tab = array('openphoto' => __('OpenPhoto', 'openphoto'));
		return array_merge($tabs, $tab);
	}

	function media_include_openphoto_iframe() {
    	return wp_iframe( array( &$this, 'media_render_openphoto_tab'));
	}	
	
	function media_render_openphoto_tab() {
		media_upload_header();

		$openphoto = get_option('openphoto_wordpress_settings');

		$curl_get  = '?';
		$curl_get .= 'oauth_consumer_key=' . $openphoto["oauth_consumer_key"];
		$curl_get .= '&oauth_consumer_secret=' . $openphoto["oauth_consumer_secret"];
		$curl_get .= '&oauth_token=' . $openphoto["oauth_token"];
		$curl_get .= '&oauth_token_secret=' . $openphoto["oauth_token_secret"];
		$curl_get .= '&returnSizes=32x32,128x128';		
		$curl_options = array(
	  				CURLOPT_HEADER => 0,
	  				CURLOPT_URL => trailingslashit($openphoto['host']) . 'photos/list.json' . $curl_get,
	  				CURLOPT_FRESH_CONNECT => 1,
	  				CURLOPT_RETURNTRANSFER => 1,
		);
		$ch = curl_init();
		curl_setopt_array($ch, $curl_options);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response);
		$photos = $response->result;
		
		if ($photos)
		{
		
			echo '<form id="gallery-form">';
			echo '<table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>Media</th><th>Action</th>';
			echo '</tr>';
		
			foreach($photos as $photo)
			{
				echo '<tr>';		
				echo '<td>';
				if ($photo->path32x32 != "") echo '<img src="' . $photo->path32x32 . '" /> &nbsp;';	
				if ($photo->title != "") { echo $photo->title; } else { echo $photo->pathBase; }
				echo '<br />';
				print_r($photo);
				echo '</td>';
				echo '<td>Show</td>';
				echo '</tr>';
			}
			
			echo '</tr>';
			echo '</thead>';
			echo '</table>';
			echo '</form>';
			
		}		
	}	
}

class WP_OpenPhoto_Settings {

	function WP_OpenPhoto_Settings()
	{
		$this->__construct();	
	}

	function __construct()
	{
		add_action('admin_init', array( &$this, 'settings_init'));
		add_action('admin_menu', array( &$this, 'settings_add_openphoto_page'));		
	}
	
	function settings_init() {
		register_setting( 'openphoto_wordpress_settings', 'openphoto_wordpress_settings', array(&$this,'settings_validate_submission'));
	}				
	
	function settings_add_openphoto_page()
	{
		add_options_page('Configure OpenPhoto Integration', 'OpenPhoto', 'manage_options', 'openphoto_wordpress_settings', array( &$this, 'settings_render_openphoto_page'));
	}

	function settings_render_openphoto_page()
	{
		
		$options = get_option('openphoto_wordpress_settings');
		$auto_submit_js = false;

		// if all the values are set
		if (isset($_REQUEST["oauth_consumer_key"]) && $_REQUEST["oauth_consumer_key"] != "" &&
			isset($_REQUEST["oauth_consumer_secret"]) &&
			isset($_REQUEST["oauth_token"]) && 
			isset($_REQUEST["oauth_token_secret"]) && 		
			isset($_REQUEST["oauth_verifier"]) )
		{

			// if even one of the values in the database is different than those in the request
			if ($options['oauth_consumer_key'] != $_REQUEST["oauth_consumer_key"] || 
				$options['oauth_consumer_secret'] != $_REQUEST["oauth_consumer_secret"] ||
				$options['unauthorized_token'] != $_REQUEST["oauth_token"] ||
				$options['unauthorized_token_secret'] != $_REQUEST["oauth_token_secret"] ||
				$options['oauth_verifier'] != $_REQUEST["oauth_verifier"])
			{

				// load the values from the request into the input boxes			
				$options['oauth_consumer_key'] = $_REQUEST["oauth_consumer_key"];		
				$options['oauth_consumer_secret'] = $_REQUEST["oauth_consumer_secret"];
				$options['unauthorized_token'] = $_REQUEST["oauth_token"];
				$options['unauthorized_token_secret'] = $_REQUEST["oauth_token_secret"];			
				$options['oauth_verifier'] = $_REQUEST["oauth_verifier"];
			
				$curl_post = array('oauth_consumer_key' => $_REQUEST["oauth_consumer_key"],'oauth_consumer_secret' => $_REQUEST["oauth_consumer_secret"], 'oauth_token' => $_REQUEST["oauth_token"], 'oauth_token_secret' => $_REQUEST["oauthoauth_token_secret_token"], 'oauth_token_secret' => $_REQUEST["oauthoauth_token_secret_token"], 'oauth_verifier' => $_REQUEST['oauth_verifier']);
				$curl_options = array(
		  			CURLOPT_POST => 1,
	  				CURLOPT_HEADER => 0,
	  				CURLOPT_URL => trailingslashit($options['host']) . 'v1/oauth/token/access',
	  				CURLOPT_FRESH_CONNECT => 1,
	  				CURLOPT_RETURNTRANSFER => 1,
		  			CURLOPT_POSTFIELDS => http_build_query($curl_post)
				);
				$ch = curl_init();
				curl_setopt_array($ch, $curl_options);
				$response = curl_exec($ch);
				curl_close($ch);
			
				$authorized = wp_parse_args($response);
				$options['oauth_token'] = $authorized['oauth_token'];
				$options['oauth_token_secret'] = $authorized['oauth_token_secret'];

				// hide the values and submit the form on page load
				$auto_submit_js = true;
			
			}
		}
		
		if ($options["host_changed"] && $_REQUEST["settings-updated"]=="true" && $auto_submit_js==false)
		{
			$auto_redirect_js = true;
		}

		if ($auto_submit_js==true || $auto_redirect_js==true) echo '<style type="text/css">body.js form#openphoto_wordpress_settings_form {visibility: hidden;}</style>';

		echo '<div class="wrap">';
		echo '<div class="icon32" id="icon-options-general"><br /></div>';
		echo '<h2>Configure OpenPhoto Integration</h2>';
		echo '<form action="options.php" method="post" id="openphoto_wordpress_settings_form">';
		settings_fields('openphoto_wordpress_settings');
		echo '<table class="form-table">';
		echo '<tr valign="top"><th scope="row">Host</th><td><input id="openphoto_wordpress_settings_host" name="openphoto_wordpress_settings[host]" size="100" type="text" value="' . esc_attr($options['host']) . '" />';
		if ($options["host_changed"]) echo ' <a id="openphoto_wordpress_settings_authenticate" href="' . trailingslashit(esc_attr($options['host'])) . 'v1/oauth/authorize?oauth_callback=' . urlencode(admin_url("options-general.php?page=openphoto_wordpress_settings")) . '&name=' . urlencode('OpenPhoto WordPress Plugin ' . ereg_replace("(https?)://", "", get_bloginfo('url')) . '') . '">Authenticate &rarr;</a>';
		echo '<p class="description"><em>Enter the web address of the home page of your OpenPhoto installation.</em></p></td></tr>';
		echo '<tr valign="top"><th scope="row">Consumer Key</th><td><input id="openphoto_wordpress_settings_oauth_consumer_key" name="openphoto_wordpress_settings[oauth_consumer_key]" size="40" type="text" value="' . esc_attr($options['oauth_consumer_key']) . '" /></td></tr>';
		echo '<tr valign="top"><th scope="row">Consumer Secret</th><td><input id="openphoto_wordpress_settings_oauth_consumer_secret" name="openphoto_wordpress_settings[oauth_consumer_secret]" size="40" type="text" value="' . esc_attr($options['oauth_consumer_secret']) . '" /></td></tr>';
		echo '<tr valign="top"><th scope="row">Unauthorized Token</th><td><input id="openphoto_wordpress_settings_unauthorized_token" name="openphoto_wordpress_settings[unauthorized_token]" size="40" type="text" value="' . esc_attr($options['unauthorized_token']) . '" /></td></tr>';
		echo '<tr valign="top"><th scope="row">Unauthorized Token Secret</th><td><input id="openphoto_wordpress_settings_unauthorized_token_secret" name="openphoto_wordpress_settings[unauthorized_token_secret]" size="40" type="text" value="' . esc_attr($options['unauthorized_token_secret']) . '" /></td></tr>';
		echo '<tr valign="top"><th scope="row">Token</th><td><input id="openphoto_wordpress_settings_oauth_token" name="openphoto_wordpress_settings[oauth_token]" size="40" type="text" value="' . esc_attr($options['oauth_token']) . '" /></td></tr>';
		echo '<tr valign="top"><th scope="row">Token Secret</th><td><input id="openphoto_wordpress_settings_oauth_token_secret" name="openphoto_wordpress_settings[oauth_token_secret]" size="40" type="text" value="' . esc_attr($options['oauth_token_secret']) . '" /></td></tr>';
		echo '<tr valign="top"><th scope="row">Verifier</th><td><input id="openphoto_wordpress_settings_oauth_verifier" name="openphoto_wordpress_settings[oauth_verifier]" size="40" type="text" value="' . esc_attr($options['oauth_verifier']) . '" /></td></tr>';
		echo '</table>';
		echo '<p class="submit"><input class="button-primary" name="Submit" type="submit" value="' . esc_attr('Save Changes') . '" /></p>';
		echo '</form>';
		echo '</div>';
		
		// when returning from the oauth request, submit the form to save the data
		if ($auto_submit_js==true) {
			echo '<script type="text/javascript">';
			echo '    var el = document.getElementById("openphoto_wordpress_settings_form");';
			echo '    el.submit();';
			echo '</script >';						
		}

		// after saving a new host name, redirect to oauth request		
		if ($auto_redirect_js==true) {
			echo '<script type="text/javascript">';
			echo '    var el = document.getElementById("openphoto_wordpress_settings_authenticate");';
			echo '    window.location = el.href;';
			echo '</script >';			
		}		

	}
	
	function settings_validate_submission($input)
	{
			
		$old = get_option('openphoto_wordpress_settings');
		
		$newinput['host'] = trim($input['host']);
		$newinput['oauth_consumer_key'] = trim($input['oauth_consumer_key']);
		$newinput['oauth_consumer_secret'] = trim($input['oauth_consumer_secret']);		
		$newinput['unauthorized_token'] = trim($input['unauthorized_token']);		
		$newinput['unauthorized_token_secret'] = trim($input['unauthorized_token_secret']);		
		$newinput['oauth_token'] = trim($input['oauth_token']);		
		$newinput['oauth_token_secret'] = trim($input['oauth_token_secret']);		
		$newinput['oauth_verifier'] = trim($input['oauth_verifier']);
		
		$newinput['host_changed'] = 0;		
		if (isset($newinput['host']) && preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $newinput['host']) && $newinput['host'] != $old['host'])
		{
			$newinput['host_changed'] = 1;
		}

		return $newinput;
	}	

}