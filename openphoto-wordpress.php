<?php
/*
Plugin Name: OpenPhoto for WordPress
Version: 0.9.5
Plugin URI: https://github.com/openphoto/openphoto-wordpress
Author: Randy Hoyt, Randy Jensen
Author URI: http://amesburyweb.com/
Description: Connects a WordPress installation to an OpenPhoto installation.  
*/

class WP_OpenPhoto {

	function WP_OpenPhoto() {
		$this->__construct();
	}

	function __construct() {
		new WP_OpenPhoto_Settings;
		add_filter('media_upload_tabs', array( &$this, 'media_add_openphoto_tab' ));
		add_action('media_upload_openphoto', array( &$this, 'media_include_openphoto_iframe'));
	}

	function media_add_openphoto_tab( $tabs ) {
		$tab = array('openphoto' => __('OpenPhoto', 'openphoto'));
		return array_merge($tabs, $tab);
	}

	function media_include_openphoto_iframe() {
    	return wp_iframe( array( &$this, 'media_render_openphoto_tab'));
	}	
	
	function media_render_openphoto_tab() {
    	
    	if ( $wp_version >= 3.5 ) {
        	// do nothing
        } else {
    		media_upload_header();
        }
		
		$post_id = intval($_REQUEST['post_id']);
		$m = trim($_REQUEST['m']);
		$pg = trim($_REQUEST['pg']);

		$openphoto = get_option('openphoto_wordpress_settings');
		$client = new OpenPhotoOAuth(str_replace('http://','',$openphoto['host']),$openphoto["oauth_consumer_key"],$openphoto["oauth_consumer_secret"],$openphoto["oauth_token"],$openphoto["oauth_token_secret"]);
		
		// get photos 
		$sizes['thumbnail']['w'] = get_option('thumbnail_size_w');
		$sizes['thumbnail']['h'] = get_option('thumbnail_size_h');
		$sizes['thumbnail']['crop'] = get_option('thumbnail_crop');
		$sizes['thumbnail']      = $sizes['thumbnail']['w'] . 'x' . $sizes['thumbnail']['h'];
		if (isset($sizes['thumbnail']['crop']) && $sizes['thumbnail']['crop']==1) $sizes['thumbnail'] .= 'xCR';
		$sizes['medium']['w']    = get_option('medium_size_w');
		$sizes['medium']['h']    = get_option('medium_size_h');
		$sizes['medium']         = $sizes['medium']['w'] . 'x' . $sizes['medium']['h']; 
		$sizes['large']['w']     = get_option('large_size_w');
		$sizes['large']['h']     = get_option('large_size_h');
		$sizes['large']          = $sizes['large']['w'] . 'x' . $sizes['large']['h'];

		$parameters['returnSizes'] = '32x32xCR,128x128,'. $sizes['thumbnail'] . ',' . $sizes['medium']  . ',' . $sizes['large'];
		if(!empty($m)) $parameters['tags'] = $m;
		if(!empty($pg)) $parameters['page'] = $pg;
		$parameters['generate'] = 'true';
		$parameters['generated'] = 'true';
		$response = $client->get("/photos/list.json", $parameters);
		$response = json_decode($response);
		$photos = $response->result;
		
		// get tags 
		$response = $client->get("/tags/list.json");
		$response = json_decode($response);
		$tags = $response->result;
		?>
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
					alignment = parent_el.find('.alignment-area input[type="radio"]:checked').val();
					size = parent_el.find('.size-area input[type="radio"]:checked').val();
					size_height = parent_el.find('.size-area input[type="radio"]:checked').attr('data-image-height');
					size_width = parent_el.find('.size-area input[type="radio"]:checked').attr('data-image-width');
					size_alt =  parent_el.find('.size-area input[type="radio"]:checked').attr('alt');
					size_class = 'size-' + size;
					height = size_height;
					width = size_width;
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
						img += '[caption id="'+op_single+'" align="'+alignment + '" width="' + width + '" caption="'+caption_text+'"]';
						aligment = '';
					}
					
