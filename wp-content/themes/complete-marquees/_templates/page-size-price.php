<?php

//  Template Name: Size Prices

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

  <section class="section--whole pads">

     <main class="container--large">

        <div class="grid--wide">

           <div class="grid__item--two__thirds">

           <?php marquees_breadcrumbs(); ?>

           <?php the_content(); ?>

           <?php get_template_part('_partials/content', 'pricing-table'); ?>

           </div><!--
         --><div class="grid__item--third">

           <?php get_sidebar(); ?>

           </div>

        </div>

     </main>

  </section>

<?php endwhile; ?>

<?php get_footer(); ?>
