<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the template part used for displaying content in 'page.php'

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

?>

<section class="section--whole pads">

   <main class="container--large">

      <div class="grid--wide">

         <div class="grid__item--two__thirds">
         
         <?php marquees_breadcrumbs(); ?>

         <?php the_content(); ?>

         <?php //is it prices page? ?>
         <?php if ( is_page('sizes-prices') ) { 

            get_template_part('_partials/content', 'pricing-table');

         }  ?>

         <?php //is it euqipment hire page? ?>
         <?php if ( is_page('equipment-hire') ) { 

            get_template_part('_partials/content', 'equipment');

         }  ?>

         <?php //is it euqipment hire page? ?>
         <?php if ( is_page('faqs') ) { 

            get_template_part('_partials/content', 'faqs');

         }  ?>
         <?php //is it wedding, event, corporate, etc for cta? ?>
         <?php if ( is_page( array('wedding-marquees', 'party-marquees', 'corporate-event-marquees', 'special-events') ) ) { 

            get_template_part('_partials/content', 'dissapointment');

         }  ?>

         </div><!-- 
       --><div class="grid__item--third">

         <?php get_sidebar(); ?>
         
         </div>
       
      </div>

   </main>
    
</section>
