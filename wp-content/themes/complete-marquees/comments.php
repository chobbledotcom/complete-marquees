<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the template for displaying comments and the comment submission
//  form on pages where comments are enabled.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  If the post is password protected and the user has not entered the password
//  we will return early without loading the comments.

if ( post_password_required() ) {
  return;
}

?>

<div id="comments" class="comments-area">
  
  <?php comment_form(); ?>
  
  <?php
  
  //  You can start editing here -- including this comment!  //

  if ( have_comments() ) : ?>
   
    <h2 class="comments-title">
      <?php comments_number('No Comments', 'One Comment', '% Comments' );?>
    </h2>
    
    <ol class="comment-list">
      <?php
        wp_list_comments( array(
          'style'      => 'ol',
          'short_ping' => true,
        ) );
      ?>
    </ol>
    
    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : //  are there comments to navigate through?  // ?>
     
      <nav class="nav--comments">
       
        <div class="nav__previous"><?php previous_comments_link( __( '&larr; Older Comments', 'hobbes' ) ); ?></div>
        <div class="nav__next"><?php next_comments_link( __( 'Newer Comments &rarr;', 'hobbes' ) ); ?></div>
        
      </nav>
      
    <?php endif; //  check for comment navigation  // ?>
    
  <?php endif; //  have_comments() end  // ?>
  
  <?php
  
  //  If comments are closed and there ARE comments let users know  //
  
  if ( ! comments_open() && '0' != get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
   
    <p class="no-comments"><?php _e( 'Comments are closed.', 'hobbes' ); ?></p>
    
  <?php endif; ?>
  
</div><?php //  .comments-area end  // ?>