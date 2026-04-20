<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the default template for displaying all single posts

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //
get_header(); ?>
<?php while ( have_posts() ) : the_post(); ?>
  <?php get_template_part( '_partials/content', 'single' ); ?>
<?php endwhile; ?>
<?php get_footer(); ?>