					img += '<a href="'+url_text+'" id="'+op_single+'"><img class="'+alignment + ' ' + size_class + ' ' + '" title="' + title_text + '" src="' + size_alt + '" alt="' + alt_text + '" width="' + width + '" height="' + height + '" /></a>';
					
					if (caption_text != "") {
						img += '[/caption]';
					}
					
					var win = window.dialogArguments || opener || parent || top;
					win.send_to_editor(img);
					
					return false;
				});
			});
			</script>

        <form id="op-filter" action="?post_id=<?php echo $post_id ?>&type=image&tab=openphoto" method="post">
            <input type="hidden" name="type" value="image">
            <input type="hidden" name="tab" value="library">
            <input type="hidden" name="post_id" value="<?php echo $post_id ?>">
            <input type="hidden" name="post_mime_type" value="">
            <ul class="subsubsub">
            <?php
            if ( $photos ) {
                $total_pages = $photos[0]->totalPages;
                $current_page = $photos[0]->currentPage;
                $total_photos = $photos[0]->totalRows;
            	echo '<li>Total Images <span class="count">(<span id="image-counter">'. $total_photos . '</span>)</span></li>';	
            }
			
            ?></ul>
            <div class="tablenav">
            <?php
            if ( $photos ) {			
                if ($total_pages > 1) {
                    echo '<div class="tablenav-pages">';

                    if ($current_page > 1) {
                    	echo '<a class="next page-numbers" href="?post_id='. $post_id . '&amp;type=image&amp;tab=openphoto&amp;m=' . $m . '&amp;pg='. ($current_page-1) . '">&laquo;</a> ';
                    }
                    for( $i=1;$i<=$total_pages;$i++ ) {
                        $current = "";	
                        if ($current_page == $i) {
                            $current = ' current ';
                            echo '<span class="page-numbers'. $current . '">'. $i . '</span> ';
                        } else {
                            echo '<a class="page-numbers" href="?post_id=' . $post_id . '&amp;type=image&amp;tab=openphoto&amp;m=' . $m . '&amp;pg='. $i . '">'. $i . '</a> ';
                        }
                    }

                    if ($current_page < $total_pages) {
                        echo '<a class="next page-numbers" href="?post_id='. $post_id . '&amp;type=image&amp;tab=openphoto&amp;m=' . $m . '&amp;pg='. ($current_page+1) . '">&raquo;</a> ';
                    }
                    echo '</div>';
                }
            }
            
            if ( $tags ) {
			?><div class="alignleft actions">
                    <select name="m">
                        <option value="0">Show all tags</option>
                        <?php
                            foreach( $tags as $tag ) {
                                $tag->id = trim($tag->id);
                                $selected = "";	
                                if ( $tag->id==$m ) $selected = ' selected="selected"';
                                if ($tag->count > 0) echo '<option value="'.$tag->id .'"' . $selected . '>' . $tag->id . ' (' . $tag->count . ')</option>';
                            }
                        ?>
                        </select>
                    <input type="submit" name="post-query-submit" id="op-post-query-submit" class="button-secondary" value="Filter »">
                </div>
                <br class="clear">
            <?php } ?></div>
        </form>
		<?php             
		if ( $photos ) { 
            
			echo '<form enctype="multipart/form-data" method="post" action="'.home_url().'/wp-admin/media-upload.php?type=image&amp;tab=library&amp;post_id='.$post_id.'" class="media-upload-form validate" id="library-form">';
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
			
			$size_for_image_url_link = $openphoto['size'];
			if ($size_for_image_url_link == "") $size_for_image_url_link = "original";					

			foreach( $photos as $unique_id => $photo ) {
					
				$src = array();
				$src["thumbnail"] = $photo->{"photo".$sizes['thumbnail']}[0];
				$src["medium"] = $photo->{"photo".$sizes['medium']}[0];
				$src["large"] = $photo->{"photo".$sizes['large']}[0];
				$src["original"] = $photo->pathOriginal;
				if (strpos($src["original"],"http")===false) $src["original"] = 'http://'.$photo->host.$photo->pathOriginal; // in older versions of the API, pathOriginal did not have the full address

				$info = pathinfo(basename($src["original"]));
				$photo->extension = $info['extension'];
				if ("" == $photo->title) {$photo->title = basename($src["original"],'.'.$photo->extension);}				
							
				echo '<div id="media-item-'.$unique_id.'" class="media-item child-of-'.$post_id.' preloaded"><div class="progress" style="display: none; "></div><div id="media-upload-error-'.$unique_id.'"></div><div class="filename"></div>';
				echo '<input type="hidden" id="type-of-'.$unique_id.'" value="image">';
				echo '<a class="toggle describe-toggle-on" href="#">Show</a>';
				echo '<a class="toggle describe-toggle-off" href="#">Hide</a>';
				echo '<input type="hidden" name="attachments['.$unique_id.'][menu_order]" value="0">';
				echo '<div class="filename new"><span class="title">';
				echo esc_attr($photo->title);
				echo '</span></div>';
				echo '<table class="slidetoggle describe startclosed">';
					echo '<thead class="media-item-info" id="media-head-'.$unique_id.'">';
						echo '<tr valign="top">';
							echo '<td class="A1B1" id="thumbnail-head-'.$unique_id.'">';
								echo '<p style="height:100px;padding-right:10px;"><a href="'.$src["original"].'" target="_blank"><img class="thumbnail" src="'.$photo->path128x128.'" alt="" style="margin-top: 3px;"></a></p>';
								//echo '<p><input type="button" id="imgedit-open-btn-'.$unique_id.'" onclick="imageEdit.open( '.$unique_id.', &quot;98f2ea4727&quot; )" class="button" value="Edit Image"> <img src="'.home_url().'/wp-admin/images/wpspin_light.gif" class="imgedit-wait-spin" alt=""></p>';
							echo '</td>';
							echo '<td>';
								echo '<p><strong>File name:</strong> '.$photo->title.'</p>';
								echo '<p><strong>File type:</strong> '.$photo->extension.'</p>';
								echo '<p><strong>Upload date:</strong> '.date('F d Y', (int) $photo->dateUploaded).'</p>';
								echo '<p><strong>Dimensions:</strong> <span id="media-dims-'.$unique_id.'">'.$photo->width.'&nbsp;×&nbsp;'.$photo->height.'</span> </p>';
							echo '</td>';
						echo '</tr>';
					echo '</thead>';
					echo '<tbody>';
						echo '<input type="hidden" name="op-attachment-'.$photo->id.'" id="op-single" >';
						echo '<tr><td colspan="2" class="imgedit-response" id="imgedit-response-'.$unique_id.'"></td></tr>';
						echo '<tr><td style="display:none" colspan="2" class="image-editor" id="image-editor-'.$unique_id.'"></td></tr>';
						echo '<tr class="post_title form-required">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][post_title]"><span class="alignleft">Title</span><span class="alignright"><abbr title="required" class="required">*</abbr></span><br class="clear"></label></th>'; 
							echo '<td class="field"><input type="text" class="text title-text" id="attachments['.$unique_id.'][post_title]" name="attachments['.$unique_id.'][post_title]" value="'.$photo->title.'" aria-required="true"></td>';
						echo '</tr>';
						echo '<tr class="image_alt">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][image_alt]"><span class="alignleft">Alternate Text</span><br class="clear"></label></th>';
							echo '<td class="field"><input type="text" class="text alt-text" id="attachments['.$unique_id.'][image_alt]" name="attachments['.$unique_id.'][image_alt]" value=""><p class="help">Alt text for the image, e.g. "The Mona Lisa"</p></td>';
						echo '</tr>';
						echo '<tr class="post_excerpt">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][post_excerpt]"><span class="alignleft">Caption</span><br class="clear"></label></th>';
							echo '<td class="field"><input type="text" class="text caption-text" id="attachments['.$unique_id.'][post_excerpt]" name="attachments['.$unique_id.'][post_excerpt]" value=""></td>';
						echo '</tr>';
						//echo '<tr class="post_content">';
							//echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][post_content]"><span class="alignleft">Description</span><br class="clear"></label></th>';
							//echo '<td class="field"><textarea id="attachments['.$unique_id.'][post_content]" name="attachments['.$unique_id.'][post_content]"></textarea></td>';
						//echo '</tr>';
						echo '<tr class="url">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][url]"><span class="alignleft">Link URL</span><br class="clear"></label></th>';
							echo '<td class="field">';
								echo '<input type="text" class="text urlfield url-text" name="attachments['.$unique_id.'][url]" value="'.$src[$size_for_image_url_link].'"><br>';
								echo '<button type="button" class="button urlnone" title="">None</button>';
								echo '<button type="button" class="button urlfile" title="'.$src[$size_for_image_url_link].'">File URL</button>';
								echo '<button type="button" class="button urlpost" title="'.$photo->url.'">OpenPhoto URL</button>';
								echo '<p class="help">Enter a link URL or click above for presets.</p>';
							echo '</td>';
						echo '</tr>';
						echo '<tr class="align">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][align]"><span class="alignleft">Alignment</span><br class="clear"></label></th>';
							echo '<td class="field alignment-area">';
								echo '<input type="radio" name="attachments['.$unique_id.'][align]" id="image-align-none-'.$unique_id.'" value="none" checked="checked"><label for="image-align-none-'.$unique_id.'" class="align image-align-none-label">None</label>';
								echo '<input type="radio" name="attachments['.$unique_id.'][align]" id="image-align-left-'.$unique_id.'" value="left"><label for="image-align-left-'.$unique_id.'" class="align image-align-left-label">Left</label>';
								echo '<input type="radio" name="attachments['.$unique_id.'][align]" id="image-align-center-'.$unique_id.'" value="center"><label for="image-align-center-'.$unique_id.'" class="align image-align-center-label">Center</label>';
								echo '<input type="radio" name="attachments['.$unique_id.'][align]" id="image-align-right-'.$unique_id.'" value="right"><label for="image-align-right-'.$unique_id.'" class="align image-align-right-label">Right</label>';
							echo '</td>';
						echo '</tr>';
						echo '<tr class="image-size">';
							echo '<th valign="top" scope="row" class="label"><label for="attachments['.$unique_id.'][image-size]"><span class="alignleft">Size</span><br class="clear"></label></th>';
							echo '<td class="field size-area">';
								$checked = ' checked="checked"';
								$thumbnail_width = $photo->{"photo".$sizes['thumbnail']}[1];
								$thumbnail_height = $photo->{"photo".$sizes['thumbnail']}[2];
								echo '<div class="image-size-item">';
								if ($thumbnail_width < $photo->width || $thumbnail_height < $photo->height) {
									echo '<input type="radio" name="attachments['.$unique_id.'][image-size]" id="image-size-thumbnail-'.$unique_id.'" value="thumbnail" alt="'.$photo->{"photo".$sizes['thumbnail']}[0] . '" data-image-height="'.$thumbnail_height.'" data-image-width="'.$thumbnail_width.'"' . $checked . '><label for="image-size-thumbnail-'.$unique_id.'">Thumbnail</label> <label for="image-size-thumbnail-'.$unique_id.'" class="help">(' . $thumbnail_width. '&nbsp;×&nbsp;' . $thumbnail_height . ')</label>';
									$checked = "";
								} else {
									echo '<input type="radio" disabled="disabled" /><label for="image-size-thumbnail-'.$unique_id.'">Thumbnail</label>';								
								}
								echo '</div>';								
								$medium_width = $photo->{"photo".$sizes['medium']}[1];
								$medium_height = $photo->{"photo".$sizes['medium']}[2];
								echo '<div class="image-size-item">';
								if ($medium_width < $photo->width || $medium_height < $photo->height) {
									echo '<input type="radio" name="attachments['.$unique_id.'][image-size]" id="image-size-medium-'.$unique_id.'" value="medium" alt="'.$photo->{"photo".$sizes['medium']}[0].'" data-image-height="'.$medium_height.'" data-image-width="'.$medium_width.'"' . $checked . '><label for="image-size-medium-'.$unique_id.'">Medium</label> <label for="image-size-medium-'.$unique_id.'" class="help">(' . $medium_width . '&nbsp;×&nbsp;' . $medium_height . ')</label>';
									$checked = "";
								} else {
									echo '<input type="radio" disabled="disabled" /><label for="image-size-medium-'.$unique_id.'">Medium</label>';								
								}
								echo '</div>';
								$large_width = $photo->{"photo".$sizes['large']}[1];
								$large_height = $photo->{"photo".$sizes['large']}[2];
								echo '<div class="image-size-item">';								
								if ($large_width < $photo->width || $large_height < $photo->height) {
									echo '<input type="radio" name="attachments['.$unique_id.'][image-size]" id="image-size-large-'.$unique_id.'" value="large" alt="'.$photo->{"photo".$sizes['large']}[0].'" data-image-height="'.$large_height.'" data-image-width="'.$large_width.'"' . $checked . '><label for="image-size-large-'.$unique_id.'">Large</label> <label for="image-size-large-'.$unique_id.'" class="help">('. $large_width . '&nbsp;×&nbsp;'. $large_height . ')</label>';
									$checked = "";
								} else {
									echo '<input type="radio" disabled="disabled" /><label for="image-size-large-'.$unique_id.'">Large</label>';
								}
								echo '</div>';
								echo '<div class="image-size-item"><input type="radio" name="attachments['.$unique_id.'][image-size]" id="image-size-full-'.$unique_id.'" value="full" alt="'.$src["original"].'" data-image-height="'.$photo->height.'" data-image-width="'.$photo->width.'"' . $checked . '><label for="image-size-full-'.$unique_id.'">Full Size</label> <label for="image-size-full-'.$unique_id.'" class="help">('.$photo->width.'&nbsp;×&nbsp;'.$photo->height.')</label></div>';
							echo '</td>';
						echo '</tr>';
				echo '<tr class="submit">';
				echo '<td></td>';
				echo '<td class="savesend">';
				echo '<input type="submit" name="send['.$unique_id.']" id="send['.$unique_id.']" class="op-send-to-editor button" value="Insert into Post">';
				//echo '<input type="submit" name="send['.$unique_id.']" id="send['.$unique_id.']" class="button" value="Insert into Post"> ';
				//echo '<a class="wp-post-thumbnail" id="wp-post-thumbnail-'.$unique_id.'" href="#" onclick="WPSetAsThumbnail(&quot;'.$unique_id.'&quot;, &quot;2cf0f581b0&quot;);return false;">Use as featured image</a> ';
				//echo '<a href="#" class="del-link" onclick="document.getElementById(\'del_attachment_'.$unique_id.'\').style.display=\'block\';return false;">Delete</a>';
				//echo ' <div id="del_attachment_'.$unique_id.'" class="del-attachment" style="display:none;">You are about to delete <strong>splash_1920x1200.jpg</strong>.';
				//echo '<a href="post.php?action=delete&amp;post='.$unique_id.'&amp;_wpnonce=3bfab9cd8c" id="del['.$unique_id.']" class="button">Continue</a>';
				//echo '<a href="#" class="button" onclick="this.parentNode.style.display=\'none\';return false;">Cancel</a>';
				echo '</div>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '</div>';
				
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
		add_action( 'admin_init', array( &$this, 'settings_init') );
		add_action( 'admin_menu', array( &$this, 'settings_add_openphoto_page') );		
	}
	
	function settings_init() {
		register_setting( 'openphoto_wordpress_settings', 'openphoto_wordpress_settings', array(&$this,'settings_validate_submission'));
	}				
	
	function settings_add_openphoto_page() {
		add_options_page( 'Configure OpenPhoto Integration', 'OpenPhoto', 'manage_options', 'openphoto_wordpress_settings', array( &$this, 'settings_render_openphoto_page') );
	}

	function settings_render_openphoto_page() {
		
		$openphoto = get_option('openphoto_wordpress_settings');		
		
		$action = $_REQUEST['action'];
		if ( "update" == $action ) {		

			$nonce=$_REQUEST['_wpnonce'];
			if (! wp_verify_nonce($nonce, 'openphoto_wordpress_settings') ) wp_die('You do not have permission to save this page.');
			
			$input = $_REQUEST['openphoto_wordpress_settings'];
			$newinput['host'] = trim($input['host']);
			$newinput['size'] = trim($input['size']);			
			$newinput['oauth_consumer_key'] = trim($input['oauth_consumer_key']);
			$newinput['oauth_consumer_secret'] = trim($input['oauth_consumer_secret']);		
			$newinput['unauthorized_token'] = trim($input['unauthorized_token']);		
			$newinput['unauthorized_token_secret'] = trim($input['unauthorized_token_secret']);		
			$newinput['oauth_token'] = trim($input['oauth_token']);		
			$newinput['oauth_token_secret'] = trim($input['oauth_token_secret']);		
			$newinput['oauth_verifier'] = trim($input['oauth_verifier']);

			if ( $newinput['host'] != $openphoto['host'] ) {
				$host_changed = true;
			}			

			$openphoto = $newinput;
			update_option('openphoto_wordpress_settings',$openphoto);

			if ($host_changed || empty( $openphoto['oauth_token'] ) || empty( $openphoto['oauth_token_secret'] ) ) {
				wp_redirect(trailingslashit(esc_attr($openphoto['host'])) . 'v1/oauth/authorize?oauth_callback=' . urlencode(admin_url("options-general.php?page=openphoto_wordpress_settings&action=authenticate&noheader=true")) . '&name=' . urlencode('OpenPhoto WordPress Plugin (' . ereg_replace("(https?)://", "", get_bloginfo('url')) . ')'));
			} else {
				wp_redirect('options-general.php?page=openphoto_wordpress_settings&message=1');
			}

		} elseif ( "authenticate" == $action ) {

			// if all the values are set
			if ( isset($_REQUEST["oauth_consumer_key"]) &&
				 isset($_REQUEST["oauth_consumer_key"]) &&
				 isset($_REQUEST["oauth_consumer_secret"]) &&
				 isset($_REQUEST["oauth_token"]) && 
				 isset($_REQUEST["oauth_token_secret"]) && 		
				 isset($_REQUEST["oauth_verifier"]) ) {
	
				// if even one of the values in the database is different than those in the request
				if ( $openphoto['oauth_consumer_key'] != $_REQUEST["oauth_consumer_key"] || 
					 $openphoto['oauth_consumer_secret'] != $_REQUEST["oauth_consumer_secret"] ||
					 $openphoto['unauthorized_token'] != $_REQUEST["oauth_token"] ||
					 $openphoto['unauthorized_token_secret'] != $_REQUEST["oauth_token_secret"] ||
					 $openphoto['oauth_verifier'] != $_REQUEST["oauth_verifier"]) {
			/*
					$curl_post = array('oauth_consumer_key' => $_REQUEST["oauth_consumer_key"],'oauth_consumer_secret' => $_REQUEST["oauth_consumer_secret"], 'oauth_token' => $_REQUEST["oauth_token"], 'oauth_token_secret' => $_REQUEST["oauthoauth_token_secret_token"], 'oauth_token_secret' => $_REQUEST["oauthoauth_token_secret_token"], 'oauth_verifier' => $_REQUEST['oauth_verifier']);
					$curl_options = array(
			  			CURLOPT_POST => 1,
		  				CURLOPT_HEADER => 0,
		  				CURLOPT_URL => trailingslashit($openphoto['host']) . 'v1/oauth/token/access',
		  				CURLOPT_FRESH_CONNECT => 1,
		  				CURLOPT_RETURNTRANSFER => 1,
			  			CURLOPT_POSTFIELDS => http_build_query($curl_post)
					);
					$ch = curl_init();
					curl_setopt_array($ch, $curl_options);
					$response = curl_exec($ch);
					curl_close($ch);
			 
			 */
					if( !class_exists( 'WP_Http' ) )
						include_once( ABSPATH . WPINC. '/class-http.php' );
					$request = new WP_Http;					
					$body = array(
						'oauth_consumer_key' => $_REQUEST["oauth_consumer_key"],
						'oauth_consumer_secret' => $_REQUEST["oauth_consumer_secret"],
						'oauth_token' => $_REQUEST["oauth_token"],
						'oauth_token_secret' => $_REQUEST["oauthoauth_token_secret_token"],
						'oauth_token_secret' => $_REQUEST["oauthoauth_token_secret_token"],
						'oauth_verifier' => $_REQUEST['oauth_verifier']
					);
					$url = trailingslashit($openphoto['host']) . 'v1/oauth/token/access';
					$result = $request->request( $url, array( 'method' => 'POST', 'body' => $body) );
					
					if ($result['response']['code'] == 200) {
						$access = wp_parse_args($result['body']);
						$openphoto['oauth_consumer_key'] = $_REQUEST["oauth_consumer_key"];
						$openphoto['oauth_consumer_secret'] = $_REQUEST["oauth_consumer_secret"];
						$openphoto['oauth_token'] = $access['oauth_token'];
						$openphoto['oauth_token_secret'] = $access['oauth_token_secret'];
						
						if ( isset($access['oauth_token']) && $access['oauth_token_secret']) {
							$message = 2;
						} else {
							$message = 3;
						}
					} else {
						$message = 3;						
					}					
				}

				update_option('openphoto_wordpress_settings',$openphoto);
				wp_redirect('options-general.php?page=openphoto_wordpress_settings&message=' . $message);

			}			
		}	
		
		echo '<div class="wrap">';
		echo '<div class="icon32" id="icon-options-general"><br /></div>';
		echo '<h2>Configure OpenPhoto Integration</h2>';
		
		switch ( $_REQUEST['message'] ) {
			case 1:
				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Settings saved.</strong></p></div>';
				break;
			case 2:
				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Your OpenPhoto credentials have been retrieved, and your settings have been saved.</strong></p></div>';
				break;		
			case 3:
				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>There was an error retrieving your OpenPhoto credentials; please save these settings to try again.</strong></p></div>';
				break;
		}		
		
		echo '<form action="?page=openphoto_wordpress_settings&noheader=true" method="post" id="openphoto_wordpress_settings_form">';
		echo '<input type="hidden" id="_wpnonce" name="_wpnonce" value="' .  wp_create_nonce('openphoto_wordpress_settings') . '" />';		
		echo '<input type="hidden" name="action" value="update" />';		
		echo '<table class="form-table">';
		echo '<tr valign="top"><th scope="row">Host</th><td><input id="openphoto_wordpress_settings_host" name="openphoto_wordpress_settings[host]" size="100" type="text" value="' . esc_attr($openphoto['host']) . '" />';
		echo '<p class="description"><em>Enter the web address of the home page of your OpenPhoto installation.</em></p></td></tr>';
		echo '<tr valign="top"><th scope="row">Size for Links</th><td><select id="openphoto_wordpress_settings_size" name="openphoto_wordpress_settings[size]">';
		$sizes = array();
		$sizes[0] = 'Original';
		$sizes[1] = 'Thumbnail';
		$sizes[2] = 'Medium';
		$sizes[3] = 'Large';
		foreach ($sizes as $size) {
			$selected = '';	
			if (strtolower($size)==$openphoto['size']) $selected = ' selected="selected"';
			echo '<option value="' . strtolower($size) . '"' . $selected . '>' . $size . '</option>';
		}
		echo 'type="text" value="' . esc_attr($openphoto['size']) . '" />';
		echo '</select><p class="description"><em>Select which size of the image the File URL link should reference when an image is inserted into a post.</em></p></td></tr>';
		echo '<tr style="display: none;" valign="top"><th scope="row">Consumer Key</th><td><input id="openphoto_wordpress_settings_oauth_consumer_key" name="openphoto_wordpress_settings[oauth_consumer_key]" size="40" type="text" value="' . esc_attr($openphoto['oauth_consumer_key']) . '" /></td></tr>';
		echo '<tr style="display: none;" valign="top"><th scope="row">Consumer Secret</th><td><input id="openphoto_wordpress_settings_oauth_consumer_secret" name="openphoto_wordpress_settings[oauth_consumer_secret]" size="40" type="text" value="' . esc_attr($openphoto['oauth_consumer_secret']) . '" /></td></tr>';
		echo '<tr style="display: none;" valign="top"><th scope="row">Token</th><td><input id="openphoto_wordpress_settings_oauth_token" name="openphoto_wordpress_settings[oauth_token]" size="40" type="text" value="' . esc_attr($openphoto['oauth_token']) . '" /></td></tr>';
		echo '<tr style="display: none;" valign="top"><th scope="row">Token Secret</th><td><input id="openphoto_wordpress_settings_oauth_token_secret" name="openphoto_wordpress_settings[oauth_token_secret]" size="40" type="text" value="' . esc_attr($openphoto['oauth_token_secret']) . '" /></td></tr>';
		echo '</table>';
		echo '<input id="openphoto_wordpress_settings_unauthorized_token" name="openphoto_wordpress_settings[unauthorized_token]" type="hidden" value="' . esc_attr($openphoto['unauthorized_token']) . '" />';
		echo '<input id="openphoto_wordpress_settings_unauthorized_token_secret" name="openphoto_wordpress_settings[unauthorized_token_secret]" type="hidden" value="' . esc_attr($openphoto['unauthorized_token_secret']) . '" />';
		echo '<input id="openphoto_wordpress_settings_oauth_verifier" name="openphoto_wordpress_settings[oauth_verifier]" type="hidden" value="' . esc_attr($openphoto['oauth_verifier']) . '" />';
		echo '<p class="submit"><input class="button-primary" name="submit" type="submit" value="' . esc_attr('Save Changes') . '" /></p>';
		echo '</form>';
		echo '</div>';

	}
	
	function settings_validate_submission( $input ) {
		
		$newinput['host'] = trim($input['host']);
		$newinput['size'] = trim($input['size']);
		$newinput['oauth_consumer_key'] = trim($input['oauth_consumer_key']);
		$newinput['oauth_consumer_secret'] = trim($input['oauth_consumer_secret']);		
		$newinput['unauthorized_token'] = trim($input['unauthorized_token']);		
		$newinput['unauthorized_token_secret'] = trim($input['unauthorized_token_secret']);		
		$newinput['oauth_token'] = trim($input['oauth_token']);		
		$newinput['oauth_token_secret'] = trim($input['oauth_token_secret']);		
		$newinput['oauth_verifier'] = trim($input['oauth_verifier']);

		return $newinput;
	}	

}

new WP_OpenPhoto;
require_once('openphoto-php/OpenPhotoOAuth.php');
