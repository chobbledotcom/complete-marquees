<?php

//  Template Name: Contact

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<section class="section--whole pads">

  <main class="container--large">

  <div class="grid--wide">

    <div class="grid__item--two__thirds">
    
      <?php marquees_breadcrumbs(); ?>
      <?php the_content(); ?>

    </div><!-- 
    --><div class="grid__item--third">

      <?php get_sidebar(); ?>
      
    </div>
    
  </div>

  </main>
    
</section>

<?php endwhile; ?>

<?php get_footer(); ?>