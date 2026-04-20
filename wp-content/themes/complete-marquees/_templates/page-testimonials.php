<?php

//  Template Name: Testimonials

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<section class="section--whole pads">

  <main class="container--small">

    <div class="grid--wide">

      <div class="grid__item--whole">

      <?php if ( have_posts() ) : ?>
        <?php marquees_breadcrumbs(); ?>
        <h1>Testimonials</h1>
       
        <?php
        $args = array( 
          'post_type' => 'testimonials', 
          'posts_per_page' => 100 
        );
        $loop = new WP_Query( $args );
        while ( $loop->have_posts() ) : $loop->the_post(); ?>

          <article itemscope itemtype="http://schema.org/Service">
            <div itemprop="review" itemscope itemtype="http://schema.org/Review">
              <h2 itemprop="name"><?php the_title(); ?></h2>
              <div itemprop="description">
                <?php the_content(); ?>
              </div>
            </div>
          </article>
        
        <?php endwhile; ?>
          
      <?php else : ?>
          
        <?php get_template_part( '_partials/content', 'none' ); ?>
       
      <?php endif; ?>
      
      </div>

    </div>

  </main>

</section>

<?php get_footer(); ?>