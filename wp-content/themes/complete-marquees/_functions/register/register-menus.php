<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Register navigation menus for our theme.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function register_hobbes_menus() {
  
  register_nav_menus(
    array(
      'header-menu'   => __( 'Header Menu' ),
      
      //  Add additional navigation menus here  //
    )
  );
  
}

add_action( 'init', 'register_hobbes_menus' );