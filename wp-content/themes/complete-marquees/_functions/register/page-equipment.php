<?php

//  Template Name: Equipment Hire

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<section class="section--whole pads">

   <main class="container--large">

      <div class="grid--wide">

         <div class="grid__item--two__thirds">
         
         <?php marquees_breadcrumbs(); ?>

         <?php the_content(); ?>

         <?php //is it euqipment hire page? ?>
         <?php if ( is_page('equipment-hire') ) { 

            get_template_part('_partials/content', 'equipment');

         }  ?>

         </div><!-- 
       --><div class="grid__item--third">

         <?php get_sidebar(); ?>
         
         </div>
       
      </div>

   </main>
    
</section>

<?php get_footer(); ?>