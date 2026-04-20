<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that enables navigation to the next/previous posts if applicable.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_paging_nav' ) ) :

function hobbes_paging_nav() {
  
  //  Don't print empty markup if there's only one page  //
  
  if ( $GLOBALS['wp_query']->max_num_pages < 2 ) { return; } ?>
  
  <nav class="nav--paging">
    
    <?php if ( get_next_posts_link() ) : ?>
      <div class="nav__previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'hobbes' ) ); ?></div>
    <?php endif; ?>
    
    <?php if ( get_previous_posts_link() ) : ?>
      <div class="nav__next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'hobbes' ) ); ?></div>
    <?php endif; ?>
    
  </nav>
  
  <?php
}
endif;