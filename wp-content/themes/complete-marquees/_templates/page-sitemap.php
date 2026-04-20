<?php

//  Template Name: Sitemap

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the default template for displaying pages as denoted by the
//  WordPress 'pages' post type.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<section class="with__aside">
  
	<?php marquees_breadcrumbs(); ?>
  	<?php the_content(); ?>
  	<?php wp_nav_menu(array('menu' => 'Sitemap')); ?>
  
</section>

<?php endwhile; ?>

<?php get_footer(); ?>