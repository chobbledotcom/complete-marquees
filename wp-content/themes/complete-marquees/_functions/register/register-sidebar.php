<?php
//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Register sidebar (widget areas) for our theme.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function hobbes_widgets_init() {
  
  register_sidebar( 
    array(
      'name'          => __( 'Default Sidebar', 'hobbes' ),
      'id'            => 'default-sidebar',
      'description'   => '',
      'before_widget' => '<div id="%1$s" class="widget %2$s">',
      'after_widget'  => '</div>',
      'before_title'  => '<p class="widget__title">',
      'after_title'   => '</p>'
    )
  );
  
  //  Add additional sidebars here  //
}

add_action( 'widgets_init', 'hobbes_widgets_init' );