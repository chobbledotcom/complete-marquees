<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Fixes the categories functionality. If there is only one category on
//  the blog, this removes it all together. It also renames the 'Uncategorised'
//  category to a more appropriate name.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  Decide if this blog is categories or not  //

function hobbes_categorized_blog() {
  if ( false === ( $all_the_cool_cats = get_transient( 'hobbes_categories' ) ) ) {
    
    //  Create an array of all the categories that are attached to posts  //
    $all_the_cool_cats = get_categories( array(
      'fields'     => 'ids',
      'hide_empty' => 1,
      
      //  We only need to know if there is more than one category  //
      'number'     => 2,
    ) );
    
    //  Count the number of categories that are attached to the posts  //
    $all_the_cool_cats = count( $all_the_cool_cats );
    
    set_transient( 'hobbes_categories', $all_the_cool_cats );
  }
  
  if ( $all_the_cool_cats > 1 ) {
    
    //  This blog has more than 1 category so hobbes_categorized_blog should return true  //
    return true;
    
  } else {
    
    //  This blog has only 1 category so hobbes_categorized_blog should return false  //
    return false;
    
  }
}


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  Flush out the transients used in hobbes_categorized_blog  //

function hobbes_category_transient_flusher() {
  // Like, beat it. Dig?
  delete_transient( 'hobbes_categories' );
}

add_action( 'edit_category', 'hobbes_category_transient_flusher' );
add_action( 'save_post',     'hobbes_category_transient_flusher' );


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  Rename that pesty 'Uncategoriesd' category  //

function hobbes_rename_uncategorised() {
  
  $term = get_term_by('name', 'Uncategorised', 'category');
  
  if( $term ) {
    wp_update_term(
      $term->term_taxonomy_id, 
      'category', 
      
      array(
        'name' => 'Latest News',
        'slug' => 'latest-news'
      )
  );
}


}
add_action( 'after_switch_theme', 'hobbes_rename_uncategorised' );