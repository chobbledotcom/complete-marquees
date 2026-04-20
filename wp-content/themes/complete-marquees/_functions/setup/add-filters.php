<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Runs a selections core filters through our theme
//
//  [_1_] Allow .svg uploads
//  [_2_] Remove Empty <p> tags & filter <p> tags
//  [_3_] Remove width and height attributes from <img>
//  [_4_] Remove <p> tags from <img>
//  [_5_] Enables or disables comments (defaults to disabled)
//  [_6_] Set Custom Thumbnail sizes (if required)
//  [_7_] Set Excerpt Length
//  [_8_] Remove JS & CSS Version Numbers

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_1_] Allow .svg uploads  //

function hobbes_mime_types( $mimes ){
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}

add_filter( 'upload_mimes', 'hobbes_mime_types' );


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


// [_2_] Remove Empty <p> tags & filter <p> tags //

function hobbes_remove_empty_p($content){
  $content = force_balance_tags($content);
  return preg_replace('#<p>\s*+(<br\s*/*>)?\s*</p>#i', '', $content);
}

add_filter('the_content', 'hobbes_remove_empty_p', 20, 1);

function hobbes_acf_filter_ptags_on_images($content){
   return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}

add_filter('acf_the_content', 'hobbes_acf_filter_ptags_on_images');

function hobbes_filter_ptags_on_images($content){
   return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}

add_filter('the_content', 'hobbes_filter_ptags_on_images');


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_3_] Remove width and height attributes from <img>  //

function hobbes_remove_img_dimensions( $html ) {
  $html = preg_replace( '/(width|height)=\"\d*\"\s/', "", $html );
  return $html;
}

//  From the_post_thumbnail  //
add_filter( 'post_thumbnail_html', 'hobbes_remove_img_dimensions', 10 );

//  From the WYSIWYG editor  //
add_filter( 'image_send_to_editor', 'hobbes_remove_img_dimensions', 10 );

//  From the_content  //
add_filter( 'the_content', 'hobbes_remove_img_dimensions', 10 );


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_4_] Remove <p> tags from <img>  //

function hobbes_remove_img_p($content){
  return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}

add_filter('the_content', 'hobbes_remove_img_p');



//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_5_] Enables or disables comments (defaults to disabled)  //

function hobbes_enable_comments() {
  //return true; // shows comments
  return false; // hides comments
}
add_filter( 'comments_open', 'hobbes_enable_comments', 20, 2 );
add_filter( 'pings_open', 'hobbes_enable_comments', 20, 2 );


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_6_] Set Custom Thumbnail sizes (if required)  //
add_theme_support( 'post-thumbnails' );

// set_post_thumbnail_size( 150, 150, true ); //  Set basic thumbnail size, crop mode  //
add_image_size( 'gallery_wide', 1300, 250, true ); //  Custom featured image size, crop mode  //
add_image_size( 'gallery_crop', 690, 400, true ); //  Custom featured image size, crop mode  //


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_7_] Set Excerpt Length  //

// E.g. echo excerpt(22); //

function excerpt($limit) {
  $excerpt = explode(' ', get_the_excerpt(), $limit);
  if (count($excerpt)>=$limit) {
      array_pop($excerpt);
      $excerpt = implode(" ",$excerpt).'...';
    } else {
      $excerpt = implode(" ",$excerpt);
    } 
  $excerpt = preg_replace('`\[[^\]]*\]`','',$excerpt);
  return $excerpt;
}


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_8_] Remove JS & CSS Version Numbers

//  This removes all version extensions on css and js linked through each page

function remove_cssjs_ver( $src ) {
    if( strpos( $src, '?ver=' ) )
        $src = remove_query_arg( 'ver', $src );
    return $src;
}
add_filter( 'style_loader_src', 'remove_cssjs_ver', 10, 2 );
add_filter( 'script_loader_src', 'remove_cssjs_ver', 10, 2 );


//hide email function
function hide_email($email)

{ $character_set = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

  $key = str_shuffle($character_set); $cipher_text = ''; $id = 'e'.rand(1,999999999);

  for ($i=0;$i<strlen($email);$i+=1) $cipher_text.= $key[strpos($character_set,$email[$i])];

  $script = 'var a="'.$key.'";var b=a.split("").sort().join("");var c="'.$cipher_text.'";var d="";';

  $script.= 'for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));';

  $script.= 'document.getElementById("'.$id.'").innerHTML="<a href=\\"mailto:"+d+"\\">"+d+"</a>"';

  $script = "eval(\"".str_replace(array("\\",'"'),array("\\\\",'\"'), $script)."\")"; 

  $script = '<script type="text/javascript">/*<![CDATA[*/'.$script.'/*]]>*/</script>';

  return '<span id="'.$id.'">[Please enable javascript to see this email address]</span>'.$script;

}


//shortcodes

//[foobar]
function foobar_func( $atts ){
  return "foo and bar";
}
add_shortcode( 'foobar', 'foobar_func' );


//wide container
function wide_container_func( $atts ){
  return '
    <div class="container--full">
    <div class="grid--wide">
    <div class="grid__item--whole">
  ';
}
add_shortcode( 'wide_container', 'wide_container_func' );

function wide_container_end_func( $atts ){
  return '
    </div>
    </div>
    </div>
  ';
}
add_shortcode( 'wide_container_end', 'wide_container_end_func' );

//large container
function large_container_func( $atts ){
  return '
    <div class="container--large">
    <div class="grid--wide">
    <div class="grid__item--whole">
  ';
}
add_shortcode( 'large_container', 'large_container_func' );

function large_container_end_func( $atts ){
  return '
    </div>
    </div>
    </div>
  ';
}
add_shortcode( 'large_container_end', 'large_container_end_func' );

//small container
function small_container_func( $atts ){
  return '
    <div class="container--small">
    <div class="grid--wide">
    <div class="grid__item--whole">
  ';
}
add_shortcode( 'small_container', 'small_container_func' );

function small_container_end_func( $atts ){
  return '
    </div>
    </div>
    </div>
  ';
}
add_shortcode( 'small_container_end', 'small_container_end_func' );