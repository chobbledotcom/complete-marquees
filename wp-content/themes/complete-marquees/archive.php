<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the template for archive pages

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<section class="section--whole pads">

  <main class="container--large">

  <div class="grid">

    <div class="grid__item--two__thirds">
 
  <?php if ( have_posts() ) : ?>
   
    <h1>
      <?php
        if ( is_category() ) :
          single_cat_title();
          
        elseif ( is_tag() ) :
          single_tag_title();
          
        elseif ( is_author() ) :
          printf( __( 'Author: %s', 'hobbes' ), '<span class="vcard">' . get_the_author() . '</span>' );
          
        elseif ( is_day() ) :
          printf( __( 'Day: %s', 'hobbes' ), '<span>' . get_the_date() . '</span>' );
          
        elseif ( is_month() ) :
          printf( __( 'Month: %s', 'hobbes' ), '<span>' . get_the_date( _x( 'F Y', 'monthly archives date format', 'hobbes' ) ) . '</span>' );
          
        elseif ( is_year() ) :
          printf( __( 'Year: %s', 'hobbes' ), '<span>' . get_the_date( _x( 'Y', 'yearly archives date format', 'hobbes' ) ) . '</span>' );
          
        elseif ( is_tax( 'post_format', 'post-format-aside' ) ) :
          _e( 'Asides', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) :
          _e( 'Galleries', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-image' ) ) :
          _e( 'Images', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-video' ) ) :
          _e( 'Videos', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-quote' ) ) :
          _e( 'Quotes', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-link' ) ) :
          _e( 'Links', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-status' ) ) :
          _e( 'Statuses', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-audio' ) ) :
          _e( 'Audios', 'hobbes' );
          
        elseif ( is_tax( 'post_format', 'post-format-chat' ) ) :
          _e( 'Chats', 'hobbes' );
          
        else :
          _e( 'Archives', 'hobbes' );
          
        endif;
      ?>
    </h1>
    
    <?php
      
      // Show an optional term description
      
      $term_description = term_description();
      
      if ( ! empty( $term_description ) ) :
        printf( '<div class="taxonomy-description">%s</div>', $term_description );
      endif;
      
    ?>
   
    <?php
    
    //  Start the loop
    
    while ( have_posts() ) : the_post(); ?>
    
    <?php
    
    //  Retrieves the relevant format for the template. Post format parts
    //  can be found within the '_partials' folder.
    
    get_template_part( '_partials/content', get_post_format() ); ?>
    
    <?php endwhile; ?>
   
      <?php hobbes_paging_nav(); ?>
      
    <?php else : ?>
      
      <?php get_template_part( '_partials/content', 'none' ); ?>
   
  <?php endif; ?>
  </div><!-- 
    --><div class="grid__item--third">

      <?php get_sidebar(); ?>
      
    </div>
</div>
</main>
</section>

<?php get_footer(); ?>