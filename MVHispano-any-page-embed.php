<?php
/*
Plugin Name: MindValley Hispano Any Page Embed
Description: This plugin allows you to take any page from the internet and embed it in a wordpress page. Ideal for people who have a wordpress setup for their whole site as CMS but they need landing pages that are out of the theme structure. Contributors: Specs by <a href="http://www.juanmartitegui.com/" target="_blank">Juan Martitegui</a>, Code by <a href="http://d-castillo.info/" target="_blank">David Castillo</a>
Version: 1.3

*/

function mindvalley_startsWith($haystack, $needle){
    return !strncmp($haystack, $needle, strlen($needle));
}

function mindvalley_custom_embeded_box() {
	add_meta_box('mindvalley_custom_pages_box', 'MindValley Hispano Any Page Embed', 'mindvalley_custom_embeded_form',  'page', 'normal', 'high');
}

function mindvalley_custom_embeded_form() {
	global $post;
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'mindvalley_custom_embeded_noncename' );

  // The actual fields for data entry
  // Use get_post_meta to retrieve an existing value from the database and use the value for the form
  $value = get_post_meta( $post->ID,'mindvalley_custom_embeded_url', true );
  echo '<label for="mindvalley_custom_embeded_url">URL to Embed: </label> ';
  echo '<input type="text" id="myplugin_new_field" name="mindvalley_custom_embeded_url" value="'.esc_attr($value).'" size="105" />';
}

function mindvalley_custom_embeded_save(){
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	if ( !isset( $_POST['mindvalley_custom_embeded_noncename'] ) || !wp_verify_nonce( $_POST['mindvalley_custom_embeded_noncename'], plugin_basename( __FILE__ ) ) )
		return;

	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return;
	} else {
		return;
	}
	$post_ID = $_POST['post_ID'];
	$mindvalley_custom_embeded_url = sanitize_text_field( $_POST['mindvalley_custom_embeded_url'] );
	update_post_meta($post_ID, 'mindvalley_custom_embeded_url', $mindvalley_custom_embeded_url);
}

function mindvalley_custom_embeded_redirection() {  
    global $wp_query, $post;
	$url=trim(get_post_meta($post->ID,'mindvalley_custom_embeded_url',true));
	$stop = true;
	if (mindvalley_startsWith($url,"http://") or mindvalley_startsWith($url,"https://")){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$result = curl_exec($ch);
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$urlparts = parse_url($url);
		curl_close($ch);
		if ($urlparts["scheme"]==""){
			$urldomainpath = $urlparts["path"];
		} else {
			if ($urlparts["path"]==""){
				$urldomainpath = $urlparts["scheme"]."://".$urlparts["host"]."/";
			} else {
				$urldomainpath = $urlparts["scheme"]."://".$urlparts["host"].$urlparts["path"];
			}
		}
		$lastSlash = strrpos($urldomainpath,"/");
		if (false!==$lastSlash){
			$urldomainpath = substr($urldomainpath,0,$lastSlash+1);
		}
		preg_match_all('/([^\/]{4,5}:\/\/[^\/]+\/)/', $urldomainpath, $urldomain, PREG_PATTERN_ORDER);
		$urldomain = $urldomain[0][0];
		//filtrar y ajustar "src"s
		$result=preg_replace('/src=([("\')]{1})\/([^\/]{1})/', 'src=$1'.$urldomain.'$2', $result);
		$result=preg_replace('/src=([("\')]{1})([^h]{1}[^t]{2}[^p]{1}[^s]{0,1}[^:]{1}[^\/]{2})/', 'src=$1'.$urldomainpath.'$2', $result);
		//filtrar y ajustar "href"s
		$result=preg_replace('/href=([("\')]{1})\/([^\/]{1})/', 'href=$1'.$urldomain.'$2', $result);
		$result=preg_replace('/href=([("\')]{1})([^h]{1}[^t]{2}[^p]{1}[^s]{0,1}[^:]{1}[^\/]{2})/', 'href=$1'.$urldomainpath.'$2', $result);
		//filtrar y ajustar "action"s
		$result=preg_replace('/action=([("\')]{1})\/([^\/]{1})/', 'action=$1'.$urldomain.'$2', $result);
		$result=preg_replace('/action=([("\')]{1})([^h]{1}[^t]{2}[^p]{1}[^s]{0,1}[^:]{1}[^\/]{2})/', 'action=$1'.$urldomainpath.'$2', $result);
	} else {
		$stop=false;
	}
	if ($stop){
		//var_dump ($url);
		echo $result;
		status_header( '200' );
		die();
		//throw new Exception("--");
	}

}

add_action('save_post', 'mindvalley_custom_embeded_save', 999);
add_action('admin_menu', 'mindvalley_custom_embeded_box', 999);
add_action('template_redirect', 'mindvalley_custom_embeded_redirection', 999);

?>
