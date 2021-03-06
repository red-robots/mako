<?php
class Mako {
	private static $initiated = false;
	
	public static function init() {		
		$current_user = wp_get_current_user();
		//only run plugin as admin editor and not on admin page
		if ( user_can( $current_user, "edit_posts" ) && !is_admin() && ! self::$initiated ) {
			self::init_hooks();
		}
	}
	
	private static function init_hooks(){
		self::$initiated = true;
		//add all mako tags for frontend processing
		add_action("the_posts",array("Mako","tag_all_post_types"),10,2);
		//tag all images
		add_action("post_thumbnail_html", array("Mako","tag_all_images"),10,5);
		//add functionality for wpautop since it won't autop tagged posts
		add_action("the_content",array("Mako","makoautop"),10);
		add_action("the_excerpt",array("Mako","makoautop"),10);
		//remove original filtering so it's not done twice
		remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
		//enqueue mako front end js
		wp_enqueue_script("makojs",plugin_dir_url( __FILE__ )."js/mako.js",array('wp-api'),null,true);
		//enque mako frontend stylesheet
		wp_enqueue_style("makocss",plugin_dir_url( __FILE__ )."css/mako.css");
	}
	
	public static function tag_all_post_types($posts,$query){
		foreach($posts as $post):
			$route = $post->post_type."-".$post->ID;
			$post->post_content = '<div class="mako" data-mako="'.$route.';content">'.self::tag_images_in_content($post->post_content).'</div>';
			$post->post_title = '<div class="mako" data-mako="'.$route.';title">'.$post->post_title.'</div>';
		endforeach;
		return $posts;
	}
	public static function tag_images_in_content($html){
         $temp_html = preg_replace('/(\<\s*?img[^\>]*?)(\>)/i','$1'.' data-mako-embedded-image '.'$2',$html);
        if($temp_html !== null)return $temp_html;
        else return $html;
	}
	public static function tag_all_images($html, $postid, $postthumbnailid){
        $temp_post = get_post($postid);
        $route = $temp_post->post_type."-".$postid;
		$html='<div class="mako" data-mako="'.$route.';featured_media" data-mako-image="'.$postthumbnailid.'">'.$html.'</div>';
		return $html;
	}
	//wrapper for auto p that allows the mako block elements contents to be autopd
	public static function makoautop($content){
		$matches = array();
		if(preg_match("/\A(\<\s*div[^\>]*?mako[^\>]*?)(\>)/i",$content,$matches)===1){
			$start1 = $matches[1];
			$start2 = $matches[2];
			$totalstart = $start1.$start2;
			$content_temp = substr($content,strlen($totalstart));
			if(preg_match("/(<\s*\/\s*div\s*>)\Z/i",$content_temp,$matches)===1){
				$end = $matches[1];
				$length = strlen($content_temp)-strlen($end)-1;
				if($length>0){
					$content = $start1.'data-mako-autop="true"'.$start2.wpautop(substr($content_temp,0,$length)).$end;
				}
			}
		}	
		return $content;
	}
}
