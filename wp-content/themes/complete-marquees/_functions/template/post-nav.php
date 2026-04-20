<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that enables navigation to the next/previous post if applicable.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_post_nav' ) ) :

function hobbes_post_nav() {
  
  // Don't print empty markup if there's nowhere to navigate  //
  
  $previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
  $next     = get_adjacent_post( false, '', false );
  
  if ( ! $next && ! $previous ) { return; } ?>
  
  <nav class="nav--posts">
   
    <?php
      previous_post_link( '<div class="nav__previous">%link</div>', _x( '<span class="meta-nav">&larr;</span>&nbsp;%title', 'Previous post link', 'hobbes' ) );
      next_post_link(     '<div class="nav__next">%link</div>',     _x( '%title&nbsp;<span class="meta-nav">&rarr;</span>', 'Next post link',     'hobbes' ) );
    ?>
    
  </nav>
  
  <?php
}
endif;
