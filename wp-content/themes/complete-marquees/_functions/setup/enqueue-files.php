<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Equeues theme scripts and stylesheets to wp_head.
//
//  NB. Because our theme does not use the default 'styles.css' file, our
//  global stylesheet (/build/css/global.css) must be enqueued here.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function enqueue_hobbes_files() {
  //  Register jQuery from Google Libraries, not locally
  wp_deregister_script('jquery');
  wp_register_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js', '', null, 'true');

  //  Register Global files  //
  wp_register_style( 'add-global-styles', get_template_directory_uri() . '/build/css/global.css','','', 'all' );
  wp_register_script( 'add-global-scripts', get_template_directory_uri() . '/build/js/global.min.js?v=2.1', '', null, 'true'  );


  // Make any CSS changes via the shame file if you do not know SASS //
  wp_register_style( 'add-edited-styles', get_template_directory_uri() . '/build/css/shame.css','','', 'all' );

  //  Enqueue Global Styles  //
  //wp_enqueue_style( 'add-global-styles' );

  //  Enqueue Shame Styles  //
  //wp_enqueue_style( 'add-edited-styles' );

  //  Enqueue Global Scripts  //
  wp_enqueue_script('jquery');
  wp_enqueue_script( 'add-global-scripts' );

}
add_action( 'wp_enqueue_scripts', 'enqueue_hobbes_files' );

function my_login_stylesheet() {

  //  Register log in page styles file  //
  wp_enqueue_style( 'custom-login', get_template_directory_uri() . '/build/css/login.css','', null,'' );

  //  Enqueue Global Styles  //
  wp_enqueue_style( 'custom-login' );
}
add_action( 'login_enqueue_scripts', 'my_login_stylesheet' );


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Scripts used for adding support for older browers should not be enqueued
//  as normal. Instead they are added to wp_head below.


function boilerplate_html5shiv() {

  echo '<!--[if lt IE 9]>';
  echo '<script src="'. get_template_directory_uri() .'/build/js/default.min.js"></script>';
  echo '<![endif]-->';

}
add_action('wp_head', 'boilerplate_html5shiv');
