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
		$post_id = $_GET["post_id"];
		
		if ($photos)
		{ ?>
			<script>
			jQuery(document).ready(function() {
				jQuery('.op-send-to-editor').click(function() {
					var parent_el, title_text, alt_text, caption_text, url_text, alignment, size, size_alt, op_single, img;
					parent_el = jQuery(this).parents('tbody');
					title_text = parent_el.find('.title-text').val();
					alt_text = parent_el.find('.alt-text').val();
					caption_text = parent_el.find('.caption-text').val();
					caption_text.replace(/'/g, '&#039;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
					url_text = parent_el.find('.url-text').val();
					alignment = parent_el.find('.alignment-area input[type="radio"]]:checked').val();
					size = parent_el.find('.size-area input[type="radio"]]:checked').val();
					size_alt =  parent_el.find('.size-area input[type="radio"]]:checked').attr('alt');
					size_class = 'size-' + size;
					op_single = parent_el.find('#op-single').attr('name');
					img = '';
					
					if (alt_text === "") {
						alt_text = title_text;
					}
					
					title_text = title_text;
					
					if (alignment == 'none') {
						alignment = 'alignnone';
					} else if (alignment == 'left') {
						alignment = ' alignleft ';
					} else if (alignment == 'center') {
						alignment = ' aligncenter ';
					} else if (alignment == 'right') {
						alignment = ' alignright ';
					}
					
					if (caption_text != "") {
						img += '[caption id="'+op_single+'" align="'+alignment + '" width="32" caption="'+caption_text+'"]';
						aligment = '';
					}
					
					img += '<a href="'+size_alt+'" id="'+op_single+'"><img class="'+alignment + ' ' + size_class + ' ' + '" title="' + title_text + '" src="' + size_alt + '" alt="' + alt_text + '" width="32" height="32" /></a>';
					
					if (caption_text != "") {
						img += '[/caption]';
					}
					
					var win = window.dialogArguments || opener || parent || top;
					win.send_to_editor(img);
					
					return false;
				});
			});
			</script>
            
            <form id="op-filter" action="" method="get">
                <div class="tablenav">
                    <div class="alignleft actions">
                        <select name="m">
                            <option value="0">Show all tags</option>
                            <?php
                            foreach($photos as $photo) {
								$tags = $photo->tags;
								foreach($tags as $tag) {
									echo '<option value="'.$tag.'">' . $tag . '</option>';
								}
                            } ?>
                            </select>
                        <input type="submit" name="post-query-submit" id="op-post-query-submit" class="button-secondary" value="Filter »">
                    </div>
                    <br class="clear">
                </div>
            </form>
            
			<?php echo '<form enctype="multipart/form-data" method="post" action="'.home_url().'/wp-admin/media-upload.php?type=image&amp;tab=library&amp;post_id='.$post_id.'" class="media-upload-form validate" id="library-form">';
			echo '<input type="hidden" id="_wpnonce" name="_wpnonce" value="5acb57476d" /><input type="hidden" name="_wp_http_referer" value="/wp-admin/media-upload.php?post_id='.$post_id.'&amp;type=image&amp;tab=library" />';
			echo '<script type="text/javascript">
			<!--
			jQuery(function($){
				var preloaded = $(".media-item.preloaded");
				if ( preloaded.length > 0 ) {
					preloaded.each(function(){prepareMediaItem({id:this.id.replace(/[^0-9]/g, "")},"");});
					updateMediaForm();
				}
			});
			-->
			</script>';
			echo '<div id="media-items">';
		
			foreach($photos as $photo)
			{
				$uniquie_id = $photo->dateTaken;
				
				echo '<div id="media-item-'.$uniquie_id.'" class="media-item child-of-'.$post_id.' preloaded"><div class="progress" style="display: none; "></div><div id="media-upload-error-'.$uniquie_id.'"></div><div class="filename"></div>';
				echo '<input type="hidden" id="type-of-'.$uniquie_id.'" value="image">';
				echo '<a class="toggle describe-toggle-on" href="#">Show</a>';
				echo '<a class="toggle describe-toggle-off" href="#">Hide</a>';
				echo '<input type="hidden" name="attachments['.$uniquie_id.'][menu_order]" value="0">';
				echo '<div class="filename new"><span class="title">';
				if ($photo->title != "") {
					echo $photo->title;
				} else {
					substr(strrchr($photo->pathOriginal, "/"), 1 );
				}
				echo '</span></div>';
				echo '<table class="slidetoggle describe startclosed">';
					echo '<thead class="media-item-info" id="media-head-'.$uniquie_id.'">';
						echo '<tr valign="top">';
							echo '<td class="A1B1" id="thumbnail-head-'.$uniquie_id.'">';
								echo '<p style="height:100px;padding-right:10px;"><a href="'.$post->path128x128.'" target="_blank"><img class="thumbnail" src="'.$photo->path128x128.'" alt="" style="margin-top: 3px;"></a></p>';
								//echo '<p><input type="button" id="imgedit-open-btn-'.$uniquie_id.'" onclick="imageEdit.open( '.$uniquie_id.', &quot;98f2ea4727&quot; )" class="button" value="Edit Image"> <img src="'.home_url().'/wp-admin/images/wpspin_light.gif" class="imgedit-wait-spin" alt=""></p>';
							echo '</td>';
							echo '<td>';
								echo '<p><strong>File name:</strong> '.substr(strrchr($photo->pathOriginal, "/"), 1 ).'</p>';
								echo '<p><strong>File type:</strong> .'.substr(strrchr($photo->pathOriginal, "."), 1 ).'</p>';
								echo '<p><strong>Upload date:</strong> '.date('F d Y', $photo->dateTaken).'</p>';
								echo '<p><strong>Dimensions:</strong> <span id="media-dims-'.$uniquie_id.'">'.$photo->width.'&nbsp;×&nbsp;'.$photo->height.'</span> </p>';
							echo '</td>';
						echo '</tr>';
					echo '</thead>';
					echo '<tbody>';
						echo '<input type="hidden" name="op-attachment-'.$photo->id.'" id="op-single" >';
						echo '<tr><td colspan="2" class="imgedit-response" id="imgedit-response-'.$uniquie_id.'"></td></tr>';
						echo '<tr><td style="display:none" colspan="2" class="image-editor" id="image-editor-'.$uniquie_id.'"></td></tr>';
						echo '<tr class="post_title form-required">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][post_title]"><span class="alignleft">Title</span><span class="alignright"><abbr title="required" class="required">*</abbr></span><br class="clear"></label></th>';
							echo '<td class="field"><input type="text" class="text title-text" id="attachments['.$uniquie_id.'][post_title]" name="attachments['.$uniquie_id.'][post_title]" value="'.substr(strrchr($photo->pathOriginal, "/"), 1 ).'" aria-required="true"></td>';
						echo '</tr>';
						echo '<tr class="image_alt">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][image_alt]"><span class="alignleft">Alternate Text</span><br class="clear"></label></th>';
							echo '<td class="field"><input type="text" class="text alt-text" id="attachments['.$uniquie_id.'][image_alt]" name="attachments['.$uniquie_id.'][image_alt]" value=""><p class="help">Alt text for the image, e.g. "The Mona Lisa"</p></td>';
						echo '</tr>';
						echo '<tr class="post_excerpt">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][post_excerpt]"><span class="alignleft">Caption</span><br class="clear"></label></th>';
							echo '<td class="field"><input type="text" class="text caption-text" id="attachments['.$uniquie_id.'][post_excerpt]" name="attachments['.$uniquie_id.'][post_excerpt]" value=""></td>';
						echo '</tr>';
						//echo '<tr class="post_content">';
							//echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][post_content]"><span class="alignleft">Description</span><br class="clear"></label></th>';
							//echo '<td class="field"><textarea id="attachments['.$uniquie_id.'][post_content]" name="attachments['.$uniquie_id.'][post_content]"></textarea></td>';
						//echo '</tr>';
						echo '<tr class="url">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][url]"><span class="alignleft">Link URL</span><br class="clear"></label></th>';
							echo '<td class="field">';
								echo '<input type="text" class="text urlfield url-text" name="attachments['.$uniquie_id.'][url]" value="http://'.$photo->host.$photo->pathOriginal.'"><br>';
								echo '<button type="button" class="button urlnone" title="">None</button>';
								echo '<button type="button" class="button urlfile" title="http://'.$photo->host.$photo->pathOriginal.'">File URL</button>';
								//echo '<button type="button" class="button urlpost" title="http://2011.handcraftedwp.com/?attachment_id='.$uniquie_id.'">Post URL</button>';
								echo '<p class="help">Enter a link URL or click above for presets.</p>';
							echo '</td>';
						echo '</tr>';
						echo '<tr class="align">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][align]"><span class="alignleft">Alignment</span><br class="clear"></label></th>';
							echo '<td class="field alignment-area">';
								echo '<input type="radio" name="attachments['.$uniquie_id.'][align]" id="image-align-none-'.$uniquie_id.'" value="none" checked="checked"><label for="image-align-none-'.$uniquie_id.'" class="align image-align-none-label">None</label>';
								echo '<input type="radio" name="attachments['.$uniquie_id.'][align]" id="image-align-left-'.$uniquie_id.'" value="left"><label for="image-align-left-'.$uniquie_id.'" class="align image-align-left-label">Left</label>';
								echo '<input type="radio" name="attachments['.$uniquie_id.'][align]" id="image-align-center-'.$uniquie_id.'" value="center"><label for="image-align-center-'.$uniquie_id.'" class="align image-align-center-label">Center</label>';
								echo '<input type="radio" name="attachments['.$uniquie_id.'][align]" id="image-align-right-'.$uniquie_id.'" value="right"><label for="image-align-right-'.$uniquie_id.'" class="align image-align-right-label">Right</label>';
							echo '</td>';
						echo '</tr>';
						echo '<tr class="image-size">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$uniquie_id.'][image-size]"><span class="alignleft">Size</span><br class="clear"></label></th>';
							echo '<td class="field size-area">';
								echo '<div class="image-size-item"><input type="radio" name="attachments['.$uniquie_id.'][image-size]" id="image-size-thumbnail-'.$uniquie_id.'" value="thumbnail" alt="'.$photo->path32x32.'" checked="checked"><label for="image-size-thumbnail-'.$uniquie_id.'">Thumbnail</label> <label for="image-size-thumbnail-'.$uniquie_id.'" class="help">(150&nbsp;×&nbsp;150)</label></div>';
								echo '<div class="image-size-item"><input type="radio" name="attachments['.$uniquie_id.'][image-size]" id="image-size-medium-'.$uniquie_id.'" value="medium" alt="'.$photo->path128x128.'"><label for="image-size-medium-'.$uniquie_id.'">Medium</label> <label for="image-size-medium-'.$uniquie_id.'" class="help">(300&nbsp;×&nbsp;187)</label></div>';
								echo '<div class="image-size-item"><input type="radio" name="attachments['.$uniquie_id.'][image-size]" id="image-size-large-'.$uniquie_id.'" value="large" alt="'.$photo->path32x32.'"><label for="image-size-large-'.$uniquie_id.'">Large</label> <label for="image-size-large-'.$uniquie_id.'" class="help">(584&nbsp;×&nbsp;365)</label></div>';
								echo '<div class="image-size-item"><input type="radio" name="attachments['.$uniquie_id.'][image-size]" id="image-size-full-'.$uniquie_id.'" value="full" alt="'.$photo->path32x32.'"><label for="image-size-full-'.$uniquie_id.'">Full Size</label> <label for="image-size-full-'.$uniquie_id.'" class="help">('.$photo->height.'&nbsp;×&nbsp;'.$photo->width.')</label></div>';
							echo '</td>';
						echo '</tr>';
				echo '<tr class="submit">';
				echo '<td></td>';
				echo '<td class="savesend">';
				echo '<input type="submit" name="send['.$uniquie_id.']" id="send['.$uniquie_id.']" class="op-send-to-editor button" value="Insert into Post">';
				//echo '<input type="submit" name="send['.$uniquie_id.']" id="send['.$uniquie_id.']" class="button" value="Insert into Post"> ';
				//echo '<a class="wp-post-thumbnail" id="wp-post-thumbnail-'.$uniquie_id.'" href="#" onclick="WPSetAsThumbnail(&quot;'.$uniquie_id.'&quot;, &quot;2cf0f581b0&quot;);return false;">Use as featured image</a> ';
				//echo '<a href="#" class="del-link" onclick="document.getElementById(\'del_attachment_'.$uniquie_id.'\').style.display=\'block\';return false;">Delete</a>';
				//echo ' <div id="del_attachment_'.$uniquie_id.'" class="del-attachment" style="display:none;">You are about to delete <strong>splash_1920x1200.jpg</strong>.';
				//echo '<a href="post.php?action=delete&amp;post='.$uniquie_id.'&amp;_wpnonce=3bfab9cd8c" id="del['.$uniquie_id.']" class="button">Continue</a>';
				//echo '<a href="#" class="button" onclick="this.parentNode.style.display=\'none\';return false;">Cancel</a>';
				echo '</div>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '</div>';
				
				//if ($photo->path32x32 != "") echo '<img src="' . $photo->path32x32 . '" /> &nbsp;';	
				//if ($photo->title != "") { echo $photo->title; } else { echo $photo->pathBase; }
				//print_r($photo);
			}
			
			echo '</div>';
			
			echo '<p class="ml-submit">';
				echo '<input type="submit" name="save" id="save" class="button savebutton" value="Save all changes">';
				echo '<input type="hidden" name="post_id" id="post_id" value="'.$post_id.'">';
			echo '</p>';
			
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