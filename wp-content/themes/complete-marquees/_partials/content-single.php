<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the template part used for displaying content in 'single.php'

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

?>

<section class="section--whole pads">

  <main class="container--large">

  <div class="grid">

    <div class="grid__item--two__thirds">

      <div class="content">

        <article class="article__post">

          <?php the_title( '<h1>', '</h1>' ); ?>

          <?php hobbes_posted_on(); ?>

          <?php the_content(); ?>

          <?php hobbes_posted_on(); ?>

        </article>

        <?php hobbes_post_nav(); ?>

        <?php if ( comments_open() || '0' != get_comments_number() ) :

          comments_template();

        endif; ?>

      </div>

    </div><!-- 
    --><div class="grid__item--third">

      <?php get_sidebar(); ?>
      
    </div>

</section>