<?php
//  Template Name: Gallery
//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //
get_header(); ?>

<section class="section--whole pads-top">
   <div class="container--small">
      <?php marquees_breadcrumbs(); ?>
   </div>
</section>

<section class="section--whole">

      <main class="grid--wide">

         <div class="grid__item--whole">

            <?php while ( have_posts() ) : the_post(); ?>

            <?php the_content(); ?>

            <?php endwhile; ?>

         </div>

      </main>
    
</section>

<?php 
   get_template_part('_partials/content', 'tabs');
   get_footer(); 
?>