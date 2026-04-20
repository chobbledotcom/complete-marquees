<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Prints HTML meta information including publish date and author.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_posted_on' ) ) :

function hobbes_posted_on() {
  
  $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
  
  $time_string = sprintf( $time_string,
    esc_attr( get_the_date( 'c' ) ),
    esc_html( get_the_date() )
  );
  
  $posted_on = sprintf(
    _x( 'Posted on %s', 'post date', 'hobbes' ),
    '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
  );
  
  $byline = sprintf(
    _x( 'by %s', 'post author', 'hobbes' ),
    '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
  );
  
  echo '<div class="post__meta"><span class="posted-on">' . $posted_on . '</span><span class="byline"> ' . $byline . '</span></div>';
  
}

endif;