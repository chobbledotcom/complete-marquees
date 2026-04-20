<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This file creates any custom post types we need for our theme. Get started
//  by un-commenting everything below, and customizing the cpt to suit.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function create_hobbes_post_types() {

  register_post_type( 'testimonials',
    array(

      //  What will the CPT be known as?  //
      'labels' => array(
        'name'                => __( 'Testimonials' ),
        'singular_name'       => __( 'Testimonial' ),
      ),

      //  Settings - how will the CPT behave?  //
      'public'              => true,
      'show_ui'             => true,
      'publicly_queryable'  => true,
      'rewrite'             => true,
      'exclude_from_search' => false,
      'capability_type'     => 'post',
      'has_archive'         => true,
      'menu_icon'           => 'dashicons-star-filled',

      //  What editable fields will the CPT support?  //
      'supports' => array(
        'title',
        'editor',
        'excerpt',
        'thumbnail'
      ),

      //  Which Taxonomies will be applicable?  //
      //  'taxonomies'  => array(
      //  'testimonials_categories'
      //),

    )
  );

  //  Register any additional CPTs here  //

  flush_rewrite_rules();
}

add_action( 'init', 'create_hobbes_post_types' );
