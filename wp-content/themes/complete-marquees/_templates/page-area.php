<?php

//  Template Name: Area

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<section class="section--whole pads">

   <main class="container--large">

      <div class="grid--wide">

         <div class="grid__item--two__thirds">

            <?php while ( have_posts() ) : the_post(); ?>

            <?php 
               marquees_breadcrumbs();
               the_content(); 
               //get_template_part('_partials/content', 'dissapointment');
            ?>

            <?php endwhile; ?>

         </div><!-- 
       --><div class="grid__item--third">

         <?php get_sidebar(); ?>
         
         </div>
       
      </div>

   </main>
    
</section>

<?php get_template_part('_partials/content', 'tabs'); ?>

<?php get_footer(); ?>