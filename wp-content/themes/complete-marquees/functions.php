<?php
//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This file acts as a manifest for theme functions and controllers.
//  These files live in the '_functions' folder.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  THEME SETUP
//
//  These files prepare the theme for us, overwriting a few Wordpress defaults
//  and adding some additional theme agnostic functionality.

require_once('_functions/setup/clean-head.php');      //  Remove junk from the head (rsd links etc.)
require_once('_functions/setup/add-filters.php');     //  Filters control the post output directly
require_once('_functions/setup/categories.php');      //  Sorts out the default categories functionality
require_once('_functions/setup/controllers.php');     //  Indirect theme controllers (permalinks etc.)
require_once('_functions/setup/enqueue-files.php');   //  Enqueue scripts and styles to wp_head


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  REGISTER
//
//  For lack of a better name, the files below register additional theme
//  specific functionality, including menus, sidebars and post types.

require_once('_functions/register/register-menus.php');      //  Register three preset menus
require_once('_functions/register/register-sidebar.php');    //  Register default widget area
require_once('_functions/register/options-pages.php');       //  Registers the options pages
require_once('_functions/register/custom-post-types.php');   //  Creates additional post types
require_once('_functions/register/custom-taxonomies.php');   //  Creates additional taxonomies
require_once('_functions/register/metaboxes.php'); 		     //  Creates metaboxes



//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  TEMPLATE TAGS
//
//  The following template tags can be called to display specific reusable
//  content at any point during the theme.

require_once('_functions/template/logo.php');            //  Add a logo with schema to the header
require_once('_functions/template/head-styling.php');    //  Calls the header specific styling
require_once('_functions/template/paging-nav.php');      //  Navigation for next/prev posts (home.php / index.php)
require_once('_functions/template/post-nav.php');        //  Navigation for next/prev post (single.php)
require_once('_functions/template/posted-on.php');       //  Post meta data (posted on, author etc.)
require_once('_functions/template/address.php');         //  Prints the address with schema
require_once('_functions/template/full-details.php');    //  Prints the full address and contact details with schema
require_once('_functions/template/phone-number.php');    //  Add a Click-to-Call phone number
require_once('_functions/template/json.php');            //  Print the company number from the global options


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  ADMIN FUNCTIONS
//
//  Boosts functionality to the Wordpress backend

require_once('_functions/admin/global-options.php'); //  User controled global options (logo etc.)

//HTML Minify

class WP_HTML_Compression {
    protected $compress_css = true;
    protected $compress_js = true;
    protected $info_comment = true;
    protected $remove_comments = false;
 
    protected $html;
    public function __construct($html) {
      if (!empty($html)) {
		    $this->parseHTML($html);
	    }
    }
    public function __toString() {
	    return $this->html;
    }
    protected function bottomComment($raw, $compressed) {
	    $raw = strlen($raw);
	    $compressed = strlen($compressed);		
	    $savings = ($raw-$compressed) / $raw * 100;		
	    $savings = round($savings, 2);		
	    return '';
    }
    protected function minifyHTML($html) {
	    $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
	    preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
	    $overriding = false;
	    $raw_tag = false;
	    $html = '';
	    foreach ($matches as $token) {
		    $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
		    $content = $token[0];
		    if (is_null($tag)) {
			    if ( !empty($token['script']) ) {
				    $strip = $this->compress_js;
			    }
			    else if ( !empty($token['style']) ) {
				    $strip = $this->compress_css;
			    }
			    else if ($content == '<!--wp-html-compression no compression-->') {
				    $overriding = !$overriding;
				    continue;
			    }
			    else if ($this->remove_comments) {
				    if (!$overriding && $raw_tag != 'textarea') {
					    $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
				    }
			    }
		    }
		    else {
			    if ($tag == 'pre' || $tag == 'textarea') {
				    $raw_tag = $tag;
			    }
			    else if ($tag == '/pre' || $tag == '/textarea') {
				    $raw_tag = false;
			    }
			    else {
				    if ($raw_tag || $overriding) {
					    $strip = false;
				    }
				    else {
					    $strip = true;
					    $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
					    $content = str_replace(' />', '/>', $content);
				    }
			    }
		    }
		    if ($strip) {
			    $content = $this->removeWhiteSpace($content);
		    }
		    $html .= $content;
	    }
	    return $html;
    }
    public function parseHTML($html) {
	    $this->html = $this->minifyHTML($html);
	    if ($this->info_comment) {
		    $this->html .= "\n" . $this->bottomComment($html, $this->html);
	    }
    }
    protected function removeWhiteSpace($str) {
	    $str = str_replace("\t", ' ', $str);
	    $str = str_replace("\n",  '', $str);
	    $str = str_replace("\r",  '', $str);
	    while (stristr($str, '  ')) {
		    $str = str_replace('  ', ' ', $str);
	    }
	    return $str;
    }
}
function wp_html_compression_finish($html) {
    return new WP_HTML_Compression($html);
}
function wp_html_compression_start() {
    ob_start('wp_html_compression_finish');
}
add_action('get_header', 'wp_html_compression_start');