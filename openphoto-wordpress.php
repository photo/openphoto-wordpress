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

class WP_OpenPhoto {

	function WP_OpenPhoto()
	{
		$this->__construct();
	}

	function __construct()
	{
		new WP_OpenPhoto_Options;
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
		echo '<form><h3>OpenPhoto</h3><p>Hello, world!</p></form>';
	}	

}

class WP_OpenPhoto_Options {

	function WP_OpenPhoto_Options()
	{
		$this->__construct();
	}

	function __construct()
	{
		# Place your add_actions and add_filters here
	} // function
	
	function admin_init()
	{
		//add_action( 'admin_init', array( &$this, 'admin_init' ) );
		//add_action( 'init', array( &$this, 'init' ) );
	} // function

}