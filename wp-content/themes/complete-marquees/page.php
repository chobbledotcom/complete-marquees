<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the default template for displaying pages as denoted by the
//  WordPress 'pages' post type.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<?php while ( have_posts() ) : the_post(); if ( is_front_page() ) :

  get_template_part( '_partials/content', 'front' );
  
  else :

  get_template_part( '_partials/content', 'page' ); ?>

<?php endif; endwhile; ?>
<?php get_footer(); ?>