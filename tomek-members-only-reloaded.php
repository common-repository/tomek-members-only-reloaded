<?php
/*
Plugin Name: Tomek Members Only Reloaded
Description: This plugin lets only the posts without more tag read for the visitors . Otherwise it is only to the more tag readable.
Author: Tomek
Author URI: https://wp-learning.net
Plugin URI: https://wp-learning.net/blog/csak-tagoknak-2
Version: 1.1
*/

add_filter('the_content','members_only');
add_action('add_meta_boxes','members_only_meta_box');
add_action('save_post','members_only_post'); 
register_deactivation_hook( __FILE__, 'deactivation_members_only' );
load_plugin_textdomain( 'members_only', '', dirname( plugin_basename( __FILE__ ) ) . '/lang' );

function deactivation_members_only() {
	global $wpdb;
	$wpdb->get_results("DELETE FROM $wpdb->prefix.`postmeta` WHERE `meta_key` LIKE 'members_only'");
	$wpdb->get_results("DELETE FROM $wpdb->prefix.`postmeta` WHERE `meta_key` LIKE 'members_only_advertise'");
	$wpdb->get_results("DELETE FROM $wpdb->prefix.`postmeta` WHERE `meta_key` LIKE 'members_only_code'");
}

function members_only_meta_box() {
    add_meta_box('members-only-meta-box',__('Members Only','members_only'),'members_only_meta','post','side','high');
    add_meta_box('members-only-meta-box',__('Members Only','members_only'),'members_only_meta','page','side','high');
}

function members_only_meta($post) {
    $post_id = get_post_meta($post->ID);
		?>
		<label for="members_only"><input type="checkbox" name="members_only" id="members_only" value="disabled" <?php if ( isset ( $post_id['members_only'] ) ) checked( $post_id['members_only'][0], 'disabled' ); ?>><?php _e('Everyone can read it','members_only') ?></label>
		<br>
		<label for="members_only_advertise"><input type="checkbox" name="members_only_advertise" id="members_only_advertise" value="yes" <?php if ( isset ( $post_id['members_only_advertise'] ) ) checked( $post_id['members_only_advertise'][0], 'yes' ); ?>><?php _e('Enable advertising before reading the full content','members_only') ?></label>
		<br>
		<textarea rows="1" cols="40" style="width:100%" placeholder="<?php _e('HTML code','members_only') ?>" id="members_only_code" name="members_only_code"><?php echo get_post_meta($post->ID,'members_only_code',true) ?></textarea>
		<?php
}

function members_only_post($post_id) {
	if( isset($_POST['members_only'])) {
		update_post_meta($post_id,'members_only','disabled');
	} else {
		delete_post_meta($post_id,'members_only');
	}
	if( isset($_POST['members_only_advertise'])) {
		update_post_meta($post_id,'members_only_advertise','yes');
	} else {
		delete_post_meta($post_id,'members_only_advertise');
	}
	if( !empty($_POST['members_only_code'])) {
		update_post_meta($post_id,'members_only_code',$_POST['members_only_code']);
	}
}

function members_only($content) {
  global $post;
  if (is_single() || is_page()) {
    $url = get_bloginfo('url');
    $morestring = '/<span id\=\"(more\-\d+)"><\/span>/';
    $cont = preg_split($morestring,$content);
    $more_tag = preg_match($morestring, $content);
    if ( is_user_logged_in() ) {
		if ( current_user_can('administrator') ) {
				return $content;
			} else {
				if(get_post_meta($post->ID,'members_only_advertise',true)){
					?>
					<script type="text/javascript">
					function check_cookie() {
						if (document.cookie.indexOf("members-only") != -1){
						   document.getElementById("message").style.display = "none";
						   document.getElementById("full").style.display = "block";
						}
					}
					function post(){
						 document.getElementById("message").style.display = "none";
						 document.getElementById("full").style.display = "block";
						 document.cookie="members-only=message=0";
					}
					window.onload=check_cookie
					</script>
					<?php
					return "<div id='message' style='display:block'>".$cont[0]."<br><center>".get_post_meta($post->ID,'members_only_code',true)."<br><br><p align='right'><a href='javascript:post()'>".__('Jump to the full content','members_only')."</a></p></center></div><div id='full' style='display:none'>".$content."</div>";
				} else {
					return $content;
				}
			}
    } else {
      if ( $more_tag == 0 || get_post_meta($post->ID, 'members_only', true) == "disabled") {
		if(get_post_meta($post->ID,'members_only_advertise',true)){
			?>
			<script type="text/javascript">
			function check_cookie() {
				if (document.cookie.indexOf("members-only") != -1){
				   document.getElementById("message").style.display = "none";
				   document.getElementById("full").style.display = "block";
				}
			}
			function post(){
				 document.getElementById("message").style.display = "none";
				 document.getElementById("full").style.display = "block";
				 document.cookie="members-only=message=0";
			}
			window.onload=check_cookie
			</script>
			<?php
			return "<div id='message' style='display:block'>".$cont[0]."<br><center>".get_post_meta($post->ID,'members_only_code',true)."<br><br><p align='right'><a href='javascript:post()'>".__('Jump to the full content','members_only')."</a></p></center></div><div id='full' style='display:none'>".$content."</div>";
		} else {
			return $content;
		}
      } else {
         $text1 = __("The post doesn't end.","members_only");
         $text2 = __("You need","members_only");
         $text3 = __("registration","members_only");
         $text4 = __("and","members_only");
         $text5 = __("login","members_only");
         $text6 = __("to read the whole post.","members_only");
         return $cont[0]."<br><br><center>".$text1."<br>".$text2." <a href='".$url."/wp-login.php?action=register'>".$text3."</a> ".$text4." <a href='".$url."/wp-login.php?redirect_to=".$url."/?p=".get_the_id()."'>".$text5."</a> ".$text6."</center>";
      }
    }
  }
  return $content;
}
?>